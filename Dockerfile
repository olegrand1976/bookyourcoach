# Dockerfile pour la production Acti'Vibe
FROM php:8.2-fpm-alpine AS base

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
    oniguruma-dev

# Installer Node.js 22 (dernière version disponible dans Alpine 3.22)
RUN apk add --no-cache nodejs npm

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

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Créer l'utilisateur www-data
RUN addgroup -g 1000 -S www-data \
    && adduser -u 1000 -D -S -G www-data www-data

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers de configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/php.ini

# Copier les fichiers de l'application
COPY --chown=www-data:www-data . .

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Installer les dépendances Node.js et build le frontend
RUN cd frontend \
    && npm install \
    && find node_modules/.bin -name "*.mjs" -exec chmod +x {} \; 2>/dev/null || true \
    && chmod +x node_modules/@nuxt/cli/bin/nuxi.mjs 2>/dev/null || true \
    && chmod +x node_modules/vitest/vitest.mjs 2>/dev/null || true \
    && chmod +x node_modules/@esbuild/linux-x64/bin/esbuild 2>/dev/null || true \
    && find node_modules/@esbuild -name "esbuild" -exec chmod +x {} \; 2>/dev/null || true \
    && npm run build \
    && npm cache clean --force

# Configurer le frontend pour le port 3001
ENV NUXT_PORT=3001
ENV NUXT_HOST=0.0.0.0

# Créer les répertoires nécessaires
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Générer la clé d'application Laravel
RUN php artisan key:generate --no-interaction

# Optimiser Laravel pour la production
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Exposer les ports
EXPOSE 80
EXPOSE 3001

# Démarrer Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]