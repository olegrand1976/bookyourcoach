#!/bin/bash

# Script de diagnostic rapide pour identifier le problème

echo "🔍 DIAGNOSTIC RAPIDE"
echo "==================="

echo ""
echo "📁 Fichiers docker-compose disponibles:"
ls -la docker-compose*.yml 2>/dev/null || echo "Aucun fichier trouvé"

echo ""
echo "📋 Contenu des noms de conteneurs dans docker-compose.prod.yml:"
if [ -f "docker-compose.prod.yml" ]; then
    grep -n "container_name:" docker-compose.prod.yml || echo "Pas de container_name défini"
else
    echo "Fichier docker-compose.prod.yml manquant"
fi

echo ""
echo "🐳 Conteneurs actuellement en cours d'exécution:"
docker ps --format "table {{.Names}}\t{{.Image}}\t{{.Ports}}"

echo ""
echo "📂 Variables d'environnement importantes:"
if [ -f ".env" ]; then
    grep -E "(NEO4J_PASSWORD|APP_NAME)" .env 2>/dev/null || echo "Variables manquantes"
else
    echo "Fichier .env manquant"
fi

echo ""
echo "🔧 Test de configuration docker-compose:"
docker compose -f docker-compose.prod.yml config --services 2>/dev/null || echo "Erreur de configuration"
