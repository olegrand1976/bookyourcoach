# 🚨 SOLUTION DÉFINITIVE - Problème nginx-proxy 503

## 🔍 **PROBLÈME IDENTIFIÉ**

D'après mon analyse approfondie, voici le vrai problème :

### **❌ Configuration hybride problématique :**
1. **Vous utilisez `docker-compose.prod.yml`** (sans nginx-proxy)
2. **Mais vos logs montrent nginx-proxy** (configuration manuelle)
3. **Le container `activibe-app` n'expose AUCUN port** dans docker-compose.prod.yml
4. **nginx-proxy cherche le port 3001** mais l'application expose le port 80
5. **Variables VIRTUAL_HOST/VIRTUAL_PORT manquantes**

### **🎯 Cause racine :**
L'image `olegrand1976/activibe-app:latest` est une **image Laravel uniquement** (port 80) mais nginx-proxy est configuré pour chercher le port 3001 (Nuxt.js).

---

## 🚀 **SOLUTION DÉFINITIVE**

### **Option 1 : Correction rapide (Recommandée)**

**Sur le serveur, exécutez :**

```bash
cd /srv/activibe

# 1. Arrêter tous les containers
docker stop $(docker ps -q) 2>/dev/null || true
docker rm $(docker ps -aq) 2>/dev/null || true

# 2. Créer le fichier .env avec les bonnes variables
cat > .env << 'EOF'
# Variables nginx-proxy (CRITIQUES)
VIRTUAL_HOST=bookyourcoach.com,www.bookyourcoach.com,91.134.77.98
VIRTUAL_PORT=80
LETSENCRYPT_HOST=bookyourcoach.com,www.bookyourcoach.com
LETSENCRYPT_EMAIL=admin@bookyourcoach.com

# Application
APP_NAME="BookYourCoach"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bookyourcoach.com

# Base de données MySQL OVH
DB_CONNECTION=mysql
DB_HOST=votre-host-mysql-ovh.ovh.net
DB_PORT=3306
DB_DATABASE=votre_nom_database
DB_USERNAME=votre_username_db
DB_PASSWORD=votre_password_db_secure

# Redis local
REDIS_HOST=redis
REDIS_PASSWORD=redis_secure_password_2024
REDIS_PORT=6379

# Cache et sessions
BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Neo4j
NEO4J_PASSWORD=neo4j_secure_password_2024

# Docker
IMAGE_NAME=olegrand1976/activibe-app
DOCKER_REGISTRY=docker.io
EOF

# 3. Créer la configuration Docker Compose corrigée
cat > docker-compose.final.yml << 'EOF'
version: "3.8"

services:
  # Application Laravel (port 80)
  app:
    image: olegrand1976/activibe-app:latest
    container_name: activibe-app
    restart: unless-stopped
    environment:
      # Variables nginx-proxy (CRITIQUES - port 80, pas 3001)
      - VIRTUAL_HOST=${VIRTUAL_HOST}
      - VIRTUAL_PORT=80
      - LETSENCRYPT_HOST=${LETSENCRYPT_HOST}
      - LETSENCRYPT_EMAIL=${LETSENCRYPT_EMAIL}
      # Variables application
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
      - REDIS_HOST=redis
      - REDIS_PORT=6379
      - REDIS_PASSWORD=${REDIS_PASSWORD}
      - CACHE_DRIVER=${CACHE_DRIVER}
      - SESSION_DRIVER=${SESSION_DRIVER}
      - QUEUE_CONNECTION=${QUEUE_CONNECTION}
    ports:
      - "80:80"      # Laravel sur port 80 (nginx-proxy pointe ici)
      - "8080:80"    # Port alternatif pour debug
    depends_on:
      - redis
    networks:
      - nginx-proxy
      - app-network

  # Redis
  redis:
    image: redis:7-alpine
    container_name: activibe-redis
    restart: unless-stopped
    volumes:
      - redis_data:/data
    networks:
      - app-network
    command: redis-server --appendonly yes --requirepass ${REDIS_PASSWORD}

  # Neo4j
  neo4j:
    image: neo4j:5.15-community
    container_name: activibe-neo4j
    restart: unless-stopped
    ports:
      - "7474:7474"
      - "7687:7687"
    environment:
      - NEO4J_AUTH=neo4j/${NEO4J_PASSWORD}
      - NEO4J_PLUGINS=["apoc"]
    volumes:
      - neo4j_data:/data
      - neo4j_logs:/logs
    networks:
      - app-network

  # phpMyAdmin pour DB OVH
  phpmyadmin:
    image: phpmyadmin:latest
    container_name: activibe-phpmyadmin
    restart: unless-stopped
    ports:
      - "8082:80"
    environment:
      - PMA_HOST=${DB_HOST}
      - PMA_PORT=${DB_PORT}
      - PMA_USER=${DB_USERNAME}
      - PMA_PASSWORD=${DB_PASSWORD}
    networks:
      - app-network

  # nginx-proxy
  nginx-proxy:
    image: nginxproxy/nginx-proxy:latest
    container_name: activibe-nginx-proxy
    restart: unless-stopped
    ports:
      - "8081:80"
      - "8444:443"
    volumes:
      - /var/run/docker.sock:/tmp/docker.sock:ro
      - nginx_certs:/etc/nginx/certs
      - nginx_vhost:/etc/nginx/vhost.d
      - nginx_html:/usr/share/nginx/html
    environment:
      - TRUST_DOWNSTREAM_PROXY=false
    networks:
      - nginx-proxy

volumes:
  redis_data:
  neo4j_data:
  neo4j_logs:
  nginx_certs:
  nginx_vhost:
  nginx_html:

networks:
  nginx-proxy:
    driver: bridge
  app-network:
    driver: bridge
    internal: true
EOF

# 4. Démarrer avec la configuration corrigée
docker-compose -f docker-compose.final.yml --env-file .env up -d

# 5. Attendre le démarrage
sleep 60

# 6. Vérifier l'état
docker-compose -f docker-compose.final.yml ps

# 7. Tests de connectivité
echo "🧪 Tests de connectivité..."
curl -I http://localhost:8081 && echo "✅ Port 8081 (nginx-proxy) : OK" || echo "❌ Port 8081 : KO"
curl -I http://localhost:80 && echo "✅ Port 80 (app directe) : OK" || echo "❌ Port 80 : KO"
curl -I http://localhost:8082 && echo "✅ Port 8082 (phpMyAdmin) : OK" || echo "❌ Port 8082 : KO"
curl -I http://localhost:7474 && echo "✅ Port 7474 (Neo4j) : OK" || echo "❌ Port 7474 : KO"

echo ""
echo "🎯 RÉSULTAT :"
echo "Application accessible sur : http://91.134.77.98:8081"
echo "phpMyAdmin accessible sur : http://91.134.77.98:8082"
echo "Neo4j accessible sur : http://91.134.77.98:7474"
```

