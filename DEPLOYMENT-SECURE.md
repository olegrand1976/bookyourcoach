# 🚀 Guide de Déploiement BookYourCoach - Version Sécurisée

## ⚠️ **IMPORTANT: Préservation du Port 80**

**ATTENTION**: Votre serveur a une application critique sur le port 80 qui ne doit PAS être arrêtée.

## 🎯 **Options de Déploiement**

### Option 1: Déploiement Sécurisé (Recommandé)
```bash
# Déploiement sur port 8080 (préserve le port 80)
./deploy-port-8080.sh
```

### Option 2: Migration Sécurisée
```bash
# Migration avec préservation de l'application critique
./migrate-safe.sh
```

### Option 3: Basculement vers Port 80 (ATTENTION!)
```bash
# ⚠️ ARRÊTE l'application critique sur le port 80
./switch-to-port-80.sh
```

## 📋 **Instructions pour votre Serveur**

### 1. Récupérer les dernières modifications
```bash
cd /srv/activibe
git pull origin main
```

### 2. Déploiement Sécurisé (Recommandé)
```bash
# Déploiement sur port 8080
./deploy-port-8080.sh
```

### 3. Vérification
```bash
# Vérifier le statut
docker compose -f docker-compose.prod.yml ps

# Voir les logs
docker compose -f docker-compose.prod.yml logs -f

# Tester l'accès
curl -I http://91.134.77.98:8080
```

## 🌐 **Accès à l'Application**

- **Port 8080**: http://91.134.77.98:8080 (BookYourCoach)
- **Port 80**: Préservé pour l'application critique

## 🔧 **Commandes Utiles**

### Redémarrer BookYourCoach
```bash
docker compose -f docker-compose.prod.yml restart
```

### Arrêter BookYourCoach
```bash
docker compose -f docker-compose.prod.yml down
```

### Voir les logs
```bash
docker compose -f docker-compose.prod.yml logs -f
```

## 🚨 **Dépannage**

### Erreur 503 Service Temporarily Unavailable
```bash
# Vérifier les conteneurs
docker ps

# Vérifier les logs
docker compose -f docker-compose.prod.yml logs app

# Redémarrer si nécessaire
docker compose -f docker-compose.prod.yml restart
```

### Problème de Base de Données
```bash
# Vérifier MySQL
docker compose -f docker-compose.prod.yml logs mysql

# Tester la connexion
docker exec bookyourcoach_app_prod php artisan tinker
```

## ⚠️ **Basculement vers Port 80 (Si Nécessaire)**

**ATTENTION**: Cette opération arrête l'application critique !

```bash
# 1. Identifier l'application critique
docker ps | grep ":80"

# 2. Basculer vers le port 80
./switch-to-port-80.sh

# 3. Vérifier
curl -I http://91.134.77.98
```

## 📊 **Statut Attendu**

Après déploiement réussi :
```bash
NAME                     IMAGE                                        STATUS
bookyourcoach_app_prod   olegrand1976/activibe-app:latest           Up
bookyourcoach_mysql_prod mysql:8.0                                   Up
bookyourcoach_redis_prod redis:7-alpine                              Up
bookyourcoach_webserver_prod nginx:alpine                           Up
```

## 🎉 **Résultat Final**

- ✅ **BookYourCoach déployé** sur port 8080
- ✅ **Application critique préservée** sur port 80
- ✅ **Configuration automatique** Laravel
- ✅ **Base de données** MySQL + Redis
- ✅ **Prêt pour la production**

---

**🚀 Exécutez `./deploy-port-8080.sh` pour un déploiement sécurisé !**
