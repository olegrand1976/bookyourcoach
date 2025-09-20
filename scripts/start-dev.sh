#!/bin/bash

# =============================================================================
# Script de démarrage développement - BookYourCoach
# =============================================================================

set -e

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

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
    echo "  --build                   Construire les images avant démarrage"
    echo "  --clean                   Nettoyer les volumes avant démarrage"
    echo "  --logs                    Afficher les logs après démarrage"
    echo "  -h, --help                Afficher cette aide"
    echo ""
    echo "Exemples:"
    echo "  $0                        # Démarrage simple"
    echo "  $0 --build                # Build et démarrage"
    echo "  $0 --clean --build        # Nettoyage, build et démarrage"
}

# Parser les arguments
BUILD=false
CLEAN=false
SHOW_LOGS=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --build)
            BUILD=true
            shift
            ;;
        --clean)
            CLEAN=true
            shift
            ;;
        --logs)
            SHOW_LOGS=true
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

log_step "🛠️ Démarrage de l'environnement de développement BookYourCoach"

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

# Nettoyage si demandé
if [ "$CLEAN" = true ]; then
    log_step "🧹 Nettoyage des volumes et conteneurs..."
    
    $DOCKER_COMPOSE -f docker-compose.dev.yml down -v
    docker system prune -f
    
    log_success "✅ Nettoyage terminé"
fi

# Construction des images si demandé
if [ "$BUILD" = true ]; then
    log_step "🔨 Construction des images de développement..."
    
    $DOCKER_COMPOSE -f docker-compose.dev.yml build
    
    log_success "✅ Images construites"
fi

# Vérifier que les fichiers de configuration existent
if [ ! -f ".env.local" ]; then
    log_warn "⚠️ Fichier .env.local non trouvé"
    log_info "Création d'un fichier .env.local basique..."
    
    cat > .env.local << EOF
# Configuration de développement
APP_NAME=BookYourCoach
APP_ENV=local
APP_KEY=base64:$(openssl rand -base64 32)
APP_DEBUG=true
APP_URL=http://localhost:8080

# Base de données
DB_CONNECTION=mysql
DB_HOST=mysql-dev
DB_PORT=3306
DB_DATABASE=book_your_coach_dev
DB_USERNAME=activibe_user
DB_PASSWORD=activibe_password

# Neo4j
NEO4J_PASSWORD=development

# Frontend
FRONTEND_API_BASE=http://localhost:8001/api
FRONTEND_URL=http://localhost:3000
EOF
    
    log_success "✅ Fichier .env.local créé"
fi

# Démarrage des services
log_step "🚀 Démarrage des services de développement..."

$DOCKER_COMPOSE -f docker-compose.dev.yml up -d

# Attendre que les services démarrent
log_step "⏳ Attente du démarrage des services..."
sleep 20

# Vérification de la santé des services
log_step "🏥 Vérification de la santé des services..."

# Vérifier le backend
if curl -f http://localhost:8080 > /dev/null 2>&1; then
    log_success "✅ Backend accessible sur http://localhost:8080"
else
    log_warn "⚠️ Backend non accessible (peut être normal au premier démarrage)"
fi

# Vérifier le frontend
if curl -f http://localhost:3000 > /dev/null 2>&1; then
    log_success "✅ Frontend accessible sur http://localhost:3000"
else
    log_warn "⚠️ Frontend non accessible (peut être normal au premier démarrage)"
fi

# Vérifier Neo4j
if curl -f http://localhost:7475 > /dev/null 2>&1; then
    log_success "✅ Neo4j accessible sur http://localhost:7475"
else
    log_warn "⚠️ Neo4j non accessible"
fi

# Afficher les logs si demandé
if [ "$SHOW_LOGS" = true ]; then
    log_step "📋 Affichage des logs..."
    $DOCKER_COMPOSE -f docker-compose.dev.yml logs -f
else
    # Afficher l'état des services
    log_step "📋 État des services:"
    $DOCKER_COMPOSE -f docker-compose.dev.yml ps
    
    # Résumé final
    log_success "🎉 Environnement de développement démarré!"
    echo ""
    log_info "Services disponibles:"
    log_info "  🌐 Frontend: http://localhost:3000"
    log_info "  🔧 Backend:  http://localhost:8080"
    log_info "  📊 Neo4j:    http://localhost:7475"
    log_info "  🗄️ MySQL:    localhost:3309"
    
    echo ""
    log_info "Commandes utiles:"
    log_info "  📋 Voir les logs: $DOCKER_COMPOSE -f docker-compose.dev.yml logs -f"
    log_info "  🛑 Arrêter:      $DOCKER_COMPOSE -f docker-compose.dev.yml down"
    log_info "  🔄 Redémarrer:   $DOCKER_COMPOSE -f docker-compose.dev.yml restart"
    log_info "  🧹 Nettoyer:     $DOCKER_COMPOSE -f docker-compose.dev.yml down -v"
fi
