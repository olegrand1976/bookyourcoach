#!/bin/bash

echo "🔍 Diagnostic de configuration API Frontend"
echo "==========================================="

cd /home/olivier/projets/bookyourcoach/copilot/frontend

echo ""
echo "📂 Fichier .env:"
cat .env 2>/dev/null || echo "Fichier .env introuvable"

echo ""
echo "🔧 Configuration Nuxt (nuxt.config.ts):"
grep -A 5 -B 5 "apiBase" nuxt.config.ts

echo ""
echo "🌐 Variables d'environnement API:"
env | grep -i api || echo "Aucune variable API trouvée"

echo ""
echo "🧹 Nettoyage des caches..."
rm -rf .nuxt node_modules/.cache 2>/dev/null

echo ""
echo "🔄 Test de configuration runtime..."
export API_BASE_URL="http://localhost:8081/api"
echo "API_BASE_URL forcé à: $API_BASE_URL"

echo ""
echo "🚀 Redémarrage du frontend avec configuration explicite..."
npm run dev &
DEV_PID=$!

echo "PID du processus frontend: $DEV_PID"
echo ""
echo "⏳ Attente que le frontend démarre..."
sleep 10

echo ""
echo "🧪 Test de la configuration runtime..."
curl -s "http://localhost:3001/_nuxt" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Frontend accessible sur le port 3001"
else
    echo "❌ Frontend non accessible sur le port 3001"
fi

echo ""
echo "📋 Processus frontend en cours:"
ps aux | grep -E "(npm|nuxt|node)" | grep -v grep