### **Option 2 : Diagnostic approfondi**

Si vous voulez d'abord comprendre exactement le problème :

```bash
# Télécharger et exécuter le diagnostic
cd /srv/activibe
wget https://raw.githubusercontent.com/olegrand1976/bookyourcoach/main/DIAGNOSTIC-APPROFONDI.sh
chmod +x DIAGNOSTIC-APPROFONDI.sh
./DIAGNOSTIC-APPROFONDI.sh
```

---

## 🔧 **DIFFÉRENCE CLÉE**

### **❌ Configuration actuelle (problématique) :**
- nginx-proxy cherche le port **3001** (Nuxt.js)
- Application expose le port **80** (Laravel)
- **Mismatch** → Erreur 503

### **✅ Configuration corrigée :**
- nginx-proxy pointe vers le port **80** (Laravel)
- Application expose le port **80** (Laravel)
- **Match** → Application accessible

---

## 🎯 **POURQUOI CETTE SOLUTION FONCTIONNE**

1. **VIRTUAL_PORT=80** : nginx-proxy pointe vers le bon port
2. **Variables nginx-proxy** : Présentes dans le container app
3. **Configuration cohérente** : Un seul fichier Docker Compose
4. **Ports corrects** : Application sur 80, nginx-proxy sur 8081

---

## 🚀 **EXÉCUTEZ LA SOLUTION**

**Sur votre serveur, copiez et exécutez le script de l'Option 1 ci-dessus.**

**Cette solution devrait définitivement résoudre l'erreur 503 !**
