# BookYourCoach 🏃‍♂️⚽🎾

Une plateforme moderne de gestion de cours et clubs sportifs développée avec Laravel 11 et PHP 8.3.

## 🚀 Fonctionnalités

- **Gestion des utilisateurs** avec rôles multiples (admin, enseignant, étudiant, club)
- **Système de clubs** avec gestion des enseignants et étudiants
- **Planification des cours** avec disponibilités et réservations
- **Authentification sécurisée** avec Laravel Sanctum
- **QR Codes** pour l'ajout rapide d'utilisateurs aux clubs
- **Analyses avancées** avec intégration Neo4j
- **Dashboard financier** pour les clubs
- **Tests complets** avec PHPUnit 12

## 🛠 Stack Technique

- **Backend:** Laravel 11.x + PHP 8.3
- **Base de données:** MySQL 8.0
- **Cache:** Redis 7
- **Tests:** PHPUnit 12.3.8
- **Docker:** Support complet avec Docker Compose
- **CI/CD:** GitHub Actions

## 📋 Prérequis

- PHP 8.3+
- Composer
- Docker & Docker Compose
- MySQL 8.0+
- Redis

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/votre-username/bookyourcoach.git
cd bookyourcoach
```

### 2. Installation avec Docker (Recommandé)

```bash
# Copier le fichier d'environnement
cp .env.example .env

# Démarrer les services
docker-compose up -d

# Installer les dépendances
docker exec -it activibe_app composer install

# Générer la clé d'application
docker exec -it activibe_app php artisan key:generate

# Exécuter les migrations
docker exec -it activibe_app php artisan migrate

# Seed de données (optionnel)
docker exec -it activibe_app php artisan db:seed
```

### 3. Installation locale

```bash
# Installer les dépendances
composer install

# Copier et configurer l'environnement
cp .env.example .env
php artisan key:generate

# Configurer la base de données dans .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=bookyourcoach
# DB_USERNAME=root
# DB_PASSWORD=

# Exécuter les migrations
php artisan migrate

# Démarrer le serveur
php artisan serve
```

## 🧪 Tests

Le projet utilise PHPUnit 12 avec une couverture complète :

```bash
# Tous les tests
docker exec -it activibe_app php artisan test

# Tests unitaires uniquement
docker exec -it activibe_app php artisan test --testsuite=Unit

# Tests avec couverture
docker exec -it activibe_app php artisan test --coverage
```

### Statistiques des Tests

- ✅ **303 tests unitaires** - Tous passent
- 🧪 **Tests Feature** - Authentification corrigée
- 📊 **Couverture** - Models, Services, Controllers

## 🐳 Docker

### Services disponibles

- **app** - Application Laravel (PHP 8.3)
- **mysql** - Base de données MySQL 8.0
- **redis** - Cache et sessions Redis 7
- **webserver** - Nginx (optionnel)

### Commandes Docker utiles

```bash
# Reconstruire les containers
docker-compose build --no-cache

# Voir les logs
docker-compose logs -f app

# Accéder au container
docker exec -it activibe_app bash

# Redémarrer les services
docker-compose restart
```

## 📚 API Documentation

### Authentification

```bash
# Login
POST /api/auth/login
{
  "email": "user@example.com",
  "password": "password"
}

# Logout
POST /api/auth/logout
Authorization: Bearer {token}
```

### Endpoints principaux

- `GET /api/admin/stats` - Statistiques plateforme
- `GET /api/club/dashboard` - Dashboard club
- `GET /api/student/dashboard/stats` - Stats étudiant
- `GET /api/teacher/dashboard` - Dashboard enseignant

Voir la [documentation technique](docs/TECHNICAL_DOCUMENTATION.md) pour plus de détails.

## 🔧 Configuration

### Variables d'environnement importantes

```env
APP_NAME=BookYourCoach
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bookyourcoach.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=activibe_prod

REDIS_HOST=redis
REDIS_PORT=6379

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,bookyourcoach.com
```

## 🚀 Déploiement

### Production avec Docker

```bash
# Construire l'image de production
docker build -t bookyourcoach:latest .

# Déployer avec docker-compose.prod.yml
docker-compose -f docker-compose.prod.yml up -d
```

### CI/CD avec GitHub Actions

Le pipeline automatique inclut :

1. **Tests** avec PHP 8.3 et MySQL
2. **Analyse de sécurité** avec Composer
3. **Build Docker** multi-architecture
4. **Déploiement automatique** (staging/production)

## 🛡 Sécurité

- **Authentification** via Laravel Sanctum
- **Middleware personnalisé** pour l'administration
- **Validation** stricte des données d'entrée
- **Audit de sécurité** automatique dans CI/CD

## 🤝 Contribution

1. Fork du projet
2. Créer une branche feature (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit des changements (`git commit -am 'Ajouter nouvelle fonctionnalité'`)
4. Push vers la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Ouvrir une Pull Request

### Standards de développement

- **PSR-12** pour le style de code
- **Tests obligatoires** pour toute nouvelle fonctionnalité
- **Documentation** des nouvelles API

## 📋 Roadmap

- [ ] Interface mobile React Native
- [ ] Notifications push
- [ ] Intégration calendrier externe
- [ ] Module de paiement avancé
- [ ] Analytics temps réel

## 🐛 Support

Pour signaler un bug ou demander une fonctionnalité :

1. Vérifier les [issues existantes](https://github.com/votre-username/bookyourcoach/issues)
2. Créer une nouvelle issue avec un template approprié
3. Fournir un maximum de détails et de contexte

## 📄 License

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## ✨ Remerciements

- **Laravel Team** pour le framework
- **PHPUnit Team** pour les outils de test
- **Communauté Open Source** pour les packages utilisés

---

**Développé avec ❤️ par l'équipe BookYourCoach**

# BookYourCoach API

## Déploiement

Le déploiement est automatisé via GitHub Actions.

<!-- Trigger CI -->