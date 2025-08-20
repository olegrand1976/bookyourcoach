#!/bin/bash

# Script de nettoyage automatique pour BookYourCoach
# Ce script arrête tous les processus de développement et libère les ports

echo "🧹 Démarrage du nettoyage des processus de développement..."

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages colorés
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

# 1. Arrêt des processus npm/node spécifiques au projet
log_info "Arrêt des processus npm/node du projet..."
pkill -f "npm.*dev" 2>/dev/null && log_success "Processus npm dev arrêtés" || log_warning "Aucun processus npm dev trouvé"
pkill -f "nuxt.*dev" 2>/dev/null && log_success "Processus nuxt dev arrêtés" || log_warning "Aucun processus nuxt dev trouvé"
pkill -f "vite.*bookyourcoach" 2>/dev/null && log_success "Processus vite du projet arrêtés" || log_warning "Aucun processus vite du projet trouvé"

# 2. Libération des ports spécifiques
log_info "Libération des ports de développement..."
PORTS=(3000 3001 5173 5174 5175 4001 8080 8081)

for port in "${PORTS[@]}"; do
    PID=$(lsof -ti:$port 2>/dev/null)
    if [ ! -z "$PID" ]; then
        kill -9 $PID 2>/dev/null && log_success "Port $port libéré (PID: $PID)" || log_error "Impossible de libérer le port $port"
    else
        log_info "Port $port déjà libre"
    fi
done

# 3. Nettoyage des processus node orphelins dans le dossier du projet
log_info "Nettoyage des processus node orphelins..."
PROJECT_DIR="/home/olivier/projets/bookyourcoach/copilot"
ps aux | grep node | grep "$PROJECT_DIR" | grep -v grep | awk '{print $2}' | xargs -r kill -9 2>/dev/null
log_success "Processus node orphelins nettoyés"

# 4. Nettoyage des fichiers temporaires
log_info "Nettoyage des fichiers temporaires..."
cd "$PROJECT_DIR"

# Nettoyage frontend
if [ -d "frontend" ]; then
    cd frontend
    rm -rf .nuxt/ .output/ node_modules/.vite/ node_modules/.cache/ 2>/dev/null
    log_success "Cache frontend nettoyé"
    cd ..
fi

# Nettoyage racine
rm -rf .vite/ node_modules/.cache/ 2>/dev/null
log_success "Cache racine nettoyé"

# 5. Vérification finale
log_info "Vérification finale des ports..."
sleep 2

for port in "${PORTS[@]}"; do
    if lsof -ti:$port >/dev/null 2>&1; then
        log_warning "Port $port encore occupé"
    else
        log_success "Port $port libre"
    fi
done

# 6. Affichage du statut des services Docker
log_info "Statut des services Docker:"
docker-compose ps 2>/dev/null || log_warning "Docker Compose non disponible"

echo ""
log_success "🎉 Nettoyage terminé ! Vous pouvez maintenant redémarrer vos services de développement."
echo ""
echo -e "${BLUE}Pour démarrer le frontend:${NC}"
echo "  cd frontend && npm run dev"
echo ""
echo -e "${BLUE}Pour démarrer le backend:${NC}"
echo "  docker-compose up -d  # ou php artisan serve"
echo ""
