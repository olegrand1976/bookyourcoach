#!/bin/bash

echo "🎨 Test d'upload de logo BookYourCoach"
echo "====================================="

# 1. Se connecter et récupérer le token
echo "📋 Étape 1: Connexion admin"
LOGIN_RESPONSE=$(curl -s -X POST "http://localhost:8081/api/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "admin@bookyourcoach.com", "password": "admin123"}')

echo "Réponse de connexion: $LOGIN_RESPONSE"

# Extraire le token (format Sanctum)
TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo "❌ Échec de l'extraction du token"
    exit 1
fi

echo "✅ Token obtenu: ${TOKEN:0:20}..."

# 2. Créer un fichier image de test (simple PNG de 1x1 pixel)
echo "📋 Étape 2: Création d'un fichier PNG de test"
# Créer un PNG minimal en base64
echo "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAGAGU2kqgAAAABJRU5ErkJggg==" | base64 -d > test-logo.png

# 3. Tester l'upload de logo
echo "📋 Étape 3: Test d'upload de logo"
UPLOAD_RESPONSE=$(curl -s -X POST "http://localhost:8081/api/admin/upload-logo" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -F "logo=@test-logo.png")

echo "Réponse d'upload: $UPLOAD_RESPONSE"

# 4. Vérifier les paramètres mis à jour
echo "📋 Étape 4: Vérification des paramètres"
SETTINGS_RESPONSE=$(curl -s -X GET "http://localhost:8081/api/admin/settings" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "Paramètres actuels: $SETTINGS_RESPONSE"

# Nettoyage
rm -f test-logo.png

echo "🎯 Test terminé !"
