#!/bin/bash

# Script de démarrage rapide BookYourCoach
# Pour démarrer rapidement l'application après le déploiement

echo "🚀 Démarrage rapide BookYourCoach..."

# Vérifier si le fichier .env existe
if [ ! -f ".env" ]; then
    echo "⚠️  Fichier .env non trouvé. Copie depuis production.env..."
    cp production.env .env
fi

# Démarrer les services
echo "📦 Démarrage des services..."
docker compose -f docker-compose.prod.yml up -d

# Attendre le démarrage
echo "⏳ Attente du démarrage (15 secondes)..."
sleep 15

# Vérifier le statut
echo "📊 Statut des conteneurs:"
docker compose -f docker-compose.prod.yml ps

echo ""
echo "✅ Application démarrée!"
echo "🌐 Accès: http://localhost"
echo "📋 Logs: docker compose -f docker-compose.prod.yml logs -f"