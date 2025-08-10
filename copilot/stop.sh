#!/bin/bash

# Script d'arrêt pour BookYourCoach

echo "🛑 Arrêt de BookYourCoach..."

# Arrêter tous les conteneurs
docker-compose down

echo "✅ Tous les conteneurs ont été arrêtés."
echo ""
echo "💡 Pour redémarrer: ./start.sh"
echo "🗑️ Pour supprimer complètement (données incluses): docker-compose down -v"
