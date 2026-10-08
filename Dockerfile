# --- 1. PHP dependencies ---------------------------------------------------
# Dev dependencies are kept on purpose: the demo seeders use Faker.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

# --- 2. Frontend build (needs vendor/ for the Filament theme preset) --------
FROM node:20-alpine AS assets
WORKDIR /app
ARG VITE_APP_NAME=Laravel
ENV VITE_APP_NAME=${VITE_APP_NAME}
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
COPY --from=vendor /app/vendor ./vendor
# Client build only; no Node SSR server runs on Render's free tier.
RUN npx vite build

# --- 3. Runtime ------------------------------------------------------------
FROM php:8.3-apache

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql intl gd exif zip bcmath opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite headers \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf "upload_max_filesize=20M\npost_max_size=25M\nmemory_limit=256M\n" > "$PHP_INI_DIR/conf.d/app.ini"

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi \
    && php artisan storage:link \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
