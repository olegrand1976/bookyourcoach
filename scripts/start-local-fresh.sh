#!/bin/bash
# Script pour démarrer l'environnement local avec les dernières versions
# Usage: ./scripts/start-local-fresh.sh

set -e

echo "🚀 Démarrage de l'environnement local avec rebuild"
echo "===================================================="
echo ""

echo "📥 Pull des dernières images de base et reconstruction..."
docker-compose -f docker-compose.local.yml build --pull

echo ""
echo "🚀 Démarrage des conteneurs..."
docker-compose -f docker-compose.local.yml up -d

echo ""
echo "✅ Environnement démarré avec succès!"
echo ""
echo "📊 État des conteneurs:"
docker-compose -f docker-compose.local.yml ps

echo ""
echo "📝 Logs disponibles avec: docker-compose -f docker-compose.local.yml logs -f"

