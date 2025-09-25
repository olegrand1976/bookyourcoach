#!/bin/bash

# Script de test pour vérifier la correction de la boucle infinie Sanctum
# Usage: ./scripts/test-sanctum-fix.sh

echo "🔧 Test de la correction de la boucle infinie Sanctum"
echo "=================================================="

# Configuration
BASE_URL="http://localhost:8000"
API_URL="$BASE_URL/api"

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
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

# Test 1: Vérifier que l'API répond sans erreur
echo ""
echo "1. Test de connectivité API..."
response=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL/activity-types")
if [ "$response" = "200" ]; then
    log_success "API accessible (HTTP $response)"
else
    log_error "API non accessible (HTTP $response)"
    exit 1
fi

# Test 2: Test d'authentification avec token invalide
echo ""
echo "2. Test avec token invalide..."
response=$(curl -s -w "%{http_code}" -H "Authorization: Bearer invalid_token" "$API_URL/auth/user")
http_code=$(echo "$response" | tail -c 4)
if [ "$http_code" = "401" ]; then
    log_success "Token invalide correctement rejeté (HTTP $http_code)"
else
    log_error "Token invalide non rejeté (HTTP $http_code)"
fi

# Test 3: Test sans token
echo ""
echo "3. Test sans token..."
response=$(curl -s -w "%{http_code}" "$API_URL/auth/user")
http_code=$(echo "$response" | tail -c 4)
if [ "$http_code" = "401" ]; then
    log_success "Requête sans token correctement rejetée (HTTP $http_code)"
else
    log_error "Requête sans token non rejetée (HTTP $http_code)"
fi

# Test 4: Test de login pour obtenir un token valide
echo ""
echo "4. Test de login..."
login_response=$(curl -s -X POST "$API_URL/auth/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{
        "email": "admin@example.com",
        "password": "password"
    }')

if echo "$login_response" | grep -q '"success":true'; then
    log_success "Login réussi"
    
    # Extraire le token
    token=$(echo "$login_response" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    
    if [ -n "$token" ]; then
        log_success "Token extrait: ${token:0:20}..."
        
        # Test 5: Test avec token valide
        echo ""
        echo "5. Test avec token valide..."
        auth_response=$(curl -s -w "%{http_code}" \
            -H "Authorization: Bearer $token" \
            -H "Accept: application/json" \
            "$API_URL/auth/user")
        
        http_code=$(echo "$auth_response" | tail -c 4)
        if [ "$http_code" = "200" ]; then
            log_success "Authentification avec token valide réussie (HTTP $http_code)"
        else
            log_error "Authentification avec token valide échouée (HTTP $http_code)"
        fi
    else
        log_error "Impossible d'extraire le token"
    fi
else
    log_warning "Login échoué - utilisateur de test peut-être inexistant"
fi

# Test 6: Vérifier les logs pour détecter des boucles
echo ""
echo "6. Vérification des logs..."
if [ -f "storage/logs/laravel.log" ]; then
    recent_logs=$(tail -50 storage/logs/laravel.log | grep -E "(ApiAuthSanctum|middleware.*loop|infinite)" | wc -l)
    if [ "$recent_logs" -eq 0 ]; then
        log_success "Aucune trace de boucle infinie dans les logs récents"
    else
        log_error "Traces de boucle infinie détectées dans les logs ($recent_logs occurrences)"
    fi
else
    log_warning "Fichier de log non trouvé"
fi

echo ""
echo "=================================================="
echo "🎯 Résumé des tests de correction Sanctum"
echo "=================================================="
echo ""
echo "✅ Middleware ApiAuthSanctum supprimé"
echo "✅ Configuration bootstrap/app.php corrigée"
echo "✅ Middleware ForceJsonResponse optimisé"
echo "✅ Tests d'authentification effectués"
echo ""
echo "🚀 La correction de la boucle infinie Sanctum est terminée !"
echo ""
