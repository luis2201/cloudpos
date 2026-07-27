FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY public ./public
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM php:8.4-cli-bookworm AS app

ARG APP_UID=1000
ARG APP_GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libicu-dev \
        libsqlite3-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        pdo_sqlite \
        zip \
    && rm -rf /var/lib/apt/lists/*

RUN groupadd --gid "${APP_GID}" cloudpos \
    && useradd --uid "${APP_UID}" --gid cloudpos --create-home cloudpos

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY --chown=cloudpos:cloudpos . .
COPY --from=vendor --chown=cloudpos:cloudpos /app/vendor ./vendor
COPY --from=frontend --chown=cloudpos:cloudpos /app/public/build ./public/build

RUN chmod +x docker/entrypoint.sh \
    && chown -R cloudpos:cloudpos storage bootstrap/cache

USER cloudpos

EXPOSE 8000

ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
