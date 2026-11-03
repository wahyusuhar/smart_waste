# Production image of the prediction service, built with ml/ as the context so models/ can be copied.
# It sits in ml/ (not ml/api/) because Vercel's container runtime expects the Dockerfile at the project root.
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

# The host sets $PORT; exec lets uvicorn receive the stop signal directly. "::" listens on IPv6 and IPv4,
# which Railway's private network needs (older Railway environments are IPv6-only).
CMD ["sh", "-c", "exec uvicorn main:app --app-dir api --host :: --port ${PORT}"]
