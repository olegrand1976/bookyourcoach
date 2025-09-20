#!/bin/bash

# =============================================================================
# Script de déploiement - BookYourCoach
# =============================================================================

set -e

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT=${ENVIRONMENT:-"production"}
BACKEND_IMAGE=${BACKEND_IMAGE:-"olegrand1976/activibe-app"}
FRONTEND_IMAGE=${FRONTEND_IMAGE:-"olegrand1976/activibe-frontend"}
TAG=${TAG:-"latest"}

# Fonctions de logging
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

log_success() {
    echo -e "${PURPLE}[SUCCESS]${NC} $1"
}

# Fonction d'aide
show_help() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -e, --env ENV            Environnement (production|staging|dev) (défaut: production)"
    echo "  -t, --tag TAG            Tag des images (défaut: latest)"
    echo "  -b, --backend-image NAME Nom de l'image backend"
    echo "  -f, --frontend-image NAME Nom de l'image frontend"
    echo "  --build                  Construire les images avant déploiement"
    echo "  --pull                   Tirer les images depuis le registry"
    echo "  --no-backup              Ne pas créer de backup avant déploiement"
    echo "  -h, --help               Afficher cette aide"
    echo ""
    echo "Exemples:"
    echo "  $0                       # Déploiement production avec images existantes"
    echo "  $0 --build               # Build et déploiement"
    echo "  $0 -e staging --build    # Build et déploiement staging"
    echo "  $0 -t v1.0.0 --pull      # Déploiement avec tag spécifique"
}

# Parser les arguments
BUILD=false
PULL=false
NO_BACKUP=false

while [[ $# -gt 0 ]]; do
    case $1 in
        -e|--env)
            ENVIRONMENT="$2"
            shift 2
            ;;
        -t|--tag)
            TAG="$2"
            shift 2
            ;;
        -b|--backend-image)
            BACKEND_IMAGE="$2"
            shift 2
            ;;
        -f|--frontend-image)
            FRONTEND_IMAGE="$2"
            shift 2
            ;;
        --build)
            BUILD=true
            shift
            ;;
        --pull)
            PULL=true
            shift
            ;;
        --no-backup)
            NO_BACKUP=true
            shift
            ;;
        -h|--help)
            show_help
            exit 0
            ;;
        *)
            log_error "Option inconnue: $1"
            show_help
            exit 1
            ;;
    esac
done

# Validation de l'environnement
case $ENVIRONMENT in
    production|staging|dev)
        ;;
    *)
        log_error "Environnement invalide: $ENVIRONMENT"
        log_error "Environnements supportés: production, staging, dev"
        exit 1
        ;;
esac

log_step "🚀 Début du déploiement BookYourCoach"
log_info "Environnement: ${ENVIRONMENT}"
log_info "Tag: ${TAG}"
log_info "Backend: ${BACKEND_IMAGE}:${TAG}"
log_info "Frontend: ${FRONTEND_IMAGE}:${TAG}"

# Vérifier que Docker et Docker Compose sont disponibles
if ! command -v docker &> /dev/null; then
    log_error "Docker n'est pas installé ou n'est pas dans le PATH"
    exit 1
fi

if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    log_error "Docker Compose n'est pas installé ou n'est pas dans le PATH"
    exit 1
fi

# Déterminer la commande Docker Compose
if docker compose version &> /dev/null; then
    DOCKER_COMPOSE="docker compose"
else
    DOCKER_COMPOSE="docker-compose"
fi

# Construction des images si demandé
if [ "$BUILD" = true ]; then
    log_step "🔨 Construction des images..."
    
    # Exporter les variables pour les scripts de build
    export IMAGE_NAME="${BACKEND_IMAGE}"
    export TAG="${TAG}"
    export PUSH="true"
    
    if ./scripts/build-all.sh; then
        log_success "✅ Images construites avec succès"
    else
        log_error "❌ Échec de la construction des images"
        exit 1
    fi
