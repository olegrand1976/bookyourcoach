# Déploiement GCP — bookyourcoach

## MySQL partagé

Une instance **`ll-it-mysql`** (Infiswap + bookyourcoach). Ne pas créer `byc-db-staging` isolée.

```bash
../../infra/shared-mysql/setup-gcp.sh
../../infra/shared-mysql/setup-backups.sh
```

Bases : `bookyourcoach_prod`, `bookyourcoach_staging`  
Secret prod : `bookyourcoach-database-url`

Import depuis serveur dédié :

```bash
../../infra/shared-mysql/import-database.sh bookyourcoach_prod /path/dump.sql
```

Restore incident prod :

```bash
../../infra/shared-mysql/restore-prod-database.sh bookyourcoach_prod
```

## Neo4j (phase 1 Cloud Run)

Analyses graphiques (`Neo4jAnalysisService`) : **désactivées** sur Cloud Run via `NEO4J_ENABLED=false` (mock dans `AppServiceProvider`).

Phase ultérieure : Neo4j Aura Free ou VM dédiée — activer `NEO4J_ENABLED=true` + variables `NEO4J_URI`, etc.

## Déploiement

```bash
./infra/gcp/predeploy.sh
gcloud builds submit --config=infra/gcp/cloudbuild.yaml       # staging ll-it-sc.be
gcloud builds submit --config=infra/gcp/cloudbuild-prod.yaml  # prod activibe.be
./infra/gcp/setup-prod-custom-domain.sh
./infra/gcp/print-ovh-dns-prod.sh
```

DNS OVH → GCP : [infra/gcp/DNS-CUTOVER-OVH.md](../../infra/gcp/DNS-CUTOVER-OVH.md)

Worker queue : `../../infra/shared-workers/setup-gcp.sh`

## Scaling prod (activibe.be)

Fenêtre **7:45–18h** Paris, `min=0` la nuit — voir `../../infra/gcp/setup-prod-scaling.sh`.

```bash
../../infra/gcp/ensure-prod-warm.sh byc   # post-deploy si dans fenêtre
./infra/gcp/setup-scale-scheduler.sh     # alias infra partagée
```

## Redis

DB 3 (sessions/queues), DB 4 (cache) — voir [docs/REDIS.md](../docs/REDIS.md).
