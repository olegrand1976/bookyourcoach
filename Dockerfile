FROM php:8.2-fpm

# Arguments pour la configuration
ARG user=laravel
ARG uid=1000

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libgd-dev \
    jpegoptim \
    optipng \
    pngquant \
    gifsicle \
    vim \
    nano \
    ghostscript \
    libmagickwand-dev --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Installation de Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Installation d'ImageMagick
RUN pecl install imagick && docker-php-ext-enable imagick

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Création de l'utilisateur système pour Laravel
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Configuration PHP
# COPY ./docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

# Définition du répertoire de travail
WORKDIR /var/www

# Copie de tous les fichiers d'abord
COPY . .
COPY --chown=$user:$user . .

# Copie de la configuration PHP après avoir copié tous les fichiers
COPY ./docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

# Installation des dépendances PHP
RUN composer install --optimize-autoloader

# Finalisation de l'installation Composer
# RUN composer dump-autoload --optimize

# Permissions correctes
RUN chown -R $user:www-data /var/www
RUN chmod -R 755 /var/www/storage
RUN chmod -R 755 /var/www/bootstrap/cache

# Changement vers l'utilisateur Laravel
USER $user

# Exposition du port
EXPOSE 9000

# Commande par défaut
CMD ["php-fpm"]
