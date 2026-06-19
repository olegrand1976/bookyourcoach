# Defaults GCP bookyourcoach **production** (activibe.be)

GCP_PROJECT_ID="${GCP_PROJECT_ID:-premedica-prod-2025}"
GCP_RUN_REGION="${GCP_RUN_REGION:-europe-west9}"
GCP_AR_REGION="${GCP_AR_REGION:-europe-west1}"
VPC_CONNECTOR="${VPC_CONNECTOR:-premedica-connector}"

AR_REPO="${AR_REPO:-bookyourcoach}"
SERVICE_ACCOUNT="${SERVICE_ACCOUNT:-bookyourcoach-run}"

API_SERVICE="${API_SERVICE:-bookyourcoach-api-prod}"
WEB_SERVICE="${WEB_SERVICE:-bookyourcoach-web-prod}"

WEB_DOMAIN="${WEB_DOMAIN:-activibe.be}"
API_DOMAIN="${API_DOMAIN:-activibe.be}"
WEB_PUBLIC_URL="${WEB_PUBLIC_URL:-https://${WEB_DOMAIN}}"
API_PUBLIC_URL="${API_PUBLIC_URL:-https://${API_DOMAIN}}"

ACTIVIBE_HOSTS="${ACTIVIBE_HOSTS:-activibe.be,www.activibe.be}"

CLOUDSQL_INSTANCE="${CLOUDSQL_INSTANCE:-premedica-prod-2025:europe-west9:ll-it-mysql}"
DB_SECRET="${DB_SECRET:-bookyourcoach-database-url}"

URL_MAP="${URL_MAP:-staging-premedica-care-urlmap}"
HTTPS_PROXY="${HTTPS_PROXY:-staging-premedica-care-proxy}"
LB_IP="${LB_IP:-34.54.99.89}"

REDIS_SECRET="${REDIS_SECRET:-bookyourcoach-redis-url}"

connector_path() {
  echo "projects/${GCP_PROJECT_ID}/locations/${GCP_RUN_REGION}/connectors/${VPC_CONNECTOR}"
}
