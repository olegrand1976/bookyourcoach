# Defaults GCP — bookyourcoach staging

GCP_PROJECT_ID="${GCP_PROJECT_ID:-premedica-prod-2025}"
GCP_RUN_REGION="${GCP_RUN_REGION:-europe-west9}"
GCP_AR_REGION="${GCP_AR_REGION:-europe-west1}"
VPC_CONNECTOR="${VPC_CONNECTOR:-premedica-connector}"

AR_REPO="${AR_REPO:-bookyourcoach}"
SERVICE_ACCOUNT="${SERVICE_ACCOUNT:-bookyourcoach-run}"
API_SERVICE="${API_SERVICE:-bookyourcoach-api-staging}"
WEB_SERVICE="${WEB_SERVICE:-bookyourcoach-web-staging}"

WEB_DOMAIN="${WEB_DOMAIN:-bookyourcoach.ll-it-sc.be}"
API_DOMAIN="${API_DOMAIN:-api-bookyourcoach.ll-it-sc.be}"
WEB_PUBLIC_URL="${WEB_PUBLIC_URL:-https://${WEB_DOMAIN}}"
API_PUBLIC_URL="${API_PUBLIC_URL:-https://${API_DOMAIN}}"

CLOUDSQL_INSTANCE="${CLOUDSQL_INSTANCE:-premedica-prod-2025:europe-west9:ll-it-mysql}"
MYSQL_INSTANCE_NAME="${MYSQL_INSTANCE_NAME:-ll-it-mysql}"

REDIS_SECRET="${REDIS_SECRET:-bookyourcoach-redis-url}"
REDIS_CACHE_SECRET="${REDIS_CACHE_SECRET:-bookyourcoach-redis-cache-url}"

connector_path() {
  echo "projects/${GCP_PROJECT_ID}/locations/${GCP_RUN_REGION}/connectors/${VPC_CONNECTOR}"
}
