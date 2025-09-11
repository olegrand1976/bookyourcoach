# 🚨 DIAGNOSTIC - Erreur 503 nginx-proxy

## 🔍 **PROBLÈME IDENTIFIÉ**

D'après vos logs, voici exactement ce qui ne va pas :

### **❌ Cause principale : Variables nginx-proxy manquantes**

Le container `activibe-app` n'a **PAS** les variables d'environnement requises :
- `VIRTUAL_HOST` ❌ (manquant)
- `VIRTUAL_PORT` ❌ (manquant)

**Résultat :** nginx-proxy ne sait pas vers quel service rediriger les requêtes → **Erreur 503**

### **❌ Configuration utilisée incorrecte**

Vous utilisez encore `docker-compose.prod.yml` au lieu de `docker-compose.nginx-proxy.yml`

---

## 🚀 **SOLUTION IMMÉDIATE**

### **Sur le serveur, exécutez :**

```bash
# 1. Télécharger et exécuter le script de correction
cd /srv/activibe
wget https://raw.githubusercontent.com/olegrand1976/bookyourcoach/main/FIX-NGINX-PROXY-IMMEDIAT.sh
chmod +x FIX-NGINX-PROXY-IMMEDIAT.sh
./FIX-NGINX-PROXY-IMMEDIAT.sh
```

**OU si wget ne fonctionne pas :**

```bash
# 1. Copier manuellement le contenu du script FIX-NGINX-PROXY-IMMEDIAT.sh
cd /srv/activibe
nano fix.sh
# Coller le contenu du script
chmod +x fix.sh
./fix.sh
```

---

## 🔧 **CORRECTION MANUELLE RAPIDE**

Si le script ne fonctionne pas, voici les commandes manuelles :

### **1. Arrêter tous les containers**
```bash
cd /srv/activibe
docker stop $(docker ps -q)
docker rm $(docker ps -aq)
```

### **2. Créer le fichier .env avec les variables nginx-proxy**
```bash
cat > .env << 'EOF'
VIRTUAL_HOST=bookyourcoach.com,www.bookyourcoach.com,91.134.77.98
VIRTUAL_PORT=3001
LETSENCRYPT_HOST=bookyourcoach.com,www.bookyourcoach.com
LETSENCRYPT_EMAIL=admin@bookyourcoach.com
APP_NAME="BookYourCoach"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bookyourcoach.com
DB_CONNECTION=mysql
DB_HOST=votre-host-mysql-ovh.ovh.net
DB_PORT=3306
DB_DATABASE=votre_nom_database
DB_USERNAME=votre_username_db
DB_PASSWORD=votre_password_db_secure
REDIS_HOST=redis
REDIS_PASSWORD=redis_secure_password_2024
REDIS_PORT=6379
BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
NEO4J_PASSWORD=neo4j_secure_password_2024
IMAGE_NAME=olegrand1976/activibe-app
DOCKER_REGISTRY=docker.io
EOF
```

### **3. Redémarrer avec les bonnes variables**
```bash
# Utiliser le nouveau fichier de configuration
docker-compose -f docker-compose.nginx-proxy.yml --env-file .env up -d

# OU si ce fichier n'existe pas, utiliser cette configuration minimale :
cat > docker-compose.temp.yml << 'EOF'
version: "3.8"
services:
  app:
    image: olegrand1976/activibe-app:latest
    container_name: activibe-app
    restart: unless-stopped
    environment:
      - VIRTUAL_HOST=${VIRTUAL_HOST}
      - VIRTUAL_PORT=${VIRTUAL_PORT}
      - LETSENCRYPT_HOST=${LETSENCRYPT_HOST}
      - LETSENCRYPT_EMAIL=${LETSENCRYPT_EMAIL}
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
      - "3001:3001"
      - "8080:80"
      - "9000:9000"
    depends_on:
      - redis
    networks:
      - nginx-proxy
      - app-network

  redis:
    image: redis:7-alpine
    container_name: activibe-redis
    restart: unless-stopped
    volumes:
      - redis_data:/data
    networks:
      - app-network
    command: redis-server --appendonly yes --requirepass ${REDIS_PASSWORD}

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

docker-compose -f docker-compose.temp.yml --env-file .env up -d
```

---

## 🧪 **TESTS DE VÉRIFICATION**

Après correction, testez :

```bash
# 1. Vérifier que les variables nginx-proxy sont présentes
docker exec activibe-app env | grep -E "(VIRTUAL_HOST|VIRTUAL_PORT)"

# 2. Vérifier la configuration nginx-proxy
docker exec activibe-nginx-proxy cat /etc/nginx/conf.d/default.conf | grep activibe-app

# 3. Test de connectivité
curl -I http://localhost:8081
curl -I http://localhost:3001

# 4. Vérifier l'état des containers
docker ps
```

---

## 📊 **RÉSULTAT ATTENDU**

Après correction, vous devriez avoir :

### **Variables dans activibe-app :**
```
VIRTUAL_HOST=bookyourcoach.com,www.bookyourcoach.com,91.134.77.98
VIRTUAL_PORT=3001
```

### **Configuration nginx-proxy :**
```nginx
upstream bookyourcoach.com {
    server activibe-app:3001;
}

server {
    listen 80;
    server_name bookyourcoach.com www.bookyourcoach.com 91.134.77.98;
    location / {
        proxy_pass http://bookyourcoach.com;
        ...
    }
}
```

### **Tests de connectivité :**
- ✅ `curl http://localhost:8081` → 200 OK
- ✅ `curl http://localhost:3001` → 200 OK
- ✅ http://91.134.77.98:8081 accessible depuis le navigateur

---

## 🆘 **SI LE PROBLÈME PERSISTE**

### **1. Vérifiez les logs nginx-proxy :**
```bash
docker logs activibe-nginx-proxy --tail=20
```

### **2. Vérifiez la connectivité interne :**
```bash
docker exec activibe-nginx-proxy curl -I http://activibe-app:3001
```

### **3. Redémarrez nginx-proxy :**
```bash
docker restart activibe-nginx-proxy
sleep 30
curl -I http://localhost:8081
```

### **4. En dernier recours, contactez-moi avec :**
- Résultat de `docker ps`
- Résultat de `docker exec activibe-app env | grep VIRTUAL`
- Logs de `docker logs activibe-nginx-proxy --tail=10`

**🎯 Cette correction devrait résoudre définitivement l'erreur 503 !**
