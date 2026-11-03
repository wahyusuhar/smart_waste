# Production image, built from the web/ folder. Follows Vercel's guide for Laravel in a container
# (FrankenPHP, caches built into the image, nothing slow at startup); runs on Render or Railway too.

# 1. PHP dependencies, without dev packages.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Extensions are checked against the real PHP 8.2 in the last stage (composer dump-autoload).
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs --no-interaction

# 2. Vite assets. Tailwind also scans Laravel's pagination views in vendor/ (resources/css/app.css).
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# 3. FrankenPHP (Caddy + PHP 8.2 in one process) running the app.
FROM dunglas/frankenphp:1.13.4-php8.2-bookworm

# pdo_mysql talks to Aiven. gd is not needed: the seeder, which draws placeholder photos, runs from a laptop.
RUN install-php-extensions pdo_mysql opcache

RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-smart-waste.ini
COPY docker/Caddyfile /etc/caddy/Caddyfile

# Vercel's filesystem is read-only apart from /tmp, so Caddy keeps its own state there.
ENV XDG_CONFIG_HOME=/tmp/caddy/config \
    XDG_DATA_HOME=/tmp/caddy/data

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY --from=vendor /app/vendor vendor
COPY . .
COPY --from=assets /app/public/build public/build

# View and event caches need no runtime values, so they are built into the image. config:cache is left
# out on purpose: the platform's env vars only exist at runtime. The start script may arrive with Windows
# line endings. setcap lets FrankenPHP bind port 80 although it runs as www-data.
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan view:cache \
    && php artisan event:cache \
    && sed -i 's/\r$//' docker/start.sh \
    && chmod +x docker/start.sh \
    && chown -R www-data:www-data storage bootstrap/cache \
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp

USER www-data
CMD ["docker/start.sh"]
