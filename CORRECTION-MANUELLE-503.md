# 🚨 CORRECTION MANUELLE IMMÉDIATE - Erreur 503

## 🔍 **ANALYSE DES LOGS PRÉCÉDENTS**

D'après vos logs, voici le problème exact :

### **❌ Problème identifié :**
1. **Container `activibe-app`** expose le port 3001
2. **nginx-proxy** cherche le port 3001 mais ne trouve pas le service
3. **Variables nginx-proxy manquantes** dans le container `activibe-app`
4. **Configuration hybride** : `docker-compose.prod.yml` + nginx-proxy manuel

### **🎯 Cause racine :**
Le container `activibe-app` n'a **PAS** les variables d'environnement `VIRTUAL_HOST` et `VIRTUAL_PORT` que nginx-proxy utilise pour détecter le service.

---

## 🚀 **CORRECTION MANUELLE IMMÉDIATE**

### **Sur le serveur, exécutez ces commandes :**

```bash
cd /srv/activibe

# 1. Vérifier l'état actuel
echo "=== État actuel ==="
docker ps --format "table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}"

# 2. Vérifier les variables nginx-proxy
echo "=== Variables nginx-proxy ==="
docker exec activibe-app env | grep -E "(VIRTUAL_HOST|VIRTUAL_PORT)" || echo "❌ Variables nginx-proxy MANQUANTES !"

# 3. Arrêter le container activibe-app
echo "=== Arrêt du container activibe-app ==="
docker stop activibe-app
docker rm activibe-app

# 4. Recréer le container avec les variables nginx-proxy
echo "=== Recréation du container avec variables nginx-proxy ==="
docker run -d \
    --name activibe-app \
    --network activibe_app-network \
    -e VIRTUAL_HOST=bookyourcoach.com,www.bookyourcoach.com,91.134.77.98 \
    -e VIRTUAL_PORT=3001 \
    -e LETSENCRYPT_HOST=bookyourcoach.com,www.bookyourcoach.com \
    -e LETSENCRYPT_EMAIL=admin@bookyourcoach.com \
    -e APP_NAME="BookYourCoach" \
    -e APP_ENV=production \
    -e APP_DEBUG=false \
    -e APP_URL=https://bookyourcoach.com \
    -e DB_CONNECTION=mysql \
    -e DB_HOST=votre-host-mysql-ovh.ovh.net \
    -e DB_PORT=3306 \
    -e DB_DATABASE=votre_nom_database \
    -e DB_USERNAME=votre_username_db \
    -e DB_PASSWORD=votre_password_db_secure \
    -e REDIS_HOST=redis \
    -e REDIS_PORT=6379 \
    -e REDIS_PASSWORD=redis_secure_password_2024 \
    -e CACHE_DRIVER=redis \
    -e SESSION_DRIVER=redis \
    -e QUEUE_CONNECTION=redis \
    -p 3001:3001 \
    -p 8080:80 \
    -p 9000:9000 \
    olegrand1976/activibe-app:latest

# 5. Attendre le démarrage
echo "=== Attente du démarrage (30 secondes) ==="
sleep 30

# 6. Redémarrer nginx-proxy pour qu'il détecte les nouvelles variables
echo "=== Redémarrage de nginx-proxy ==="
docker restart activibe-nginx-proxy
sleep 10

# 7. Vérifier la correction
echo "=== Vérification de la correction ==="
docker exec activibe-app env | grep -E "(VIRTUAL_HOST|VIRTUAL_PORT)"

# 8. Tests de connectivité
echo "=== Tests de connectivité ==="
curl -I http://localhost:8081 && echo "✅ Port 8081 (nginx-proxy) : OK" || echo "❌ Port 8081 : KO"
curl -I http://localhost:3001 && echo "✅ Port 3001 (app directe) : OK" || echo "❌ Port 3001 : KO"
curl -I http://localhost:8080 && echo "✅ Port 8080 (app directe) : OK" || echo "❌ Port 8080 : KO"

# 9. État final
echo "=== État final ==="
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
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

---

## 🎯 **POURQUOI CETTE CORRECTION FONCTIONNE**

1. **Variables nginx-proxy ajoutées** : nginx-proxy peut maintenant détecter le service
2. **VIRTUAL_PORT=3001** : correspond au port exposé par l'application
3. **VIRTUAL_HOST** : nginx-proxy sait vers quel domaine rediriger
4. **Redémarrage nginx-proxy** : recharge la configuration avec le nouveau service

**🎯 Cette correction devrait résoudre définitivement l'erreur 503 !**
