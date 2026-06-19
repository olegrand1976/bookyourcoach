#!/usr/bin/env bash
# Prépare IAM, Artifact Registry et secrets pour bookyourcoach Cloud Run.
# Usage: ./infra/gcp/predeploy.sh
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../../.." && pwd)"
# shellcheck source=lib/gcp-env.sh
source "${SCRIPT_DIR}/lib/gcp-env.sh"

gcloud config set project "$GCP_PROJECT_ID" >/dev/null

echo "=== bookyourcoach predeploy — ${GCP_PROJECT_ID} ==="

SA_EMAIL="${SERVICE_ACCOUNT}@${GCP_PROJECT_ID}.iam.gserviceaccount.com"
CB_SA="$(gcloud projects describe "$GCP_PROJECT_ID" --format='value(projectNumber)')@cloudbuild.gserviceaccount.com"

if ! gcloud artifacts repositories describe "$AR_REPO" --location="$GCP_AR_REGION" --project="$GCP_PROJECT_ID" >/dev/null 2>&1; then
  gcloud artifacts repositories create "$AR_REPO" \
    --repository-format=docker --location="$GCP_AR_REGION" --project="$GCP_PROJECT_ID" --quiet
fi

if ! gcloud iam service-accounts describe "$SA_EMAIL" --project="$GCP_PROJECT_ID" >/dev/null 2>&1; then
  gcloud iam service-accounts create "$SERVICE_ACCOUNT" \
    --display-name="bookyourcoach Cloud Run" --project="$GCP_PROJECT_ID" --quiet
fi

for role in roles/cloudsql.client roles/secretmanager.secretAccessor roles/run.invoker; do
  gcloud projects add-iam-policy-binding "$GCP_PROJECT_ID" \
    --member="serviceAccount:${SA_EMAIL}" --role="$role" --quiet >/dev/null
done

gcloud projects add-iam-policy-binding "$GCP_PROJECT_ID" \
  --member="serviceAccount:${CB_SA}" --role=roles/run.admin --quiet >/dev/null

echo "MySQL : ../../infra/shared-mysql/setup-gcp.sh"
echo "Deploy : gcloud builds submit --config=infra/gcp/cloudbuild.yaml"
