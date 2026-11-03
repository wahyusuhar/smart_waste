# Production image of the prediction service, built with ml/ as the context so models/ can be copied.
# It sits in ml/ (not ml/api/) because Vercel's container runtime expects the Dockerfile at the project root.
FROM python:3.11-slim

# HOST and PORT can be overridden by the platform. 0.0.0.0 (IPv4) is what Vercel's proxy reaches;
# Railway's private network needs HOST=:: because older Railway environments are IPv6-only.
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    HOST=0.0.0.0 \
    PORT=8001

WORKDIR /app

# Only the serving dependencies (LiteRT), not TensorFlow from ml/requirements.txt.
COPY api/requirements.txt api/requirements.txt
RUN pip install --no-cache-dir -r api/requirements.txt

# main.py looks for the model in ../models relative to itself, i.e. /app/models.
COPY api/main.py api/main.py
COPY models/waste_classifier.tflite models/labels.json models/

# exec lets uvicorn receive the stop signal directly.
CMD ["sh", "-c", "exec uvicorn main:app --app-dir api --host ${HOST} --port ${PORT}"]
