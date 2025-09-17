# Configuration de Développement Local

Ce document explique comment configurer et utiliser l'environnement de développement local pour ActiVibe.

## ⚠️ Important

**La configuration de production utilise le fichier `docker-compose.yml` et `.env`**
**La configuration de développement local utilise `docker-compose.local.yml` et `.env.local`**

## 🚀 Démarrage Rapide

### Pour le développement local :

```bash
# Démarrer l'environnement local
./start-local.sh

# Arrêter l'environnement local
./stop-local.sh
```

### Pour la production :

```bash
# Démarrer l'environnement de production
docker-compose up -d

# Arrêter l'environnement de production
docker-compose down
```

## 📁 Fichiers de Configuration

### Production
- `docker-compose.yml` - Configuration Docker pour la production
- `.env` - Variables d'environnement pour la production (base de données OVH)

### Développement Local
- `docker-compose.local.yml` - Configuration Docker pour le développement local
- `.env.local` - Variables d'environnement pour le développement local (MySQL local)
- `start-local.sh` - Script pour démarrer l'environnement local
- `stop-local.sh` - Script pour arrêter l'environnement local

## 🗄️ Base de Données

### Production
- **MySQL OVH** : `mysql-dae24fb8-odf582313.database.cloud.ovh.net:20184`
- **Base de données** : `book-your-coach`

### Développement Local
- **MySQL Local** : `mysql-local:3306` (accessible via `localhost:3308`)
- **Base de données** : `book_your_coach_local`
- **phpMyAdmin** : http://localhost:8082

## 🔧 Services Disponibles

### Production
- **Frontend** : http://localhost:3000
- **Backend API** : http://localhost:8080
- **Neo4j Browser** : http://localhost:7474

### Développement Local
- **Frontend** : http://localhost:3000
- **Backend API** : http://localhost:8080
- **Neo4j Browser** : http://localhost:7474
- **phpMyAdmin** : http://localhost:8082

## 🛠️ Commandes Utiles

### Développement Local

```bash
# Voir les logs du backend
docker-compose -f docker-compose.local.yml logs -f backend

# Exécuter les migrations
docker-compose -f docker-compose.local.yml exec backend php artisan migrate

# Exécuter les seeders
docker-compose -f docker-compose.local.yml exec backend php artisan db:seed

# Accéder au shell du container backend
docker-compose -f docker-compose.local.yml exec backend bash

# Vider le cache
docker-compose -f docker-compose.local.yml exec backend php artisan cache:clear
```

### Production

```bash
# Voir les logs du backend
docker-compose logs -f backend

# Exécuter les migrations
docker-compose exec backend php artisan migrate

# Accéder au shell du container backend
docker-compose exec backend bash
```

## 🔄 Migration entre Environnements

### Passer de Production à Local

```bash
# Arrêter la production
docker-compose down

# Démarrer le local
./start-local.sh
```

### Passer de Local à Production

```bash
# Arrêter le local
./stop-local.sh

# Démarrer la production
docker-compose up -d
```

## 🐛 Dépannage

### Problème de Port Occupé

Si vous avez des erreurs de port occupé, vérifiez quels ports sont utilisés :

```bash
# Voir les ports utilisés
netstat -tulpn | grep :8080
netstat -tulpn | grep :3000
netstat -tulpn | grep :3308
```

### Problème de Base de Données

Si la base de données locale ne démarre pas :

```bash
# Voir les logs MySQL
docker-compose -f docker-compose.local.yml logs mysql-local

# Redémarrer MySQL
docker-compose -f docker-compose.local.yml restart mysql-local
```

### Problème de Cache

Si vous avez des problèmes de cache :

```bash
# Vider tous les caches
docker-compose -f docker-compose.local.yml exec backend php artisan cache:clear
docker-compose -f docker-compose.local.yml exec backend php artisan config:clear
docker-compose -f docker-compose.local.yml exec backend php artisan route:clear
docker-compose -f docker-compose.local.yml exec backend php artisan view:clear
```
