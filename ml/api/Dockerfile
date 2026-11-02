# Production image for Render, built with ml/ as the context (see render.yaml) so models/ can be copied.
FROM python:3.11-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PORT=8001

WORKDIR /app

# Only the serving dependencies (LiteRT), not TensorFlow from ml/requirements.txt.
COPY api/requirements.txt api/requirements.txt
RUN pip install --no-cache-dir -r api/requirements.txt

# main.py looks for the model in ../models relative to itself, i.e. /app/models.
COPY api/main.py api/main.py
COPY models/waste_classifier.tflite models/labels.json models/

# Render sets $PORT; exec lets uvicorn receive the stop signal directly.
CMD ["sh", "-c", "exec uvicorn main:app --app-dir api --host 0.0.0.0 --port ${PORT}"]
