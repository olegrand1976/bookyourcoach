#!/bin/bash

# Script de démarrage du worker de queue Laravel
# Usage: ./start-queue-worker.sh

echo "🚀 Démarrage du worker de queue Laravel..."
echo ""

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "artisan" ]; then
    echo "❌ Erreur: Le fichier 'artisan' n'a pas été trouvé."
    echo "   Veuillez exécuter ce script depuis la racine du projet."
    exit 1
fi

# Vérifier que les migrations de queue ont été exécutées
echo "📋 Vérification des migrations..."
php artisan migrate:status | grep -q "create_jobs_table"
if [ $? -ne 0 ]; then
    echo "⚠️  Les migrations de queue n'ont pas été exécutées."
    echo "   Exécution de: php artisan migrate"
    php artisan migrate --force
fi

# Afficher la configuration actuelle
echo ""
echo "📊 Configuration actuelle:"
echo "   QUEUE_CONNECTION: $(grep QUEUE_CONNECTION .env | cut -d '=' -f2)"
echo ""

# Lancer le worker
echo "✅ Lancement du worker de queue..."
echo "   (Appuyez sur Ctrl+C pour arrêter)"
echo ""

php artisan queue:work --verbose --tries=3 --timeout=120



