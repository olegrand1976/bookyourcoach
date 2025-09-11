# 🚨 CORRECTION IMMÉDIATE - Serveur de Production

## 🔍 **DIAGNOSTIC DU PROBLÈME**

D'après les logs, voici ce qui se passe :

### **✅ Services qui fonctionnent :**
- `activibe-app` : En cours d'exécution (nginx + php-fpm OK)
- `activibe-neo4j` : Démarré et accessible sur port 7474
- `activibe-redis` : En fonctionnement
- `activibe-nginx-proxy` : En cours d'exécution
- `infiswap_frontend_prod` : Préservé sur port 80 ✅

### **❌ Problème identifié :**
- **nginx-proxy retourne 503** car il ne trouve pas le service backend
- **Variables d'environnement manquantes** pour nginx-proxy
- **Configuration actuelle utilise `docker-compose.prod.yml`** au lieu de `docker-compose.nginx-proxy.yml`

---

## 🎯 **SOLUTION IMMÉDIATE**

### **Étape 1 : Corriger la configuration nginx-proxy**

Le problème est que le container `activibe-app` n'a pas les variables d'environnement requises par nginx-proxy.

**Sur le serveur, exécutez :**

```bash
# Arrêter les containers actuels
docker stop activibe-app activibe-nginx-proxy activibe-redis activibe-neo4j activibe-phpmyadmin 2>/dev/null || true

# Supprimer les containers
docker rm activibe-app activibe-nginx-proxy activibe-redis activibe-neo4j activibe-phpmyadmin 2>/dev/null || true

# Créer le fichier .env avec les bonnes variables
cat > /srv/activibe/.env << 'EOF'
# Configuration nginx-proxy
VIRTUAL_HOST=bookyourcoach.com,www.bookyourcoach.com,91.134.77.98
VIRTUAL_PORT=3001
LETSENCRYPT_HOST=bookyourcoach.com,www.bookyourcoach.com
LETSENCRYPT_EMAIL=admin@bookyourcoach.com

# Application
APP_NAME="BookYourCoach"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bookyourcoach.com

# Base de données MySQL OVH (à distance)
DB_CONNECTION=mysql
DB_HOST=votre-host-ovh-mysql
DB_PORT=3306
DB_DATABASE=votre_db_name
DB_USERNAME=votre_db_user
DB_PASSWORD=votre_db_password

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
```

### **Étape 2 : Créer une configuration Docker Compose simplifiée**

```bash
# Créer le fichier docker-compose.fix.yml
cat > /srv/activibe/docker-compose.fix.yml << 'EOF'
version: "3.8"

services:
  # Application principale avec variables nginx-proxy
  app:
    image: olegrand1976/activibe-app:latest
    container_name: activibe-app
    restart: unless-stopped
    environment:
      - VIRTUAL_HOST=bookyourcoach.com,www.bookyourcoach.com,91.134.77.98
      - VIRTUAL_PORT=3001
      - LETSENCRYPT_HOST=bookyourcoach.com,www.bookyourcoach.com
      - LETSENCRYPT_EMAIL=admin@bookyourcoach.com
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
      - "3001:3001"  # Frontend Nuxt
      - "8080:80"    # Backend Laravel (pour debug)
      - "9000:9000"  # PHP-FPM (pour debug)
    depends_on:
      - redis
    networks:
      - nginx-proxy
      - app-network

  # Redis local
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
      - NEO4J_dbms_security_procedures_unrestricted=apoc.*
      - NEO4J_dbms_security_procedures_allowlist=apoc.*
    volumes:
      - neo4j_data:/data
      - neo4j_logs:/logs
    networks:
      - app-network

  # phpMyAdmin pour administrer la base de données OVH
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
      - UPLOAD_LIMIT=256M
      - MEMORY_LIMIT=512M
    networks:
      - app-network
    depends_on:
      - redis

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
```

### **Étape 3 : Redémarrer avec la nouvelle configuration**

```bash
# Démarrer avec la nouvelle configuration
cd /srv/activibe
docker-compose -f docker-compose.fix.yml --env-file .env up -d

# Attendre le démarrage
sleep 30

# Vérifier l'état
docker-compose -f docker-compose.fix.yml ps
```

### **Étape 4 : Tests de connectivité**

```bash
# Test nginx-proxy
curl -I http://localhost:8081

# Test application directe
curl -I http://localhost:3001

# Test infiswap (doit rester sur port 80)
curl -I http://localhost:80

# Test phpMyAdmin
curl -I http://localhost:8082

# Vérifier les logs nginx-proxy
docker logs activibe-nginx-proxy --tail=20
```

---

## 🔧 **SI LE PROBLÈME PERSISTE**

### **Debug nginx-proxy :**

```bash
# Vérifier la configuration générée par nginx-proxy
docker exec activibe-nginx-proxy cat /etc/nginx/conf.d/default.conf

# Vérifier les variables d'environnement du container app
docker exec activibe-app env | grep VIRTUAL

# Tester la connectivité interne
docker exec activibe-nginx-proxy curl -I http://activibe-app:3001
```

### **Variables d'environnement critiques :**

Assurez-vous que le container `activibe-app` a bien ces variables :
- `VIRTUAL_HOST=bookyourcoach.com,www.bookyourcoach.com,91.134.77.98`
- `VIRTUAL_PORT=3001`

---

## 🎯 **RÉSULTAT ATTENDU**

Après cette correction :
- ✅ **nginx-proxy** : Doit proxifier vers `activibe-app:3001`
- ✅ **Application** : Accessible via http://91.134.77.98:8081
- ✅ **infiswap** : Préservé sur http://91.134.77.98:80
- ✅ **Neo4j** : Accessible sur http://91.134.77.98:7474
- ✅ **phpMyAdmin** : Administration DB sur http://91.134.77.98:8082
- ✅ **Base de données** : Connexion à MySQL OVH

**🚀 Exécutez ces commandes sur le serveur pour corriger immédiatement le problème !**
