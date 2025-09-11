# 🔍 INVESTIGATION APPROFONDIE - Problème 503 nginx-proxy

## 🚨 **PROBLÈME IDENTIFIÉ**

D'après l'analyse approfondie de vos logs et de la configuration, voici les **vraies causes** du problème 503 :

### **❌ Cause principale : Incompatibilité de ports**

1. **L'image `olegrand1976/activibe-app` expose le port 80** (Laravel avec nginx interne)
2. **nginx-proxy cherche le port 3001** (Nuxt.js) qui n'existe pas dans cette image
3. **Résultat :** nginx-proxy ne trouve pas le service → **Erreur 503**

### **❌ Cause secondaire : Configuration incorrecte**

1. **Vous utilisez encore `docker-compose.prod.yml`** au lieu de `docker-compose.nginx-proxy.yml`
2. **Les variables nginx-proxy ne sont pas dans le bon fichier**
3. **Réseaux Docker incohérents**

---

## 🔍 **DIAGNOSTIC APPROFONDI**

### **Analyse de l'image Docker :**

L'image `olegrand1976/activibe-app:latest` est une **image Laravel uniquement** qui :
- ✅ Expose le port **80** (nginx + Laravel)
- ❌ N'expose **PAS** le port 3001 (Nuxt.js)
- ❌ Ne contient **PAS** le frontend Nuxt.js

### **Analyse de la configuration actuelle :**

```bash
# Votre commande actuelle
docker compose -f docker-compose.prod.yml logs -f

# Problème : docker-compose.prod.yml utilise VIRTUAL_PORT=3001
# Mais l'image expose le port 80
```

---

## 🎯 **SOLUTION APPROFONDIE**

### **Option 1 : Correction immédiate (Recommandée)**

**Sur le serveur, exécutez :**

```bash
# 1. Télécharger et exécuter le script de correction approfondie
cd /srv/activibe
wget https://raw.githubusercontent.com/olegrand1976/bookyourcoach/main/CORRECTION-APPROFONDIE-503.sh
chmod +x CORRECTION-APPROFONDIE-503.sh
./CORRECTION-APPROFONDIE-503.sh
```

**Ce script :**
- ✅ Arrête complètement l'ancienne configuration
- ✅ Configure `VIRTUAL_PORT=80` (port Laravel)
- ✅ Crée une configuration Docker Compose corrigée
- ✅ Redémarre avec les bonnes variables nginx-proxy

### **Option 2 : Correction manuelle**

```bash
# 1. Arrêter tous les containers
cd /srv/activibe
docker stop $(docker ps -q)
docker rm $(docker ps -aq)

# 2. Créer le fichier .env avec VIRTUAL_PORT=80
cat > .env << 'EOF'
VIRTUAL_HOST=bookyourcoach.com,www.bookyourcoach.com,91.134.77.98
VIRTUAL_PORT=80
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

# 3. Utiliser la nouvelle configuration
docker-compose -f docker-compose.nginx-proxy.yml --env-file .env up -d
```

---

## 🧪 **TESTS DE VÉRIFICATION**

Après correction, testez :

```bash
# 1. Vérifier que les variables nginx-proxy sont présentes
docker exec activibe-app env | grep -E "(VIRTUAL_HOST|VIRTUAL_PORT)"

# Doit afficher :
# VIRTUAL_HOST=bookyourcoach.com,www.bookyourcoach.com,91.134.77.98
# VIRTUAL_PORT=80

# 2. Vérifier la configuration nginx-proxy
docker exec activibe-nginx-proxy cat /etc/nginx/conf.d/default.conf | grep activibe-app

# 3. Test de connectivité
curl -I http://localhost:8081
curl -I http://localhost:80

# 4. Vérifier l'état des containers
docker ps
```

---

## 📊 **RÉSULTAT ATTENDU**

Après correction, vous devriez avoir :

### **Variables dans activibe-app :**
```
VIRTUAL_HOST=bookyourcoach.com,www.bookyourcoach.com,91.134.77.98
VIRTUAL_PORT=80
```

### **Configuration nginx-proxy :**
```nginx
upstream bookyourcoach.com {
    server activibe-app:80;  # ← Port 80, pas 3001 !
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
- ✅ `curl http://localhost:80` → 200 OK
- ✅ http://91.134.77.98:8081 accessible depuis le navigateur

---

## 🔧 **POURQUOI CETTE SOLUTION FONCTIONNE**

### **Problème original :**
```
nginx-proxy cherche activibe-app:3001
Mais l'image expose seulement le port 80
→ nginx-proxy ne trouve pas le service
→ Erreur 503
```

### **Solution appliquée :**
```
nginx-proxy cherche activibe-app:80
L'image expose le port 80
→ nginx-proxy trouve le service
→ Application accessible
```

---

## 🆘 **SI LE PROBLÈME PERSISTE**

### **1. Diagnostic approfondi :**
```bash
# Exécuter le script de diagnostic
cd /srv/activibe
wget https://raw.githubusercontent.com/olegrand1976/bookyourcoach/main/DIAGNOSTIC-APPROFONDI-503.sh
chmod +x DIAGNOSTIC-APPROFONDI-503.sh
./DIAGNOSTIC-APPROFONDI-503.sh
```

### **2. Vérifications supplémentaires :**
```bash
# Vérifier les ports de l'image
docker inspect olegrand1976/activibe-app:latest | jq -r '.[0].Config.ExposedPorts'

# Vérifier les processus dans le container
docker exec activibe-app ps aux

# Vérifier les ports en écoute
docker exec activibe-app netstat -tlnp
```

### **3. En dernier recours :**
Si le problème persiste, il peut y avoir un problème avec l'image Docker elle-même. Dans ce cas :
1. Vérifiez que l'image est bien construite avec le bon Dockerfile
2. Reconstruisez l'image si nécessaire
3. Contactez-moi avec les résultats du diagnostic approfondi

---

## 🎯 **RÉSUMÉ DE LA SOLUTION**

**Le problème 503 vient de l'incompatibilité entre :**
- **nginx-proxy** qui cherche le port 3001
- **L'image Docker** qui expose le port 80

**La solution est de :**
- ✅ Configurer `VIRTUAL_PORT=80` dans nginx-proxy
- ✅ Utiliser la bonne configuration Docker Compose
- ✅ S'assurer que les variables nginx-proxy sont présentes

**🎯 Cette correction devrait résoudre définitivement l'erreur 503 !**
