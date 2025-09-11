# 🚀 Déploiement BookYourCoach - Guide Automatique

## 📋 Prérequis
- Docker installé
- Docker Compose installé
- Accès au repository GitHub

## 🎯 Déploiement Automatique (Recommandé)

### Option 1: Déploiement Complet Automatique
```bash
# Cloner le repository
git clone https://github.com/olegrand1976/bookyourcoach.git
cd bookyourcoach

# Déploiement automatique complet
./deploy-auto.sh
```

### Option 2: Démarrage Rapide (si déjà configuré)
```bash
# Démarrage rapide
./start.sh
```

## 🔧 Déploiement Manuel

### 1. Configuration
```bash
# Copier le fichier de configuration
cp production.env .env

# Modifier les mots de passe dans .env si nécessaire
nano .env
```

### 2. Déploiement
```bash
# Démarrer les services
docker compose -f docker-compose.prod.yml up -d

# Attendre le démarrage
sleep 30

# Configurer Laravel
docker exec bookyourcoach_app_prod php artisan key:generate --force
docker exec bookyourcoach_app_prod php artisan config:cache
docker exec bookyourcoach_app_prod php artisan migrate --force
docker exec bookyourcoach_app_prod php artisan optimize
```

## 📊 Vérification

### Statut des conteneurs
```bash
docker compose -f docker-compose.prod.yml ps
```

### Logs
```bash
docker compose -f docker-compose.prod.yml logs -f
```

### Test d'accès
```bash
curl -I http://localhost
```

## 🌐 Accès à l'Application

- **URL**: http://votre-domaine.com (port 80)
- **Local**: http://localhost

## 🔧 Commandes Utiles

### Redémarrer l'application
```bash
docker compose -f docker-compose.prod.yml restart
```

### Arrêter l'application
```bash
docker compose -f docker-compose.prod.yml down
```

### Mise à jour
```bash
git pull origin main
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d
```

### Nettoyage
```bash
docker system prune -f
docker volume prune -f
```

## 🛠️ Configuration

### Variables d'environnement importantes
- `DB_PASSWORD`: Mot de passe de la base de données
- `MYSQL_ROOT_PASSWORD`: Mot de passe root MySQL
- `REDIS_PASSWORD`: Mot de passe Redis
- `APP_URL`: URL de l'application

### Ports utilisés
- **80**: Application web (HTTP)
- **443**: Application web (HTTPS)
- **3306**: MySQL (interne)
- **6379**: Redis (interne)

## 🚨 Dépannage

### Application non accessible
```bash
# Vérifier les logs
docker compose -f docker-compose.prod.yml logs app

# Vérifier le statut
docker compose -f docker-compose.prod.yml ps

# Redémarrer
docker compose -f docker-compose.prod.yml restart
```

### Problème de base de données
```bash
# Vérifier les logs MySQL
docker compose -f docker-compose.prod.yml logs mysql

# Tester la connexion
docker exec bookyourcoach_app_prod php artisan tinker
```

### Problème de cache
```bash
# Vider le cache
docker exec bookyourcoach_app_prod php artisan cache:clear
docker exec bookyourcoach_app_prod php artisan config:clear
docker exec bookyourcoach_app_prod php artisan route:clear
```

## 📞 Support

En cas de problème, vérifiez :
1. Les logs avec `docker compose -f docker-compose.prod.yml logs -f`
2. Le statut avec `docker compose -f docker-compose.prod.yml ps`
3. La connectivité avec `curl -I http://localhost`

---

**🎉 L'application BookYourCoach est maintenant déployée et configurée automatiquement !**
