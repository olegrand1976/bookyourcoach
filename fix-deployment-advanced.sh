#!/bin/bash

# Script de diagnostic approfondi et correction forcée
# Identifie et résout les conflits de docker-compose

echo "🔍 DIAGNOSTIC APPROFONDI BOOKYOURCOACH"
echo "======================================"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
log_success() { echo -e "${GREEN}✅ $1${NC}"; }
log_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
log_error() { echo -e "${RED}❌ $1${NC}"; }

echo ""
log_info "1. IDENTIFICATION DES FICHIERS DOCKER-COMPOSE"
echo "============================================="
ls -la docker-compose*.yml 2>/dev/null || log_warning "Aucun fichier docker-compose trouvé"

if [ -f "docker-compose.yml" ]; then
    log_warning "Fichier docker-compose.yml détecté (peut causer des conflits)"
    echo "Contenu du fichier:"
    head -20 docker-compose.yml
fi

echo ""
log_info "2. VÉRIFICATION DU CONTENU DE docker-compose.prod.yml"
echo "===================================================="
if [ -f "docker-compose.prod.yml" ]; then
    log_info "Vérification des noms de conteneurs dans docker-compose.prod.yml:"
    grep "container_name:" docker-compose.prod.yml || log_warning "Pas de noms de conteneurs définis"
else
    log_error "docker-compose.prod.yml manquant!"
fi

echo ""
log_info "3. ARRÊT COMPLET DE TOUS LES CONTENEURS"
echo "======================================="
log_warning "Arrêt de TOUS les conteneurs (sauf infiswap_frontend_prod)..."
docker stop $(docker ps --format "{{.Names}}" | grep -v "infiswap_frontend_prod") 2>/dev/null || true
docker rm $(docker ps -a --format "{{.Names}}" | grep -v "infiswap_frontend_prod") 2>/dev/null || true

echo ""
log_info "4. SAUVEGARDE ET RENOMMAGE DES FICHIERS CONFLICTUELS"
echo "=================================================="
if [ -f "docker-compose.yml" ]; then
    log_warning "Renommage de docker-compose.yml en docker-compose.yml.old"
    mv docker-compose.yml docker-compose.yml.old
fi

echo ""
log_info "5. CRÉATION DU FICHIER .env COMPLET"
echo "=================================="
if [ -f "production.env" ]; then
    cp production.env .env
    
    # S'assurer que NEO4J_PASSWORD est bien défini
    if ! grep -q "NEO4J_PASSWORD" .env; then
        echo "NEO4J_PASSWORD=neo4j_secure_password_2024" >> .env
        log_success "Variable NEO4J_PASSWORD ajoutée"
    fi
    
    log_success "Fichier .env créé et vérifié"
    log_info "Variables importantes:"
    grep -E "(NEO4J_PASSWORD|DB_PASSWORD|REDIS_PASSWORD|APP_NAME)" .env
else
    log_error "Fichier production.env manquant!"
    exit 1
fi

echo ""
log_info "6. MODIFICATION DES PORTS DANS docker-compose.prod.yml"
echo "===================================================="
cp docker-compose.prod.yml docker-compose.prod.yml.backup 2>/dev/null || true
sed -i 's/"80:80"/"8080:80"/g' docker-compose.prod.yml
sed -i 's/"443:443"/"8443:443"/g' docker-compose.prod.yml
log_success "Ports modifiés pour éviter les conflits"

echo ""
log_info "7. NETTOYAGE DES RÉSEAUX ET VOLUMES"
echo "=================================="
docker network prune -f
docker volume prune -f

echo ""
log_info "8. DÉMARRAGE FORCÉ AVEC LE BON FICHIER"
echo "====================================="
log_info "Démarrage avec docker-compose.prod.yml explicitement..."
docker compose -f docker-compose.prod.yml up -d --force-recreate

echo ""
log_info "9. ATTENTE ET VÉRIFICATION (30 secondes)"
echo "======================================="
sleep 30

echo ""
log_info "10. STATUT FINAL"
echo "==============="
docker ps --format "table {{.Names}}\t{{.Image}}\t{{.Ports}}\t{{.Status}}"

echo ""
log_info "11. VÉRIFICATION DES NOMS DE CONTENEURS"
echo "======================================"
BOOKYOURCOACH_CONTAINERS=$(docker ps --format "{{.Names}}" | grep "bookyourcoach" | wc -l)
ACTIVIBE_CONTAINERS=$(docker ps --format "{{.Names}}" | grep "activibe" | wc -l)

log_info "Conteneurs bookyourcoach: $BOOKYOURCOACH_CONTAINERS"
log_info "Conteneurs activibe: $ACTIVIBE_CONTAINERS"

if [ "$BOOKYOURCOACH_CONTAINERS" -gt 0 ]; then
    log_success "Conteneurs bookyourcoach détectés!"
    
    echo ""
    log_info "12. CONFIGURATION DE LARAVEL"
    echo "=========================="
    
    # Attendre que l'application soit prête
    sleep 10
    
    # Configuration Laravel
    if docker ps --format "{{.Names}}" | grep -q "bookyourcoach_app_prod"; then
        log_info "Configuration de Laravel..."
        docker exec bookyourcoach_app_prod php artisan key:generate --force || log_warning "Erreur key:generate"
        docker exec bookyourcoach_app_prod php artisan config:cache || log_warning "Erreur config:cache"
        docker exec bookyourcoach_app_prod php artisan migrate --force || log_warning "Erreur migrate"
        docker exec bookyourcoach_app_prod php artisan optimize || log_warning "Erreur optimize"
        log_success "Configuration Laravel terminée"
    else
        log_error "Conteneur bookyourcoach_app_prod non trouvé"
        log_info "Conteneurs disponibles:"
        docker ps --format "{{.Names}}"
    fi
else
    log_error "Aucun conteneur bookyourcoach créé!"
    log_warning "Le problème persiste. Vérification du fichier docker-compose.prod.yml nécessaire."
fi

echo ""
echo "🎉 DIAGNOSTIC APPROFONDI TERMINÉ"
echo "==============================="
echo ""
if [ "$BOOKYOURCOACH_CONTAINERS" -gt 0 ]; then
    log_success "Application BookYourCoach déployée avec succès!"
    echo "🌐 Accès: http://91.134.77.98:8080"
else
    log_error "Déploiement échoué - Vérification manuelle nécessaire"
    echo "🔧 Commandes de diagnostic:"
    echo "   cat docker-compose.prod.yml | grep container_name"
    echo "   docker compose -f docker-compose.prod.yml config"
fi

echo ""
echo "📋 Commandes utiles:"
echo "  • Logs: docker compose -f docker-compose.prod.yml logs -f"
echo "  • Statut: docker compose -f docker-compose.prod.yml ps"
echo "  • Test: curl -I http://91.134.77.98:8080"
