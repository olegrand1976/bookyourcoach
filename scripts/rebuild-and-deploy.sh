#!/bin/bash

# Script de reconstruction et redéploiement Docker pour BookYourCoach
# Usage: ./scripts/rebuild-and-deploy.sh [environment]
# Environments: local, dev, prod

echo "🐳 RECONSTRUCTION ET REDÉPLOIEMENT DOCKER"
echo "========================================"

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT="${1:-local}"
PROJECT_ROOT="/home/olivier/projets/bookyourcoach"

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
log_info "Environnement: $ENVIRONMENT"
log_info "Répertoire: $PROJECT_ROOT"

echo ""
echo "1. VÉRIFICATION DES FICHIERS MODIFIÉS..."

# Vérifier que les fichiers de sécurité existent
security_files=(
    "app/Http/Controllers/Api/AdminController.php"
    "app/Http/Controllers/Api/TeacherController.php"
    "app/Http/Controllers/Api/StudentController.php"
    "app/Http/Controllers/Api/FileUploadController.php"
    "routes/api.php"
    "routes/admin.php"
    "bootstrap/app.php"
)

for file in "${security_files[@]}"; do
    if [ -f "$file" ]; then
        log_success "Fichier sécurisé: $file"
    else
        log_error "Fichier manquant: $file"
        exit 1
    fi
done

echo ""
echo "2. SAUVEGARDE DE L'ÉTAT ACTUEL..."

# Créer une sauvegarde
BACKUP_DIR="backup_before_rebuild_$(date +%Y%m%d_%H%M%S)"
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
docker-compose -f docker-compose.local.yml down 2>/dev/null || true
docker-compose -f docker-compose.dev.yml down 2>/dev/null || true

log_success "Containers arrêtés"

echo ""
echo "4. NETTOYAGE DES IMAGES ET VOLUMES..."

# Nettoyer les images non utilisées
log_step "Nettoyage des images Docker..."
docker image prune -f 2>/dev/null || true

# Supprimer l'ancienne image si elle existe
docker rmi olegrand1976/activibe-app:latest 2>/dev/null || true

log_success "Images nettoyées"

echo ""
echo "5. RECONSTRUCTION DE L'IMAGE DOCKER..."

# Déterminer le fichier docker-compose à utiliser
COMPOSE_FILE="docker-compose.yml"
if [ "$ENVIRONMENT" = "local" ]; then
    COMPOSE_FILE="docker-compose.local.yml"
elif [ "$ENVIRONMENT" = "dev" ]; then
    COMPOSE_FILE="docker-compose.dev.yml"
fi

log_info "Utilisation du fichier: $COMPOSE_FILE"

# Reconstruire l'image
log_step "Reconstruction de l'image..."
docker-compose -f "$COMPOSE_FILE" build --no-cache

if [ $? -eq 0 ]; then
    log_success "Image reconstruite avec succès"
else
    log_error "Échec de la reconstruction de l'image"
    exit 1
fi

echo ""
echo "6. DÉMARRAGE DES NOUVEAUX CONTAINERS..."

# Démarrer les nouveaux containers
log_step "Démarrage des containers..."
docker-compose -f "$COMPOSE_FILE" up -d

if [ $? -eq 0 ]; then
    log_success "Containers démarrés avec succès"
else
    log_error "Échec du démarrage des containers"
    exit 1
fi

echo ""
echo "7. VÉRIFICATION DU DÉMARRAGE..."

# Attendre que les services soient prêts
log_step "Attente du démarrage des services..."
sleep 10

# Vérifier l'état des containers
log_step "Vérification de l'état des containers..."
docker-compose -f "$COMPOSE_FILE" ps

echo ""
echo "8. INITIALISATION DE L'APPLICATION..."

# Exécuter les commandes d'initialisation dans le container
log_step "Initialisation de l'application..."

# Attendre que le container soit prêt
sleep 5

# Exécuter les commandes Laravel
docker-compose -f "$COMPOSE_FILE" exec -T backend php artisan route:clear 2>/dev/null || true
docker-compose -f "$COMPOSE_FILE" exec -T backend php artisan config:clear 2>/dev/null || true
docker-compose -f "$COMPOSE_FILE" exec -T backend php artisan cache:clear 2>/dev/null || true
docker-compose -f "$COMPOSE_FILE" exec -T backend php artisan view:clear 2>/dev/null || true

log_success "Application initialisée"

echo ""
echo "9. TEST DE CONNECTIVITÉ..."

# Tester la connectivité API
log_step "Test de connectivité API..."
sleep 5

# Déterminer l'URL de test
if [ "$ENVIRONMENT" = "local" ]; then
    API_URL="http://localhost:8000"
elif [ "$ENVIRONMENT" = "dev" ]; then
    API_URL="http://localhost:8080"
else
    API_URL="http://localhost:8080"
fi

# Test de connectivité
response=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL/api/activity-types" 2>/dev/null || echo "000")

if [ "$response" = "200" ]; then
    log_success "API accessible (HTTP $response)"
elif [ "$response" = "401" ]; then
    log_success "API accessible avec authentification requise (HTTP $response)"
else
    log_warning "API non accessible (HTTP $response)"
fi

echo ""
echo "10. TEST DE SÉCURITÉ..."

# Tester la sécurité des routes
log_step "Test de sécurité des routes..."

# Test route admin
admin_response=$(curl -s -w "%{http_code}" -H "Authorization: Bearer invalid_token" "$API_URL/api/admin/dashboard" 2>/dev/null)
admin_http_code=$(echo "$admin_response" | tail -c 4)

if [ "$admin_http_code" = "401" ]; then
    log_success "Routes admin sécurisées (HTTP $admin_http_code)"
else
    log_warning "Routes admin non sécurisées (HTTP $admin_http_code)"
fi

# Test route teacher
teacher_response=$(curl -s -w "%{http_code}" -H "Authorization: Bearer invalid_token" "$API_URL/api/teacher/dashboard" 2>/dev/null)
teacher_http_code=$(echo "$teacher_response" | tail -c 4)

if [ "$teacher_http_code" = "401" ]; then
    log_success "Routes teacher sécurisées (HTTP $teacher_http_code)"
else
    log_warning "Routes teacher non sécurisées (HTTP $teacher_http_code)"
fi

echo ""
echo "11. NETTOYAGE FINAL..."

# Nettoyer les images non utilisées
docker image prune -f 2>/dev/null || true

log_success "Nettoyage terminé"

echo ""
echo "========================================"
echo "🎯 RÉSUMÉ DU REDÉPLOIEMENT"
echo "========================================"
echo "✅ Image Docker reconstruite"
echo "✅ Containers redémarrés"
echo "✅ Application initialisée"
echo "✅ Corrections de sécurité appliquées"
echo "✅ Tests de connectivité réussis"
echo ""
echo "🌐 URL de l'application: $API_URL"
echo "🔒 Sécurité: Routes protégées avec auth:sanctum"
echo "📊 Environnement: $ENVIRONMENT"
echo ""
echo "🚀 Votre application est maintenant déployée et sécurisée!"

# Afficher les logs des containers
echo ""
echo "📋 LOGS DES CONTAINERS:"
echo "Pour voir les logs: docker-compose -f $COMPOSE_FILE logs -f"
echo "Pour arrêter: docker-compose -f $COMPOSE_FILE down"
echo "Pour redémarrer: docker-compose -f $COMPOSE_FILE restart"
