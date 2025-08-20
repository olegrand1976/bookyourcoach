#!/bin/bash

# Script de démarrage propre pour BookYourCoach
# Ce script nettoie d'abord, puis démarre les services

echo "🚀 Démarrage propre de BookYourCoach..."

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

PROJECT_DIR="/home/olivier/projets/bookyourcoach/copilot"

# 1. Nettoyage automatique
log_info "Étape 1: Nettoyage automatique..."
cd "$PROJECT_DIR"
./cleanup.sh

echo ""
log_info "Étape 2: Vérification des prérequis..."

# 2. Vérification que Docker est lancé
if ! docker info >/dev/null 2>&1; then
    log_error "Docker n'est pas démarré. Veuillez démarrer Docker d'abord."
    exit 1
fi
log_success "Docker est disponible"

# 3. Démarrage des services Docker
log_info "Étape 3: Démarrage des services Docker..."
docker-compose up -d
if [ $? -eq 0 ]; then
    log_success "Services Docker démarrés"
else
    log_error "Erreur lors du démarrage des services Docker"
    exit 1
fi

# 4. Attendre que les services soient prêts
log_info "Attente que l'API soit prête..."
max_attempts=30
attempt=0

while [ $attempt -lt $max_attempts ]; do
    if curl -s http://localhost:8081/api/health >/dev/null 2>&1; then
        log_success "API disponible sur http://localhost:8081"
        break
    fi
    
    attempt=$((attempt + 1))
    echo -n "."
    sleep 1
done

if [ $attempt -eq $max_attempts ]; then
    log_error "L'API n'est pas accessible après 30 secondes"
    echo "Vérifiez les logs Docker: docker-compose logs"
    exit 1
fi

echo ""

# 5. Démarrage du frontend
log_info "Étape 4: Démarrage du frontend..."
cd "$PROJECT_DIR/frontend"

# Vérifier que les dépendances sont installées
if [ ! -d "node_modules" ]; then
    log_info "Installation des dépendances npm..."
    npm install
fi

log_info "Démarrage de Nuxt en mode développement..."
echo ""
log_success "🎉 Tout est prêt !"
echo ""
echo -e "${GREEN}Services disponibles:${NC}"
echo -e "  📱 Frontend: ${BLUE}http://localhost:3001${NC}"
echo -e "  🔧 API:      ${BLUE}http://localhost:8081${NC}"
echo -e "  🗄️  Database: ${BLUE}localhost:3306${NC}"
echo ""
echo -e "${YELLOW}Identifiants de test:${NC}"
echo -e "  📧 Email:    ${BLUE}admin@bookyourcoach.com${NC}"
echo -e "  🔑 Password: ${BLUE}admin123${NC}"
echo ""
echo -e "${BLUE}Démarrage du serveur Nuxt...${NC}"

# Démarrer Nuxt (ce processus va rester en premier plan)
npm run dev
