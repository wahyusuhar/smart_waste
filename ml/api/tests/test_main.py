import io
import json

import pytest
from fastapi.testclient import TestClient
from PIL import Image

from main import LABELS_PATH, MAX_UPLOAD_BYTES, app

LABELS = json.loads(LABELS_PATH.read_text(encoding="utf-8"))


@pytest.fixture(scope="module")
def client():
    # The context manager runs the lifespan handler, which loads the model.
    with TestClient(app) as test_client:
        yield test_client


def make_image(image_format):
    # Synthetic image: tests check the API contract, not model accuracy.
    image = Image.new("RGB", (320, 240), color=(120, 160, 90))
    buffer = io.BytesIO()
    image.save(buffer, format=image_format)
    return buffer.getvalue()


def test_health_reports_loaded_model(client):
    response = client.get("/health")

    assert response.status_code == 200
    assert response.json() == {"status": "ok", "model_loaded": True, "labels": LABELS}


@pytest.mark.parametrize(
    ("image_format", "content_type"), [("JPEG", "image/jpeg"), ("PNG", "image/png")]
)
def test_predict_returns_label_and_probabilities(client, image_format, content_type):
    files = {"file": (f"photo.{image_format.lower()}", make_image(image_format), content_type)}

    response = client.post("/predict", files=files)

    assert response.status_code == 200
    body = response.json()
    assert body["label"] in LABELS
    assert set(body["probabilities"]) == set(LABELS)
    assert sum(body["probabilities"].values()) == pytest.approx(1.0, abs=1e-3)
    assert body["confidence"] == max(body["probabilities"].values())
    assert body["probabilities"][body["label"]] == body["confidence"]


def test_predict_rejects_non_image_content_type(client):
    files = {"file": ("notes.txt", b"bukan gambar", "text/plain")}

    response = client.post("/predict", files=files)

    assert response.status_code == 415


def test_predict_rejects_corrupt_image(client):
    files = {"file": ("photo.jpg", b"\x00\x01\x02 bukan jpeg", "image/jpeg")}

    response = client.post("/predict", files=files)

    assert response.status_code == 400


def test_predict_rejects_file_over_5_mb(client):
    files = {"file": ("big.jpg", b"\xff" * (MAX_UPLOAD_BYTES + 1), "image/jpeg")}

    response = client.post("/predict", files=files)

    assert response.status_code == 413


def test_predict_checks_token_when_configured(client, monkeypatch):
    monkeypatch.setattr("main.ML_TOKEN", "rahasia-123")
    files = {"file": ("photo.jpg", make_image("JPEG"), "image/jpeg")}

    assert client.post("/predict", files=files).status_code == 401
    assert client.post("/predict", files=files, headers={"X-ML-Token": "salah"}).status_code == 401
    assert client.post("/predict", files=files, headers={"X-ML-Token": "rahasia-123"}).status_code == 200


def test_health_does_not_need_token(client, monkeypatch):
    # Render's health check calls /health without any header.
    monkeypatch.setattr("main.ML_TOKEN", "rahasia-123")

    assert client.get("/health").status_code == 200


def test_predict_requires_file_field(client):
    response = client.post("/predict")

    assert response.status_code == 422
