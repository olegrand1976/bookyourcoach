#!/usr/bin/env bash
set -euo pipefail

API_BASE="http://localhost:8080/api"
ORIGIN_HEADER="http://localhost:3000"

log() { echo -e "[$(date +%H:%M:%S)] $1"; }
pass() { echo -e "✅ $1"; }
fail() { echo -e "❌ $1"; }

login() {
  local email="$1"
  local password="$2"
  local token
  local status

  log "Tentative de connexion pour: ${email}"
  status=$(curl -s -o /tmp/login_resp.json -w "%{http_code}" -X POST "${API_BASE}/auth/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Origin: ${ORIGIN_HEADER}" \
    -d "{\"email\":\"${email}\",\"password\":\"${password}\"}") || true

  if [[ "$status" != "200" ]]; then
    fail "Login HTTP ${status} pour ${email}"; cat /tmp/login_resp.json; echo; return 1
  fi

  token=$(cat /tmp/login_resp.json | sed -n 's/.*"token":"\([^"]*\)".*/\1/p')
  if [[ -z "${token}" ]]; then
    fail "Token manquant pour ${email}"; cat /tmp/login_resp.json; echo; return 1
  fi

  pass "Login OK (${email})"
  echo -n "$token"
}

check_dashboard() {
  local token="$1"
  local status

  status=$(curl -s -o /tmp/dash_resp.json -w "%{http_code}" "${API_BASE}/club/dashboard" \
    -H "Authorization: Bearer ${token}" \
    -H "Accept: application/json" \
    -H "Origin: ${ORIGIN_HEADER}") || true

  if [[ "$status" != "200" ]]; then
    fail "Dashboard HTTP ${status}"; cat /tmp/dash_resp.json; echo; return 1
  fi

  pass "Dashboard OK"
}

run_for_user() {
  local email="$1"
  local password="$2"

  log "---- Tests pour ${email} ----"
  local token
  if ! token=$(login "$email" "$password"); then
    return 1
  fi

  if ! check_dashboard "$token"; then
    return 1
  fi

  pass "Tous les tests OK pour ${email}"
  echo
}

main() {
  log "Démarrage des tests Club"

  # Comptes club connus
  run_for_user "manager@club-Équestre-de-la-vallee-doree.fr" "password" || true
  run_for_user "manager@centre-Équestre-des-Étoiles.fr" "password" || true

  log "Fin des tests"
}

main "$@"
