#!/bin/bash

echo "🔧 Test de débogage complet de l'authentification des clubs"
echo "======================================================="

# Configuration
API_BASE="http://localhost:8080/api"
EMAIL="manager@centre-equestre-des-etoiles.fr"
PASSWORD="password"

echo ""
echo "📋 Informations de test :"
echo "   API: $API_BASE"
echo "   Email: $EMAIL"
echo "   Password: $PASSWORD"

echo ""
echo "1. 🔑 Test de login..."
RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}" \
  -w "HTTPSTATUS:%{http_code}")

HTTP_STATUS=$(echo $RESPONSE | grep -o "HTTPSTATUS:[0-9]*" | cut -d: -f2)
BODY=$(echo $RESPONSE | sed -E 's/HTTPSTATUS:[0-9]*$//')

echo "   Status: $HTTP_STATUS"

if [ "$HTTP_STATUS" -eq 200 ]; then
    echo "   ✅ Login réussi"
    
    # Extraire les informations
    TOKEN=$(echo "$BODY" | jq -r '.access_token // empty')
    USER_EMAIL=$(echo "$BODY" | jq -r '.user.email // empty')
    USER_ROLE=$(echo "$BODY" | jq -r '.user.role // empty')
    
    echo "   📧 Email: $USER_EMAIL"
    echo "   👤 Rôle: $USER_ROLE"
    echo "   🎫 Token: ${TOKEN:0:20}..."
    
    if [ -n "$TOKEN" ] && [ "$TOKEN" != "null" ]; then
        echo ""
        echo "2. 🔍 Test de vérification utilisateur..."
        
        USER_RESPONSE=$(curl -s -X GET "$API_BASE/auth/user" \
          -H "Accept: application/json" \
          -H "Authorization: Bearer $TOKEN" \
          -w "HTTPSTATUS:%{http_code}")
        
        USER_HTTP_STATUS=$(echo $USER_RESPONSE | grep -o "HTTPSTATUS:[0-9]*" | cut -d: -f2)
        echo "   Status: $USER_HTTP_STATUS"
        
        if [ "$USER_HTTP_STATUS" -eq 200 ]; then
            echo "   ✅ Vérification utilisateur réussie"
        else
            echo "   ❌ Échec vérification utilisateur"
            echo "   📄 $(echo $USER_RESPONSE | sed -E 's/HTTPSTATUS:[0-9]*$//')"
        fi
        
        echo ""
        echo "3. 🏠 Test d'accès dashboard club..."
        
        DASHBOARD_RESPONSE=$(curl -s -X GET "$API_BASE/club/dashboard" \
          -H "Accept: application/json" \
          -H "Authorization: Bearer $TOKEN" \
          -w "HTTPSTATUS:%{http_code}")
        
        DASHBOARD_HTTP_STATUS=$(echo $DASHBOARD_RESPONSE | grep -o "HTTPSTATUS:[0-9]*" | cut -d: -f2)
        echo "   Status: $DASHBOARD_HTTP_STATUS"
        
        if [ "$DASHBOARD_HTTP_STATUS" -eq 200 ]; then
            echo "   ✅ Accès dashboard autorisé"
        else
            echo "   ❌ Échec accès dashboard"
            echo "   📄 $(echo $DASHBOARD_RESPONSE | sed -E 's/HTTPSTATUS:[0-9]*$//')"
        fi
        
    else
        echo "   ❌ Token invalide ou manquant"
    fi
    
else
    echo "   ❌ Échec du login"
    echo "   📄 $BODY"
fi

echo ""
echo "======================================================="
echo "🏁 Test terminé"
echo ""
echo "📌 Instructions pour tester le frontend :"
echo "1. Ouvrez http://localhost:3000 dans votre navigateur"
echo "2. Connectez-vous avec :"
echo "   Email: $EMAIL"
echo "   Password: $PASSWORD"
echo "3. Ouvrez les outils de développement (F12)"
echo "4. Regardez la console pour les messages de debug"
echo "5. Vérifiez que vous arrivez sur /club/dashboard sans erreur"
