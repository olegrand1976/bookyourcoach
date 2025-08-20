#!/bin/bash

# Script de diagnostic et installation complète pour BookYourCoach
# Ce script gère l'installation, la configuration et les tests de bout en bout

echo "🚀 BookYourCoach - Script de diagnostic et installation complète"
echo "=================================================================="

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Variables
PROJECT_DIR="/home/olivier/projets/bookyourcoach/copilot"
FRONTEND_DIR="$PROJECT_DIR/frontend"
LOG_FILE="$PROJECT_DIR/setup-diagnostic.log"
BACKEND_URL="http://localhost:8081"
FRONTEND_URL="http://localhost:3001"

# Fonction pour afficher les messages colorés
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}" | tee -a "$LOG_FILE"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}❌ $1${NC}" | tee -a "$LOG_FILE"
}

log_step() {
    echo -e "${PURPLE}🔧 $1${NC}" | tee -a "$LOG_FILE"
}

log_test() {
    echo -e "${CYAN}🧪 $1${NC}" | tee -a "$LOG_FILE"
}

# Fonction pour attendre qu'un service soit prêt
wait_for_service() {
    local url=$1
    local service_name=$2
    local max_attempts=60
    local attempt=0

    log_info "Attente que $service_name soit prêt sur $url..."
    
    while [ $attempt -lt $max_attempts ]; do
        if curl -s "$url" >/dev/null 2>&1; then
            log_success "$service_name est prêt !"
            return 0
        fi
        
        attempt=$((attempt + 1))
        echo -n "."
        sleep 1
    done
    
    log_error "$service_name n'est pas accessible après $max_attempts secondes"
    return 1
}

# Fonction pour tester la base de données
test_database() {
    log_test "Test de la base de données..."
    
    # Test de connexion MySQL
    if docker-compose exec -T mysql mysql -u root -proot_password -e "SHOW DATABASES;" >/dev/null 2>&1; then
        log_success "Base de données MySQL accessible"
        
        # Vérifier que la base bookyourcoach existe
        if docker-compose exec -T mysql mysql -u root -proot_password -e "USE bookyourcoach; SHOW TABLES;" >/dev/null 2>&1; then
            log_success "Base de données 'bookyourcoach' existe"
            
            # Compter les utilisateurs
            USER_COUNT=$(docker-compose exec -T mysql mysql -u root -proot_password -e "USE bookyourcoach; SELECT COUNT(*) FROM users;" 2>/dev/null | tail -n 1)
            log_info "Nombre d'utilisateurs en base: $USER_COUNT"
            
            # Vérifier l'utilisateur admin
            ADMIN_EXISTS=$(docker-compose exec -T mysql mysql -u root -proot_password -e "USE bookyourcoach; SELECT COUNT(*) FROM users WHERE email='admin@bookyourcoach.com';" 2>/dev/null | tail -n 1)
            if [ "$ADMIN_EXISTS" = "1" ]; then
                log_success "Utilisateur admin existe en base"
            else
                log_error "Utilisateur admin n'existe pas en base"
                return 1
            fi
        else
            log_error "Base de données 'bookyourcoach' n'existe pas"
            return 1
        fi
    else
        log_error "Impossible de se connecter à MySQL"
        return 1
    fi
    
    return 0
}