fi

# Tirage des images si demandé
if [ "$PULL" = true ]; then
    log_step "📥 Tirage des images depuis le registry..."
    
    docker pull "${BACKEND_IMAGE}:${TAG}" || {
        log_error "❌ Échec du tirage de l'image backend"
        exit 1
    }
    
    docker pull "${FRONTEND_IMAGE}:${TAG}" || {
        log_error "❌ Échec du tirage de l'image frontend"
        exit 1
    }
    
    log_success "✅ Images tirées avec succès"
fi

# Backup des volumes si demandé
if [ "$NO_BACKUP" != true ] && [ "$ENVIRONMENT" = "production" ]; then
    log_step "💾 Création d'un backup des volumes..."
    
    BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    
    # Backup des volumes Docker
    docker run --rm -v activibe_mysql_local_data:/data -v "$(pwd)/$BACKUP_DIR":/backup alpine tar czf /backup/mysql_data.tar.gz -C /data .
    docker run --rm -v activibe_neo4j_data:/data -v "$(pwd)/$BACKUP_DIR":/backup alpine tar czf /backup/neo4j_data.tar.gz -C /data .
    
    log_success "✅ Backup créé dans $BACKUP_DIR"
fi

# Arrêt des services existants
log_step "🛑 Arrêt des services existants..."
$DOCKER_COMPOSE down

# Déploiement selon l'environnement
case $ENVIRONMENT in
    production)
        log_step "🏭 Déploiement en production..."
        
        # Exporter les variables d'environnement
        export BACKEND_IMAGE="${BACKEND_IMAGE}:${TAG}"
        export FRONTEND_IMAGE="${FRONTEND_IMAGE}:${TAG}"
        
        # Déployer avec docker-compose.yml
        $DOCKER_COMPOSE up -d
        
        ;;
    staging)
        log_step "🧪 Déploiement en staging..."
        
        # Exporter les variables d'environnement
        export BACKEND_IMAGE="${BACKEND_IMAGE}:${TAG}"
        export FRONTEND_IMAGE="${FRONTEND_IMAGE}:${TAG}"
        
        # Déployer avec docker-compose.yml (même config que production)
        $DOCKER_COMPOSE up -d
        
        ;;
    dev)
        log_step "🛠️ Déploiement en développement..."
        
        # Déployer avec docker-compose.dev.yml
        $DOCKER_COMPOSE -f docker-compose.dev.yml up -d
        
        ;;
esac

# Attendre que les services démarrent
log_step "⏳ Attente du démarrage des services..."
sleep 30

# Vérification de la santé des services
log_step "🏥 Vérification de la santé des services..."

# Vérifier le backend
if curl -f http://localhost:8080/health > /dev/null 2>&1; then
    log_success "✅ Backend accessible"
else
    log_warn "⚠️ Backend non accessible (peut être normal selon la configuration)"
fi

# Vérifier le frontend
if curl -f http://localhost:3000 > /dev/null 2>&1; then
    log_success "✅ Frontend accessible"
else
    log_warn "⚠️ Frontend non accessible (peut être normal selon la configuration)"
fi

# Afficher les logs des services
log_step "📋 État des services:"
$DOCKER_COMPOSE ps

# Résumé final
log_success "🎉 Déploiement terminé avec succès!"
echo ""
log_info "Services déployés:"
log_info "  🌐 Frontend: http://localhost:3000"
log_info "  🔧 Backend:  http://localhost:8080"
log_info "  🗄️ phpMyAdmin: http://localhost:8082"
log_info "  📊 Neo4j:    http://localhost:7474"

echo ""
log_info "Commandes utiles:"
log_info "  📋 Voir les logs: $DOCKER_COMPOSE logs -f"
log_info "  🛑 Arrêter:      $DOCKER_COMPOSE down"
log_info "  🔄 Redémarrer:   $DOCKER_COMPOSE restart"
