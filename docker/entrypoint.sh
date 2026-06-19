#!/bin/sh
set -e

# Cloud Run (K_SERVICE) : startup minimal — image préparée au build Docker
if [ -n "${K_SERVICE:-}" ]; then
    APP_KEY_VAL=""
    if [ -f /var/www/html/.env ]; then
        APP_KEY_LINE=$(grep -E '^APP_KEY=' /var/www/html/.env 2>/dev/null | tail -n1 || true)
        APP_KEY_VAL=${APP_KEY_LINE#APP_KEY=}
        APP_KEY_VAL=$(printf '%s' "$APP_KEY_VAL" | tr -d '\r' | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//")
    fi
    if [ -z "$APP_KEY_VAL" ] && [ -n "${APP_KEY:-}" ]; then
        APP_KEY_VAL="$APP_KEY"
    fi
    if [ -z "$APP_KEY_VAL" ]; then
        echo "FATAL: APP_KEY is empty (.env et variable d'environnement)." >&2
        exit 1
    fi
    exec "$@"
fi

# Docker Compose / volumes locaux — permissions + cache
if [ -d /var/www/html/storage ]; then
    chown -R www-data:www-data /var/www/html/storage 2>/dev/null || true
    chmod -R 775 /var/www/html/storage 2>/dev/null || true
fi
if [ -d /var/www/html/bootstrap/cache ]; then
    chown -R www-data:www-data /var/www/html/bootstrap/cache 2>/dev/null || true
    chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true
fi

mkdir -p /var/www/html/storage/app/temp /var/www/html/storage/app/public /var/www/html/storage/app/public/cancellation_certificates 2>/dev/null || true
chown -R www-data:www-data /var/www/html/storage/app/temp /var/www/html/storage/app/public 2>/dev/null || true

APP_KEY_VAL=""
if [ -f /var/www/html/.env ]; then
    APP_KEY_LINE=$(grep -E '^APP_KEY=' /var/www/html/.env 2>/dev/null | tail -n1 || true)
    APP_KEY_VAL=${APP_KEY_LINE#APP_KEY=}
    APP_KEY_VAL=$(printf '%s' "$APP_KEY_VAL" | tr -d '\r' | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//")
fi
if [ -z "$APP_KEY_VAL" ] && [ -n "${APP_KEY:-}" ]; then
    APP_KEY_VAL="$APP_KEY"
fi
if [ -z "$APP_KEY_VAL" ]; then
    echo "FATAL: APP_KEY is empty (.env et variable d'environnement)." >&2
    echo "Generate: php artisan key:generate --show" >&2
    exit 1
fi

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
