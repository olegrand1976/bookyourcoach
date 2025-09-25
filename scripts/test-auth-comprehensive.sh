#!/bin/bash

# Script de test approfondi - Certification complète de l'authentification backend
# Usage: ./scripts/test-auth-comprehensive.sh

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${PURPLE}🔐 Test Approfondi - Certification Authentification Backend${NC}"
echo -e "${PURPLE}=======================================================${NC}"

# Configuration
FRONTEND_URL="http://localhost:3000"
BACKEND_URL="http://localhost:8080"
EMAIL="manager@centre-equestre-des-etoiles.fr"
PASSWORD="password"
INVALID_EMAIL="invalid@test.com"
INVALID_PASSWORD="wrongpassword"

# Compteurs de tests
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Fonction pour exécuter un test
run_test() {
    local test_name="$1"
    local test_command="$2"
    local expected_status="$3"
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    echo -e "${BLUE}🧪 Test $TOTAL_TESTS: $test_name${NC}"
    
    # Exécuter la commande et capturer le statut HTTP
    HTTP_STATUS=$(eval "$test_command" 2>/dev/null | head -1)
    
    if [ "$HTTP_STATUS" = "$expected_status" ]; then
        echo -e "${GREEN}✅ PASSÉ - Code: $HTTP_STATUS${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${RED}❌ ÉCHOUÉ - Code: $HTTP_STATUS (Attendu: $expected_status)${NC}"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
    echo ""
}

echo -e "${CYAN}🎯 Objectif: Certifier que l'authentification backend est 100% fonctionnelle${NC}"
echo ""

# ==========================================
# 1. TESTS DE DISPONIBILITÉ DES SERVICES
# ==========================================
echo -e "${YELLOW}📡 SECTION 1: Disponibilité des Services${NC}"
echo -e "${YELLOW}=====================================${NC}"

run_test "Frontend accessible" "curl -s -o /dev/null -w '%{http_code}' $FRONTEND_URL" "200"
run_test "Backend accessible" "curl -s -o /dev/null -w '%{http_code}' $BACKEND_URL/api/status" "200"

# ==========================================
# 2. TESTS D'AUTHENTIFICATION DE BASE
# ==========================================
echo -e "${YELLOW}🔑 SECTION 2: Authentification de Base${NC}"
echo -e "${YELLOW}=====================================${NC}"

# Test de connexion valide
echo -e "${BLUE}🧪 Test de connexion valide...${NC}"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
LOGIN_RESPONSE=$(curl -s -X POST "$BACKEND_URL/api/auth/login" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\",\"remember\":true}")

if echo "$LOGIN_RESPONSE" | grep -q "Connexion r"; then
    echo -e "${GREEN}✅ Connexion valide réussie${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
    
    # Extraire le token
    TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    echo -e "${YELLOW}🔑 Token obtenu: ${TOKEN:0:20}...${NC}"
else
    echo -e "${RED}❌ Connexion valide échouée${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
    echo "Réponse: $LOGIN_RESPONSE"
fi
echo ""

# Test de connexion invalide
run_test "Connexion avec email invalide" "curl -s -X POST '$BACKEND_URL/api/auth/login' -H 'Content-Type: application/json' -d '{\"email\":\"$INVALID_EMAIL\",\"password\":\"$PASSWORD\"}' | grep -o '\"message\":\"[^\"]*\"' | head -1" "message"

# Test de connexion avec mot de passe invalide
run_test "Connexion avec mot de passe invalide" "curl -s -X POST '$BACKEND_URL/api/auth/login' -H 'Content-Type: application/json' -d '{\"email\":\"$EMAIL\",\"password\":\"$INVALID_PASSWORD\"}' | grep -o '\"message\":\"[^\"]*\"' | head -1" "message"

# ==========================================
# 3. TESTS D'API PROTÉGÉES AVEC TOKEN
# ==========================================
echo -e "${YELLOW}🛡️ SECTION 3: API Protégées avec Token${NC}"
echo -e "${YELLOW}=====================================${NC}"

