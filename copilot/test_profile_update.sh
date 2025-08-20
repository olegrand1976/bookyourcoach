#!/bin/bash

echo "=== Test de mise à jour du profil ==="

# Configuration
API_URL="http://127.0.0.1:8081/api"
EMAIL="admin@bookyourcoach.com"
PASSWORD="admin123"

echo "1. Connexion et récupération du token..."
LOGIN_RESPONSE=$(curl -s -X POST "$API_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\": \"$EMAIL\", \"password\": \"$PASSWORD\"}")

TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.token')

if [ "$TOKEN" = "null" ] || [ -z "$TOKEN" ]; then
    echo "❌ Erreur de connexion"
    echo "$LOGIN_RESPONSE"
    exit 1
fi

echo "✅ Token obtenu: ${TOKEN:0:20}..."

echo ""
echo "2. Récupération du profil actuel..."
CURRENT_PROFILE=$(curl -s -X GET "$API_URL/profile" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")

echo "✅ Profil actuel:"
echo "$CURRENT_PROFILE" | jq

echo ""
echo "3. Mise à jour du profil avec téléphone et date de naissance..."
UPDATE_RESPONSE=$(curl -s -X PUT "$API_URL/profile" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+33987654321",
    "birth_date": "1990-12-25"
  }')

echo "✅ Réponse de mise à jour:"
echo "$UPDATE_RESPONSE" | jq

echo ""
echo "4. Vérification des données mises à jour..."
UPDATED_PROFILE=$(curl -s -X GET "$API_URL/profile" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")

echo "✅ Profil mis à jour:"
echo "$UPDATED_PROFILE" | jq

# Vérification spécifique des champs
PHONE=$(echo "$UPDATED_PROFILE" | jq -r '.profile.phone')
BIRTH_DATE=$(echo "$UPDATED_PROFILE" | jq -r '.profile.date_of_birth')

echo ""
echo "=== RÉSULTATS ==="
if [ "$PHONE" = "+33987654321" ]; then
    echo "✅ Téléphone mis à jour correctement: $PHONE"
else
    echo "❌ Erreur téléphone: attendu '+33987654321', obtenu '$PHONE'"
fi

if [ "$BIRTH_DATE" = "1990-12-25T00:00:00.000000Z" ]; then
    echo "✅ Date de naissance mise à jour correctement: $BIRTH_DATE"
else
    echo "❌ Erreur date de naissance: attendu '1990-12-25T00:00:00.000000Z', obtenu '$BIRTH_DATE'"
fi

echo ""
echo "=== Test terminé ==="
