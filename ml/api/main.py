import io
import json
import logging
import os
import threading
from contextlib import asynccontextmanager
from pathlib import Path

import numpy as np
from ai_edge_litert.interpreter import Interpreter
from fastapi import FastAPI, File, HTTPException, UploadFile
from fastapi.responses import JSONResponse
from PIL import Image, UnidentifiedImageError
from pydantic import BaseModel

ML_DIR = Path(__file__).resolve().parent.parent
MODEL_PATH = Path(os.environ.get("MODEL_PATH", ML_DIR / "models" / "waste_classifier.tflite"))
LABELS_PATH = Path(os.environ.get("LABELS_PATH", ML_DIR / "models" / "labels.json"))
IMAGE_SIZE = 224
MAX_UPLOAD_BYTES = 5 * 1024 * 1024
ALLOWED_CONTENT_TYPES = {"image/jpeg", "image/png"}
ALLOWED_FORMATS = {"JPEG", "PNG"}

logger = logging.getLogger("uvicorn.error")

# A LiteRT interpreter holds mutable tensor buffers, so concurrent requests must not share it at once.
inference_lock = threading.Lock()


@asynccontextmanager
async def lifespan(app: FastAPI):
    app.state.interpreter = None
    app.state.labels = []
    try:
        labels = json.loads(LABELS_PATH.read_text(encoding="utf-8"))
        interpreter = Interpreter(model_path=str(MODEL_PATH))
        interpreter.allocate_tensors()
        app.state.input_index = interpreter.get_input_details()[0]["index"]
        app.state.output_index = interpreter.get_output_details()[0]["index"]
        app.state.labels = labels
        app.state.interpreter = interpreter
        logger.info("Model loaded from %s", MODEL_PATH)
    except Exception:
        # Keep the process up so /health can report the problem instead of a crash loop.
        logger.exception("Failed to load model from %s", MODEL_PATH)
    yield


app = FastAPI(
    title="Smart Waste Classifier",
    description="Klasifikasi foto sampah menjadi organic, plastic, atau paper.",
    version="1.0.0",
    lifespan=lifespan,
)


class PredictResponse(BaseModel):
    label: str
    confidence: float
    probabilities: dict[str, float]


class ErrorResponse(BaseModel):
    detail: str


def preprocess(image: Image.Image) -> np.ndarray:
    # Must stay identical to load_image() in ml/train.py so serving matches training.
    image = image.convert("RGB").resize((IMAGE_SIZE, IMAGE_SIZE), Image.BILINEAR)
    return np.asarray(image, dtype=np.float32)[np.newaxis]


@app.get("/health")
def health():
    if app.state.interpreter is None:
        return JSONResponse(status_code=503, content={"status": "error", "model_loaded": False})
    return {"status": "ok", "model_loaded": True, "labels": app.state.labels}


@app.post(
    "/predict",
    response_model=PredictResponse,
    responses={
        400: {"model": ErrorResponse, "description": "File bukan gambar yang valid"},
        413: {"model": ErrorResponse, "description": "File lebih dari 5 MB"},
        415: {"model": ErrorResponse, "description": "Format selain JPG/PNG"},
        503: {"model": ErrorResponse, "description": "Model belum dimuat"},
    },
)
def predict(file: UploadFile = File(...)):
    if app.state.interpreter is None:
        raise HTTPException(status_code=503, detail="Model belum siap.")
    if file.content_type not in ALLOWED_CONTENT_TYPES:
        raise HTTPException(status_code=415, detail="Format file harus JPG atau PNG.")

    data = file.file.read(MAX_UPLOAD_BYTES + 1)
    if len(data) > MAX_UPLOAD_BYTES:
        raise HTTPException(status_code=413, detail="Ukuran file maksimal 5 MB.")

    try:
        image = Image.open(io.BytesIO(data))
        image.load()
    except (UnidentifiedImageError, OSError, Image.DecompressionBombError):
        raise HTTPException(status_code=400, detail="File bukan gambar yang valid.")
    # The content-type header is set by the client, so the decoded format is checked as well.
    if image.format not in ALLOWED_FORMATS:
        raise HTTPException(status_code=415, detail="Format file harus JPG atau PNG.")

    pixels = preprocess(image)
    with inference_lock:
        app.state.interpreter.set_tensor(app.state.input_index, pixels)
        app.state.interpreter.invoke()
        probabilities = app.state.interpreter.get_tensor(app.state.output_index)[0].copy()

    labels = app.state.labels
    best = int(probabilities.argmax())
    return PredictResponse(
        label=labels[best],
        confidence=round(float(probabilities[best]), 4),
        probabilities={label: round(float(p), 4) for label, p in zip(labels, probabilities)},
    )
