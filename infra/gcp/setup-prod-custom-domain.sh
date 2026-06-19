#!/usr/bin/env bash
# activibe.be → bookyourcoach-api-prod / bookyourcoach-web-prod (API sur /api/*).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/gcp-env-prod.sh
source "${SCRIPT_DIR}/lib/gcp-env-prod.sh"

NEG_API="${NEG_API:-bookyourcoach-api-prod-neg}"
NEG_WEB="${NEG_WEB:-bookyourcoach-web-prod-neg}"
BACKEND_API="${BACKEND_API:-bookyourcoach-api-prod-backend}"
BACKEND_WEB="${BACKEND_WEB:-bookyourcoach-web-prod-backend}"
PATH_MATCHER="${PATH_MATCHER:-activibe}"
CERT_NAME="${CERT_NAME:-activibe-be-cert}"

PATH_RULES="/api/*=${BACKEND_API},/sanctum/*=${BACKEND_API},/broadcasting/*=${BACKEND_API}"

gcloud config set project "$GCP_PROJECT_ID" >/dev/null

ensure_neg() {
  local neg="$1" service="$2"
  if gcloud compute network-endpoint-groups describe "$neg" \
    --region="$GCP_RUN_REGION" --project="$GCP_PROJECT_ID" &>/dev/null; then
    return 0
  fi
  if ! gcloud run services describe "$service" \
    --region="$GCP_RUN_REGION" --project="$GCP_PROJECT_ID" &>/dev/null; then
    echo "ERREUR: déployer ${service} avant setup LB (cloudbuild-prod.yaml)" >&2
    exit 1
  fi
  gcloud compute network-endpoint-groups create "$neg" \
    --region="$GCP_RUN_REGION" \
    --network-endpoint-type=serverless \
    --cloud-run-service="$service" \
    --project="$GCP_PROJECT_ID"
}

ensure_backend() {
  local backend="$1" neg="$2"
  if ! gcloud compute backend-services describe "$backend" \
    --global --project="$GCP_PROJECT_ID" &>/dev/null; then
    gcloud compute backend-services create "$backend" \
      --global --load-balancing-scheme=EXTERNAL --project="$GCP_PROJECT_ID"
    gcloud compute backend-services add-backend "$backend" \
      --global \
      --network-endpoint-group="$neg" \
      --network-endpoint-group-region="$GCP_RUN_REGION" \
      --project="$GCP_PROJECT_ID"
  fi
}

echo "→ NEG + backends"
ensure_neg "$NEG_API" "$API_SERVICE"
ensure_neg "$NEG_WEB" "$WEB_SERVICE"
ensure_backend "$BACKEND_API" "$NEG_API"
ensure_backend "$BACKEND_WEB" "$NEG_WEB"

echo "→ URL map path matcher ${PATH_MATCHER}"
if ! gcloud compute url-maps describe "$URL_MAP" --global --project="$GCP_PROJECT_ID" \
  --format='value(pathMatchers.name)' | tr ';' '\n' | grep -qx "$PATH_MATCHER"; then
  gcloud compute url-maps add-path-matcher "$URL_MAP" \
    --global \
    --path-matcher-name="$PATH_MATCHER" \
    --default-service="$BACKEND_WEB" \
    --path-rules="$PATH_RULES" \
    --new-hosts="$ACTIVIBE_HOSTS" \
    --project="$GCP_PROJECT_ID"
else
  gcloud compute url-maps add-host-rule "$URL_MAP" \
    --global \
    --hosts="$ACTIVIBE_HOSTS" \
    --path-matcher-name="$PATH_MATCHER" \
    --project="$GCP_PROJECT_ID" 2>/dev/null || true
fi

echo "→ Certificat ${CERT_NAME}"
if ! gcloud compute ssl-certificates describe "$CERT_NAME" \
  --global --project="$GCP_PROJECT_ID" &>/dev/null; then
  gcloud compute ssl-certificates create "$CERT_NAME" \
    --domains="$ACTIVIBE_HOSTS" \
    --global --project="$GCP_PROJECT_ID"
fi

EXISTING_CERTS="$(gcloud compute target-https-proxies describe "$HTTPS_PROXY" \
  --global --project="$GCP_PROJECT_ID" \
  --format='value(sslCertificates.basename())' | tr ';\n' ',,' | tr -s ',' | sed 's/^,//;s/,$//')"
if ! echo ",${EXISTING_CERTS}," | grep -q ",${CERT_NAME},"; then
  gcloud compute target-https-proxies update "$HTTPS_PROXY" \
    --global \
    --ssl-certificates="${EXISTING_CERTS},${CERT_NAME}" \
    --project="$GCP_PROJECT_ID"
fi

for SVC in "$API_SERVICE" "$WEB_SERVICE"; do
  gcloud run services add-iam-policy-binding "$SVC" \
    --region="$GCP_RUN_REGION" --member="allUsers" --role="roles/run.invoker" \
    --project="$GCP_PROJECT_ID" --quiet 2>/dev/null || true
done

echo "OK — ./infra/gcp/print-ovh-dns-prod.sh"
