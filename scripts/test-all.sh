#!/bin/bash

# Script principal pour tester toutes les fonctionnalités de l'application
# Usage: ./scripts/test-all.sh [option]
# Options:
#   login     - Test du processus de connexion
#   api       - Test des APIs
#   docker    - Test des conteneurs Docker
#   all       - Tous les tests (par défaut)

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
PROJECT_ROOT="/home/olivier/projets/bookyourcoach"
FRONTEND_URL="http://localhost:3000"
BACKEND_URL="http://localhost:8080"

echo -e "${PURPLE}🧪 BookYourCoach - Suite de tests${NC}"
echo -e "${PURPLE}=================================${NC}"

# Fonction pour afficher l'aide
show_help() {
    echo -e "${BLUE}Usage: $0 [option]${NC}"
    echo ""
    echo -e "${YELLOW}Options disponibles:${NC}"
    echo "  login     - Test du processus de connexion complet"
    echo "  api       - Test des APIs backend"
    echo "  docker    - Test des conteneurs Docker"
    echo "  frontend  - Test du frontend"
    echo "  all       - Tous les tests (par défaut)"
    echo "  help      - Afficher cette aide"
    echo ""
    echo -e "${YELLOW}Exemples:${NC}"
    echo "  $0 login"
    echo "  $0 api"
    echo "  $0 all"
}

# Fonction pour tester Docker
test_docker() {
    echo -e "${BLUE}🐳 Test des conteneurs Docker...${NC}"
    
    if docker compose ps | grep -q "Up"; then
        echo -e "${GREEN}✅ Conteneurs Docker en cours d'exécution${NC}"
        
        # Vérifier chaque service
        SERVICES=("activibe-frontend" "activibe-backend" "activibe-mysql-local" "activibe-neo4j" "activibe-phpmyadmin")
        for service in "${SERVICES[@]}"; do
            if docker compose ps | grep -q "$service.*Up"; then
                echo -e "${GREEN}  ✅ $service${NC}"
            else
                echo -e "${RED}  ❌ $service${NC}"
            fi
        done
    else
        echo -e "${RED}❌ Aucun conteneur Docker en cours d'exécution${NC}"
        echo -e "${YELLOW}💡 Lancez 'docker compose up -d' pour démarrer les services${NC}"
        return 1
    fi
}

# Fonction pour tester le frontend
test_frontend() {
    echo -e "${BLUE}🌐 Test du frontend...${NC}"
    
    if curl -s -I "$FRONTEND_URL" | grep -q "200 OK"; then
        echo -e "${GREEN}✅ Frontend accessible sur $FRONTEND_URL${NC}"
        
        # Test de la page de connexion
        if curl -s "$FRONTEND_URL/login" | grep -q "Connexion"; then
            echo -e "${GREEN}✅ Page de connexion accessible${NC}"
        else
            echo -e "${RED}❌ Page de connexion non accessible${NC}"
        fi
        
        # Test de la redirection
        REDIRECT_RESPONSE=$(curl -s "$FRONTEND_URL/club/dashboard")
        if echo "$REDIRECT_RESPONSE" | grep -q "url=/login"; then
            echo -e "${GREEN}✅ Redirection vers login fonctionne${NC}"
        else
            echo -e "${RED}❌ Redirection vers login ne fonctionne pas${NC}"
        fi
    else
        echo -e "${RED}❌ Frontend non accessible${NC}"
        return 1
    fi
}

# Fonction pour tester les APIs
test_api() {
    echo -e "${BLUE}🔌 Test des APIs backend...${NC}"
    
    if curl -s -I "$BACKEND_URL/api/auth/login" | grep -q "405 Method Not Allowed"; then
        echo -e "${GREEN}✅ Backend accessible sur $BACKEND_URL${NC}"
        
        # Test de connexion
        LOGIN_RESPONSE=$(curl -s -X POST "$BACKEND_URL/api/auth/login" \
            -H "Content-Type: application/json" \
            -d '{"email":"manager@centre-equestre-des-etoiles.fr","password":"password","remember":true}')
        
        if echo "$LOGIN_RESPONSE" | grep -q "Connexion r"; then
            echo -e "${GREEN}✅ API de connexion fonctionne${NC}"
            
            # Extraire le token et tester le dashboard
            TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
            DASHBOARD_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" "$BACKEND_URL/api/club/dashboard")
            
            if echo "$DASHBOARD_RESPONSE" | grep -q '"success":true'; then
                echo -e "${GREEN}✅ API dashboard accessible${NC}"
            else
                echo -e "${RED}❌ API dashboard non accessible${NC}"
            fi
        else
            echo -e "${RED}❌ API de connexion ne fonctionne pas${NC}"
        fi
    else
        echo -e "${RED}❌ Backend non accessible${NC}"
        return 1
    fi
}

# Fonction pour tester le processus de connexion complet
test_login() {
    echo -e "${BLUE}🔐 Test du processus de connexion complet...${NC}"
    
    if [ -f "$PROJECT_ROOT/scripts/test-login-process.sh" ]; then
        bash "$PROJECT_ROOT/scripts/test-login-process.sh"
    else
        echo -e "${RED}❌ Script test-login-process.sh non trouvé${NC}"
        return 1
    fi
}

# Fonction pour tous les tests
test_all() {
    echo -e "${BLUE}🚀 Exécution de tous les tests...${NC}"
    echo ""
    
    local all_passed=true
    
    # Test Docker
    if ! test_docker; then
        all_passed=false
    fi
    echo ""
    
    # Test Frontend
    if ! test_frontend; then
        all_passed=false
    fi
    echo ""
    
    # Test API
    if ! test_api; then
        all_passed=false
    fi
    echo ""
    
    # Test Login complet
    if ! test_login; then
        all_passed=false
    fi
    
    echo ""
    if [ "$all_passed" = true ]; then
        echo -e "${GREEN}🎉 TOUS LES TESTS SONT PASSÉS !${NC}"
        echo -e "${GREEN}L'application fonctionne correctement.${NC}"
    else
        echo -e "${RED}❌ Certains tests ont échoué${NC}"
        echo -e "${YELLOW}Vérifiez les logs ci-dessus pour plus de détails.${NC}"
    fi
}

# Traitement des arguments
case "${1:-all}" in
    "login")
        test_login
        ;;
    "api")
        test_api
        ;;
    "docker")
        test_docker
        ;;
    "frontend")
        test_frontend
        ;;
    "all")
        test_all
        ;;
    "help"|"-h"|"--help")
        show_help
        ;;
    *)
        echo -e "${RED}❌ Option inconnue: $1${NC}"
        echo ""
        show_help
        exit 1
        ;;
esac
