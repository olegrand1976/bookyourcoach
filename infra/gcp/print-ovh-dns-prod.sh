#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/gcp-env-prod.sh
source "${SCRIPT_DIR}/lib/gcp-env-prod.sh"

LB_IP="${LB_IP:-34.54.99.89}"

cat <<EOF
=== OVH — zone activibe.be ===

Ancienne cible OVH : 91.134.67.10
Nouvelle cible GCP : ${LB_IP}

| Sous-domaine | Type | Cible        |
|--------------|------|--------------|
| @            | A    | ${LB_IP}     |
| www          | A    | ${LB_IP}     |

API : https://activibe.be/api (pas de sous-domaine api.)

Certificat :
  gcloud compute ssl-certificates describe activibe-be-cert --global \\
    --project=${GCP_PROJECT_ID} --format='yaml(managed.domainStatus)'

Tests :
  curl -I https://activibe.be
  curl -I https://activibe.be/api/health

EOF
