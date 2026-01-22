#!/bin/sh
set -e

# Corriger les permissions de storage et bootstrap/cache pour éviter
# "Permission denied" lors de la compilation des vues Blade (storage/framework/views)
# et de l'écriture des fichiers temporaires (storage/app/temp, logs, etc.)
# Nécessaire quand des volumes sont montés ou que l'image est déployée avec
# un utilisateur différent.
if [ -d /var/www/html/storage ]; then
    chown -R www-data:www-data /var/www/html/storage 2>/dev/null || true
    chmod -R 775 /var/www/html/storage 2>/dev/null || true
fi
if [ -d /var/www/html/bootstrap/cache ]; then
    chown -R www-data:www-data /var/www/html/bootstrap/cache 2>/dev/null || true
    chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true
fi

# S'assurer que storage/app/temp existe pour la génération des PDF (lettres de volontariat)
mkdir -p /var/www/html/storage/app/temp 2>/dev/null || true
chown www-data:www-data /var/www/html/storage/app/temp 2>/dev/null || true

exec "$@"
