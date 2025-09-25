# Scripts BookYourCoach

Ce dossier contient tous les scripts utilitaires pour gérer, tester et déployer l'application BookYourCoach.

## 📋 Scripts disponibles

### 🧪 Tests
- **`test-all.sh`** - Suite complète de tests pour toutes les fonctionnalités
- **`test-login-process.sh`** - Test spécifique du processus de connexion

### 🐳 Docker
- **`docker-maintenance.sh`** - Gestion des conteneurs Docker (start, stop, restart, clean, logs)

### 🚀 Déploiement
- **`deploy.sh`** - Déploiement automatique selon l'environnement (local, dev, prod)

## 🚀 Utilisation rapide

### Tests
```bash
# Tous les tests
./scripts/test-all.sh

# Test spécifique
./scripts/test-all.sh login
./scripts/test-all.sh api
./scripts/test-all.sh docker
./scripts/test-all.sh frontend
```

### Maintenance Docker
```bash
# Démarrer les services
./scripts/docker-maintenance.sh start

# Arrêter les services
./scripts/docker-maintenance.sh stop

# Redémarrer les services
./scripts/docker-maintenance.sh restart

# Reconstruire et redémarrer
./scripts/docker-maintenance.sh rebuild

# Nettoyer Docker
./scripts/docker-maintenance.sh clean

# Voir les logs
./scripts/docker-maintenance.sh logs

# Voir le statut
./scripts/docker-maintenance.sh status
```

### Déploiement
```bash
# Déploiement local
./scripts/deploy.sh local

# Déploiement développement
./scripts/deploy.sh dev

# Déploiement production
./scripts/deploy.sh prod
```

## 🔧 Configuration

Les scripts utilisent les configurations suivantes par défaut :
- **Frontend** : http://localhost:3000
- **Backend** : http://localhost:8080
- **phpMyAdmin** : http://localhost:8082
- **Neo4j** : http://localhost:7474
- **MySQL** : localhost:3308

## 📝 Notes importantes

- Tous les scripts sont exécutables (`chmod +x`)
- Les scripts utilisent des couleurs pour une meilleure lisibilité
- Les erreurs sont affichées en rouge, les succès en vert
- Les scripts vérifient les prérequis avant l'exécution

## 🆘 Aide

Pour obtenir de l'aide sur un script spécifique :
```bash
./scripts/nom-du-script.sh help
```

## 🔄 Mise à jour

Les scripts sont automatiquement mis à jour lors des modifications du projet. Assurez-vous d'avoir les dernières versions en faisant :
```bash
git pull origin main
```
