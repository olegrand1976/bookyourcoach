#!/usr/bin/env bash
# Tâches planifiées Laravel en production (routes/console.php).
#
# Cloud Run (API) n'exécute pas `schedule:run` et s'éteint la nuit : chaque commande devient un
# Cloud Run Job (`bookyourcoach-cron-*`) déclenché par Cloud Scheduler — même schéma que
# business-management. Image, variables, secrets, Cloud SQL, VPC et compte de service sont
# recopiés du service API : relancer ce script après un changement d'environnement du service.
# Le workflow de déploiement met ensuite à jour l'image de ces jobs à chaque push sur main.
#
# Usage :
#   ./infra/gcp/setup-scheduler-jobs.sh             # crée / met à jour jobs et déclencheurs
#   ./infra/gcp/setup-scheduler-jobs.sh --dry-run   # affiche les commandes sans rien créer
#   ./infra/gcp/setup-scheduler-jobs.sh --resume NOM   # réactive un déclencheur mis en pause
#
# Activation progressive : les tâches qui modifient les données (cours, débits) sont créées
# EN PAUSE. Les tester d'abord, p. ex. :
#   gcloud run jobs execute bookyourcoach-cron-prolong --region=europe-west9 \
#     --args=artisan,recurring-slots:prolong,--dry-run --wait
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/gcp-env-prod.sh
source "${SCRIPT_DIR}/lib/gcp-env-prod.sh"

SCHEDULER_REGION="${SCHEDULER_REGION:-europe-west1}"
TZ_NAME="Europe/Brussels"
PREFIX="bookyourcoach-cron"
SA_EMAIL="${SERVICE_ACCOUNT}@${GCP_PROJECT_ID}.iam.gserviceaccount.com"

DRY_RUN=false
RESUME=""
case "${1:-}" in
  --dry-run) DRY_RUN=true ;;
  --resume) RESUME="${2:?nom du job, p. ex. prolong}" ;;
  "") ;;
  *) echo "Option inconnue : $1" >&2; exit 1 ;;
esac

run() {
  if $DRY_RUN; then echo "[dry-run] $*"; else "$@"; fi
}

if [[ -n "$RESUME" ]]; then
  run gcloud scheduler jobs resume "${PREFIX}-${RESUME}" \
    --location="$SCHEDULER_REGION" --project="$GCP_PROJECT_ID"
  exit 0
fi

# NOM | ARGUMENTS artisan | CRON (Europe/Brussels) | ÉTAT initial
# Horaires repris de routes/console.php.
TASKS=(
  "watch-expiring|artisan,subscriptions:watch-expiring|45 7 * * *|ENABLED"
  "replacement-reminders|artisan,lesson-replacements:send-pending-reminders|0 * * * *|ENABLED"
  "prune-idle-tokens|artisan,auth:prune-idle-tokens|30 3 * * *|ENABLED"
  "prune-login-history|artisan,auth:prune-login-history|45 3 * * *|ENABLED"
  "expense-limits|artisan,volunteer:fetch-expense-limits|0 6 1 1 *|ENABLED"
  # Jamais exécutées en prod jusqu'ici : premier passage = rattrapage. En pause jusqu'à validation.
  "planning-insights|artisan,club:send-daily-planning-insights|0 8 * * *|PAUSED"
  "generate-lessons|artisan,recurring-slots:generate-lessons|0 2 * * *|PAUSED"
  "prolong|artisan,recurring-slots:prolong|30 2 * * *|PAUSED"
  "expire-links|artisan,recurring-slots:expire-subscriptions|0 3 * * *|PAUSED"
  "consume-past-lessons|artisan,subscriptions:consume-past-lessons|5 * * * *|PAUSED"
)

WORK_DIR="$(mktemp -d)"
trap 'rm -rf "$WORK_DIR"' EXIT

echo "=== Lecture de la configuration du service ${API_SERVICE} ==="
gcloud run services describe "$API_SERVICE" --region="$GCP_RUN_REGION" \
  --project="$GCP_PROJECT_ID" --format=json > "$WORK_DIR/service.json"

