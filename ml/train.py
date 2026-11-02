import argparse
import csv
import hashlib
import json
import random
import time
from pathlib import Path

import matplotlib

matplotlib.use("Agg")
import matplotlib.pyplot as plt
import numpy as np
import tensorflow as tf
from ai_edge_litert.interpreter import Interpreter
from PIL import Image
from sklearn.metrics import ConfusionMatrixDisplay, classification_report, confusion_matrix
from tensorflow import keras

ML_DIR = Path(__file__).resolve().parent
IMAGE_SIZE = 224
LABELS = ["organic", "plastic", "paper"]
SOURCE_FOLDERS = {"organic": "biological", "plastic": "plastic", "paper": "paper"}
IMAGE_EXTENSIONS = {".jpg", ".jpeg", ".png"}
TARGET_ACCURACY = 0.80


def parse_args():
    parser = argparse.ArgumentParser(description="Train waste classifier (MobileNetV2).")
    parser.add_argument("--data-dir", type=Path, default=ML_DIR / "dataset" / "garbage_classification")
    parser.add_argument("--models-dir", type=Path, default=ML_DIR / "models")
    parser.add_argument("--reports-dir", type=Path, default=ML_DIR / "reports")
    parser.add_argument("--per-class", type=int, default=300)
    parser.add_argument("--seed", type=int, default=42)
    parser.add_argument("--batch-size", type=int, default=32)
    parser.add_argument("--head-epochs", type=int, default=15)
    parser.add_argument("--finetune-epochs", type=int, default=10)
    parser.add_argument("--finetune-layers", type=int, default=30)
    return parser.parse_args()


def collect_images(data_dir):
    """Return {label: [paths]} of readable images, without exact duplicates.

    Scraped datasets often contain the same file more than once; a duplicate
    landing in both train and test would inflate the test accuracy.
    """
    hash_owners = {}
    for label, folder in SOURCE_FOLDERS.items():
        class_dir = data_dir / folder
        if not class_dir.is_dir():
            raise SystemExit(f"Folder not found: {class_dir}")
        for path in sorted(class_dir.iterdir()):
            if path.suffix.lower() not in IMAGE_EXTENSIONS:
                continue
            try:
                with Image.open(path) as image:
                    image.verify()
            except Exception:
                print(f"  skip unreadable: {path.name}")
                continue
            digest = hashlib.sha1(path.read_bytes()).hexdigest()
            hash_owners.setdefault(digest, []).append((label, path))

    images = {label: [] for label in LABELS}
    duplicates = 0
    for owners in hash_owners.values():
        labels = {label for label, _ in owners}
        duplicates += len(owners) - 1
        # The same picture filed under two classes has no trustworthy label.
        if len(labels) > 1:
            duplicates += 1
            continue
        label, path = owners[0]
        images[label].append(path)

    for label in LABELS:
        print(f"  {label}: {len(images[label])} unique images")
    print(f"  removed duplicates: {duplicates}")
    return images


def split_dataset(images, per_class, seed):
    rng = random.Random(seed)
    rows = []
    for label in LABELS:
        paths = sorted(images[label])
        if len(paths) < per_class:
            raise SystemExit(f"Class {label} has only {len(paths)} images, need {per_class}.")
        chosen = rng.sample(paths, per_class)
        n_train = round(per_class * 0.70)
        n_val = round(per_class * 0.15)
        for i, path in enumerate(chosen):
            split = "train" if i < n_train else "val" if i < n_train + n_val else "test"
            rows.append({"split": split, "label": label, "path": path})
    return rows


