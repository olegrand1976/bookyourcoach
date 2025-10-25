# Documentation BookYourCoach 📚

Bienvenue dans la documentation complète de BookYourCoach, une plateforme moderne de gestion de cours et clubs sportifs.

## 📖 Table des Matières

### 🚀 Démarrage Rapide
- [README.md](../README.md) - Guide d'installation et utilisation
- [Installation avec Docker](../README.md#installation) - Configuration Docker complète

### 🛠 Documentation Technique
- [Documentation Technique](TECHNICAL_DOCUMENTATION.md) - Architecture, API, configuration
- [Configuration GitHub Actions](GITHUB_ACTIONS_CONFIG.md) - Pipeline CI/CD
- [Déploiement Production](PRODUCTION_DEPLOYMENT.md) - Guide de déploiement

### 📋 Historique
- [CHANGELOG.md](../CHANGELOG.md) - Historique des modifications

## 🎯 Vue d'Ensemble

BookYourCoach est développé avec :
- **Laravel 11** + **PHP 8.3**
- **MySQL 8.0** + **Redis 7**
- **PHPUnit 12** pour les tests
- **Docker** pour la conteneurisation
- **GitHub Actions** pour CI/CD

## 🏗 Architecture

```
├── app/
│   ├── Models/              # Modèles Eloquent
│   ├── Http/Controllers/    # Contrôleurs API
│   ├── Services/           # Services métier
│   └── Http/Middleware/    # Middlewares
├── tests/
│   ├── Unit/              # Tests unitaires (303 tests)
│   └── Feature/           # Tests d'intégration
├── docker/                # Configuration Docker
├── .github/workflows/     # Pipeline CI/CD
└── docs/                  # Documentation
```

## 🧪 Tests

Le projet inclut une suite de tests complète :

```bash
# Tests unitaires
docker exec -it activibe_app php artisan test --testsuite=Unit

# Tests avec couverture
docker exec -it activibe_app php artisan test --coverage

# Résultat actuel : 303 tests ✅
```

## 🚀 Déploiement

### Développement Local
```bash
docker-compose up -d
```

### Production
```bash
docker-compose -f docker-compose.prod.yml up -d
```

### CI/CD Automatique
- **Staging** : Push sur `develop`
- **Production** : Push sur `main`

## 📊 Fonctionnalités Principales

- ✅ **Gestion des utilisateurs** avec rôles multiples
- ✅ **Système de clubs** avec enseignants et étudiants
- ✅ **Planification des cours** avec réservations
- ✅ **Authentification sécurisée** avec Sanctum
- ✅ **QR Codes** pour adhésion rapide
- ✅ **Analyses avancées** avec Neo4j
- ✅ **Dashboard financier** pour les clubs
- ✅ **Tests complets** avec PHPUnit 12

## 🔧 Configuration

### Variables d'Environnement Principales

```env
APP_NAME=BookYourCoach
APP_ENV=production
DB_CONNECTION=mysql
REDIS_HOST=redis
```

### Services Docker

- **app** - Application Laravel (PHP 8.3)
- **mysql** - Base de données MySQL 8.0
- **redis** - Cache et sessions Redis 7
- **webserver** - Nginx (production)

## 🛡 Sécurité

- Authentification via Laravel Sanctum
- Middleware personnalisé pour l'administration
- Validation stricte des données
- Audit de sécurité automatique

## 📈 Performance

- Cache Redis pour les sessions et données
- Optimisations Docker
- Compression Nginx
- Monitoring des ressources

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature
3. Ajouter des tests
4. Créer une Pull Request

### Standards

- **PSR-12** pour le style de code
- **Tests obligatoires** pour toute nouvelle fonctionnalité
- **Documentation** des nouvelles API

## 📞 Support

- **Issues GitHub** : [Créer une issue](https://github.com/owner/bookyourcoach/issues)
- **Documentation** : Consulter cette documentation
- **Tests** : Vérifier que tous les tests passent

## 📄 License

Ce projet est sous licence MIT. Voir [LICENSE](../LICENSE) pour plus de détails.

---

**Développé avec ❤️ par l'équipe BookYourCoach**

*Dernière mise à jour : Septembre 2025*
