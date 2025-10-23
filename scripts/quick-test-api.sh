#!/bin/bash

# Script rapide pour tester l'API de filtrage
# Utilise les credentials par défaut pour se connecter automatiquement

set -e

API_BASE="http://localhost:8080/api"

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}    Test Rapide du Filtrage des Types de Cours${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"

# 1. Essayer de se connecter avec différents comptes
echo -e "${YELLOW}Tentative de connexion...${NC}\n"

EMAILS=("club@test.com" "club@example.com" "admin@test.com" "test@club.com")
PASSWORDS=("password" "Password123" "admin123")

TOKEN=""

for EMAIL in "${EMAILS[@]}"; do
    for PASSWORD in "${PASSWORDS[@]}"; do
        if [ -z "$TOKEN" ]; then
            echo -e "${BLUE}→ Essai avec ${EMAIL} / ${PASSWORD}${NC}"
            
            LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
                -H "Content-Type: application/json" \
                -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}" 2>/dev/null || echo "")
            
            if [ ! -z "$LOGIN_RESPONSE" ]; then
                TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*' | cut -d'"' -f4 || echo "")
                
                if [ ! -z "$TOKEN" ]; then
                    ROLE=$(echo "$LOGIN_RESPONSE" | grep -o '"role":"[^"]*' | cut -d'"' -f4 || echo "")
                    echo -e "  ${GREEN}✅ Connecté ! (Rôle: $ROLE)${NC}\n"
                    break 2
                fi
            fi
        fi
    done
done

if [ -z "$TOKEN" ]; then
    echo -e "${RED}❌ Impossible de se connecter avec les credentials de test${NC}"
    echo -e "${YELLOW}Veuillez créer un compte club ou fournir le token manuellement:${NC}"
    echo -e "  ${BLUE}./scripts/quick-test-api.sh YOUR_TOKEN${NC}\n"
    
    # Afficher comment récupérer le token manuellement
    echo -e "${YELLOW}Pour récupérer votre token:${NC}"
    echo -e "  1. Connectez-vous sur ${BLUE}http://localhost:3000${NC}"
    echo -e "  2. Ouvrez la console (F12)"
    echo -e "  3. Tapez: ${BLUE}localStorage.getItem('token')${NC}"
    echo -e "  4. Copiez le token et exécutez:"
    echo -e "     ${BLUE}./scripts/quick-test-api.sh \"votre_token\"${NC}\n"
    exit 1
fi

# Utiliser le token fourni en argument si disponible
if [ ! -z "$1" ]; then
    TOKEN="$1"
    echo -e "${GREEN}✅ Utilisation du token fourni${NC}\n"
fi

# 2. Tester l'endpoint de debug
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Test de l'API de debug${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"

