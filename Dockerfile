FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY resources ./resources
COPY vite.config.js ./
COPY public ./public

ENV VITE_REVERB_APP_KEY=zernio-chat-key
ENV VITE_REVERB_HOST=127.0.0.1
ENV VITE_REVERB_PORT=8080
ENV VITE_REVERB_SCHEME=http

RUN npm run build


FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts


FROM php:8.3-cli-bookworm

WORKDIR /var/www

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        libxml2-dev \
    && docker-php-ext-install \
        bcmath \
        curl \
        intl \
        mbstring \
        pcntl \
        pdo_mysql \
        xml \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /app/vendor ./vendor

COPY . .

COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache

RUN php artisan package:discover --ansi

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000
EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]
