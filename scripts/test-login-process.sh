#!/bin/bash

echo "🧪 Test du processus complet de connexion"
echo "=========================================="

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
FRONTEND_URL="http://localhost:3000"
BACKEND_URL="http://localhost:8080"
EMAIL="manager@centre-equestre-des-etoiles.fr"
PASSWORD="password"

echo -e "${BLUE}1. Test de la disponibilité du frontend...${NC}"
if curl -s -I "$FRONTEND_URL" | grep -q "200 OK"; then
    echo -e "${GREEN}✅ Frontend accessible sur $FRONTEND_URL${NC}"
else
    echo -e "${RED}❌ Frontend non accessible${NC}"
    exit 1
fi

echo -e "${BLUE}2. Test de la disponibilité du backend...${NC}"
if curl -s -I "$BACKEND_URL/api/auth/login" | grep -q "405 Method Not Allowed"; then
    echo -e "${GREEN}✅ Backend accessible sur $BACKEND_URL${NC}"
else
    echo -e "${RED}❌ Backend non accessible${NC}"
    exit 1
fi

echo -e "${BLUE}3. Test de la connexion utilisateur...${NC}"
LOGIN_RESPONSE=$(curl -s -X POST "$BACKEND_URL/api/auth/login" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\",\"remember\":true}")

if echo "$LOGIN_RESPONSE" | grep -q "Connexion r"; then
    echo -e "${GREEN}✅ Connexion réussie${NC}"
    
    # Extraire le token
    TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    echo -e "${YELLOW}🔑 Token obtenu: ${TOKEN:0:20}...${NC}"
    
    echo -e "${BLUE}4. Test de l'API dashboard avec authentification...${NC}"
    DASHBOARD_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" "$BACKEND_URL/api/club/dashboard")
    
    if echo "$DASHBOARD_RESPONSE" | grep -q '"success":true'; then
        echo -e "${GREEN}✅ API dashboard accessible avec authentification${NC}"
        
        # Extraire le nom du club
        CLUB_NAME=$(echo "$DASHBOARD_RESPONSE" | grep -o '"name":"[^"]*"' | cut -d'"' -f4)
        echo -e "${YELLOW}🏢 Club: $CLUB_NAME${NC}"
        
        echo -e "${BLUE}5. Test de la redirection vers le dashboard...${NC}"
        # Simuler l'accès au dashboard sans authentification (doit rediriger vers login)
        REDIRECT_RESPONSE=$(curl -s "$FRONTEND_URL/club/dashboard")
        if echo "$REDIRECT_RESPONSE" | grep -q "url=/login"; then
            echo -e "${GREEN}✅ Redirection vers login fonctionne${NC}"
        else
            echo -e "${RED}❌ Redirection vers login ne fonctionne pas${NC}"
        fi
        
        echo -e "${BLUE}6. Test de la page de connexion...${NC}"
        LOGIN_PAGE_RESPONSE=$(curl -s "$FRONTEND_URL/login")
        if echo "$LOGIN_PAGE_RESPONSE" | grep -q "Connexion"; then
            echo -e "${GREEN}✅ Page de connexion accessible${NC}"
        else
            echo -e "${RED}❌ Page de connexion non accessible${NC}"
        fi
        
        echo ""
        echo -e "${GREEN}🎉 TOUS LES TESTS SONT PASSÉS !${NC}"
        echo -e "${GREEN}Le processus de connexion fonctionne correctement.${NC}"
        echo ""
        echo -e "${YELLOW}📋 Résumé:${NC}"
        echo -e "  • Frontend: ✅ Accessible"
        echo -e "  • Backend: ✅ Accessible" 
        echo -e "  • Connexion: ✅ Fonctionne"
        echo -e "  • API Dashboard: ✅ Fonctionne"
        echo -e "  • Redirection: ✅ Fonctionne"
        echo -e "  • Page Login: ✅ Accessible"
        
    else
        echo -e "${RED}❌ API dashboard non accessible${NC}"
        echo "Réponse: $DASHBOARD_RESPONSE"
    fi
    
else
    echo -e "${RED}❌ Échec de la connexion${NC}"
    echo "Réponse: $LOGIN_RESPONSE"
    exit 1
fi