if [ -n "$TOKEN" ]; then
    # Test API dashboard avec token valide
    run_test "API dashboard avec token valide" "curl -s -o /dev/null -w '%{http_code}' -H 'Authorization: Bearer $TOKEN' '$BACKEND_URL/api/club/dashboard'" "200"
    
    # Test API user avec token valide
    run_test "API user avec token valide" "curl -s -o /dev/null -w '%{http_code}' -H 'Authorization: Bearer $TOKEN' '$BACKEND_URL/api/auth/user'" "200"
    
    # Test API profile avec token valide
    run_test "API profile avec token valide" "curl -s -o /dev/null -w '%{http_code}' -H 'Authorization: Bearer $TOKEN' '$BACKEND_URL/api/club/profile'" "200"
else
    echo -e "${RED}❌ Impossible de tester les API protégées - Token manquant${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 3))
    TOTAL_TESTS=$((TOTAL_TESTS + 3))
fi

# ==========================================
# 4. TESTS D'API SANS TOKEN (DOIVENT ÉCHOUER)
# ==========================================
echo -e "${YELLOW}🚫 SECTION 4: API sans Token (Doivent échouer)${NC}"
echo -e "${YELLOW}=============================================${NC}"

run_test "API dashboard sans token" "curl -s -o /dev/null -w '%{http_code}' '$BACKEND_URL/api/club/dashboard'" "401"
run_test "API user sans token" "curl -s -o /dev/null -w '%{http_code}' '$BACKEND_URL/api/auth/user'" "401"
run_test "API profile sans token" "curl -s -o /dev/null -w '%{http_code}' '$BACKEND_URL/api/club/profile'" "401"

# ==========================================
# 5. TESTS D'API AVEC TOKEN INVALIDE
# ==========================================
echo -e "${YELLOW}🔒 SECTION 5: API avec Token Invalide${NC}"
echo -e "${YELLOW}=====================================${NC}"

INVALID_TOKEN="invalid_token_12345"
run_test "API dashboard avec token invalide" "curl -s -o /dev/null -w '%{http_code}' -H 'Authorization: Bearer $INVALID_TOKEN' '$BACKEND_URL/api/club/dashboard'" "401"
run_test "API user avec token invalide" "curl -s -o /dev/null -w '%{http_code}' -H 'Authorization: Bearer $INVALID_TOKEN' '$BACKEND_URL/api/auth/user'" "401"
run_test "API profile avec token invalide" "curl -s -o /dev/null -w '%{http_code}' -H 'Authorization: Bearer $INVALID_TOKEN' '$BACKEND_URL/api/club/profile'" "401"

# ==========================================
# 6. TESTS DE VALIDATION DES DONNÉES
# ==========================================
echo -e "${YELLOW}📊 SECTION 6: Validation des Données${NC}"
echo -e "${YELLOW}=====================================${NC}"

if [ -n "$TOKEN" ]; then
    # Test des données du dashboard
    echo -e "${BLUE}🧪 Test des données du dashboard...${NC}"
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    DASHBOARD_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" "$BACKEND_URL/api/club/dashboard")
    
    if echo "$DASHBOARD_RESPONSE" | grep -q '"success":true' && echo "$DASHBOARD_RESPONSE" | grep -q '"data"'; then
        echo -e "${GREEN}✅ Données du dashboard valides${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
        
        # Extraire le nom du club
        CLUB_NAME=$(echo "$DASHBOARD_RESPONSE" | grep -o '"name":"[^"]*"' | cut -d'"' -f4)
        echo -e "${YELLOW}🏢 Club: $CLUB_NAME${NC}"
    else
        echo -e "${RED}❌ Données du dashboard invalides${NC}"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
    echo ""
    
    # Test des données utilisateur
    echo -e "${BLUE}🧪 Test des données utilisateur...${NC}"
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    USER_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" "$BACKEND_URL/api/auth/user")
    
    if echo "$USER_RESPONSE" | grep -q '"user"' && echo "$USER_RESPONSE" | grep -q '"email"'; then
        echo -e "${GREEN}✅ Données utilisateur valides${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
        
        # Extraire l'email
        USER_EMAIL=$(echo "$USER_RESPONSE" | grep -o '"email":"[^"]*"' | cut -d'"' -f4)
        echo -e "${YELLOW}👤 Utilisateur: $USER_EMAIL${NC}"
    else
        echo -e "${RED}❌ Données utilisateur invalides${NC}"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
    echo ""
else
    echo -e "${RED}❌ Impossible de tester la validation des données - Token manquant${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 2))
    TOTAL_TESTS=$((TOTAL_TESTS + 2))
fi

