#!/usr/bin/env bash
# Bootstrap d'une VM e2-micro GCP pour Redis partagé.
# Usage (depuis votre poste, avec gcloud configuré) :
#   ./setup-gce.sh [PROJECT_ID] [ZONE] [VM_NAME]
#
# Prérequis : gcloud CLI, projet GCP avec Compute Engine activé.

set -euo pipefail

PROJECT_ID="${1:-${GCP_PROJECT:-}}"
ZONE="${2:-europe-west1-b}"
VM_NAME="${3:-shared-redis}"
MACHINE_TYPE="e2-micro"
NETWORK_TAG="shared-redis"
# Sous-réseau applicatif — ajuster selon votre VPC (ex. OVH 10.0.0.0/24)
ALLOWED_SOURCE="${REDIS_ALLOWED_CIDR:-10.0.0.0/24}"

if [[ -z "$PROJECT_ID" ]]; then
  echo "Usage: $0 PROJECT_ID [ZONE] [VM_NAME]"
  exit 1
fi

echo "==> Création VM ${VM_NAME} (${MACHINE_TYPE}) dans ${PROJECT_ID}/${ZONE}"

gcloud compute instances create "$VM_NAME" \
  --project="$PROJECT_ID" \
  --zone="$ZONE" \
  --machine-type="$MACHINE_TYPE" \
  --image-family=ubuntu-2204-lts \
  --image-project=ubuntu-os-cloud \
  --boot-disk-size=10GB \
  --tags="$NETWORK_TAG" \
  --metadata=startup-script='#!/bin/bash
set -e
apt-get update
apt-get install -y docker.io docker-compose-v2
systemctl enable --now docker
usermod -aG docker $(logname 2>/dev/null || echo ubuntu)
mkdir -p /opt/shared-redis
'

echo "==> Règle pare-feu : TCP 6379 depuis ${ALLOWED_SOURCE} uniquement"
gcloud compute firewall-rules create allow-redis-from-apps \
  --project="$PROJECT_ID" \
  --direction=INGRESS \
  --priority=1000 \
  --network=default \
  --action=ALLOW \
  --rules=tcp:6379 \
  --source-ranges="$ALLOWED_SOURCE" \
  --target-tags="$NETWORK_TAG" \
  2>/dev/null || echo "(règle existante ou déjà créée)"

INTERNAL_IP=$(gcloud compute instances describe "$VM_NAME" \
  --project="$PROJECT_ID" \
  --zone="$ZONE" \
  --format='get(networkInterfaces[0].networkIP)')

echo ""
echo "VM prête. IP interne : ${INTERNAL_IP}"
echo ""
echo "Étapes suivantes :"
echo "  1. scp -r infra/shared-redis/* ${VM_NAME}:/opt/shared-redis/"
echo "  2. ssh ${VM_NAME} 'cd /opt/shared-redis && cp .env.example .env && nano .env'"
echo "  3. ssh ${VM_NAME} 'cd /opt/shared-redis && docker compose up -d'"
echo "  4. Dans chaque app : REDIS_HOST=${INTERNAL_IP} REDIS_PASSWORD=<mot de passe>"