def write_split_csv(rows, data_dir, out_path):
    with out_path.open("w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(["split", "label", "file"])
        for row in rows:
            writer.writerow([row["split"], row["label"], row["path"].relative_to(data_dir).as_posix()])


def load_image(path):
    # Must stay identical to the preprocessing in ml/api so serving matches training.
    with Image.open(path) as image:
        image = image.convert("RGB").resize((IMAGE_SIZE, IMAGE_SIZE), Image.BILINEAR)
        return np.asarray(image, dtype=np.uint8)


def load_split(rows, split):
    subset = [row for row in rows if row["split"] == split]
    x = np.stack([load_image(row["path"]) for row in subset])
    y = np.array([LABELS.index(row["label"]) for row in subset], dtype=np.int32)
    return x, y


def make_datasets(x_train, y_train, x_val, y_val, batch_size, seed):
    augment = keras.Sequential(
        [
            keras.layers.RandomFlip("horizontal", seed=seed),
            keras.layers.RandomRotation(0.05, seed=seed),
            keras.layers.RandomZoom(0.1, seed=seed),
            keras.layers.RandomContrast(0.1, seed=seed),
        ]
    )
    train_ds = (
        tf.data.Dataset.from_tensor_slices((x_train.astype("float32"), y_train))
        .shuffle(len(x_train), seed=seed)
        .batch(batch_size)
        .map(lambda x, y: (augment(x, training=True), y), num_parallel_calls=tf.data.AUTOTUNE)
        .prefetch(tf.data.AUTOTUNE)
    )
    val_ds = (
        tf.data.Dataset.from_tensor_slices((x_val.astype("float32"), y_val))
        .batch(batch_size)
        .prefetch(tf.data.AUTOTUNE)
    )
    return train_ds, val_ds


def build_model():
    # Normalisation lives inside the model, so callers only resize and pass raw 0-255 pixels.
    inputs = keras.Input(shape=(IMAGE_SIZE, IMAGE_SIZE, 3))
    x = keras.layers.Rescaling(1 / 127.5, offset=-1)(inputs)
    base = keras.applications.MobileNetV2(
        input_shape=(IMAGE_SIZE, IMAGE_SIZE, 3), include_top=False, weights="imagenet"
    )
    base.trainable = False
    # training=False keeps BatchNorm statistics frozen, also during fine-tuning.
    x = base(x, training=False)
    x = keras.layers.GlobalAveragePooling2D()(x)
    x = keras.layers.Dropout(0.2)(x)
    outputs = keras.layers.Dense(len(LABELS), activation="softmax")(x)
    return keras.Model(inputs, outputs), base


def fit(model, train_ds, val_ds, epochs, learning_rate):
    model.compile(
        optimizer=keras.optimizers.Adam(learning_rate),
        loss="sparse_categorical_crossentropy",
        metrics=["accuracy"],
    )
    early_stop = keras.callbacks.EarlyStopping(
        monitor="val_loss", patience=3, restore_best_weights=True
    )
    history = model.fit(
        train_ds, validation_data=val_ds, epochs=epochs, callbacks=[early_stop], verbose=2
    )
    return history.history


def plot_history(head, finetune, out_path):
    fig, axes = plt.subplots(1, 2, figsize=(11, 4))
    head_epochs = len(head["loss"])
    for ax, metric, title in [(axes[0], "accuracy", "Akurasi"), (axes[1], "loss", "Loss")]:
        train = head[metric] + finetune[metric]
        val = head[f"val_{metric}"] + finetune[f"val_{metric}"]
        epochs = range(1, len(train) + 1)
        ax.plot(epochs, train, label="train")
        ax.plot(epochs, val, label="validation")
        ax.axvline(head_epochs + 0.5, color="gray", linestyle="--", linewidth=1, label="mulai fine-tune")
        ax.set_title(title)
        ax.set_xlabel("Epoch")
        ax.legend()
    fig.tight_layout()
    fig.savefig(out_path, dpi=120)
    plt.close(fig)


def plot_confusion_matrix(y_true, y_pred, out_path):
    matrix = confusion_matrix(y_true, y_pred, labels=range(len(LABELS)))
    fig, ax = plt.subplots(figsize=(5, 5))
    ConfusionMatrixDisplay(matrix, display_labels=LABELS).plot(ax=ax, cmap="Blues", colorbar=False)
    ax.set_title("Confusion matrix (data test)")
    fig.tight_layout()
    fig.savefig(out_path, dpi=120)
    plt.close(fig)
    return matrix


def predict_tflite(tflite_path, x):
    interpreter = Interpreter(model_path=str(tflite_path))
    interpreter.allocate_tensors()
    input_index = interpreter.get_input_details()[0]["index"]
    output_index = interpreter.get_output_details()[0]["index"]
    probabilities = []
    for image in x:
        interpreter.set_tensor(input_index, image[np.newaxis].astype("float32"))
        interpreter.invoke()
        probabilities.append(interpreter.get_tensor(output_index)[0])
    return np.array(probabilities)


def main():
    args = parse_args()
    keras.utils.set_random_seed(args.seed)
    tf.config.experimental.enable_op_determinism()
    args.models_dir.mkdir(parents=True, exist_ok=True)
    args.reports_dir.mkdir(parents=True, exist_ok=True)
    started = time.time()

    print("Scanning dataset...")
    images = collect_images(args.data_dir)
    rows = split_dataset(images, args.per_class, args.seed)
    write_split_csv(rows, args.data_dir, args.reports_dir / "split.csv")

    x_train, y_train = load_split(rows, "train")
    x_val, y_val = load_split(rows, "val")
    x_test, y_test = load_split(rows, "test")
    print(f"Split train/val/test: {len(x_train)}/{len(x_val)}/{len(x_test)}")

    train_ds, val_ds = make_datasets(x_train, y_train, x_val, y_val, args.batch_size, args.seed)
    model, base = build_model()

    print("Stage 1: training classifier head (backbone frozen)")
    head_history = fit(model, train_ds, val_ds, args.head_epochs, 1e-3)

    print(f"Stage 2: fine-tuning top {args.finetune_layers} backbone layers")
    base.trainable = True
    for layer in base.layers[: -args.finetune_layers]:
        layer.trainable = False
    finetune_history = fit(model, train_ds, val_ds, args.finetune_epochs, 1e-5)

    keras_path = args.models_dir / "waste_classifier.keras"
    tflite_path = args.models_dir / "waste_classifier.tflite"
    model.save(keras_path)
    tflite_path.write_bytes(tf.lite.TFLiteConverter.from_keras_model(model).convert())
    (args.models_dir / "labels.json").write_text(json.dumps(LABELS, indent=2) + "\n", encoding="utf-8")

    print("Evaluating on test set...")
    keras_probs = model.predict(x_test.astype("float32"), batch_size=args.batch_size, verbose=0)
    keras_pred = keras_probs.argmax(axis=1)
    tflite_probs = predict_tflite(tflite_path, x_test)
    tflite_pred = tflite_probs.argmax(axis=1)

    keras_accuracy = float((keras_pred == y_test).mean())
    tflite_accuracy = float((tflite_pred == y_test).mean())

    report = classification_report(y_test, keras_pred, target_names=LABELS, digits=4)
    (args.reports_dir / "classification_report.txt").write_text(report, encoding="utf-8")
    matrix = plot_confusion_matrix(y_test, keras_pred, args.reports_dir / "confusion_matrix.png")
    plot_history(head_history, finetune_history, args.reports_dir / "training_curves.png")

    metrics = {
        "seed": args.seed,
        "tensorflow_version": tf.__version__,
        "labels": LABELS,
        "split_sizes": {"train": len(x_train), "val": len(x_val), "test": len(x_test)},
        "epochs_trained": {
            "head": len(head_history["loss"]),
            "finetune": len(finetune_history["loss"]),
        },
        "test_accuracy_keras": round(keras_accuracy, 4),
        "test_accuracy_tflite": round(tflite_accuracy, 4),
        "keras_tflite_prediction_agreement": round(float((keras_pred == tflite_pred).mean()), 4),
        "keras_tflite_max_probability_diff": float(np.abs(keras_probs - tflite_probs).max()),
        "confusion_matrix": matrix.tolist(),
        "training_minutes": round((time.time() - started) / 60, 1),
    }
    (args.reports_dir / "metrics.json").write_text(json.dumps(metrics, indent=2) + "\n", encoding="utf-8")

    print(report)
    print(json.dumps(metrics, indent=2))
    status = "reached" if keras_accuracy >= TARGET_ACCURACY else "NOT reached"
    print(f"Target accuracy {TARGET_ACCURACY:.0%}: {status}")


if __name__ == "__main__":
    main()
