#!/usr/bin/env bash
set -euo pipefail

# Configuration
API_BASE="http://localhost:8080/api"
EMAIL="manager@club-Équestre-de-la-vallee-doree.fr"
PASSWORD="password"
ORIGIN="http://localhost:3000"

# Couleurs pour la sortie
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log() { echo -e "\n${YELLOW}--- $1 ---${NC}"; }
pass() { echo -e "${GREEN}✅ PASSED:${NC} $1"; }
fail() { 
    echo -e "${RED}❌ FAILED:${NC} $1"
    # Affiche le contenu du fichier de sortie en cas d'erreur s'il existe
    [ -f "$2" ] && cat "$2" && echo
    exit 1
}
check_status() {
    local expected="$1"
    local actual="$2"
    local message="$3"
    local outfile="$4"
    if [ "$actual" -eq "$expected" ]; then
        pass "$message (reçu $actual comme attendu)"
    else
        fail "$message (attendu $expected, mais reçu $actual)" "$outfile"
    fi
}

# Nettoyage
rm -f /tmp/auth_*.json

# --- SCÉNARIO 1: TENTATIVE D'ACCÈS SANS AUTHENTIFICATION ---
log "Scénario 1: Accès à une route protégée sans jeton"
status1=$(curl -s -o /tmp/auth_s1_error.json -w "%{http_code}" -X GET "${API_BASE}/club/dashboard" \
    -H "Accept: application/json" \
    -H "Origin: ${ORIGIN}")
check_status 401 "$status1" "Doit être rejeté avec 401 Unauthorized" "/tmp/auth_s1_error.json"

# --- SCÉNARIO 2: CONNEXION AVEC SUCCÈS ---
log "Scénario 2: Connexion avec des identifiants valides"
status2=$(curl -s -o /tmp/auth_login.json -w "%{http_code}" -X POST "${API_BASE}/auth/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Origin: ${ORIGIN}" \
    -d "{\"email\":\"${EMAIL}\",\"password\":\"${PASSWORD}\"}")
check_status 200 "$status2" "La connexion doit réussir avec le statut 200 OK" "/tmp/auth_login.json"

TOKEN=$(cat /tmp/auth_login.json | grep -o '"token":"[^"]*' | cut -d'"' -f4)
if [ -z "$TOKEN" ]; then
    fail "Le jeton n'a pas été trouvé dans la réponse de connexion." "/tmp/auth_login.json"
else
    pass "Un jeton d'authentification a été reçu avec succès."
fi

# --- SCÉNARIO 3: ACCÈS À UNE ROUTE PROTÉGÉE AVEC UN JETON VALIDE ---
log "Scénario 3: Accès à une route protégée avec un jeton valide"
status3=$(curl -s -o /tmp/auth_dashboard.json -w "%{http_code}" -X GET "${API_BASE}/club/dashboard" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Origin: ${ORIGIN}")
check_status 200 "$status3" "L'accès au dashboard doit réussir avec le statut 200 OK" "/tmp/auth_dashboard.json"
pass "Les données du dashboard ont été récupérées avec succès."

# --- SCÉNARIO 4: DÉCONNEXION ---
log "Scénario 4: Déconnexion de l'utilisateur"
status4=$(curl -s -o /tmp/auth_logout.json -w "%{http_code}" -X POST "${API_BASE}/auth/logout" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Origin: ${ORIGIN}")
check_status 200 "$status4" "La déconnexion doit réussir avec le statut 200 OK" "/tmp/auth_logout.json"
pass "La session a été terminée côté serveur."

# --- SCÉNARIO 5: TENTATIVE D'ACCÈS AVEC UN JETON EXPIRÉ (APRÈS DÉCONNEXION) ---
log "Scénario 5: Accès à une route protégée avec l'ancien jeton"
status5=$(curl -s -o /tmp/auth_s5_error.json -w "%{http_code}" -X GET "${API_BASE}/club/dashboard" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Origin: ${ORIGIN}")
check_status 401 "$status5" "Doit être rejeté avec 401 Unauthorized car le jeton est invalidé" "/tmp/auth_s5_error.json"
pass "L'ancien jeton ne permet plus d'accéder aux ressources protégées."

log "Tous les tests automatisés du backend ont été passés avec succès !"

