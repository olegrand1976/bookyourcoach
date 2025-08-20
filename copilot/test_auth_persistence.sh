#!/bin/bash

echo "🔐 Test de persistance d'authentification BookYourCoach"
echo "======================================================"

# 1. Vérifier l'accès au frontend
echo "📋 Étape 1: Vérification de l'accès frontend"
FRONTEND_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:3000)

if [ "$FRONTEND_RESPONSE" != "200" ]; then
    echo "❌ Frontend non accessible (code: $FRONTEND_RESPONSE)"
    exit 1
fi

echo "✅ Frontend accessible"

# 2. Tester la connexion API et récupérer un token
echo "📋 Étape 2: Connexion admin et récupération du token"
LOGIN_RESPONSE=$(curl -s -X POST "http://localhost:8081/api/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "admin@bookyourcoach.com", "password": "admin123"}')

# Extraire le token
TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo "❌ Échec de l'obtention du token"
    echo "Réponse: $LOGIN_RESPONSE"
    exit 1
fi

echo "✅ Token obtenu: ${TOKEN:0:20}..."

# 3. Vérifier l'accès aux pages admin avec le token
echo "📋 Étape 3: Test d'accès aux endpoints admin"

# Test endpoint settings
SETTINGS_RESPONSE=$(curl -s -w "%{http_code}" -X GET "http://localhost:8081/api/admin/settings" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

SETTINGS_CODE=$(echo "$SETTINGS_RESPONSE" | tail -c 4)

if [ "$SETTINGS_CODE" = "200" ]; then
    echo "✅ Accès aux paramètres admin réussi"
else
    echo "❌ Échec d'accès aux paramètres admin (code: $SETTINGS_CODE)"
fi

# Test endpoint stats
STATS_RESPONSE=$(curl -s -w "%{http_code}" -X GET "http://localhost:8081/api/admin/stats" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

STATS_CODE=$(echo "$STATS_RESPONSE" | tail -c 4)

if [ "$STATS_CODE" = "200" ]; then
    echo "✅ Accès aux statistiques admin réussi"
else
    echo "❌ Échec d'accès aux statistiques admin (code: $STATS_CODE)"
fi

# 4. Vérifier que le token reste valide
echo "📋 Étape 4: Vérification de la persistance du token"

sleep 2

USER_RESPONSE=$(curl -s -w "%{http_code}" -X GET "http://localhost:8081/api/auth/user" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

USER_CODE=$(echo "$USER_RESPONSE" | tail -c 4)

if [ "$USER_CODE" = "200" ]; then
    echo "✅ Token toujours valide après 2 secondes"
else
    echo "❌ Token invalide après 2 secondes (code: $USER_CODE)"
fi

echo "🎯 Instructions pour test manuel de persistance:"
echo "1. Ouvrir http://localhost:3000/login"
echo "2. Se connecter avec admin@bookyourcoach.com / admin123"
echo "3. Aller sur une page admin (ex: /admin/settings)"
echo "4. Actualiser la page (F5 ou Ctrl+R)"
echo "5. Vérifier que vous restez connecté et gardez le statut admin"

echo ""
echo "🎯 Test terminé !"
