#!/bin/bash

# Script de test de l'API de filtrage des types de cours
# Usage: ./scripts/test-api-filtering.sh [TOKEN]

set -e

API_BASE="http://localhost:8080/api"
TOKEN="${1:-}"

# Couleurs pour l'affichage
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}    Test de filtrage des types de cours par club${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"

# Vérifier si un token est fourni
if [ -z "$TOKEN" ]; then
    echo -e "${YELLOW}⚠️  Aucun token fourni${NC}"
    echo -e "${YELLOW}   Tentative de connexion automatique...${NC}\n"
    
    # Essayer de se connecter avec des credentials de test
    echo -e "${BLUE}→ Tentative de login...${NC}"
    LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
        -H "Content-Type: application/json" \
        -d '{
            "email": "club@test.com",
            "password": "password"
        }')
    
    # Extraire le token
    TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)
    
    if [ -z "$TOKEN" ]; then
        echo -e "${RED}❌ Échec de connexion${NC}"
        echo -e "${RED}   Veuillez fournir un token: ./scripts/test-api-filtering.sh YOUR_TOKEN${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✅ Connecté avec succès${NC}\n"
fi

echo -e "${BLUE}Token:${NC} ${TOKEN:0:20}...\n"

# Test 1: Endpoint de debug complet
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}TEST 1: Endpoint de debug complet${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"

DEBUG_RESPONSE=$(curl -s -X GET "$API_BASE/debug/course-types-filtering" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")

echo "$DEBUG_RESPONSE" | jq '.'

# Extraire les statistiques
if command -v jq &> /dev/null; then
    echo -e "\n${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}RÉSUMÉ${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
    
    USER_ROLE=$(echo "$DEBUG_RESPONSE" | jq -r '.data.user.role')
    DISCIPLINES_COUNT=$(echo "$DEBUG_RESPONSE" | jq -r '.data.club_disciplines | length')
    ALL_TYPES=$(echo "$DEBUG_RESPONSE" | jq -r '.data.all_course_types | length')
    FILTERED_TYPES=$(echo "$DEBUG_RESPONSE" | jq -r '.data.filtered_course_types | length')
    FILTERING_WORKING=$(echo "$DEBUG_RESPONSE" | jq -r '.summary.filtering_working')
    
    echo -e "Rôle utilisateur:        ${YELLOW}$USER_ROLE${NC}"
    echo -e "Disciplines du club:     ${YELLOW}$DISCIPLINES_COUNT${NC}"
    echo -e "Types de cours totaux:   ${YELLOW}$ALL_TYPES${NC}"
    echo -e "Types après filtrage:    ${YELLOW}$FILTERED_TYPES${NC}"
    
    if [ "$FILTERING_WORKING" = "true" ]; then
        echo -e "Filtrage actif:          ${GREEN}✅ OUI${NC}"
        FILTERED_OUT=$((ALL_TYPES - FILTERED_TYPES))
        echo -e "Types filtrés:           ${GREEN}$FILTERED_OUT${NC}"
    else
        echo -e "Filtrage actif:          ${RED}❌ NON${NC}"
    fi
    
    # Afficher les problèmes
    echo -e "\n${BLUE}PROBLÈMES DÉTECTÉS:${NC}"
    echo "$DEBUG_RESPONSE" | jq -r '.data.issues[]' | while read -r issue; do
        if [[ $issue == *"✅"* ]]; then
            echo -e "  ${GREEN}$issue${NC}"
        elif [[ $issue == *"❌"* ]]; then
            echo -e "  ${RED}$issue${NC}"
        else
            echo -e "  ${YELLOW}$issue${NC}"
        fi
    done
    
    # Détails des créneaux
    echo -e "\n${BLUE}CRÉNEAUX:${NC}"
    SLOTS_COUNT=$(echo "$DEBUG_RESPONSE" | jq -r '.data.open_slots | length')
    echo -e "  Total: ${YELLOW}$SLOTS_COUNT${NC}\n"
    
    echo "$DEBUG_RESPONSE" | jq -r '.data.open_slots[] | 
        "  Créneau #\(.id): \(.course_types_count_before_filter) → \(.course_types_count_after_filter) types"' | while read -r line; do
        if [[ $line == *"→ 0"* ]]; then
            echo -e "  ${RED}$line${NC}"
        else
            echo -e "  ${GREEN}$line${NC}"
        fi
    done
fi

# Test 2: API standard des créneaux
echo -e "\n${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}TEST 2: API standard /club/open-slots${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"

SLOTS_RESPONSE=$(curl -s -X GET "$API_BASE/club/open-slots" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")

if command -v jq &> /dev/null; then
    SLOTS=$(echo "$SLOTS_RESPONSE" | jq -r '.data[] | 
        "Créneau #\(.id): \(.course_types | length) types de cours"')
    
    echo "$SLOTS" | while read -r line; do
        echo -e "  ${BLUE}$line${NC}"
    done
else
    echo "$SLOTS_RESPONSE" | jq '.'
fi

# Test 3: API des types de cours
echo -e "\n${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}TEST 3: API /course-types${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"

TYPES_RESPONSE=$(curl -s -X GET "$API_BASE/course-types" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")

if command -v jq &> /dev/null; then
    TYPES_COUNT=$(echo "$TYPES_RESPONSE" | jq -r '.data | length')
    echo -e "  Total types de cours retournés: ${YELLOW}$TYPES_COUNT${NC}\n"
    
    echo "$TYPES_RESPONSE" | jq -r '.data[] | 
        "  - \(.name) (ID:\(.id), Disc:\(.discipline_id // "N/A"))"' | head -20
    
    if [ "$TYPES_COUNT" -gt 20 ]; then
        echo -e "\n  ... et $((TYPES_COUNT - 20)) autres"
    fi
else
    echo "$TYPES_RESPONSE" | jq '.'
fi

echo -e "\n${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Tests terminés${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"

echo -e "${YELLOW}💡 Conseil:${NC} Pour une analyse détaillée, visitez:"
echo -e "   ${BLUE}http://localhost:3000/club/debug-api${NC}\n"

