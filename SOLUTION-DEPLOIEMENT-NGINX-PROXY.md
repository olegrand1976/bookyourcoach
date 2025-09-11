# 🔧 Solution : Problème de déploiement nginx-proxy

## 🚨 **PROBLÈMES IDENTIFIÉS**

### **1. Configuration Docker Compose incohérente**
- **Logs de production** : Containers nommés `activibe-*` avec nginx-proxy
- **Fichier local** : `docker-compose.prod.yml` définit des containers `bookyourcoach_*` sans nginx-proxy
- **Résultat** : Le serveur utilise une configuration différente de votre repository

### **2. nginx-proxy retourne 503**
- nginx-proxy ne trouve pas les services backend à proxifier
- Variable `IMAGE_NAME` non définie (warning dans les logs)
- Problème de communication entre containers

### **3. Container infiswap-front manquant**
- Le port 80 doit être préservé pour infiswap-front
- Ce container n'apparaît pas dans la configuration actuelle

---

## ✅ **SOLUTION MISE EN PLACE**

### **1. Nouvelle configuration Docker Compose**
**Fichier** : `docker-compose.nginx-proxy.yml`

**Caractéristiques** :
- ✅ Compatible avec nginx-proxy
- ✅ Containers nommés `activibe-*` (cohérent avec la production)
- ✅ Container `infiswap-front` préservé sur le port 80
- ✅ Configuration réseau optimisée
- ✅ Variables d'environnement complètes

### **2. Configuration nginx-proxy**
```yaml
nginx-proxy:
  image: nginxproxy/nginx-proxy:latest
  container_name: activibe-nginx-proxy
  ports:
    - "8081:80"   # HTTP via nginx-proxy
    - "8444:443"  # HTTPS via nginx-proxy
  environment:
    - TRUST_DOWNSTREAM_PROXY=false
```

### **3. Container d'application unifié**
```yaml
app:
  image: olegrand1976/activibe-app:latest
  container_name: activibe-app
  environment:
    - VIRTUAL_HOST=bookyourcoach.com,www.bookyourcoach.com
    - VIRTUAL_PORT=3001
  ports:
    - "3001:3001"  # Frontend Nuxt
    - "80:80"      # Backend Laravel
    - "9000:9000"  # PHP-FPM
```

### **4. Container infiswap-front préservé**
```yaml
infiswap-front:
  image: nginx:alpine
  container_name: infiswap-front
  ports:
    - "80:80"  # Port 80 préservé
```

---

## 🚀 **DÉPLOIEMENT DE LA SOLUTION**

### **Étape 1 : Sur votre serveur de production**

```bash
# Aller dans le répertoire de l'application
cd /srv/activibe  # ou le répertoire approprié

# Télécharger les nouveaux fichiers de configuration
# (copiez docker-compose.nginx-proxy.yml et production.env)

# Exécuter le script de correction
./fix-production-deployment.sh
```

### **Étape 2 : Vérifications**

```bash
# Vérifier l'état des containers
docker ps

# Doit afficher :
# - activibe-app (ports 80, 3001, 9000)
# - activibe-nginx-proxy (ports 8081, 8444)
# - infiswap-front (port 80)
# - bookyourcoach_mysql_prod
# - activibe-redis
# - activibe-neo4j
```

### **Étape 3 : Tests de connectivité**

```bash
# Test nginx-proxy
curl http://localhost:8081

# Test infiswap-front
curl http://localhost:80

# Test application directement
curl http://localhost:3001
```

---

## 📋 **VARIABLES D'ENVIRONNEMENT MISES À JOUR**

### **Nouvelles variables ajoutées dans `production.env`** :

```bash
# nginx-proxy Configuration
VIRTUAL_HOST=bookyourcoach.com,www.bookyourcoach.com
LETSENCRYPT_HOST=bookyourcoach.com,www.bookyourcoach.com
LETSENCRYPT_EMAIL=admin@bookyourcoach.com

# Hosts corrigés pour communication interne
DB_HOST=mysql          # au lieu de bookyourcoach_mysql_prod
REDIS_HOST=redis       # au lieu de bookyourcoach_redis_prod
```

---

## 🔍 **DIAGNOSTIC DES ERREURS**

### **Erreur 503 Service Temporarily Unavailable**
**Cause** : nginx-proxy ne trouve pas le service backend
**Solution** : Vérifier que `VIRTUAL_HOST` et `VIRTUAL_PORT` sont correctement configurés

### **Warning: IMAGE_NAME variable is not set**
**Cause** : Variable `IMAGE_NAME` manquante dans le fichier d'environnement
**Solution** : Ajoutée dans `production.env` : `IMAGE_NAME=olegrand1976/activibe-app`

### **Container activibe-app non accessible**
**Cause** : Ports non exposés ou service non démarré
**Solution** : Configuration des ports `3001:3001`, `80:80`, `9000:9000`

---

## 🎯 **ARCHITECTURE RÉSEAU FINALE**

```
Internet (Port 80) → infiswap-front
Internet (Port 8081) → nginx-proxy → activibe-app (Port 3001)
Internet (Port 7474) → activibe-neo4j

Réseau interne :
- activibe-app ↔ mysql (Port 3306)
- activibe-app ↔ redis (Port 6379)
- activibe-app ↔ neo4j (Port 7687)
```

---

## 🆘 **DÉPANNAGE**

### **Si l'application ne démarre toujours pas**

1. **Vérifier les logs** :
```bash
docker-compose -f docker-compose.nginx-proxy.yml logs -f
```

2. **Redémarrer les services** :
```bash
docker-compose -f docker-compose.nginx-proxy.yml restart
```

3. **Reconstruire les containers** :
```bash
docker-compose -f docker-compose.nginx-proxy.yml down
docker-compose -f docker-compose.nginx-proxy.yml up -d --force-recreate
```

### **Si nginx-proxy retourne toujours 503**

1. **Vérifier la configuration nginx-proxy** :
```bash
docker exec activibe-nginx-proxy cat /etc/nginx/conf.d/default.conf
```

2. **Vérifier les variables d'environnement** :
```bash
docker exec activibe-app env | grep VIRTUAL
```

3. **Tester la connectivité interne** :
```bash
docker exec activibe-nginx-proxy curl http://activibe-app:3001
```

---

## 🎉 **RÉSULTAT ATTENDU**

Après déploiement de cette solution :

- ✅ **nginx-proxy** : Fonctionne correctement sur le port 8081
- ✅ **infiswap-front** : Préservé sur le port 80
- ✅ **activibe-app** : Accessible via nginx-proxy
- ✅ **Base de données** : Communication interne fonctionnelle
- ✅ **Configuration cohérente** : Entre développement et production

**L'application BookYourCoach sera accessible via http://votre-serveur:8081**
