# Redis — bookyourcoach (Activibe)

## Local

Conteneur Redis dans `docker-compose` ; `.env` :

```env
REDIS_HOST=redis
REDIS_DB=0
REDIS_CACHE_DB=1
```

## Production (VM partagée GCP)

| Rôle | DB | Variable Laravel | Secret Manager |
|------|-----|------------------|----------------|
| Sessions + queues | 3 | `REDIS_DB=3` | `bookyourcoach-redis-url` |
| Cache | 4 | `REDIS_CACHE_DB=4` | `bookyourcoach-redis-cache-url` |

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=<IP depuis premedica-redis-host>
REDIS_PORT=6379
REDIS_DB=3
REDIS_CACHE_DB=4
```

Préfixe recommandé : slug `APP_NAME` / `byc:`.

## Production GCP (Cloud Run staging)

```bash
../../infra/shared-mysql/setup-gcp.sh
../../infra/shared-mysql/import-database.sh bookyourcoach_prod /path/dump.sql
./infra/gcp/predeploy.sh
gcloud builds submit --config=infra/gcp/cloudbuild.yaml
# Queue worker : ../../infra/shared-workers/setup-gcp.sh
```

Services : `bookyourcoach-api-staging`, `bookyourcoach-web-staging` (`min-instances=0`).

Déploiement actuel (legacy) : serveur dédié SSH — migration vers GCP ci-dessus.

→ [`../../infra/shared-redis/README.md`](../../infra/shared-redis/README.md)
