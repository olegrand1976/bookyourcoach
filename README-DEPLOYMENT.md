# 🚀 Guide de Déploiement - BookYourCoach

Ce guide explique comment construire et déployer l'application BookYourCoach de manière cohérente.

## 📋 Prérequis

- Docker et Docker Compose installés
- Accès au registry Docker (optionnel)
- Fichier de configuration d'environnement

## 🏗️ Construction des Images

### Construction complète
```bash
# Construire toutes les images
./scripts/build-all.sh

# Construire avec un tag spécifique
./scripts/build-all.sh -t v1.0.0

# Construire et pousser vers le registry
./scripts/build-all.sh -p
```

### Construction individuelle

#### Backend Laravel
```bash
# Construction simple
./scripts/build-backend.sh

# Construction avec tag et push
./scripts/build-backend.sh -t v1.0.0 -p
```

#### Frontend Nuxt.js
```bash
# Construction simple
./scripts/build-frontend.sh

# Construction avec tag et push
./scripts/build-frontend.sh -t v1.0.0 -p
```

## 🚀 Déploiement

### Production
```bash
# Déploiement avec images existantes
./scripts/deploy.sh

# Build et déploiement
./scripts/deploy.sh --build

# Déploiement avec tag spécifique
./scripts/deploy.sh -t v1.0.0 --pull
```

### Staging
```bash
# Déploiement staging
./scripts/deploy.sh -e staging --build
```

### Développement
```bash
# Démarrage de l'environnement de développement
./scripts/start-dev.sh

# Avec construction des images
./scripts/start-dev.sh --build

# Avec nettoyage complet
./scripts/start-dev.sh --clean --build
```

## 🐳 Docker Compose

### Production
```bash
# Démarrage
docker-compose up -d

# Arrêt
docker-compose down

# Logs
docker-compose logs -f
```

### Développement
```bash
# Démarrage
docker-compose -f docker-compose.dev.yml up -d

# Arrêt
docker-compose -f docker-compose.dev.yml down

# Logs
docker-compose -f docker-compose.dev.yml logs -f
```

## ⚙️ Configuration

### Variables d'environnement

Copiez `env.example` vers `.env.local` et configurez :

```bash
cp env.example .env.local
```

Variables importantes :
- `APP_KEY` : Clé de chiffrement Laravel
- `DB_*` : Configuration base de données
- `NEO4J_PASSWORD` : Mot de passe Neo4j
- `BACKEND_IMAGE` / `FRONTEND_IMAGE` : Noms des images Docker

### Ports par défaut

- **Frontend** : 3000
- **Backend** : 8080
- **phpMyAdmin** : 8082
- **Neo4j** : 7474 (web), 7687 (bolt)
- **MySQL** : 3308 (production), 3309 (dev)

## 🔧 Maintenance

### Sauvegarde
```bash
# Backup automatique lors du déploiement production
./scripts/deploy.sh --build

# Backup manuel
docker run --rm -v activibe_mysql_local_data:/data -v $(pwd)/backup:/backup alpine tar czf /backup/mysql_data.tar.gz -C /data .
```

### Nettoyage
```bash
# Nettoyer les images inutilisées
docker system prune -f

# Nettoyer les volumes
docker-compose down -v
```

### Logs
```bash
# Logs en temps réel
docker-compose logs -f

# Logs d'un service spécifique
docker-compose logs -f backend
docker-compose logs -f frontend
```

## 🚨 Dépannage

### Problèmes courants

1. **Erreur de build frontend**
   ```bash
   # Vérifier que package-lock.json existe
   ls frontend/package-lock.json
   
   # Régénérer si nécessaire
   cd frontend && npm install
   ```

2. **Services non accessibles**
   ```bash
   # Vérifier l'état des services
   docker-compose ps
   
   # Vérifier les logs
   docker-compose logs
   ```

3. **Problèmes de permissions**
   ```bash
   # Réparer les permissions
   sudo chown -R $USER:$USER .
   chmod +x scripts/*.sh
   ```

### Commandes de diagnostic

```bash
# État des conteneurs
docker-compose ps

# Utilisation des ressources
docker stats

# Espace disque
docker system df

# Logs détaillés
docker-compose logs --tail=100 -f
```

## 📚 Structure des Fichiers

```
├── scripts/
│   ├── build-all.sh          # Construction complète
│   ├── build-backend.sh      # Construction backend
│   ├── build-frontend.sh     # Construction frontend
│   ├── deploy.sh             # Déploiement production
│   └── start-dev.sh          # Démarrage développement
├── docker-compose.yml        # Configuration production
├── docker-compose.dev.yml    # Configuration développement
├── Dockerfile                # Backend Laravel
├── frontend/Dockerfile       # Frontend Nuxt.js
└── env.example              # Template de configuration
```

## 🔄 Workflow de Déploiement

1. **Développement** : `./scripts/start-dev.sh --build`
2. **Tests** : Vérification locale
3. **Build** : `./scripts/build-all.sh -t v1.0.0 -p`
4. **Déploiement** : `./scripts/deploy.sh -t v1.0.0 --pull`
5. **Vérification** : Tests de régression

## 📞 Support

En cas de problème :
1. Vérifier les logs : `docker-compose logs -f`
2. Consulter ce guide
3. Vérifier la configuration d'environnement
4. Contacter l'équipe de développement
