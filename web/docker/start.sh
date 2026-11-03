#!/bin/sh
# Runs every time the container starts on Render. Render free has no pre-deploy command,
# so migrations run here, before Apache accepts requests.
set -e
cd /var/www/html

# Railway has no secret files: the Aiven CA certificate arrives base64-encoded in an env var. It must be
# written before any artisan command, because config:cache below freezes MYSQL_ATTR_SSL_CA.
if [ -n "$MYSQL_CA_CERT_BASE64" ]; then
    echo "$MYSQL_CA_CERT_BASE64" | base64 -d > /tmp/aiven-ca.pem
    export MYSQL_ATTR_SSL_CA=/tmp/aiven-ca.pem
fi

php artisan migrate --force

# Seed only an empty database. Render restarts the container after every sleep, and the seeder
# replaces all reports, so running it on every start would wipe real data.
if [ "$(php artisan tinker --execute='echo App\Models\User::exists() ? "yes" : "no";')" = "no" ]; then
    php artisan db:seed --force
fi

php artisan storage:link --force
php artisan optimize

# Wake the ML service in the background: both services sleep on Render free, and the first
# photo classification would otherwise wait for its cold start.
if [ -n "$ML_SERVICE_URL" ]; then
    curl -s -o /dev/null --max-time 120 "$ML_SERVICE_URL/health" &
fi

# The artisan commands above run as root; Apache's PHP processes run as www-data.
chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
