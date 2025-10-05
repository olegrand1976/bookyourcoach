docker compose -f docker-compose.local.yml down
docker compose -f docker-compose.local.yml build --no-cache
docker compose -f docker-compose.local.yml up -d
