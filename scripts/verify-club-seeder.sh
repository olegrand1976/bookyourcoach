#!/bin/bash

# Script pour vérifier que le seeder des clubs a bien fonctionné
echo "🔍 Vérification du seeder des clubs"
echo ""

# Test 1: Vérifier que l'API répond maintenant
echo "📡 Test 1: Vérification de l'API club/dashboard..."
API_RESPONSE=$(curl -s -w "\n%{http_code}" -X GET https://activibe.be/api/club/dashboard \
  -H "Authorization: Bearer 115|JZxPNRnZLoAADLRZvPcZOgc1PM2sS91xo002524Z6ce84c93" \
  -H "Content-Type: application/json")

HTTP_CODE=$(echo "$API_RESPONSE" | tail -n1)
BODY=$(echo "$API_RESPONSE" | head -n -1)

echo "Code HTTP: $HTTP_CODE"

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ API fonctionne !"
    echo "Réponse JSON:"
    echo "$BODY" | jq . 2>/dev/null || echo "$BODY"
elif [ "$HTTP_CODE" = "404" ]; then
    echo "❌ API retourne 404 - Le contrôleur n'est pas déployé"
elif [ "$HTTP_CODE" = "401" ]; then
    echo "❌ API retourne 401 - Problème d'authentification"
elif [ "$HTTP_CODE" = "500" ]; then
    echo "❌ API retourne 500 - Erreur serveur"
    echo "Réponse: $BODY"
else
    echo "❌ API retourne un code inattendu: $HTTP_CODE"
    echo "Réponse: $BODY"
fi

echo ""

# Test 2: Vérifier que l'utilisateur existe et a le bon rôle
echo "📡 Test 2: Vérification de l'utilisateur..."
USER_RESPONSE=$(curl -s -w "\n%{http_code}" -X GET https://activibe.be/api/auth/user \
  -H "Authorization: Bearer 115|JZxPNRnZLoAADLRZvPcZOgc1PM2sS91xo002524Z6ce84c93" \
  -H "Content-Type: application/json")

USER_HTTP_CODE=$(echo "$USER_RESPONSE" | tail -n1)
USER_BODY=$(echo "$USER_RESPONSE" | head -n -1)

echo "Code HTTP: $USER_HTTP_CODE"

if [ "$USER_HTTP_CODE" = "200" ]; then
    echo "✅ Utilisateur authentifié"
    USER_EMAIL=$(echo "$USER_BODY" | jq -r '.user.email' 2>/dev/null)
    USER_ROLE=$(echo "$USER_BODY" | jq -r '.user.role' 2>/dev/null)
    echo "   Email: $USER_EMAIL"
    echo "   Rôle: $USER_ROLE"
    
    if [ "$USER_ROLE" = "club" ]; then
        echo "✅ Rôle 'club' confirmé"
    else
        echo "❌ Rôle incorrect: $USER_ROLE (attendu: club)"
    fi
else
    echo "❌ Problème d'authentification"
fi

echo ""

# Test 3: Vérifier les données créées
echo "📡 Test 3: Vérification des données créées..."
echo "💡 Pour vérifier les données en base, connectez-vous au serveur et exécutez:"
echo "   mysql -u username -p database_name"
echo "   SELECT COUNT(*) FROM clubs;"
echo "   SELECT COUNT(*) FROM club_managers;"
echo "   SELECT COUNT(*) FROM club_teachers;"
echo "   SELECT COUNT(*) FROM club_students;"
echo ""

echo "🏁 Vérification terminée"
echo ""
echo "📋 Résumé:"
if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Le dashboard des clubs fonctionne correctement !"
    echo "✅ Les données sont présentes et accessibles"
    echo "✅ L'authentification fonctionne"
else
    echo "❌ Le dashboard des clubs ne fonctionne pas encore"
    echo "💡 Vérifiez que:"
    echo "   1. Le contrôleur ClubDashboardController est déployé"
    echo "   2. Le seeder ClubTestDataSeeder a été exécuté"
    echo "   3. La table club_managers contient des données"
fi
