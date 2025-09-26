#!/bin/bash

echo "🔧 Test du correctif d'authentification pour les clubs"
echo "=================================================="

# Configuration
API_BASE="http://localhost:8080/api"
EMAIL="manager@centre-equestre-des-etoiles.fr"
PASSWORD="password"

echo ""
echo "1. 🔑 Test de connexion avec profil club..."
echo "   Email: $EMAIL"

RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}" \
  -w "HTTPSTATUS:%{http_code}")

HTTP_STATUS=$(echo $RESPONSE | grep -o "HTTPSTATUS:[0-9]*" | cut -d: -f2)
BODY=$(echo $RESPONSE | sed -E 's/HTTPSTATUS:[0-9]*$//')

echo "   Status HTTP: $HTTP_STATUS"

if [ "$HTTP_STATUS" -eq 200 ]; then
    echo "   ✅ Connexion réussie"
    
    # Extraire le token de la réponse
    TOKEN=$(echo "$BODY" | grep -o '"access_token":"[^"]*"' | sed 's/"access_token":"\([^"]*\)"/\1/')
    USER_ROLE=$(echo "$BODY" | grep -o '"role":"[^"]*"' | sed 's/"role":"\([^"]*\)"/\1/')
    
    echo "   🎫 Token reçu: ${TOKEN:0:20}..."
    echo "   👤 Rôle: $USER_ROLE"
    
    if [ -n "$TOKEN" ]; then
        echo ""
        echo "2. 🏠 Test d'accès au dashboard club..."
        
        DASHBOARD_RESPONSE=$(curl -s -X GET "$API_BASE/club/dashboard" \
          -H "Accept: application/json" \
          -H "Authorization: Bearer $TOKEN" \
          -w "HTTPSTATUS:%{http_code}")
        
        DASHBOARD_HTTP_STATUS=$(echo $DASHBOARD_RESPONSE | grep -o "HTTPSTATUS:[0-9]*" | cut -d: -f2)
        DASHBOARD_BODY=$(echo $DASHBOARD_RESPONSE | sed -E 's/HTTPSTATUS:[0-9]*$//')
        
        echo "   Status HTTP: $DASHBOARD_HTTP_STATUS"
        
        if [ "$DASHBOARD_HTTP_STATUS" -eq 200 ]; then
            echo "   ✅ Accès au dashboard autorisé"
            echo "   📊 Données reçues: $(echo "$DASHBOARD_BODY" | head -c 100)..."
        else
            echo "   ❌ Échec d'accès au dashboard"
            echo "   📄 Réponse: $DASHBOARD_BODY"
        fi
        
        echo ""
        echo "3. 🔍 Test de vérification utilisateur..."
        
        USER_RESPONSE=$(curl -s -X GET "$API_BASE/auth/user" \
          -H "Accept: application/json" \
          -H "Authorization: Bearer $TOKEN" \
          -w "HTTPSTATUS:%{http_code}")
        
        USER_HTTP_STATUS=$(echo $USER_RESPONSE | grep -o "HTTPSTATUS:[0-9]*" | cut -d: -f2)
        USER_BODY=$(echo $USER_RESPONSE | sed -E 's/HTTPSTATUS:[0-9]*$//')
        
        echo "   Status HTTP: $USER_HTTP_STATUS"
        
        if [ "$USER_HTTP_STATUS" -eq 200 ]; then
            echo "   ✅ Vérification utilisateur réussie"
            USER_EMAIL=$(echo "$USER_BODY" | grep -o '"email":"[^"]*"' | sed 's/"email":"\([^"]*\)"/\1/')
            USER_ROLE_CHECK=$(echo "$USER_BODY" | grep -o '"role":"[^"]*"' | sed 's/"role":"\([^"]*\)"/\1/')
            echo "   📧 Email: $USER_EMAIL"
            echo "   👤 Rôle: $USER_ROLE_CHECK"
        else
            echo "   ❌ Échec de vérification utilisateur"
            echo "   📄 Réponse: $USER_BODY"
        fi
        
    else
        echo "   ❌ Aucun token reçu dans la réponse"
        echo "   📄 Réponse complète: $BODY"
    fi
    
else
    echo "   ❌ Échec de connexion"
    echo "   📄 Réponse: $BODY"
fi

echo ""
echo "=================================="
echo "🏁 Test terminé"
