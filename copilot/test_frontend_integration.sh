#!/bin/bash

echo "🧪 Test intégration Frontend/Backend - Paramètres système"
echo "======================================================="

TOKEN="53|qnZnhJm9pamYufX5tBQOK7eFnkWIIaD9DDG92Vaw7182a620"
BACKEND_URL="http://localhost:8081/api/admin/settings/general"
FRONTEND_URL="http://localhost:3000/admin/settings"

echo ""
echo "1. 🔍 Vérification Backend directe:"
echo "GET $BACKEND_URL"
curl -s "$BACKEND_URL" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq .

echo ""
echo "2. 🧪 Test sauvegarde Backend:"
echo "PUT $BACKEND_URL"
curl -s "$BACKEND_URL" \
  -X PUT \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "platform_name": "TestFrontendIntegration",
    "contact_email": "integration@test.com",
    "contact_phone": "+33 1 11 11 11 11",
    "timezone": "Europe/Brussels",
    "company_address": "Test Integration Address",
    "logo_url": "/logo.svg"
  }' | jq .

echo ""
echo "3. ✅ Vérification changement:"
curl -s "$BACKEND_URL" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq .platform_name

echo ""
echo "4. 🌐 Frontend accessible sur: $FRONTEND_URL"
echo ""
echo "🎯 Prochaine étape: Tester la sauvegarde depuis l'interface web"
echo "   - Aller sur $FRONTEND_URL"  
echo "   - Modifier le nom de la plateforme"
echo "   - Cliquer sur 'Sauvegarder'"
echo "   - Vérifier que le header se met à jour"
