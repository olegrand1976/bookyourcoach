#!/bin/bash

echo "🚀 TEST DU PROCESSUS D'AUTHENTIFICATION SIMPLIFIÉ"
echo "================================================"

# Configuration
API_BASE="http://localhost:8080/api"
EMAIL="manager@centre-equestre-des-etoiles.fr"
PASSWORD="password"

echo ""
echo "🔍 VÉRIFICATION : Le processus simplifié fonctionne-t-il ?"
echo ""
echo "1. 📡 API Backend (Direct)"
echo "   ✅ Login: Génère un token"
echo "   ✅ Dashboard: Accessible avec token"
echo ""

echo "2. 🎯 Test de cohérence Frontend → API"
echo ""

# Tester que l'API backend fonctionne toujours
echo "📋 Test 1: Login direct API..."
LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}" \
  -w "HTTPSTATUS:%{http_code}")

LOGIN_STATUS=$(echo $LOGIN_RESPONSE | grep -o "HTTPSTATUS:[0-9]*" | cut -d: -f2)
LOGIN_BODY=$(echo $LOGIN_RESPONSE | sed -E 's/HTTPSTATUS:[0-9]*$//')

if [ "$LOGIN_STATUS" -eq 200 ]; then
    echo "   ✅ Login API: OK"
    TOKEN=$(echo "$LOGIN_BODY" | grep -o '"access_token":"[^"]*"' | sed 's/"access_token":"\([^"]*\)"/\1/')
    echo "   🎫 Token: ${TOKEN:0:20}..."
    
    echo ""
    echo "📋 Test 2: Dashboard avec token..."
    DASHBOARD_RESPONSE=$(curl -s -X GET "$API_BASE/club/dashboard" \
      -H "Accept: application/json" \
      -H "Authorization: Bearer $TOKEN" \
      -w "HTTPSTATUS:%{http_code}")
    
    DASHBOARD_STATUS=$(echo $DASHBOARD_RESPONSE | grep -o "HTTPSTATUS:[0-9]*" | cut -d: -f2)
    
    if [ "$DASHBOARD_STATUS" -eq 200 ]; then
        echo "   ✅ Dashboard API: OK"
    else
        echo "   ❌ Dashboard API: ERREUR ($DASHBOARD_STATUS)"
    fi
else
    echo "   ❌ Login API: ERREUR ($LOGIN_STATUS)"
fi

echo ""
echo "🎯 TESTS FRONTEND MANUELS :"
echo ""
echo "1. Ouvrez http://localhost:3000"
echo "2. Connectez-vous avec :"
echo "   📧 Email: $EMAIL"
echo "   🔑 Password: $PASSWORD"
echo ""
echo "3. 🔍 Logs à vérifier dans F12 :"
echo "   ✅ 🔑 [LOGIN] Réponse API: {...}"
echo "   ✅ 🔑 [LOGIN] Token stocké dans le store: [token]..."
echo "   ✅ 🔗 [API] Token ajouté: [token]... du store"
echo "   ✅ 🔍 [FETCH USER] Réponse: {...}"
echo "   ✅ Accès au dashboard club SANS message de session expirée"
echo ""
echo "4. ❌ Messages qui NE doivent PLUS apparaître :"
echo "   ❌ Token cookie: absent"
echo "   ❌ Token localStorage: absent"
echo "   ❌ Session expirée"
echo ""

echo "=============================================="
echo "🎯 OBJECTIF : PLUS AUCUN MESSAGE 'SESSION EXPIRÉE'"
echo "=============================================="
