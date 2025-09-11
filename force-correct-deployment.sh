#!/bin/bash

# Script de vérification et correction du docker-compose.prod.yml
# Force la création des bons noms de conteneurs

echo "🔧 CORRECTION FORCÉE DU DOCKER-COMPOSE"
echo "======================================"

# Créer un nouveau docker-compose.prod.yml correct
cat > docker-compose.prod.yml << 'EOF'
services:
  app:
    image: olegrand1976/activibe-app:latest
    container_name: bookyourcoach_app_prod
    restart: unless-stopped
    env_file:
      - .env
    environment:
      - APP_NAME=${APP_NAME}
      - APP_ENV=${APP_ENV}
      - APP_DEBUG=${APP_DEBUG}
      - APP_URL=${APP_URL}
      - DB_CONNECTION=${DB_CONNECTION}
      - DB_HOST=${DB_HOST}
      - DB_PORT=${DB_PORT}
      - DB_DATABASE=${DB_DATABASE}
      - DB_USERNAME=${DB_USERNAME}
      - DB_PASSWORD=${DB_PASSWORD}
      - REDIS_HOST=${REDIS_HOST}
      - REDIS_PORT=${REDIS_PORT}
      - REDIS_PASSWORD=${REDIS_PASSWORD}
      - CACHE_DRIVER=${CACHE_DRIVER}
      - SESSION_DRIVER=${SESSION_DRIVER}
      - QUEUE_CONNECTION=${QUEUE_CONNECTION}
    volumes:
      - app_storage:/var/www/storage
      - app_bootstrap_cache:/var/www/bootstrap/cache
    depends_on:
      - mysql
      - redis
    networks:
      - bookyourcoach_network

  mysql:
    image: mysql:8.0
    container_name: bookyourcoach_mysql_prod
    restart: unless-stopped
    env_file:
      - .env
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - bookyourcoach_network
    command: --default-authentication-plugin=mysql_native_password

  redis:
    image: redis:7-alpine
    container_name: bookyourcoach_redis_prod
    restart: unless-stopped
    env_file:
      - .env
    volumes:
      - redis_data:/data
    networks:
      - bookyourcoach_network
    command: redis-server --appendonly yes --requirepass ${REDIS_PASSWORD}

  webserver:
    image: nginx:alpine
    container_name: bookyourcoach_webserver_prod
    restart: unless-stopped
    ports:
      - "8080:80"
      - "8443:443"
    volumes:
      - ./docker/nginx/prod.conf:/etc/nginx/conf.d/default.conf
      - app_storage:/var/www/storage
    depends_on:
      - app
    networks:
      - bookyourcoach_network

volumes:
  mysql_data:
    driver: local
  redis_data:
    driver: local
  app_storage:
    driver: local
  app_bootstrap_cache:
    driver: local

networks:
  bookyourcoach_network:
    driver: bridge
EOF

echo "✅ Nouveau docker-compose.prod.yml créé avec les bons noms de conteneurs"

# Créer le fichier .env s'il n'existe pas
if [ ! -f ".env" ]; then
    if [ -f "production.env" ]; then
        cp production.env .env
        echo "✅ Fichier .env créé"
    else
        echo "❌ Fichier production.env manquant!"
        exit 1
    fi
fi

# Arrêter tous les conteneurs sauf infiswap
echo "🛑 Arrêt des conteneurs existants..."
docker stop $(docker ps --format "{{.Names}}" | grep -v "infiswap") 2>/dev/null || true
docker rm $(docker ps -a --format "{{.Names}}" | grep -v "infiswap") 2>/dev/null || true

# Nettoyer
docker network prune -f
docker volume prune -f

# Démarrer avec le nouveau fichier
echo "🚀 Démarrage avec le nouveau docker-compose..."
docker compose -f docker-compose.prod.yml up -d --force-recreate

echo "⏳ Attente du démarrage (30 secondes)..."
sleep 30

# Vérifier les conteneurs
echo "📊 Vérification des conteneurs:"
docker ps --format "table {{.Names}}\t{{.Image}}\t{{.Status}}"

# Configurer Laravel si possible
if docker ps --format "{{.Names}}" | grep -q "bookyourcoach_app_prod"; then
    echo "⚙️ Configuration de Laravel..."
    sleep 10
    docker exec bookyourcoach_app_prod php artisan key:generate --force
    docker exec bookyourcoach_app_prod php artisan config:cache
    docker exec bookyourcoach_app_prod php artisan migrate --force
    docker exec bookyourcoach_app_prod php artisan optimize
    echo "✅ Laravel configuré"
else
    echo "❌ Conteneur bookyourcoach_app_prod non trouvé"
fi

echo ""
echo "🎉 CORRECTION TERMINÉE"
echo "===================="
echo "🌐 Application: http://91.134.77.98:8080"
echo "📋 Test: curl -I http://91.134.77.98:8080"