# Variables en clair → fichier YAML ; secrets → NOM=secret:version (aucune valeur secrète lue).
python3 - "$WORK_DIR" <<'PY'
import json, sys, os
work = sys.argv[1]
svc = json.load(open(os.path.join(work, "service.json")))
tpl = svc["spec"]["template"]
c = tpl["spec"]["containers"][0]
env_lines, secrets = [], []
for e in c.get("env", []):
    ref = (e.get("valueFrom") or {}).get("secretKeyRef")
    if ref:
        secrets.append(f'{e["name"]}={ref["name"]}:{ref.get("key", "latest")}')
    else:
        env_lines.append(f'{e["name"]}: {json.dumps(e.get("value", ""))}')
# Les jobs écrivent leurs journaux sur stderr (Cloud Logging).
env_lines = [l for l in env_lines if not l.startswith("LOG_CHANNEL:")] + ['LOG_CHANNEL: "stderr"']
open(os.path.join(work, "env.yaml"), "w").write("\n".join(env_lines) + "\n")
open(os.path.join(work, "secrets"), "w").write(",".join(secrets))
open(os.path.join(work, "image"), "w").write(c["image"])
PY

IMAGE="$(cat "$WORK_DIR/image")"
SECRETS="$(cat "$WORK_DIR/secrets")"
echo "Image : $IMAGE"

for task in "${TASKS[@]}"; do
  IFS='|' read -r name args cron state <<< "$task"
  job="${PREFIX}-${name}"
  echo
  echo "=== ${job} (${cron}, ${state}) ==="

  job_flags=(
    --image="$IMAGE"
    --region="$GCP_RUN_REGION"
    --project="$GCP_PROJECT_ID"
    --command=php
    --args="$args"
    --service-account="$SA_EMAIL"
    --vpc-connector="$(connector_path)"
    --vpc-egress=private-ranges-only
    --set-cloudsql-instances="$CLOUDSQL_INSTANCE"
    --env-vars-file="$WORK_DIR/env.yaml"
    --set-secrets="$SECRETS"
    --task-timeout=900s
    --max-retries=0
    --quiet
  )
  if gcloud run jobs describe "$job" --region="$GCP_RUN_REGION" --project="$GCP_PROJECT_ID" >/dev/null 2>&1; then
    run gcloud run jobs update "$job" "${job_flags[@]}"
  else
    run gcloud run jobs create "$job" "${job_flags[@]}"
  fi

  uri="https://${GCP_RUN_REGION}-run.googleapis.com/apis/run.googleapis.com/v1/namespaces/${GCP_PROJECT_ID}/jobs/${job}:run"
  sched_flags=(
    --location="$SCHEDULER_REGION"
    --project="$GCP_PROJECT_ID"
    --schedule="$cron"
    --time-zone="$TZ_NAME"
    --uri="$uri"
    --http-method=POST
    --oauth-service-account-email="$SA_EMAIL"
    --oauth-token-scope=https://www.googleapis.com/auth/cloud-platform
    --attempt-deadline=180s
    --quiet
  )
  if gcloud scheduler jobs describe "$job" --location="$SCHEDULER_REGION" --project="$GCP_PROJECT_ID" >/dev/null 2>&1; then
    # Mise à jour : l'état (pause / actif) choisi à la main est conservé.
    run gcloud scheduler jobs update http "$job" "${sched_flags[@]}"
  else
    run gcloud scheduler jobs create http "$job" "${sched_flags[@]}"
    if [[ "$state" == "PAUSED" ]]; then
      run gcloud scheduler jobs pause "$job" --location="$SCHEDULER_REGION" --project="$GCP_PROJECT_ID"
    fi
  fi
done

echo
echo "Terminé. Déclencheurs : gcloud scheduler jobs list --location=${SCHEDULER_REGION} --filter='name~${PREFIX}'"
