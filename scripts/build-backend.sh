#!/bin/bash

# =============================================================================
# Script de build Backend Laravel - BookYourCoach
# =============================================================================

set -e

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
IMAGE_NAME=${IMAGE_NAME:-"olegrand1976/activibe-app"}
TAG=${TAG:-"latest"}
FULL_IMAGE_NAME="${IMAGE_NAME}:${TAG}"

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

# Fonction d'aide
show_help() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -t, --tag TAG        Tag de l'image (défaut: latest)"
    echo "  -i, --image NAME     Nom de l'image (défaut: olegrand1976/activibe-app)"
    echo "  -p, --push           Pousser l'image après construction"
    echo "  -h, --help           Afficher cette aide"
    echo ""
    echo "Exemples:"
    echo "  $0                           # Build avec tag latest"
    echo "  $0 -t v1.0.0                 # Build avec tag v1.0.0"
    echo "  $0 -p                        # Build et push"
    echo "  $0 -t v1.0.0 -p              # Build v1.0.0 et push"
}

# Parser les arguments
PUSH=false
while [[ $# -gt 0 ]]; do
    case $1 in
        -t|--tag)
            TAG="$2"
            shift 2
            ;;
        -i|--image)
            IMAGE_NAME="$2"
            shift 2
            ;;
        -p|--push)
            PUSH=true
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

FULL_IMAGE_NAME="${IMAGE_NAME}:${TAG}"

log_step "Début de la construction du backend Laravel"
log_info "Image: ${FULL_IMAGE_NAME}"

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "composer.json" ]; then
    log_error "composer.json non trouvé. Exécutez ce script depuis la racine du projet."
    exit 1
fi

# Vérifier que Docker est disponible
if ! command -v docker &> /dev/null; then
    log_error "Docker n'est pas installé ou n'est pas dans le PATH"
    exit 1
fi

# Construire l'image
log_step "Construction de l'image Docker..."
docker build \
    --tag "${FULL_IMAGE_NAME}" \
    --file Dockerfile \
    --progress=plain \
    .

if [ $? -eq 0 ]; then
    log_info "✅ Image construite avec succès: ${FULL_IMAGE_NAME}"
else
    log_error "❌ Échec de la construction de l'image"
    exit 1
fi

# Tester l'image si demandé
log_step "Test de l'image..."
docker run --rm -d --name test-backend -p 8080:80 "${FULL_IMAGE_NAME}"

# Attendre que le conteneur démarre
sleep 5

# Tester la santé de l'application
if curl -f http://localhost:8080 > /dev/null 2>&1; then
    log_info "✅ Application backend accessible"
else
    log_warn "⚠️ Application backend non accessible (peut être normal selon la configuration)"
fi

# Nettoyer le conteneur de test
docker stop test-backend 2>/dev/null || true

# Pousser l'image si demandé
if [ "$PUSH" = true ]; then
    log_step "Poussage de l'image vers le registry..."
    docker push "${FULL_IMAGE_NAME}"
    
    if [ $? -eq 0 ]; then
        log_info "✅ Image poussée avec succès: ${FULL_IMAGE_NAME}"
    else
        log_error "❌ Échec du poussage de l'image"
        exit 1
    fi
fi

log_info "🎉 Construction du backend terminée avec succès!"
log_info "Image: ${FULL_IMAGE_NAME}"
