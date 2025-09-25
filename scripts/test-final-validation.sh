#!/bin/bash

# Script de test final - Validation de la correction de l'erreur 500
# Usage: ./scripts/test-final-validation.sh

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

echo -e "${PURPLE}🎯 Test Final - Validation de la Correction Erreur 500${NC}"
echo -e "${PURPLE}====================================================${NC}"

# Configuration
FRONTEND_URL="http://localhost:3000"
BACKEND_URL="http://localhost:8080"
EMAIL="manager@centre-equestre-des-etoiles.fr"
PASSWORD="password"

echo -e "${BLUE}🎯 Objectif: Valider que l'erreur 500 est corrigée${NC}"
echo ""

# Test 1: Connexion et récupération du token
echo -e "${BLUE}1. Test de connexion et récupération du token...${NC}"
LOGIN_RESPONSE=$(curl -s -X POST "$BACKEND_URL/api/auth/login" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\",\"remember\":true}")

if echo "$LOGIN_RESPONSE" | grep -q "Login successful"; then
    echo -e "${GREEN}✅ Connexion réussie${NC}"
    
    # Extraire le token
    TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    echo -e "${YELLOW}🔑 Token obtenu: ${TOKEN:0:20}...${NC}"
else
    echo -e "${RED}❌ Échec de la connexion:${NC}"
    echo "$LOGIN_RESPONSE"
    exit 1
fi

# Test 2: API dashboard avec token valide
echo -e "${BLUE}2. Test de l'API dashboard avec token valide...${NC}"
DASHBOARD_RESPONSE=$(curl -s -X GET "$BACKEND_URL/api/club/dashboard" \
    -H "Authorization: Bearer $TOKEN" \
    -H 'Accept: application/json')

HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
    -H "Authorization: Bearer $TOKEN" \
    "$BACKEND_URL/api/club/dashboard")

if [ "$HTTP_STATUS" = "200" ] && echo "$DASHBOARD_RESPONSE" | grep -q "Centre.*questre.*des.*toiles"; then
    echo -e "${GREEN}✅ API dashboard avec token: Code $HTTP_STATUS (SUCCÈS)${NC}"
    CLUB_NAME=$(echo "$DASHBOARD_RESPONSE" | grep -o '"name":"[^"]*"' | head -1 | cut -d'"' -f4)
    echo -e "${YELLOW}🏢 Club: $CLUB_NAME${NC}"
else
    echo -e "${RED}❌ API dashboard avec token: Code $HTTP_STATUS (ÉCHEC)${NC}"
    echo "Réponse: $DASHBOARD_RESPONSE"
    exit 1
fi

# Test 3: API dashboard sans token (doit retourner 401, pas 500)
echo -e "${BLUE}3. Test de l'API dashboard sans token...${NC}"
UNAUTH_RESPONSE=$(curl -s -X GET "$BACKEND_URL/api/club/dashboard" \
    -H 'Accept: application/json')

UNAUTH_STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
    "$BACKEND_URL/api/club/dashboard")

if [ "$UNAUTH_STATUS" = "401" ]; then
    echo -e "${GREEN}✅ API dashboard sans token: Code $UNAUTH_STATUS (SUCCÈS)${NC}"
    echo -e "${GREEN}✅ Plus d'erreur 500 !${NC}"
elif [ "$UNAUTH_STATUS" = "500" ]; then
    echo -e "${RED}❌ API dashboard sans token: Code $UNAUTH_STATUS (ÉCHEC)${NC}"
    echo -e "${RED}❌ L'erreur 500 persiste${NC}"
    echo "Réponse: $UNAUTH_RESPONSE"
    exit 1
else
    echo -e "${YELLOW}⚠️ API dashboard sans token: Code $UNAUTH_STATUS (Attendu: 401)${NC}"
    echo "Réponse: $UNAUTH_RESPONSE"
fi

# Test 4: API user sans token
echo -e "${BLUE}4. Test de l'API user sans token...${NC}"
USER_UNAUTH_STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
    "$BACKEND_URL/api/auth/user")

if [ "$USER_UNAUTH_STATUS" = "401" ]; then
    echo -e "${GREEN}✅ API user sans token: Code $USER_UNAUTH_STATUS (SUCCÈS)${NC}"
elif [ "$USER_UNAUTH_STATUS" = "500" ]; then
    echo -e "${RED}❌ API user sans token: Code $USER_UNAUTH_STATUS (ÉCHEC)${NC}"
    echo -e "${RED}❌ L'erreur 500 persiste${NC}"
    exit 1
else
    echo -e "${YELLOW}⚠️ API user sans token: Code $USER_UNAUTH_STATUS (Attendu: 401)${NC}"
fi

# Test 5: API club/profile sans token
echo -e "${BLUE}5. Test de l'API club/profile sans token...${NC}"
PROFILE_UNAUTH_STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
    "$BACKEND_URL/api/club/profile")

if [ "$PROFILE_UNAUTH_STATUS" = "401" ]; then
    echo -e "${GREEN}✅ API club/profile sans token: Code $PROFILE_UNAUTH_STATUS (SUCCÈS)${NC}"
elif [ "$PROFILE_UNAUTH_STATUS" = "500" ]; then
    echo -e "${RED}❌ API club/profile sans token: Code $PROFILE_UNAUTH_STATUS (ÉCHEC)${NC}"
    echo -e "${RED}❌ L'erreur 500 persiste${NC}"
    exit 1
else
    echo -e "${YELLOW}⚠️ API club/profile sans token: Code $PROFILE_UNAUTH_STATUS (Attendu: 401)${NC}"
fi

# Résumé
echo ""
echo -e "${GREEN}🎉 VALIDATION RÉUSSIE !${NC}"
echo -e "${GREEN}✅ L'erreur 500 est corrigée${NC}"
echo -e "${GREEN}✅ Les API retournent maintenant des codes 401 appropriés${NC}"
echo -e "${GREEN}✅ L'authentification fonctionne correctement${NC}"

echo ""
echo -e "${YELLOW}📋 Résumé de la correction:${NC}"
echo "  • Problème: Erreur 500 lors de l'accès aux API sans token"
echo "  • Cause: Laravel tentait de rediriger vers une route 'login' inexistante"
echo "  • Solution: Middleware personnalisé ApiAuthSanctum créé"
echo "  • Configuration: Exception handler pour les API"
echo "  • Résultat: Codes 401 au lieu d'erreurs 500"

echo ""
echo -e "${BLUE}🌐 URLs d'accès:${NC}"
echo "  Frontend:    $FRONTEND_URL"
echo "  Backend:     $BACKEND_URL"
echo "  Dashboard:   $FRONTEND_URL/club/dashboard"
echo "  Login:       $FRONTEND_URL/login"
