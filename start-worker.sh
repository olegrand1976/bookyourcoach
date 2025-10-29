#!/bin/bash
# Script pour démarrer le queue worker en développement local

echo "🚀 Démarrage du queue worker Laravel..."

# Vérifier si un worker est déjà actif
EXISTING_WORKER=$(docker compose exec backend ps aux | grep "queue:work" | grep -v grep)

if [ ! -z "$EXISTING_WORKER" ]; then
    echo "⚠️  Un worker est déjà actif:"
    echo "$EXISTING_WORKER"
    echo ""
    read -p "Voulez-vous le redémarrer ? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "❌ Annulé"
        exit 1
    fi
    
    echo "🔄 Arrêt du worker existant..."
    docker compose exec backend pkill -f "queue:work"
    sleep 2
fi

# Démarrer le worker
echo "✅ Démarrage du nouveau worker..."
docker compose exec -d backend php artisan queue:work --sleep=3 --tries=3 --timeout=60 --verbose

sleep 2

# Vérifier que le worker est bien démarré
WORKER_STATUS=$(docker compose exec backend ps aux | grep "queue:work" | grep -v grep)

if [ ! -z "$WORKER_STATUS" ]; then
    echo "✅ Worker démarré avec succès !"
    echo "$WORKER_STATUS"
    echo ""
    echo "📊 Pour voir les logs en direct:"
    echo "   docker compose exec backend tail -f storage/logs/laravel.log | grep -i 'job\|queue\|volunteer'"
    echo ""
    echo "🛑 Pour arrêter le worker:"
    echo "   docker compose exec backend pkill -f 'queue:work'"
else
    echo "❌ Erreur: Le worker n'a pas démarré correctement"
    exit 1
fi

