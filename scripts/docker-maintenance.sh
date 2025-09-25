#!/bin/bash

# Script de maintenance Docker pour BookYourCoach
# Usage: ./scripts/docker-maintenance.sh [action]
# Actions:
#   start     - Démarrer tous les services
#   stop      - Arrêter tous les services
#   restart   - Redémarrer tous les services
#   rebuild   - Reconstruire et redémarrer
#   clean     - Nettoyer les conteneurs et images
#   logs      - Afficher les logs
#   status    - Afficher le statut des services

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
PROJECT_ROOT="/home/olivier/projets/bookyourcoach"

echo -e "${PURPLE}🐳 BookYourCoach - Maintenance Docker${NC}"
echo -e "${PURPLE}====================================${NC}"

# Fonction pour afficher l'aide
show_help() {
    echo -e "${BLUE}Usage: $0 [action]${NC}"
    echo ""
    echo -e "${YELLOW}Actions disponibles:${NC}"
    echo "  start     - Démarrer tous les services"
    echo "  stop      - Arrêter tous les services"
    echo "  restart   - Redémarrer tous les services"
    echo "  rebuild   - Reconstruire et redémarrer"
    echo "  clean     - Nettoyer les conteneurs et images"
    echo "  logs      - Afficher les logs des services"
    echo "  status    - Afficher le statut des services"
    echo "  help      - Afficher cette aide"
    echo ""
    echo -e "${YELLOW}Exemples:${NC}"
    echo "  $0 start"
    echo "  $0 rebuild"
    echo "  $0 logs"
}

# Fonction pour démarrer les services
start_services() {
    echo -e "${BLUE}🚀 Démarrage des services...${NC}"
    cd "$PROJECT_ROOT"
    
    if docker compose up -d; then
        echo -e "${GREEN}✅ Services démarrés avec succès${NC}"
        echo -e "${YELLOW}💡 Attendez quelques secondes que les services soient prêts...${NC}"
        sleep 5
        show_status
    else
        echo -e "${RED}❌ Erreur lors du démarrage des services${NC}"
        return 1
    fi
}

# Fonction pour arrêter les services
stop_services() {
    echo -e "${BLUE}🛑 Arrêt des services...${NC}"
    cd "$PROJECT_ROOT"
    
    if docker compose down; then
        echo -e "${GREEN}✅ Services arrêtés avec succès${NC}"
    else
        echo -e "${RED}❌ Erreur lors de l'arrêt des services${NC}"
        return 1
    fi
}

# Fonction pour redémarrer les services
restart_services() {
    echo -e "${BLUE}🔄 Redémarrage des services...${NC}"
    cd "$PROJECT_ROOT"
    
    if docker compose restart; then
        echo -e "${GREEN}✅ Services redémarrés avec succès${NC}"
        sleep 5
        show_status
    else
        echo -e "${RED}❌ Erreur lors du redémarrage des services${NC}"
        return 1
    fi
}

# Fonction pour reconstruire et redémarrer
rebuild_services() {
    echo -e "${BLUE}🔨 Reconstruction et redémarrage des services...${NC}"
    cd "$PROJECT_ROOT"
    
    echo -e "${YELLOW}⏳ Arrêt des services...${NC}"
    docker compose down
    
    echo -e "${YELLOW}⏳ Reconstruction des images...${NC}"
    if docker compose build; then
        echo -e "${GREEN}✅ Images reconstruites avec succès${NC}"
        
        echo -e "${YELLOW}⏳ Démarrage des services...${NC}"
        if docker compose up -d; then
            echo -e "${GREEN}✅ Services reconstruits et démarrés avec succès${NC}"
            sleep 5
            show_status
        else
            echo -e "${RED}❌ Erreur lors du démarrage des services${NC}"
            return 1
        fi
    else
        echo -e "${RED}❌ Erreur lors de la reconstruction des images${NC}"
        return 1
    fi
}

# Fonction pour nettoyer
clean_docker() {
    echo -e "${BLUE}🧹 Nettoyage Docker...${NC}"
    
    echo -e "${YELLOW}⏳ Arrêt des services...${NC}"
    cd "$PROJECT_ROOT"
    docker compose down
    
    echo -e "${YELLOW}⏳ Suppression des conteneurs arrêtés...${NC}"
    docker container prune -f
    
    echo -e "${YELLOW}⏳ Suppression des images non utilisées...${NC}"
    docker image prune -f
    
    echo -e "${YELLOW}⏳ Suppression des volumes non utilisés...${NC}"
    docker volume prune -f
    
    echo -e "${YELLOW}⏳ Suppression des réseaux non utilisés...${NC}"
    docker network prune -f
    
    echo -e "${GREEN}✅ Nettoyage terminé${NC}"
}

# Fonction pour afficher les logs
show_logs() {
    echo -e "${BLUE}📋 Logs des services...${NC}"
    cd "$PROJECT_ROOT"
    
    echo -e "${YELLOW}Choisissez un service pour voir ses logs:${NC}"
    echo "1. Frontend"
    echo "2. Backend"
    echo "3. MySQL"
    echo "4. Neo4j"
    echo "5. phpMyAdmin"
    echo "6. Tous les services"
    
    read -p "Votre choix (1-6): " choice
    
    case $choice in
        1)
            docker compose logs -f activibe-frontend
            ;;
        2)
            docker compose logs -f activibe-backend
            ;;
        3)
            docker compose logs -f activibe-mysql-local
            ;;
        4)
            docker compose logs -f activibe-neo4j
            ;;
        5)
            docker compose logs -f activibe-phpmyadmin
            ;;
        6)
            docker compose logs -f
            ;;
        *)
            echo -e "${RED}❌ Choix invalide${NC}"
            ;;
    esac
}

# Fonction pour afficher le statut
show_status() {
    echo -e "${BLUE}📊 Statut des services:${NC}"
    cd "$PROJECT_ROOT"
    
    echo ""
    docker compose ps
    
    echo ""
    echo -e "${YELLOW}🌐 URLs d'accès:${NC}"
    echo "  Frontend:    http://localhost:3000"
    echo "  Backend:     http://localhost:8080"
    echo "  phpMyAdmin:  http://localhost:8082"
    echo "  Neo4j:       http://localhost:7474"
    echo "  MySQL:       localhost:3308"
}

# Traitement des arguments
case "${1:-help}" in
    "start")
        start_services
        ;;
    "stop")
        stop_services
        ;;
    "restart")
        restart_services
        ;;
    "rebuild")
        rebuild_services
        ;;
    "clean")
        clean_docker
        ;;
    "logs")
        show_logs
        ;;
    "status")
        show_status
        ;;
    "help"|"-h"|"--help")
        show_help
        ;;
    *)
        echo -e "${RED}❌ Action inconnue: $1${NC}"
        echo ""
        show_help
        exit 1
        ;;
esac
