#!/bin/bash

# Script de test complet de la procédure de connexion BookYourCoach
echo "🔒 Test complet de la procédure de connexion BookYourCoach"
echo "=========================================================="

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Variables
BACKEND_URL="http://localhost:8081"
FRONTEND_DOCKER_URL="http://localhost:3000"
ADMIN_EMAIL="admin@bookyourcoach.com"
ADMIN_PASSWORD="admin123"

echo -e "${BLUE}📋 Test 1: API Backend direct${NC}"
echo "=============================="

# Test connexion API directe
response=$(curl -s -X POST "$BACKEND_URL/api/auth/login" \
    -H "Content-Type: application/json" \
    -d "{\"email\": \"$ADMIN_EMAIL\", \"password\": \"$ADMIN_PASSWORD\"}" \
    -w "%{http_code}")

http_code=$(echo "$response" | tail -c 4)
body=$(echo "$response" | head -c -4)

if [ "$http_code" = "200" ]; then
    echo -e "${GREEN}✅ API Backend - Connexion admin réussie${NC}"
    
    # Extraire le token
    token=$(echo "$body" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    if [ ! -z "$token" ]; then
        echo -e "${GREEN}✅ Token JWT reçu: ${token:0:30}...${NC}"
        
        # Test endpoint authentifié
        auth_response=$(curl -s -H "Authorization: Bearer $token" "$BACKEND_URL/api/admin/stats" -w "%{http_code}")
        auth_code=$(echo "$auth_response" | tail -c 4)
        
        if [ "$auth_code" = "200" ]; then
            echo -e "${GREEN}✅ Endpoint admin authentifié accessible${NC}"
        else
            echo -e "${RED}❌ Endpoint admin authentifié non accessible (code: $auth_code)${NC}"
        fi
    else
        echo -e "${RED}❌ Token JWT non trouvé dans la réponse${NC}"
    fi
else
    echo -e "${RED}❌ API Backend - Connexion admin échouée (code: $http_code)${NC}"
    echo -e "${RED}Réponse: $body${NC}"
fi

echo ""
echo -e "${BLUE}📋 Test 2: Frontend Docker${NC}"
echo "=========================="

# Test accès frontend Docker
frontend_response=$(curl -s "$FRONTEND_DOCKER_URL" -w "%{http_code}")
frontend_code=$(echo "$frontend_response" | tail -c 4)

if [ "$frontend_code" = "200" ]; then
    echo -e "${GREEN}✅ Frontend Docker accessible${NC}"
    
    # Tester si on peut voir le titre de la page
    if echo "$frontend_response" | grep -q "BookYourCoach"; then
        echo -e "${GREEN}✅ Page d'accueil charge correctement${NC}"
    else
        echo -e "${YELLOW}⚠️  Page accessible mais contenu inattendu${NC}"
    fi
else
    echo -e "${RED}❌ Frontend Docker non accessible (code: $frontend_code)${NC}"
fi

echo ""
echo -e "${BLUE}📋 Test 3: Base de données${NC}"
echo "========================"

# Test base de données via Docker
if docker-compose exec -T mysql mysql -u root -proot_password -e "USE bookyourcoach; SELECT id, name, email, role FROM users WHERE email='$ADMIN_EMAIL';" 2>/dev/null | grep -q "admin"; then
    echo -e "${GREEN}✅ Utilisateur admin existe en base de données${NC}"
    
    # Compter le nombre d'utilisateurs
    user_count=$(docker-compose exec -T mysql mysql -u root -proot_password -e "USE bookyourcoach; SELECT COUNT(*) FROM users;" 2>/dev/null | tail -n 1)
    echo -e "${GREEN}✅ Nombre total d'utilisateurs: $user_count${NC}"
else
    echo -e "${RED}❌ Problème avec l'utilisateur admin en base${NC}"
fi

echo ""
echo -e "${BLUE}📋 Test 4: Services Docker${NC}"
echo "=========================="

# Vérifier que tous les conteneurs sont en marche
if docker-compose ps | grep -q "Up"; then
    echo -e "${GREEN}✅ Services Docker actifs:${NC}"
    docker-compose ps | grep "Up" | while read line; do
        service_name=$(echo "$line" | awk '{print $1}')
        echo -e "${GREEN}  ✓ $service_name${NC}"
    done
else
    echo -e "${RED}❌ Aucun service Docker actif${NC}"
fi

echo ""
echo -e "${BLUE}📋 Test 5: URLs de test${NC}"
echo "======================="

echo -e "${YELLOW}🌐 Accès frontend (Docker): $FRONTEND_DOCKER_URL${NC}"
echo -e "${YELLOW}🔧 Accès backend API: $BACKEND_URL${NC}"
echo -e "${YELLOW}📊 Accès PhpMyAdmin: http://localhost:8082${NC}"
echo -e "${YELLOW}💾 Accès Redis: localhost:6380${NC}"

echo ""
echo -e "${BLUE}📋 Instructions de test manuel${NC}"
echo "==============================="

echo -e "${YELLOW}1. Ouvrir le frontend: $FRONTEND_DOCKER_URL${NC}"
echo -e "${YELLOW}2. Cliquer sur 'Se connecter' ou aller sur /login${NC}"
echo -e "${YELLOW}3. Saisir les identifiants:${NC}"
echo -e "${YELLOW}   Email:    $ADMIN_EMAIL${NC}"
echo -e "${YELLOW}   Password: $ADMIN_PASSWORD${NC}"
echo -e "${YELLOW}4. Vérifier la connexion et l'accès au dashboard${NC}"

echo ""
echo -e "${GREEN}🎯 Test terminé !${NC}"