# Fonction pour tester l'API
test_api() {
    log_test "Test de l'API backend..."
    
    # Test de santé
    if curl -s "$BACKEND_URL/api/health" >/dev/null 2>&1; then
        log_success "Endpoint de santé API accessible"
    else
        log_error "Endpoint de santé API non accessible"
        return 1
    fi
    
    # Test de connexion admin
    local response=$(curl -s -X POST "$BACKEND_URL/api/auth/login" \
        -H "Content-Type: application/json" \
        -d '{"email": "admin@bookyourcoach.com", "password": "admin123"}' \
        -w "%{http_code}")
    
    local http_code=$(echo "$response" | tail -c 4)
    local body=$(echo "$response" | head -c -4)
    
    if [ "$http_code" = "200" ]; then
        log_success "Connexion API admin réussie"
        # Extraire le token pour test
        local token=$(echo "$body" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
        if [ ! -z "$token" ]; then
            log_success "Token JWT reçu: ${token:0:20}..."
            
            # Test endpoint authentifié
            local auth_response=$(curl -s -H "Authorization: Bearer $token" "$BACKEND_URL/api/admin/stats" -w "%{http_code}")
            local auth_code=$(echo "$auth_response" | tail -c 4)
            
            if [ "$auth_code" = "200" ]; then
                log_success "Endpoint admin authentifié accessible"
            else
                log_warning "Endpoint admin authentifié non accessible (code: $auth_code)"
            fi
        else
            log_warning "Token JWT non trouvé dans la réponse"
        fi
    else
        log_error "Connexion API admin échouée (code: $http_code)"
        log_error "Réponse: $body"
        return 1
    fi
    
    return 0
}

# Fonction pour tester le frontend
test_frontend() {
    log_test "Test du frontend..."
    
    # Test que Nuxt répond
    if curl -s "$FRONTEND_URL" >/dev/null 2>&1; then
        log_success "Frontend Nuxt accessible"
    else
        log_error "Frontend Nuxt non accessible"
        return 1
    fi
    
    # Test du proxy API
    local proxy_response=$(curl -s -X POST "$FRONTEND_URL/api/auth/login" \
        -H "Content-Type: application/json" \
        -d '{"email": "admin@bookyourcoach.com", "password": "admin123"}' \
        -w "%{http_code}")
    
    local proxy_code=$(echo "$proxy_response" | tail -c 4)
    
    if [ "$proxy_code" = "200" ]; then
        log_success "Proxy API frontend fonctionne"
    else
        log_error "Proxy API frontend ne fonctionne pas (code: $proxy_code)"
        return 1
    fi
    
    return 0
}

# Fonction de nettoyage complet
cleanup_all() {
    log_step "Nettoyage complet du système..."
    
    # Arrêter tous les processus
    pkill -f "npm.*dev" 2>/dev/null || true
    pkill -f "nuxt.*dev" 2>/dev/null || true
    pkill -f "vite" 2>/dev/null || true
    
    # Libérer les ports
    local PORTS=(3000 3001 5173 5174 5175 8080 8081 3306)
    for port in "${PORTS[@]}"; do
        local PID=$(lsof -ti:$port 2>/dev/null)
        if [ ! -z "$PID" ]; then
            kill -9 $PID 2>/dev/null && log_success "Port $port libéré" || true
        fi
    done
    
    # Arrêter Docker
    cd "$PROJECT_DIR"
    docker-compose down 2>/dev/null || true
    
    # Nettoyer les caches
    rm -rf "$FRONTEND_DIR/.nuxt" "$FRONTEND_DIR/.output" 2>/dev/null || true
    rm -rf "$PROJECT_DIR/.vite" "$PROJECT_DIR/node_modules/.cache" 2>/dev/null || true
    
    log_success "Nettoyage terminé"
}

# Fonction d'installation complète
install_complete() {
    log_step "Installation complète du système..."
    
    cd "$PROJECT_DIR"
    
    # 1. Démarrer les services Docker
    log_info "Démarrage des services Docker..."
    if docker-compose up -d; then
        log_success "Services Docker démarrés"
    else
        log_error "Échec du démarrage des services Docker"
        return 1
    fi
    
    # 2. Attendre que MySQL soit prêt
    wait_for_service "http://localhost:3306" "MySQL" || return 1
    
    # 3. Attendre que l'API soit prête
    wait_for_service "$BACKEND_URL/api/health" "API Backend" || return 1
    
    # 4. Exécuter les migrations
    log_info "Exécution des migrations..."
    if docker-compose exec -T app php artisan migrate --force; then
        log_success "Migrations exécutées"
    else
        log_error "Échec des migrations"
        return 1
    fi
    
    # 5. Créer l'utilisateur admin
    log_info "Création de l'utilisateur admin..."
    docker-compose exec -T app php artisan tinker --execute="
    \$user = \App\Models\User::firstOrCreate(
        ['email' => 'admin@bookyourcoach.com'],
        [
            'name' => 'Administrateur',
            'password' => \Hash::make('admin123'),
            'role' => 'admin',
            'email_verified_at' => now()
        ]
    );
    echo 'Utilisateur admin créé/mis à jour: ' . \$user->email;
    " 2>/dev/null && log_success "Utilisateur admin configuré" || log_error "Échec création utilisateur admin"
    
    # 6. Installer les dépendances frontend
    log_info "Installation des dépendances frontend..."
    cd "$FRONTEND_DIR"
    if npm install; then
        log_success "Dépendances frontend installées"
    else
        log_error "Échec installation dépendances frontend"
        return 1
    fi
    
    # 7. Démarrer le frontend
    log_info "Démarrage du frontend..."
    npm run dev > "$PROJECT_DIR/frontend.log" 2>&1 &
    FRONTEND_PID=$!
    
    # Attendre que le frontend soit prêt
    sleep 10
    wait_for_service "$FRONTEND_URL" "Frontend Nuxt" || return 1
    
    log_success "Installation complète terminée"
    return 0
}

# Fonction de diagnostic complet
diagnostic_complete() {
    log_step "Diagnostic complet du système..."
    
    local errors=0
    
    # Test Docker
    if docker --version >/dev/null 2>&1; then
        log_success "Docker installé"
    else
        log_error "Docker non installé"
        ((errors++))
    fi
    
    # Test Docker Compose
    if docker-compose --version >/dev/null 2>&1; then
        log_success "Docker Compose installé"
    else
        log_error "Docker Compose non installé"
        ((errors++))
    fi
    
    # Test Node.js
    if node --version >/dev/null 2>&1; then
        local node_version=$(node --version)
        log_success "Node.js installé: $node_version"
    else
        log_error "Node.js non installé"
        ((errors++))
    fi
    
    # Test npm
    if npm --version >/dev/null 2>&1; then
        local npm_version=$(npm --version)
        log_success "npm installé: $npm_version"
    else
        log_error "npm non installé"
        ((errors++))
    fi
    
    # Test des services
    if docker-compose ps | grep -q "Up"; then
        log_success "Services Docker en cours d'exécution"
        
        test_database || ((errors++))
        test_api || ((errors++))
    else
        log_warning "Services Docker non démarrés"
    fi
    
    # Test frontend
    if pgrep -f "nuxt.*dev" >/dev/null; then
        log_success "Frontend Nuxt en cours d'exécution"
        test_frontend || ((errors++))
    else
        log_warning "Frontend Nuxt non démarré"
    fi
    
    echo ""
    if [ $errors -eq 0 ]; then
        log_success "✨ Diagnostic complet réussi ! Aucun problème détecté."
    else
        log_error "❌ Diagnostic terminé avec $errors erreur(s)."
    fi
    
    return $errors
}

# Fonction d'affichage de l'aide
show_help() {
    echo ""
    echo "Usage: $0 [OPTION]"
    echo ""
    echo "Options:"
    echo "  install     Installation complète du système"
    echo "  diagnostic  Diagnostic complet du système"
    echo "  clean       Nettoyage complet"
    echo "  restart     Nettoyage + Installation"
    echo "  test        Tests uniquement (sans installation)"
    echo "  help        Afficher cette aide"
    echo ""
    echo "Exemples:"
    echo "  $0 restart      # Recommandé pour résoudre les problèmes"
    echo "  $0 diagnostic   # Pour identifier les problèmes"
    echo "  $0 test         # Pour tester sans réinstaller"
    echo ""
}

# Fonction principale
main() {
    local action=${1:-"help"}
    
    # Créer le fichier de log
    echo "$(date): Démarrage du script avec action: $action" > "$LOG_FILE"
    
    case $action in
        "install")
            install_complete
            ;;
        "diagnostic")
            diagnostic_complete
            ;;
        "clean")
            cleanup_all
            ;;
        "restart")
            cleanup_all
            sleep 2
            install_complete
            sleep 5
            diagnostic_complete
            ;;
        "test")
            diagnostic_complete
            ;;
        "help"|*)
            show_help
            exit 0
            ;;
    esac
    
    local exit_code=$?
    
    echo ""
    echo "📋 Résumé:"
    echo "=========="
    if [ $exit_code -eq 0 ]; then
        log_success "Opération '$action' terminée avec succès !"
        echo ""
        echo "🌐 Services disponibles:"
        echo "  Frontend: $FRONTEND_URL"
        echo "  Backend:  $BACKEND_URL"
        echo ""
        echo "🔑 Identifiants de test:"
        echo "  Email:    admin@bookyourcoach.com"
        echo "  Password: admin123"
        echo ""
        echo "📄 Log détaillé: $LOG_FILE"
    else
        log_error "Opération '$action' échouée (code: $exit_code)"
        echo ""
        echo "📋 Pour diagnostiquer:"
        echo "  $0 diagnostic"
        echo ""
        echo "🔧 Pour tout réinstaller:"
        echo "  $0 restart"
        echo ""
        echo "📄 Log détaillé: $LOG_FILE"
    fi
    
    exit $exit_code
}

# Piège pour nettoyer en cas d'interruption
trap 'log_warning "Script interrompu par l'\''utilisateur"; exit 130' INT TERM

# Exécution principale
main "$@"
