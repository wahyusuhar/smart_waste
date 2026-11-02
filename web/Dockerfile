# Production image for Render. Built from the web/ folder (see render.yaml).

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

# 3. Apache + PHP 8.2 running the app.
FROM php:8.2-apache

# gd (with JPEG) draws the seeder's placeholder photos; pdo_mysql talks to Aiven.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libjpeg62-turbo-dev libpng-dev unzip \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install pdo_mysql gd opcache \
    && rm -rf /var/lib/apt/lists/*

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && a2enmod rewrite
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-smart-waste.ini
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Render sends traffic to $PORT (10000 unless changed); Apache reads it when it starts.
ENV PORT=10000
RUN sed -i 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY --from=vendor /app/vendor vendor
COPY . .
COPY --from=assets /app/public/build public/build

# The start script may arrive with Windows line endings if it was edited on Windows.
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && sed -i 's/\r$//' docker/start.sh \
    && chmod +x docker/start.sh \
    && chown -R www-data:www-data storage bootstrap/cache

CMD ["docker/start.sh"]