DEBUG_RESPONSE=$(curl -s -X GET "$API_BASE/debug/course-types-filtering" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json" 2>/dev/null || echo "")

if [ -z "$DEBUG_RESPONSE" ]; then
    echo -e "${RED}❌ Erreur: Pas de réponse de l'API${NC}"
    exit 1
fi

# Vérifier si c'est une erreur
ERROR=$(echo "$DEBUG_RESPONSE" | grep -o '"success":false' || echo "")
if [ ! -z "$ERROR" ]; then
    echo -e "${RED}❌ Erreur de l'API:${NC}"
    echo "$DEBUG_RESPONSE" | jq '.' 2>/dev/null || echo "$DEBUG_RESPONSE"
    exit 1
fi

# Afficher le résumé
echo -e "${GREEN}✅ Réponse reçue${NC}\n"

if command -v jq &> /dev/null; then
    # Extraire les informations importantes
    USER_ROLE=$(echo "$DEBUG_RESPONSE" | jq -r '.data.user.role' 2>/dev/null || echo "N/A")
    CLUB_NAME=$(echo "$DEBUG_RESPONSE" | jq -r '.data.club.name' 2>/dev/null || echo "N/A")
    DISCIPLINES_COUNT=$(echo "$DEBUG_RESPONSE" | jq -r '.data.club_disciplines | length' 2>/dev/null || echo "0")
    ALL_TYPES=$(echo "$DEBUG_RESPONSE" | jq -r '.data.all_course_types | length' 2>/dev/null || echo "0")
    FILTERED_TYPES=$(echo "$DEBUG_RESPONSE" | jq -r '.data.filtered_course_types | length' 2>/dev/null || echo "0")
    FILTERING_OK=$(echo "$DEBUG_RESPONSE" | jq -r '.summary.filtering_working' 2>/dev/null || echo "false")
    
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}                         RÉSUMÉ${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"
    
    echo -e "👤 Utilisateur:          ${YELLOW}$USER_ROLE${NC}"
    echo -e "🏢 Club:                 ${YELLOW}$CLUB_NAME${NC}"
    echo -e "📚 Disciplines:          ${YELLOW}$DISCIPLINES_COUNT${NC}"
    echo -e "📝 Types totaux:         ${YELLOW}$ALL_TYPES${NC}"
    echo -e "✂️  Types filtrés:        ${YELLOW}$FILTERED_TYPES${NC}"
    
    if [ "$FILTERING_OK" = "true" ]; then
        FILTERED_OUT=$((ALL_TYPES - FILTERED_TYPES))
        echo -e "🎯 Filtrage:             ${GREEN}✅ ACTIF${NC} (${FILTERED_OUT} types filtrés)"
    else
        echo -e "🎯 Filtrage:             ${RED}❌ INACTIF${NC}"
    fi
    
    # Problèmes
    echo -e "\n${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}                      PROBLÈMES${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"
    
    ISSUES=$(echo "$DEBUG_RESPONSE" | jq -r '.data.issues[]' 2>/dev/null || echo "")
    if [ ! -z "$ISSUES" ]; then
        echo "$ISSUES" | while read -r issue; do
            if [[ $issue == *"✅"* ]]; then
                echo -e "${GREEN}$issue${NC}"
            elif [[ $issue == *"❌"* ]]; then
                echo -e "${RED}$issue${NC}"
            else
                echo -e "${YELLOW}$issue${NC}"
            fi
        done
    else
        echo -e "${GREEN}✅ Aucun problème détecté${NC}"
    fi
    
    # Créneaux
    echo -e "\n${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}                       CRÉNEAUX${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"
    
    SLOTS_COUNT=$(echo "$DEBUG_RESPONSE" | jq -r '.data.open_slots | length' 2>/dev/null || echo "0")
    echo -e "Total: ${YELLOW}$SLOTS_COUNT${NC} créneaux\n"
    
    echo "$DEBUG_RESPONSE" | jq -r '.data.open_slots[] | 
        "Créneau #\(.id): \(.course_types_count_before_filter) → \(.course_types_count_after_filter) types"' 2>/dev/null | while read -r line; do
        if [[ $line == *"→ 0"* ]]; then
            echo -e "${RED}  $line${NC}"
        else
            echo -e "${GREEN}  $line${NC}"
        fi
    done
    
    # Données club détaillées
    echo -e "\n${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}                   DÉTAILS CLUB${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"
    
    DISC_RAW=$(echo "$DEBUG_RESPONSE" | jq -r '.data.club.disciplines_raw' 2>/dev/null || echo "N/A")
    DISC_TYPE=$(echo "$DEBUG_RESPONSE" | jq -r '.data.club.disciplines_type' 2>/dev/null || echo "N/A")
    DISC_IS_ARRAY=$(echo "$DEBUG_RESPONSE" | jq -r '.data.club.disciplines_is_array' 2>/dev/null || echo "N/A")
    DISC_PARSED=$(echo "$DEBUG_RESPONSE" | jq -c '.data.club.disciplines_parsed' 2>/dev/null || echo "N/A")
    
    echo -e "Disciplines (brut):      ${YELLOW}$DISC_RAW${NC}"
    echo -e "Type:                    ${YELLOW}$DISC_TYPE${NC}"
    echo -e "Est un array:            $(if [ "$DISC_IS_ARRAY" = "true" ]; then echo -e "${GREEN}✅ Oui${NC}"; else echo -e "${RED}❌ Non${NC}"; fi)"
    echo -e "Parsé:                   ${YELLOW}$DISC_PARSED${NC}"
    
else
    echo "$DEBUG_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$DEBUG_RESPONSE"
fi

echo -e "\n${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Test terminé${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"

# Sauvegarder les résultats
OUTPUT_FILE="/tmp/api-debug-result.json"
echo "$DEBUG_RESPONSE" > "$OUTPUT_FILE"
echo -e "${YELLOW}💾 Résultats sauvegardés dans: ${BLUE}$OUTPUT_FILE${NC}"
echo -e "${YELLOW}💡 Pour voir les détails: ${BLUE}cat $OUTPUT_FILE | jq '.'${NC}\n"

# URL de la page web
echo -e "${YELLOW}🌐 Interface web disponible sur:${NC}"
echo -e "   ${BLUE}http://localhost:3000/club/debug-api${NC}"
echo -e "   ${YELLOW}(Nécessite d'être connecté avec un compte club)${NC}\n"

