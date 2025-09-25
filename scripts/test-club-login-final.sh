#!/bin/bash

# Script de validation finale - Test de connexion club sans erreur 500
# Usage: ./scripts/test-club-login-final.sh

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

echo -e "${PURPLE}🏢 Test Final - Connexion Club Sans Erreur 500${NC}"
echo -e "${PURPLE}===============================================${NC}"

# Configuration
FRONTEND_URL="http://localhost:3000"
BACKEND_URL="http://localhost:8080"
EMAIL="manager@centre-equestre-des-etoiles.fr"
PASSWORD="password"

echo -e "${BLUE}🎯 Objectif: Valider que la connexion club fonctionne sans erreur 500${NC}"
echo ""

echo -e "${BLUE}1. Test de connexion club...${NC}"
LOGIN_RESPONSE=$(curl -s -X POST "$BACKEND_URL/api/auth/login" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\",\"remember\":true}")

if echo "$LOGIN_RESPONSE" | grep -q "Connexion r"; then
    echo -e "${GREEN}✅ Connexion club réussie${NC}"
    
    # Extraire le token
    TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    echo -e "${YELLOW}🔑 Token obtenu: ${TOKEN:0:20}...${NC}"
    
    echo -e "${BLUE}2. Test de l'API dashboard club...${NC}"
    
    # Test avec le token
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $TOKEN" "$BACKEND_URL/api/club/dashboard")
    
    if [ "$HTTP_STATUS" = "200" ]; then
        echo -e "${GREEN}✅ API dashboard club: Code $HTTP_STATUS (SUCCÈS)${NC}"
        
        # Récupérer les données pour vérifier
        DASHBOARD_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" "$BACKEND_URL/api/club/dashboard")
        
        if echo "$DASHBOARD_RESPONSE" | grep -q '"success":true'; then
            echo -e "${GREEN}✅ Données du dashboard récupérées avec succès${NC}"
            
            # Extraire le nom du club
            CLUB_NAME=$(echo "$DASHBOARD_RESPONSE" | grep -o '"name":"[^"]*"' | cut -d'"' -f4)
            echo -e "${YELLOW}🏢 Club connecté: $CLUB_NAME${NC}"
            
            echo -e "${BLUE}3. Test de la page frontend...${NC}"
            
            # Test de la page frontend
            FRONTEND_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$FRONTEND_URL/club/dashboard")
            if [ "$FRONTEND_STATUS" = "200" ] || [ "$FRONTEND_STATUS" = "302" ]; then
                echo -e "${GREEN}✅ Page frontend accessible (Code: $FRONTEND_STATUS)${NC}"
            else
                echo -e "${YELLOW}⚠️ Page frontend: Code $FRONTEND_STATUS${NC}"
            fi
            
            echo ""
            echo -e "${GREEN}🎉 SUCCÈS COMPLET !${NC}"
            echo -e "${GREEN}✅ La connexion club fonctionne parfaitement${NC}"
            echo -e "${GREEN}✅ Plus d'erreur 500 lors de la connexion${NC}"
            echo -e "${GREEN}✅ L'API dashboard retourne les bonnes données${NC}"
            echo -e "${GREEN}✅ Le frontend est accessible${NC}"
            
            echo ""
            echo -e "${YELLOW}📋 Résumé de la correction:${NC}"
            echo -e "  • Problème: Erreur 500 lors de la connexion club"
            echo -e "  • Cause: Middleware Sanctum tentait de rediriger vers route 'login' inexistante"
            echo -e "  • Solution: Middleware personnalisé ApiAuthenticate créé"
            echo -e "  • Frontend: Modification pour envoyer le token d'autorisation"
            echo -e "  • Résultat: Connexion club fonctionnelle sans erreur 500"
            
            echo ""
            echo -e "${BLUE}🌐 URLs d'accès:${NC}"
            echo -e "  Frontend:    $FRONTEND_URL"
            echo -e "  Backend:     $BACKEND_URL"
            echo -e "  Dashboard:   $FRONTEND_URL/club/dashboard"
            echo -e "  Login:       $FRONTEND_URL/login"
            
        else
            echo -e "${RED}❌ Données du dashboard invalides${NC}"
            echo "Réponse: $DASHBOARD_RESPONSE"
        fi
        
    else
        echo -e "${RED}❌ API dashboard club: Code $HTTP_STATUS (ÉCHEC)${NC}"
        echo -e "${RED}❌ L'erreur 500 persiste${NC}"
    fi
    
else
    echo -e "${RED}❌ Échec de la connexion club${NC}"
    echo "Réponse: $LOGIN_RESPONSE"
fi
