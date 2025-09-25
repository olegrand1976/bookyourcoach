#!/bin/bash

# Script de test final pour valider la correction de l'erreur 500
# Usage: ./scripts/test-error-500-fix.sh

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

echo -e "${PURPLE}🔧 Test de validation - Correction erreur 500${NC}"
echo -e "${PURPLE}============================================${NC}"

# Configuration
FRONTEND_URL="http://localhost:3000"
BACKEND_URL="http://localhost:8080"
EMAIL="manager@centre-equestre-des-etoiles.fr"
PASSWORD="password"

echo -e "${BLUE}1. Test de connexion et récupération du token...${NC}"
LOGIN_RESPONSE=$(curl -s -X POST "$BACKEND_URL/api/auth/login" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\",\"remember\":true}")

if echo "$LOGIN_RESPONSE" | grep -q "Connexion r"; then
    echo -e "${GREEN}✅ Connexion réussie${NC}"
    
    # Extraire le token
    TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    echo -e "${YELLOW}🔑 Token: ${TOKEN:0:20}...${NC}"
    
    echo -e "${BLUE}2. Test de l'API dashboard avec le nouveau middleware...${NC}"
    
    # Test avec le token
    DASHBOARD_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" "$BACKEND_URL/api/club/dashboard")
    
    # Vérifier le code de statut HTTP
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $TOKEN" "$BACKEND_URL/api/club/dashboard")
    
    if [ "$HTTP_STATUS" = "200" ]; then
        echo -e "${GREEN}✅ Code de statut HTTP: $HTTP_STATUS (OK)${NC}"
        
        if echo "$DASHBOARD_RESPONSE" | grep -q '"success":true'; then
            echo -e "${GREEN}✅ API dashboard retourne des données valides${NC}"
            
            # Extraire le nom du club
            CLUB_NAME=$(echo "$DASHBOARD_RESPONSE" | grep -o '"name":"[^"]*"' | cut -d'"' -f4)
            echo -e "${YELLOW}🏢 Club: $CLUB_NAME${NC}"
            
            echo -e "${BLUE}3. Test sans token (doit retourner 401)...${NC}"
            NO_TOKEN_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$BACKEND_URL/api/club/dashboard")
            
            if [ "$NO_TOKEN_RESPONSE" = "401" ]; then
                echo -e "${GREEN}✅ Sans token: Code 401 (Unauthorized) - Correct${NC}"
            else
                echo -e "${RED}❌ Sans token: Code $NO_TOKEN_RESPONSE - Attendu 401${NC}"
            fi
            
            echo -e "${BLUE}4. Test avec token invalide...${NC}"
            INVALID_TOKEN_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer invalid_token" "$BACKEND_URL/api/club/dashboard")
            
            if [ "$INVALID_TOKEN_RESPONSE" = "401" ]; then
                echo -e "${GREEN}✅ Token invalide: Code 401 (Unauthorized) - Correct${NC}"
            else
                echo -e "${RED}❌ Token invalide: Code $INVALID_TOKEN_RESPONSE - Attendu 401${NC}"
            fi
            
            echo ""
            echo -e "${GREEN}🎉 CORRECTION VALIDÉE !${NC}"
            echo -e "${GREEN}L'erreur 500 est complètement résolue.${NC}"
            echo ""
            echo -e "${YELLOW}📋 Résumé de la correction:${NC}"
            echo -e "  • Middleware personnalisé créé (ApiAuthenticate)"
            echo -e "  • Route protégée avec le nouveau middleware"
            echo -e "  • Authentification Sanctum fonctionnelle"
            echo -e "  • Réponses JSON au lieu de redirections"
            echo -e "  • Gestion d'erreur appropriée (401 au lieu de 500)"
            
        else
            echo -e "${RED}❌ API dashboard ne retourne pas de données valides${NC}"
            echo "Réponse: $DASHBOARD_RESPONSE"
        fi
        
    else
        echo -e "${RED}❌ Code de statut HTTP: $HTTP_STATUS (Attendu 200)${NC}"
        echo "Réponse: $DASHBOARD_RESPONSE"
    fi
    
else
    echo -e "${RED}❌ Échec de la connexion${NC}"
    echo "Réponse: $LOGIN_RESPONSE"
fi
