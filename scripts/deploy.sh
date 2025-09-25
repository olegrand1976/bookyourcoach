#!/bin/bash

# Script de déploiement et configuration pour BookYourCoach
# Usage: ./scripts/deploy.sh [environment]
# Environments:
#   local     - Déploiement local (par défaut)
#   dev       - Déploiement développement
#   prod      - Déploiement production

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
PROJECT_ROOT="/home/olivier/projets/bookyourcoach"
ENVIRONMENT="${1:-local}"

echo -e "${PURPLE}🚀 BookYourCoach - Déploiement${NC}"
echo -e "${PURPLE}==============================${NC}"
echo -e "${YELLOW}Environnement: $ENVIRONMENT${NC}"

# Fonction pour afficher l'aide
show_help() {
    echo -e "${BLUE}Usage: $0 [environment]${NC}"
    echo ""
    echo -e "${YELLOW}Environnements disponibles:${NC}"
    echo "  local     - Déploiement local (par défaut)"
    echo "  dev       - Déploiement développement"
    echo "  prod      - Déploiement production"
    echo ""
    echo -e "${YELLOW}Exemples:${NC}"
    echo "  $0 local"
    echo "  $0 dev"
    echo "  $0 prod"
}

# Fonction pour vérifier les prérequis
check_prerequisites() {
    echo -e "${BLUE}🔍 Vérification des prérequis...${NC}"
    
    local missing_deps=()
    
    # Vérifier Docker
    if ! command -v docker &> /dev/null; then
        missing_deps+=("docker")
    fi
    
    # Vérifier Docker Compose
    if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
        missing_deps+=("docker-compose")
    fi
    
    # Vérifier Git
    if ! command -v git &> /dev/null; then
        missing_deps+=("git")
    fi
    
    if [ ${#missing_deps[@]} -ne 0 ]; then
        echo -e "${RED}❌ Dépendances manquantes: ${missing_deps[*]}${NC}"
        echo -e "${YELLOW}💡 Installez les dépendances manquantes avant de continuer${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✅ Toutes les dépendances sont installées${NC}"
    return 0
}

# Fonction pour déploiement local
deploy_local() {
    echo -e "${BLUE}🏠 Déploiement local...${NC}"
    
    cd "$PROJECT_ROOT"
    
    # Arrêter les services existants
    echo -e "${YELLOW}⏳ Arrêt des services existants...${NC}"
    docker compose down
    
    # Construire les images
    echo -e "${YELLOW}⏳ Construction des images...${NC}"
    if docker compose build; then
        echo -e "${GREEN}✅ Images construites${NC}"
    else
        echo -e "${RED}❌ Erreur lors de la construction des images${NC}"
        return 1
    fi
    
    # Démarrer les services
    echo -e "${YELLOW}⏳ Démarrage des services...${NC}"
    if docker compose up -d; then
        echo -e "${GREEN}✅ Services démarrés${NC}"
    else
        echo -e "${RED}❌ Erreur lors du démarrage des services${NC}"
        return 1
    fi
    
    # Attendre que les services soient prêts
    echo -e "${YELLOW}⏳ Attente que les services soient prêts...${NC}"
    sleep 10
    
    # Vérifier le statut
    echo -e "${BLUE}📊 Statut des services:${NC}"
    docker compose ps
    
    echo ""
    echo -e "${GREEN}🎉 Déploiement local terminé !${NC}"
    echo -e "${YELLOW}🌐 URLs d'accès:${NC}"
    echo "  Frontend:    http://localhost:3000"
    echo "  Backend:     http://localhost:8080"
    echo "  phpMyAdmin:  http://localhost:8082"
    echo "  Neo4j:       http://localhost:7474"
}

# Fonction pour déploiement développement
deploy_dev() {
    echo -e "${BLUE}🔧 Déploiement développement...${NC}"
    
    cd "$PROJECT_ROOT"
    
    # Mettre à jour le code
    echo -e "${YELLOW}⏳ Mise à jour du code...${NC}"
    git pull origin develop
    
    # Arrêter les services
    echo -e "${YELLOW}⏳ Arrêt des services...${NC}"
    docker compose -f docker-compose.dev.yml down
    
    # Construire avec les variables d'environnement de dev
    echo -e "${YELLOW}⏳ Construction des images de développement...${NC}"
    if docker compose -f docker-compose.dev.yml build; then
        echo -e "${GREEN}✅ Images de développement construites${NC}"
    else
        echo -e "${RED}❌ Erreur lors de la construction${NC}"
        return 1
    fi
    
    # Démarrer les services
    echo -e "${YELLOW}⏳ Démarrage des services de développement...${NC}"
    if docker compose -f docker-compose.dev.yml up -d; then
        echo -e "${GREEN}✅ Services de développement démarrés${NC}"
    else
        echo -e "${RED}❌ Erreur lors du démarrage${NC}"
        return 1
    fi
    
    echo -e "${GREEN}🎉 Déploiement développement terminé !${NC}"
}

# Fonction pour déploiement production
deploy_prod() {
    echo -e "${BLUE}🏭 Déploiement production...${NC}"
    
    cd "$PROJECT_ROOT"
    
    # Vérifier que nous sommes sur la branche main
    current_branch=$(git branch --show-current)
    if [ "$current_branch" != "main" ]; then
        echo -e "${RED}❌ Vous devez être sur la branche main pour déployer en production${NC}"
        echo -e "${YELLOW}Branche actuelle: $current_branch${NC}"
        return 1
    fi
    
    # Mettre à jour le code
    echo -e "${YELLOW}⏳ Mise à jour du code...${NC}"
    git pull origin main
    
    # Arrêter les services
    echo -e "${YELLOW}⏳ Arrêt des services de production...${NC}"
    docker compose -f docker-compose.yml down
    
    # Construire les images de production
    echo -e "${YELLOW}⏳ Construction des images de production...${NC}"
    if docker compose -f docker-compose.yml build --no-cache; then
        echo -e "${GREEN}✅ Images de production construites${NC}"
    else
        echo -e "${RED}❌ Erreur lors de la construction${NC}"
        return 1
    fi
    
    # Démarrer les services
    echo -e "${YELLOW}⏳ Démarrage des services de production...${NC}"
    if docker compose -f docker-compose.yml up -d; then
        echo -e "${GREEN}✅ Services de production démarrés${NC}"
    else
        echo -e "${RED}❌ Erreur lors du démarrage${NC}"
        return 1
    fi
    
    echo -e "${GREEN}🎉 Déploiement production terminé !${NC}"
}

# Fonction principale
main() {
    # Vérifier les prérequis
    if ! check_prerequisites; then
        exit 1
    fi
    
    echo ""
    
    # Déployer selon l'environnement
    case "$ENVIRONMENT" in
        "local")
            deploy_local
            ;;
        "dev")
            deploy_dev
            ;;
        "prod")
            deploy_prod
            ;;
        "help"|"-h"|"--help")
            show_help
            ;;
        *)
            echo -e "${RED}❌ Environnement inconnu: $ENVIRONMENT${NC}"
            echo ""
            show_help
            exit 1
            ;;
    esac
}

# Exécuter la fonction principale
main