# ==========================================
# 7. TESTS DE PERFORMANCE ET STABILITÉ
# ==========================================
echo -e "${YELLOW}⚡ SECTION 7: Performance et Stabilité${NC}"
echo -e "${YELLOW}=====================================${NC}"

if [ -n "$TOKEN" ]; then
    # Test de charge simple (5 requêtes rapides)
    echo -e "${BLUE}🧪 Test de charge simple (5 requêtes)...${NC}"
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    SUCCESS_COUNT=0
    for i in {1..5}; do
        HTTP_STATUS=$(curl -s -o /dev/null -w '%{http_code}' -H "Authorization: Bearer $TOKEN" "$BACKEND_URL/api/club/dashboard")
        if [ "$HTTP_STATUS" = "200" ]; then
            SUCCESS_COUNT=$((SUCCESS_COUNT + 1))
        fi
    done
    
    if [ "$SUCCESS_COUNT" = "5" ]; then
        echo -e "${GREEN}✅ Test de charge réussi (5/5 requêtes)${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${RED}❌ Test de charge échoué ($SUCCESS_COUNT/5 requêtes)${NC}"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
    echo ""
else
    echo -e "${RED}❌ Impossible de tester la performance - Token manquant${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
fi

# ==========================================
# 8. TESTS DE SÉCURITÉ
# ==========================================
echo -e "${YELLOW}🔐 SECTION 8: Tests de Sécurité${NC}"
echo -e "${YELLOW}===============================${NC}"

# Test avec token malformé
run_test "API avec token malformé" "curl -s -o /dev/null -w '%{http_code}' -H 'Authorization: Bearer' '$BACKEND_URL/api/club/dashboard'" "401"

# Test avec header Authorization manquant
run_test "API sans header Authorization" "curl -s -o /dev/null -w '%{http_code}' '$BACKEND_URL/api/club/dashboard'" "401"

# Test avec header Authorization vide
run_test "API avec header Authorization vide" "curl -s -o /dev/null -w '%{http_code}' -H 'Authorization:' '$BACKEND_URL/api/club/dashboard'" "401"

# ==========================================
# RÉSULTATS FINAUX
# ==========================================
echo -e "${PURPLE}📊 RÉSULTATS FINAUX${NC}"
echo -e "${PURPLE}==================${NC}"

echo -e "${CYAN}Total des tests: $TOTAL_TESTS${NC}"
echo -e "${GREEN}Tests réussis: $PASSED_TESTS${NC}"
echo -e "${RED}Tests échoués: $FAILED_TESTS${NC}"

# Calcul du pourcentage de réussite
if [ $TOTAL_TESTS -gt 0 ]; then
    SUCCESS_RATE=$((PASSED_TESTS * 100 / TOTAL_TESTS))
    echo -e "${YELLOW}Taux de réussite: $SUCCESS_RATE%${NC}"
    
    if [ $SUCCESS_RATE -eq 100 ]; then
        echo ""
        echo -e "${GREEN}🎉 CERTIFICATION COMPLÈTE !${NC}"
        echo -e "${GREEN}✅ L'authentification backend est 100% fonctionnelle${NC}"
        echo -e "${GREEN}✅ Tous les tests de sécurité sont passés${NC}"
        echo -e "${GREEN}✅ La performance est stable${NC}"
        echo -e "${GREEN}✅ Les données sont correctement validées${NC}"
    elif [ $SUCCESS_RATE -ge 90 ]; then
        echo ""
        echo -e "${YELLOW}⚠️ CERTIFICATION PARTIELLE${NC}"
        echo -e "${YELLOW}La plupart des tests sont passés, mais quelques problèmes subsistent${NC}"
    else
        echo ""
        echo -e "${RED}❌ CERTIFICATION ÉCHOUÉE${NC}"
        echo -e "${RED}L'authentification backend nécessite des corrections${NC}"
    fi
else
    echo -e "${RED}❌ Aucun test exécuté${NC}"
fi

echo ""
echo -e "${BLUE}🌐 URLs d'accès:${NC}"
echo -e "  Frontend:    $FRONTEND_URL"
echo -e "  Backend:     $BACKEND_URL"
echo -e "  Dashboard:   $FRONTEND_URL/club/dashboard"
echo -e "  Login:       $FRONTEND_URL/login"

# Code de sortie basé sur le succès
if [ $SUCCESS_RATE -eq 100 ]; then
    exit 0
else
    exit 1
fi
