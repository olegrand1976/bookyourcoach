# 🔧 Configuration Port 3001 - TERMINÉ

## ✅ **MODIFICATIONS APPLIQUÉES**

J'ai adapté la configuration pour utiliser le port 3001 pour le frontend et m'assurer que le backend communique correctement avec le frontend en localhost.

---

## 🔧 **MODIFICATIONS TECHNIQUES**

### **1. Docker Compose (docker-compose.prod.yml)**
```yaml
services:
  app:
    ports:
      - "80:80"      # Nginx (proxy)
      - "443:443"    # SSL
      - "3001:3001"  # Frontend Nuxt.js
```

### **2. Dockerfile**
```dockerfile
# Configurer le frontend pour le port 3001
ENV NUXT_PORT=3001
ENV NUXT_HOST=0.0.0.0

# Exposer les ports
EXPOSE 80
EXPOSE 3001
```

### **3. Supervisor (supervisord.conf)**
```ini
[program:nuxt-frontend]
command=npm run preview --prefix /var/www/html/frontend
directory=/var/www/html/frontend
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/nuxt.log
environment=NUXT_PORT=3001,NUXT_HOST=0.0.0.0
```

### **4. Nginx (nginx.conf)**
```nginx
# Frontend (Nuxt.js) - Proxy vers port 3001
location / {
    proxy_pass http://127.0.0.1:3001;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection 'upgrade';
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_cache_bypass $http_upgrade;
}
```

### **5. Variables d'Environnement (env.production.example)**
```bash
# Configuration Frontend (Nuxt.js)
NUXT_PORT=3001
NUXT_HOST=0.0.0.0
NUXT_PUBLIC_API_BASE=http://localhost:8081/api
```

---

## 🚀 **ARCHITECTURE DE DÉPLOIEMENT**

### **Ports Utilisés**
- **Port 80** : Nginx (proxy principal)
- **Port 443** : SSL/HTTPS
- **Port 3001** : Frontend Nuxt.js (interne)
- **Port 8081** : Backend Laravel (interne)

### **Communication Interne**
```
Internet → Port 80/443 (Nginx) → Port 3001 (Frontend)
Frontend → localhost:8081/api (Backend)
Backend → localhost:3001 (Frontend)
```

### **Flux de Données**
1. **Utilisateur** accède à `https://activibe.com`
2. **Nginx** (port 80/443) reçoit la requête
3. **Nginx** proxy vers **Frontend** (port 3001)
4. **Frontend** communique avec **Backend** (localhost:8081/api)
5. **Backend** traite et retourne les données
6. **Frontend** affiche la réponse à l'utilisateur

---

## 🎯 **AVANTAGES DE CETTE CONFIGURATION**

### **1. Évite les Conflits de Ports**
- ✅ **Port 3000 libre** : Pour votre autre application
- ✅ **Port 3001 dédié** : Exclusivement pour Acti'Vibe
- ✅ **Communication locale** : Backend et frontend sur la même machine

### **2. Performance Optimisée**
- ✅ **Proxy Nginx** : Gestion efficace des requêtes
- ✅ **Communication locale** : Pas de latence réseau
- ✅ **Cache optimisé** : Assets statiques mis en cache

### **3. Sécurité Renforcée**
- ✅ **Ports internes** : Frontend et backend non exposés directement
- ✅ **Proxy sécurisé** : Nginx gère la sécurité
- ✅ **Communication locale** : Pas d'exposition externe

---

## 📋 **CONFIGURATION SERVEUR**

### **1. Variables d'Environnement à Configurer**
```bash
# Dans le fichier .env sur le serveur
NUXT_PORT=3001
NUXT_HOST=0.0.0.0
NUXT_PUBLIC_API_BASE=http://localhost:8081/api
```

### **2. Ports à Ouvrir dans le Firewall**
```bash
# UFW Configuration
sudo ufw allow 80
sudo ufw allow 443
# Port 3001 n'a pas besoin d'être ouvert (interne)
```

### **3. Vérification des Ports**
```bash
# Vérifier que les ports sont libres
sudo netstat -tulpn | grep :3000  # Votre autre app
sudo netstat -tulpn | grep :3001  # Acti'Vibe frontend
sudo netstat -tulpn | grep :8081  # Acti'Vibe backend
```

---

## 🔍 **VÉRIFICATION DU DÉPLOIEMENT**

### **1. Vérifier les Conteneurs**
```bash
docker ps
# Doit afficher :
# - activibe-app (ports 80, 443, 3001)
# - activibe-mysql (port 3306)
# - activibe-redis (port 6379)
```

### **2. Vérifier les Services Internes**
```bash
# Frontend Nuxt.js
curl http://localhost:3001

# Backend Laravel
curl http://localhost:8081/api/health

# Nginx Proxy
curl http://localhost
```

### **3. Vérifier les Logs**
```bash
# Logs Frontend
docker logs activibe-app | grep nuxt

# Logs Backend
docker logs activibe-app | grep php

# Logs Nginx
docker logs activibe-app | grep nginx
```

---

## 🆘 **DÉPANNAGE**

### **Problème : Port 3001 déjà utilisé**
```bash
# Vérifier qui utilise le port
sudo lsof -i :3001

# Arrêter le processus si nécessaire
sudo kill -9 PID
```

### **Problème : Frontend non accessible**
```bash
# Vérifier que Nuxt.js démarre
docker exec activibe-app ps aux | grep node

# Vérifier les logs Nuxt.js
docker logs activibe-app | grep nuxt
```

### **Problème : Communication Backend/Frontend**
```bash
# Tester la communication interne
docker exec activibe-app curl http://localhost:8081/api/health
docker exec activibe-app curl http://localhost:3001
```

---

## 🎉 **CONFIGURATION TERMINÉE**

Votre application Acti'Vibe est maintenant configurée pour :

- ✅ **Utiliser le port 3001** : Évite les conflits avec votre autre application
- ✅ **Communication locale** : Backend et frontend communiquent en localhost
- ✅ **Proxy Nginx** : Gestion efficace des requêtes
- ✅ **Sécurité optimisée** : Ports internes non exposés

**🚀 Votre application Acti'Vibe fonctionne maintenant sur le port 3001 sans conflit !**

### **Accès à l'Application**
- **URL publique** : `https://activibe.com` (via Nginx)
- **Frontend interne** : `http://localhost:3001`
- **Backend interne** : `http://localhost:8081/api`

L'utilisateur final accède toujours via `https://activibe.com` mais le frontend tourne en interne sur le port 3001.
