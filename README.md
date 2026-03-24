# 🏇 BookYourCoach

**La plateforme de référence pour réserver vos cours de sports avec les meilleurs clubs et enseignants !**

[![Docker](https://img.shields.io/badge/Docker-Ready-blue?logo=docker)](https://www.docker.com/)
[![Laravel](https://img.shields.io/badge/Laravel-11-red?logo=laravel)](https://laravel.com/)
[![Nuxt](https://img.shields.io/badge/Nuxt-3-green?logo=nuxt.js)](https://nuxt.com/)
[![Flutter](https://img.shields.io/badge/Flutter-Mobile-blue?logo=flutter)](https://flutter.dev/)

## 🎯 Vue d'ensemble

BookYourCoach est une application web complète permettant aux clubs sportifs de gérer leurs cours, enseignants et étudiants. L'application comprend :

- **🌐 Frontend** : Interface utilisateur moderne avec Nuxt.js 3
- **🔧 Backend** : API REST avec Laravel 11
- **🗄️ Base de données** : MySQL pour les données relationnelles
- **🕸️ Graph Database** : Neo4j pour les relations complexes
- **📱 Mobile** : Application Flutter pour iOS et Android

## 🚀 Démarrage rapide

### Prérequis
- Docker et Docker Compose
- Git

### Installation en 3 étapes

1. **Cloner le projet**
   ```bash
   git clone <repository-url>
   cd bookyourcoach
   ```

2. **Démarrer avec Docker**
   ```bash
   ./scripts/docker-maintenance.sh start
   ```

3. **Tester l'installation**
   ```bash
   ./scripts/test-all.sh
   ```

### 🌐 Accès aux services

Une fois démarré, accédez aux services via :

- **Frontend** : http://localhost:3000
- **Backend API** : http://localhost:8080
- **phpMyAdmin** : http://localhost:8082
- **Neo4j Browser** : http://localhost:7474

## 🛠️ Scripts utilitaires

Le projet inclut des scripts pour faciliter le développement :

### Tests
```bash
# Tous les tests
./scripts/test-all.sh

# Tests spécifiques
./scripts/test-all.sh login
./scripts/test-all.sh api
./scripts/test-all.sh docker
```

**PHPUnit (SQLite)** : si l’hôte n’a pas `pdo_sqlite`, utiliser le service Compose `php-test` (profil `test`) :

```bash
docker compose --profile test build php-test
composer test:docker                    # docker-compose.yml
composer test:docker:local              # docker-compose.local.yml
composer test:recurring:docker          # sous-ensemble récurrence
composer test:recurring:docker:local
```

### Maintenance Docker
```bash
# Démarrer les services
./scripts/docker-maintenance.sh start

# Arrêter les services
./scripts/docker-maintenance.sh stop

# Reconstruire et redémarrer
./scripts/docker-maintenance.sh rebuild

# Voir les logs
./scripts/docker-maintenance.sh logs
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

## 📚 Documentation

La documentation complète est disponible dans le dossier [`docs/`](docs/) :

- **[Index de la documentation](docs/INDEX.md)** – Vue d'ensemble
- **[Documentation fonctionnelle](docs/DOCUMENTATION_FONCTIONNELLE.md)** – Rôles et fonctionnalités
- **[Documentation technique](docs/DOCUMENTATION_TECHNIQUE.md)** – Architecture et API
- **[Scripts utilitaires](scripts/README.md)** – Scripts disponibles
- **[Déploiement production](docs/PRODUCTION_DEPLOYMENT.md)** – Déploiement

## 🏗️ Architecture

```
bookyourcoach/
├── 📁 app/                 # Backend Laravel
├── 📁 frontend/            # Frontend Nuxt.js
├── 📁 mobile/              # Application Flutter
├── 📁 scripts/             # Scripts utilitaires
├── 📁 docs/                # Documentation
├── 📁 docker/              # Configuration Docker
├── 📁 config/              # Configuration Laravel
├── 📁 database/            # Migrations et seeders
└── 📁 routes/              # Routes API
```

## 🔐 Authentification

L'application utilise un système d'authentification robuste avec :
- **Sanctum** pour l'authentification API
- **Tokens** pour les sessions sécurisées
- **Rôles** : Club, Enseignant, Étudiant, Admin
- **SSR** avec gestion hybride côté serveur/client

## 🎨 Fonctionnalités principales

### Pour les Clubs
- 📊 Dashboard de gestion
- 👥 Gestion des enseignants et étudiants
- 📅 Planification des cours
- 💰 Suivi des revenus
- 📱 Application mobile dédiée

### Pour les Enseignants
- 📅 Gestion du planning
- 👨‍🎓 Suivi des étudiants
- 💵 Gestion des gains
- 📱 Application mobile

### Pour les Étudiants
- 🔍 Recherche de cours
- 📅 Réservation en ligne
- 📱 Application mobile
- 📊 Suivi des progrès

## 🧪 Tests

Le projet inclut une suite de tests complète :

```bash
# Tests automatiques
./scripts/test-all.sh

# Tests spécifiques
./scripts/test-all.sh login    # Processus de connexion
./scripts/test-all.sh api      # APIs backend
./scripts/test-all.sh docker   # Conteneurs Docker
./scripts/test-all.sh frontend # Interface utilisateur
```

## 🚀 Déploiement

### Local
```bash
./scripts/deploy.sh local
```

### Production
```bash
./scripts/deploy.sh prod
```

Voir la [documentation de déploiement](docs/PRODUCTION_DEPLOYMENT.md) pour plus de détails.

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📞 Support

- 📧 **Email** : o.legrand@ll-it-sc.be
- 📞 **Téléphone** : +32 478.02.33.77
- 🏠 **Localisation** : Waudrez, Belgique

## 📄 Licence

© 2025 BookYourCoach. Tous droits réservés.

---

**Développé avec ❤️ pour la communauté sportive** 🏆
