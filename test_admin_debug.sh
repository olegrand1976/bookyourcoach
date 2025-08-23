#!/bin/bash

echo "🔍 Test de debug de l'authentification admin"
echo "============================================="

# 1. Connexion avec le nouveau compte admin
echo "📋 Étape 1: Test de connexion avec le compte admin de secours"
LOGIN_RESPONSE=$(curl -s -X POST "http://localhost:8081/api/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "admin.secours@bookyourcoach.com", "password": "secours123"}')

echo "Réponse de connexion: $LOGIN_RESPONSE"

# Extraire le token
TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo "❌ Échec de l'obtention du token"
    exit 1
fi

echo "✅ Token obtenu: ${TOKEN:0:20}..."

# 2. Vérifier l'endpoint /auth/user avec ce token
echo ""
echo "📋 Étape 2: Vérification de l'endpoint /auth/user"
USER_RESPONSE=$(curl -s -X GET "http://localhost:8081/api/auth/user" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "Réponse /auth/user: $USER_RESPONSE"

# 3. Vérifier l'accès admin
echo ""
echo "📋 Étape 3: Test d'accès aux endpoints admin"
ADMIN_RESPONSE=$(curl -s -X GET "http://localhost:8081/api/admin/settings" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "Réponse admin/settings: ${ADMIN_RESPONSE:0:200}..."

# 4. Test de votre ancien compte
echo ""
echo "📋 Étape 4: Test avec votre ancien compte"
OLD_LOGIN_RESPONSE=$(curl -s -X POST "http://localhost:8081/api/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "o.legrand@gmail.com", "password": "admin123"}')

echo "Connexion ancien compte: $OLD_LOGIN_RESPONSE"

echo ""
echo "🎯 Utilisez maintenant les identifiants suivants :"
echo "Email: admin.secours@bookyourcoach.com"
echo "Mot de passe: secours123"
echo ""
echo "🌐 Testez dans le navigateur:"
echo "1. Aller sur http://localhost:3000/login"
echo "2. Se connecter avec les nouveaux identifiants"
echo "3. Aller sur une page admin"
echo "4. Actualiser la page pour voir si le problème persiste"
