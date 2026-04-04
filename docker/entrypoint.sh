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

# S'assurer que storage/app/temp et storage/app/public existent (PDF, certificats médicaux)
mkdir -p /var/www/html/storage/app/temp /var/www/html/storage/app/public /var/www/html/storage/app/public/cancellation_certificates 2>/dev/null || true
chown -R www-data:www-data /var/www/html/storage/app/temp /var/www/html/storage/app/public 2>/dev/null || true

# Laravel refuse de démarrer sans clé : sans ce garde-fou, le healthcheck Docker
# boucle sur des 500 (réponses énormes = page d'erreur). Le .env est souvent un
# montage .env.local en lecture seule : il faut y mettre une clé (pas la régénérer ici).
if [ -f /var/www/html/.env ]; then
    APP_KEY_LINE=$(grep -E '^APP_KEY=' /var/www/html/.env 2>/dev/null | tail -n1 || true)
    APP_KEY_VAL=${APP_KEY_LINE#APP_KEY=}
    APP_KEY_VAL=$(printf '%s' "$APP_KEY_VAL" | tr -d '\r' | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//")
    if [ -z "$APP_KEY_VAL" ]; then
        echo "FATAL: APP_KEY is empty in .env (e.g. .env.local). Generate: php artisan key:generate --show" >&2
        echo "Then set APP_KEY=base64:... in .env.local (file is read-only in the container)." >&2
        exit 1
    fi
fi

# Le volume app_bootstrap_cache peut contenir packages.php / services.php générés avec
# composer install --dev (Collision, etc.). L'image Docker utilise --no-dev : le boot
# Laravel échoue avant que "php artisan optimize:clear" puisse les supprimer.
# Il faut donc effacer ces fichiers SANS passer par Artisan, puis régénérer.
if [ -d /var/www/html/bootstrap/cache ]; then
    find /var/www/html/bootstrap/cache -maxdepth 1 -type f -name '*.php' -delete 2>/dev/null || true
fi

if [ -f /var/www/html/artisan ]; then
    (cd /var/www/html && php artisan package:discover --ansi --no-interaction) 2>/dev/null || true
    (cd /var/www/html && php artisan optimize:clear --ansi --no-interaction) 2>/dev/null || true
    (cd /var/www/html && php artisan package:discover --ansi --no-interaction) 2>/dev/null || true
fi

if [ -d /var/www/html/storage ]; then
    chown -R www-data:www-data /var/www/html/storage 2>/dev/null || true
    chmod -R 775 /var/www/html/storage 2>/dev/null || true
fi
if [ -d /var/www/html/bootstrap/cache ]; then
    chown -R www-data:www-data /var/www/html/bootstrap/cache 2>/dev/null || true
    chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true
fi

exec "$@"
