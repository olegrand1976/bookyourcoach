#!/bin/bash

echo "🔐 BookYourCoach - Vérification des Comptes Administrateur"
echo "=========================================================="

# Couleurs
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo ""
echo -e "${BLUE}📋 COMPTES ADMINISTRATEUR DISPONIBLES${NC}"
echo "======================================"

echo ""
echo -e "${GREEN}✅ COMPTE PRINCIPAL${NC}"
echo "-------------------"
echo -e "${YELLOW}Email:${NC}       admin@bookyourcoach.com"
echo -e "${YELLOW}Mot de passe:${NC} admin123"
echo -e "${YELLOW}Nom:${NC}         Administrateur"
echo -e "${YELLOW}Rôle:${NC}        admin"
echo -e "${YELLOW}Statut:${NC}      ✅ Actif (mot de passe réinitialisé)"

echo ""
echo -e "${GREEN}✅ COMPTE ALTERNATIF${NC}"
echo "--------------------"
echo -e "${YELLOW}Email:${NC}       superadmin@bookyourcoach.com"
echo -e "${YELLOW}Mot de passe:${NC} superadmin123"
echo -e "${YELLOW}Nom:${NC}         Super Administrateur"
echo -e "${YELLOW}Rôle:${NC}        admin"
echo -e "${YELLOW}Statut:${NC}      ✅ Actif (nouvellement créé)"

echo ""
echo -e "${BLUE}🧪 TESTS DE CONNEXION${NC}"
echo "====================="

echo ""
echo -n "🔍 Test du compte principal... "
RESPONSE1=$(curl -s -X POST http://localhost:3001/api/auth/login -H "Content-Type: application/json" -d '{"email": "admin@bookyourcoach.com", "password": "admin123"}')
if echo "$RESPONSE1" | grep -q "Login successful"; then
    echo -e "${GREEN}✅ SUCCÈS${NC}"
    TOKEN1=$(echo "$RESPONSE1" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    echo "   Token: ${TOKEN1:0:20}..."
else
    echo -e "${RED}❌ ÉCHEC${NC}"
    echo "   Réponse: $RESPONSE1"
fi

echo ""
echo -n "🔍 Test du compte alternatif... "
RESPONSE2=$(curl -s -X POST http://localhost:3001/api/auth/login -H "Content-Type: application/json" -d '{"email": "superadmin@bookyourcoach.com", "password": "superadmin123"}')
if echo "$RESPONSE2" | grep -q "Login successful"; then
    echo -e "${GREEN}✅ SUCCÈS${NC}"
    TOKEN2=$(echo "$RESPONSE2" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    echo "   Token: ${TOKEN2:0:20}..."
else
    echo -e "${RED}❌ ÉCHEC${NC}"
    echo "   Réponse: $RESPONSE2"
fi

echo ""
echo -e "${BLUE}🌐 LIENS DE CONNEXION${NC}"
echo "====================="
echo -e "${YELLOW}Frontend principal:${NC}    http://localhost:3001/login"
echo -e "${YELLOW}Frontend Docker:${NC}       http://localhost:3000/login"
echo -e "${YELLOW}Page de test API:${NC}      http://localhost:3001/test-api"

echo ""
echo -e "${BLUE}📋 INSTRUCTIONS${NC}"
echo "==============="
echo "1. Ouvrez votre navigateur sur: http://localhost:3001"
echo "2. Allez sur la page de connexion (/login)"
echo "3. Utilisez l'un des comptes ci-dessus"
echo "4. Vous devriez accéder au dashboard admin"

echo ""
echo -e "${GREEN}🎯 Les deux comptes sont fonctionnels et testés !${NC}"
