#!/bin/sh
# Container entrypoint. It must stay fast: Vercel starts a new instance whenever traffic needs one,
# so migrations and seeding are not run here but once by hand (php artisan migrate --seed --force).
set -e

# No secret files on Vercel or Railway: the Aiven CA certificate arrives base64-encoded in an env var.
# /tmp is the only place guaranteed to be writable.
if [ -n "$MYSQL_CA_CERT_BASE64" ]; then
    echo "$MYSQL_CA_CERT_BASE64" | base64 -d > /tmp/aiven-ca.pem
    export MYSQL_ATTR_SSL_CA=/tmp/aiven-ca.pem
fi

# Wake the ML service in the background: it also stops when idle, and the first photo
# classification would otherwise wait for its cold start.
if [ -n "$ML_SERVICE_URL" ] && command -v curl > /dev/null; then
    curl -s -o /dev/null --max-time 60 "$ML_SERVICE_URL/health" &
fi

exec frankenphp run --config /etc/caddy/Caddyfile
