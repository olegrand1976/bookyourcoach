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

## Production GCP (Cloud Run)

Services : `bookyourcoach-api-prod`, `bookyourcoach-web-prod` (activibe.be), déployés par push sur `main`
(`.github/workflows/deploy-production-security.yml`). Voir [infra/gcp/README.md](../infra/gcp/README.md).

→ [`../../infra/shared-redis/README.md`](../../infra/shared-redis/README.md)
