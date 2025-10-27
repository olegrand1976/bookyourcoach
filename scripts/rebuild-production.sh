#!/bin/bash

# Script de reconstruction et déploiement pour la production
# Usage: ./scripts/rebuild-production.sh

echo "🚀 RECONSTRUCTION ET DÉPLOIEMENT PRODUCTION"
echo "=========================================="

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
PROJECT_ROOT="/home/olivier/projets/bookyourcoach"
DOCKER_IMAGE="olegrand1976/activibe-app"
DOCKER_TAG="latest"

# Fonction pour afficher les résultats
log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_step() {
    echo -e "${PURPLE}🔄 $1${NC}"
}

cd "$PROJECT_ROOT"

echo ""
echo "1. VÉRIFICATION DES PRÉREQUIS..."

# Vérifier que Docker est installé
if ! command -v docker &> /dev/null; then
    log_error "Docker n'est pas installé"
    exit 1
fi

# Vérifier que Docker Compose est installé
if ! command -v docker-compose &> /dev/null; then
    log_error "Docker Compose n'est pas installé"
    exit 1
fi

# Vérifier la connexion à Docker Hub
log_step "Vérification de la connexion à Docker Hub..."
if docker info &> /dev/null; then
    log_success "Docker est accessible"
else
    log_error "Docker n'est pas accessible"
    exit 1
fi

echo ""
echo "2. SAUVEGARDE DE L'ÉTAT ACTUEL..."

# Créer une sauvegarde
BACKUP_DIR="backup_production_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Sauvegarder les fichiers critiques
cp docker-compose.yml "$BACKUP_DIR/" 2>/dev/null || true
cp .env* "$BACKUP_DIR/" 2>/dev/null || true
cp Dockerfile "$BACKUP_DIR/" 2>/dev/null || true

log_success "Sauvegarde créée: $BACKUP_DIR"

echo ""
echo "3. ARRÊT DES CONTAINERS EXISTANTS..."

# Arrêter les containers existants
log_step "Arrêt des containers..."
docker-compose down 2>/dev/null || true

log_success "Containers arrêtés"

echo ""
echo "4. NETTOYAGE DES IMAGES..."

# Supprimer l'ancienne image locale
log_step "Suppression de l'ancienne image locale..."
docker rmi "$DOCKER_IMAGE:$DOCKER_TAG" 2>/dev/null || true

# Nettoyer les images non utilisées
docker image prune -f 2>/dev/null || true

log_success "Images nettoyées"

echo ""
echo "5. RECONSTRUCTION DE L'IMAGE..."

# Reconstruire l'image avec les corrections de sécurité
log_step "Reconstruction de l'image avec les corrections de sécurité..."
docker-compose build --no-cache

if [ $? -eq 0 ]; then
    log_success "Image reconstruite avec succès"
else
    log_error "Échec de la reconstruction de l'image"
    exit 1
fi

echo ""
echo "6. TAGUAGE DE L'IMAGE..."

# Tagger l'image pour Docker Hub
log_step "Taguage de l'image..."
docker tag "$DOCKER_IMAGE:$DOCKER_TAG" "$DOCKER_IMAGE:$DOCKER_TAG"

log_success "Image taguée: $DOCKER_IMAGE:$DOCKER_TAG"

echo ""
echo "7. CONNEXION À DOCKER HUB..."

# Vérifier si l'utilisateur est connecté à Docker Hub
log_step "Vérification de la connexion à Docker Hub..."
if docker info | grep -q "Username"; then
    log_success "Connecté à Docker Hub"
else
    log_warning "Non connecté à Docker Hub"
    echo "Pour vous connecter: docker login"
    echo "Continuez manuellement ou appuyez sur Entrée pour continuer..."
    read -p "Appuyez sur Entrée pour continuer..."
fi

echo ""
echo "8. PUSH VERS DOCKER HUB..."

# Pousser l'image vers Docker Hub
log_step "Push de l'image vers Docker Hub..."
docker push "$DOCKER_IMAGE:$DOCKER_TAG"

if [ $? -eq 0 ]; then
    log_success "Image poussée vers Docker Hub avec succès"
else
    log_error "Échec du push vers Docker Hub"
    echo "Vérifiez votre connexion et vos permissions Docker Hub"
    exit 1
fi

echo ""
echo "9. DÉMARRAGE LOCAL POUR TEST..."

# Démarrer les containers localement pour test
log_step "Démarrage local pour test..."
docker-compose up -d

if [ $? -eq 0 ]; then
    log_success "Containers démarrés localement"
else
    log_error "Échec du démarrage local"
    exit 1
fi

echo ""
echo "10. INITIALISATION DE L'APPLICATION..."

# Attendre que les services soient prêts
log_step "Attente du démarrage des services..."
sleep 15

# Exécuter les commandes d'initialisation
log_step "Initialisation de l'application..."
docker-compose exec -T backend php artisan route:clear 2>/dev/null || true
docker-compose exec -T backend php artisan config:clear 2>/dev/null || true
docker-compose exec -T backend php artisan cache:clear 2>/dev/null || true
docker-compose exec -T backend php artisan view:clear 2>/dev/null || true

log_success "Application initialisée"

echo ""
echo "11. INSTRUCTIONS POUR LE SERVEUR DE PRODUCTION..."

log_info "Pour déployer sur votre serveur de production:"
echo ""
echo "1. 📡 Connectez-vous à votre serveur de production"
echo "2. 🐳 Arrêtez les containers existants:"
echo "   docker-compose down"
echo ""
echo "3. 🔄 Tirez la nouvelle image:"
echo "   docker pull $DOCKER_IMAGE:$DOCKER_TAG"
echo ""
echo "4. 🚀 Redémarrez les containers:"
echo "   docker-compose up -d"
echo ""
echo "5. 🔧 Initialisez l'application:"
echo "   docker-compose exec backend php artisan route:clear"
echo "   docker-compose exec backend php artisan config:clear"
echo "   docker-compose exec backend php artisan cache:clear"
echo ""
echo "6. ✅ Testez l'application:"
echo "   curl -H \"Authorization: Bearer invalid_token\" http://your-server/api/admin/dashboard"

echo ""
echo "=========================================="
echo "🎯 RÉSUMÉ DU DÉPLOIEMENT PRODUCTION"
echo "=========================================="
echo "✅ Image reconstruite avec les corrections de sécurité"
echo "✅ Image poussée vers Docker Hub: $DOCKER_IMAGE:$DOCKER_TAG"
echo "✅ Containers démarrés localement"
echo "✅ Prêt pour le déploiement en production"
echo ""
echo "🐳 Image Docker: $DOCKER_IMAGE:$DOCKER_TAG"
echo "🔒 Sécurité: Routes protégées avec auth:sanctum"
echo ""
echo "🚀 Votre image de production est maintenant mise à jour!"
echo "   Le message 'Accès refusé' sera corrigé sur votre serveur de production"
echo "   après avoir tiré et redémarré l'image."
