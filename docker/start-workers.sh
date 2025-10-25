#!/bin/bash

# Script de démarrage des workers Laravel
# Ce script est utilisé par Supervisor pour démarrer les workers

set -e

# Attendre que la base de données soit prête
echo "⏳ Attente de la base de données..."
while ! mysqladmin ping -h"${DB_HOST:-mysql}" -P"${DB_PORT:-3306}" -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD}" --silent; do
    echo "Base de données non disponible, attente..."
    sleep 2
done

echo "✅ Base de données disponible"

# Attendre que Redis soit prêt
echo "⏳ Attente de Redis..."
while ! redis-cli -h "${REDIS_HOST:-redis}" -p "${REDIS_PORT:-6379}" ping > /dev/null 2>&1; do
    echo "Redis non disponible, attente..."
    sleep 2
done

echo "✅ Redis disponible"

# Générer la clé d'application si elle n'existe pas
if [ ! -f .env ]; then
    echo "📝 Création du fichier .env..."
    cp .env.example .env
fi

# Générer la clé d'application
echo "🔑 Génération de la clé d'application..."
php artisan key:generate --force

# Exécuter les migrations
echo "🗄️ Exécution des migrations..."
php artisan migrate --force

# Cache de configuration
echo "⚡ Optimisation du cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Démarrer les workers Laravel
echo "🚀 Démarrage des workers Laravel..."

# Worker pour les queues
php artisan queue:work --verbose --tries=3 --timeout=90 --sleep=3 &

# Worker pour les horaires
php artisan schedule:work &

# Attendre indéfiniment
wait