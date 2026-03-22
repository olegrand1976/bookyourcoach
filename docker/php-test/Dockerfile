# Image dédiée aux tests PHPUnit en local (pdo_sqlite) sans dépendre de l’image production.
FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libsqlite3-dev \
    libzip-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install -j"$(nproc)" pdo_sqlite zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html
