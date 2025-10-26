# Dockerfile pour BookYourCoach - Backend Laravel uniquement
FROM php:8.3-fpm-alpine AS base

# Installer les dépendances système
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    zip \
    unzip \
    git \
    mysql-client \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    autoconf \
    gcc \
    g++ \
    make

# Installer les extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        zip \
        exif \
        pcntl \
        gd \
        intl \
        bcmath

# Installer l'extension phpredis
RUN pecl install redis \
    && docker-php-ext-enable redis

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers de configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/php.ini
COPY docker/start-workers.sh /usr/local/bin/start-workers.sh

# Ajouter la configuration Nginx spécifique au site
# Copier les fichiers de l'application
COPY --chown=www-data:www-data . .

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Créer les répertoires nécessaires
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views \
    && mkdir -p /tmp/nginx_client_temp /tmp/nginx_proxy_temp /tmp/nginx_fastcgi_temp /tmp/nginx_uwsgi_temp /tmp/nginx_scgi_temp \
    && chown -R www-data:www-data storage bootstrap/cache /tmp/nginx_* \
    && chmod -R 775 storage bootstrap/cache /tmp/nginx_* \
    && chmod +x /usr/local/bin/start-workers.sh

# Créer le fichier .env à partir de .env.example
RUN cp .env.example .env

# Correction du garde d'authentification
RUN sed -i "s/'guard' => env('AUTH_GUARD', 'web')/'guard' => 'sanctum'/" config/auth.php

# Exposer le port
EXPOSE 80

# Démarrer Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]