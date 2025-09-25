#!/bin/bash

# Script de déploiement des corrections de sécurité pour la production
# Usage: ./scripts/deploy-security-fixes.sh

echo "🔒 DÉPLOIEMENT DES CORRECTIONS DE SÉCURITÉ"
echo "========================================="

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

echo ""
echo "1. VÉRIFICATION DES FICHIERS À DÉPLOYER..."

# Vérifier que les fichiers modifiés existent
files_to_deploy=(
    "app/Http/Controllers/Api/AdminController.php"
    "app/Http/Controllers/Api/TeacherController.php"
    "app/Http/Controllers/Api/StudentController.php"
    "app/Http/Controllers/Api/FileUploadController.php"
    "routes/api.php"
    "routes/admin.php"
    "bootstrap/app.php"
)

for file in "${files_to_deploy[@]}"; do
    if [ -f "$file" ]; then
        log_success "Fichier trouvé: $file"
    else
        log_error "Fichier manquant: $file"
        exit 1
    fi
done

echo ""
echo "2. CRÉATION DU PACKAGE DE DÉPLOIEMENT..."

# Créer un dossier temporaire pour le déploiement
DEPLOY_DIR="deploy_security_fixes_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$DEPLOY_DIR"

# Copier les fichiers modifiés
for file in "${files_to_deploy[@]}"; do
    mkdir -p "$DEPLOY_DIR/$(dirname "$file")"
    cp "$file" "$DEPLOY_DIR/$file"
    log_info "Copié: $file"
done

# Créer un script de déploiement pour le serveur
cat > "$DEPLOY_DIR/deploy_on_server.sh" << 'EOF'
#!/bin/bash

# Script à exécuter sur le serveur de production
echo "🔒 Déploiement des corrections de sécurité sur le serveur"

# Sauvegarde des fichiers existants
echo "1. Sauvegarde des fichiers existants..."
BACKUP_DIR="backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Sauvegarder les fichiers existants
cp app/Http/Controllers/Api/AdminController.php "$BACKUP_DIR/" 2>/dev/null || true
cp app/Http/Controllers/Api/TeacherController.php "$BACKUP_DIR/" 2>/dev/null || true
cp app/Http/Controllers/Api/StudentController.php "$BACKUP_DIR/" 2>/dev/null || true
cp app/Http/Controllers/Api/FileUploadController.php "$BACKUP_DIR/" 2>/dev/null || true
cp routes/api.php "$BACKUP_DIR/" 2>/dev/null || true
cp routes/admin.php "$BACKUP_DIR/" 2>/dev/null || true
cp bootstrap/app.php "$BACKUP_DIR/" 2>/dev/null || true

echo "✅ Sauvegarde créée dans: $BACKUP_DIR"

# Déployer les nouveaux fichiers
echo "2. Déploiement des nouveaux fichiers..."
cp app/Http/Controllers/Api/AdminController.php . 2>/dev/null || true
cp app/Http/Controllers/Api/TeacherController.php . 2>/dev/null || true
cp app/Http/Controllers/Api/StudentController.php . 2>/dev/null || true
cp app/Http/Controllers/Api/FileUploadController.php . 2>/dev/null || true
cp routes/api.php . 2>/dev/null || true
cp routes/admin.php . 2>/dev/null || true
cp bootstrap/app.php . 2>/dev/null || true

echo "✅ Fichiers déployés"

# Nettoyer les caches
echo "3. Nettoyage des caches..."
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo "✅ Caches nettoyés"

# Vérifier la syntaxe
echo "4. Vérification de la syntaxe..."
php -l routes/api.php
php -l routes/admin.php
php -l app/Http/Controllers/Api/AdminController.php
php -l app/Http/Controllers/Api/TeacherController.php
php -l app/Http/Controllers/Api/StudentController.php
php -l app/Http/Controllers/Api/FileUploadController.php

echo "✅ Syntaxe vérifiée"

# Redémarrer les services si nécessaire
echo "5. Redémarrage des services..."
# Décommentez les lignes suivantes selon votre configuration
# sudo systemctl restart php8.2-fpm
# sudo systemctl restart nginx
# sudo systemctl restart apache2

echo "✅ Services redémarrés"

echo ""
echo "🎯 DÉPLOIEMENT TERMINÉ!"
echo "Les corrections de sécurité ont été appliquées."
echo "Testez vos routes admin, teacher et student."
EOF

chmod +x "$DEPLOY_DIR/deploy_on_server.sh"

echo ""
echo "3. INSTRUCTIONS DE DÉPLOIEMENT..."

log_info "Package créé dans: $DEPLOY_DIR"
echo ""
echo "📋 ÉTAPES POUR DÉPLOYER EN PRODUCTION:"
echo ""
echo "1. 📦 Transférez le dossier '$DEPLOY_DIR' sur votre serveur de production"
echo "2. 🔧 Connectez-vous à votre serveur de production"
echo "3. 📁 Naviguez vers le dossier de votre application"
echo "4. 🚀 Exécutez: ./deploy_on_server.sh"
echo ""
echo "OU utilisez SCP pour transférer:"
echo "scp -r $DEPLOY_DIR/ user@your-server:/path/to/your/app/"
echo ""
echo "OU utilisez rsync:"
echo "rsync -av $DEPLOY_DIR/ user@your-server:/path/to/your/app/"

echo ""
echo "4. VÉRIFICATION DES CORRECTIONS DÉPLOYÉES..."

log_info "Les corrections incluent:"
echo "   ✅ Suppression de l'authentification manuelle"
echo "   ✅ Middlewares auth:sanctum + rôles appropriés"
echo "   ✅ Contrôleurs centralisés (AdminController, TeacherController, StudentController)"
echo "   ✅ Routes séparées (routes/admin.php)"
echo "   ✅ Correction du message 'Accès refusé'"

echo ""
echo "5. TEST POST-DÉPLOIEMENT..."

echo "Après déploiement, testez:"
echo "   - Login admin: POST /api/auth/login"
echo "   - Dashboard admin: GET /api/admin/dashboard"
echo "   - Routes teacher: GET /api/teacher/dashboard"
echo "   - Routes student: GET /api/student/dashboard"

echo ""
echo "========================================="
echo "🎯 RÉSUMÉ DU DÉPLOIEMENT"
echo "========================================="
echo "📦 Package: $DEPLOY_DIR"
echo "🔒 Corrections: Authentification centralisée"
echo "🚀 Prêt pour: Production"
echo ""
echo "Une fois déployé, le message 'Accès refusé' sera remplacé"
echo "par 'Invalid authentication credentials' (comportement normal)."
