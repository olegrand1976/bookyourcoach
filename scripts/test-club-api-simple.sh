#!/bin/bash

# Script de test simple pour l'API du dashboard des clubs
echo "🧪 Test de l'API du dashboard des clubs"
echo ""

# Créer un token de test
echo "🔑 Création d'un token de test..."
TOKEN=$(docker-compose -f docker-compose.local.yml exec -T backend php artisan tinker --execute="
\$manager = App\Models\User::where('role', 'club')->first();
if (\$manager) {
    \$token = \$manager->createToken('test')->plainTextToken;
    echo \$token;
} else {
    echo 'ERROR_NO_MANAGER';
}
")

if [ "$TOKEN" = "ERROR_NO_MANAGER" ]; then
    echo "❌ Aucun gestionnaire de club trouvé"
    exit 1
fi

echo "✅ Token créé: ${TOKEN:0:20}..."
echo ""

# Test de l'API
echo "📡 Test de l'API..."
RESPONSE=$(curl -s -w "\n%{http_code}" -X GET http://localhost:8080/api/club/dashboard \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")

# Séparer la réponse et le code HTTP
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | head -n -1)

echo "Code HTTP: $HTTP_CODE"
echo ""

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ API fonctionne !"
    echo "Réponse JSON:"
    echo "$BODY" | jq . 2>/dev/null || echo "$BODY"
else
    echo "❌ Erreur API (code: $HTTP_CODE)"
    echo "Réponse:"
    echo "$BODY"
fi

echo ""
echo "🏁 Test terminé"
