# Image dédiée aux tests PHPUnit (SQLite via pdo_sqlite), sans image production.
# Usage : docker compose --profile test run --rm php-test …
#         ou composer test:docker / test:docker:local
FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libicu-dev \
    libonig-dev \
    libsqlite3-dev \
    libzip-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        mbstring \
        bcmath \
        pcntl \
        pdo_sqlite \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html
