#!/bin/bash

# =============================================================================
# Script de build complet - BookYourCoach
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
    echo "  -t, --tag TAG              Tag des images (défaut: latest)"
    echo "  -b, --backend-image NAME   Nom de l'image backend (défaut: olegrand1976/activibe-app)"
    echo "  -f, --frontend-image NAME  Nom de l'image frontend (défaut: olegrand1976/activibe-frontend)"
    echo "  -p, --push                 Pousser les images après construction"
    echo "  --backend-only             Construire seulement le backend"
    echo "  --frontend-only            Construire seulement le frontend"
    echo "  -h, --help                 Afficher cette aide"
    echo ""
    echo "Exemples:"
    echo "  $0                         # Build complet avec tag latest"
    echo "  $0 -t v1.0.0               # Build complet avec tag v1.0.0"
    echo "  $0 -p                      # Build complet et push"
    echo "  $0 --backend-only          # Build seulement le backend"
    echo "  $0 --frontend-only -p      # Build frontend et push"
}

# Parser les arguments
PUSH=false
BACKEND_ONLY=false
FRONTEND_ONLY=false

while [[ $# -gt 0 ]]; do
    case $1 in
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
        -p|--push)
            PUSH=true
            shift
            ;;
        --backend-only)
            BACKEND_ONLY=true
            shift
            ;;
        --frontend-only)
            FRONTEND_ONLY=true
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

# Vérifier les conflits
if [ "$BACKEND_ONLY" = true ] && [ "$FRONTEND_ONLY" = true ]; then
    log_error "Les options --backend-only et --frontend-only sont mutuellement exclusives"
    exit 1
fi

log_step "🚀 Début de la construction complète de BookYourCoach"
log_info "Tag: ${TAG}"
log_info "Backend: ${BACKEND_IMAGE}:${TAG}"
log_info "Frontend: ${FRONTEND_IMAGE}:${TAG}"

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "composer.json" ] || [ ! -f "frontend/package.json" ]; then
    log_error "Fichiers de configuration non trouvés. Exécutez ce script depuis la racine du projet."
    exit 1
fi

# Vérifier que Docker est disponible
if ! command -v docker &> /dev/null; then
    log_error "Docker n'est pas installé ou n'est pas dans le PATH"
    exit 1
fi

# Construction du backend
if [ "$FRONTEND_ONLY" != true ]; then
    log_step "📦 Construction du backend Laravel..."
    
    # Exporter les variables pour le script backend
    export IMAGE_NAME="${BACKEND_IMAGE}"
    export TAG="${TAG}"
    export PUSH="${PUSH}"
    
    # Exécuter le script de build backend
    if ./scripts/build-backend.sh; then
        log_success "✅ Backend construit avec succès"
    else
        log_error "❌ Échec de la construction du backend"
        exit 1
    fi
fi

# Construction du frontend
if [ "$BACKEND_ONLY" != true ]; then
    log_step "🎨 Construction du frontend Nuxt.js..."
    
    # Exporter les variables pour le script frontend
    export IMAGE_NAME="${FRONTEND_IMAGE}"
    export TAG="${TAG}"
    export PUSH="${PUSH}"
    
    # Exécuter le script de build frontend
    if ./scripts/build-frontend.sh; then
        log_success "✅ Frontend construit avec succès"
    else
        log_error "❌ Échec de la construction du frontend"
        exit 1
    fi
fi

# Résumé final
log_success "🎉 Construction complète terminée avec succès!"
echo ""
log_info "Images construites:"
if [ "$FRONTEND_ONLY" != true ]; then
    log_info "  📦 Backend:  ${BACKEND_IMAGE}:${TAG}"
fi
if [ "$BACKEND_ONLY" != true ]; then
    log_info "  🎨 Frontend: ${FRONTEND_IMAGE}:${TAG}"
fi

if [ "$PUSH" = true ]; then
    log_info "📤 Images poussées vers le registry"
fi

echo ""
log_info "Pour déployer, utilisez:"
log_info "  docker-compose up -d"
