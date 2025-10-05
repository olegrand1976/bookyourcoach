#!/bin/bash
# Script pour rebuilder les images Docker locales avec la dernière version
# Usage: ./scripts/rebuild-local.sh [--no-cache]

set -e

echo "🏗️  Rebuild des images Docker locales"
echo "======================================"
echo ""

# Vérifier si l'option --no-cache est passée
NO_CACHE=""
PULL_OPTION="--pull"
if [[ "$1" == "--no-cache" ]]; then
    NO_CACHE="--no-cache"
    echo "⚠️  Mode: Rebuild complet sans cache"
else
    echo "ℹ️  Mode: Rebuild avec cache (utilisez --no-cache pour forcer)"
fi

echo ""
echo "🛑 Arrêt des conteneurs en cours..."
docker-compose -f docker-compose.local.yml down

echo ""
echo "📥 Pull des dernières images de base..."
echo "🏗️  Reconstruction des images..."
docker-compose -f docker-compose.local.yml build $NO_CACHE $PULL_OPTION

echo ""
echo "🚀 Démarrage des conteneurs..."
docker-compose -f docker-compose.local.yml up -d

echo ""
echo "✅ Rebuild terminé avec succès!"
echo ""
echo "📊 État des conteneurs:"
docker-compose -f docker-compose.local.yml ps

