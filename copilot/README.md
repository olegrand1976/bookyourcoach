# BookYourCoach - Plateforme de Réservation de Cours

## 📝 Description

API REST Laravel pour une plateforme de réservation de cours avec coaches (équestres ou autres). L'application gère trois types d'utilisateurs : Administrateurs, Enseignants et Élèves avec un système complet de gestion des réservations, paiements et facturation.

## ✨ Fonctionnalités Principales

### 🔐 Gestion des Utilisateurs

- **Authentification multi-rôles** (Admin, Teacher, Student)
- **Profils utilisateurs** avec informations personnelles complètes
- **Système RBAC** (Role-Based Access Control)
- **API d'authentification** avec tokens Sanctum

### 📚 Gestion des Cours

- **Types de cours** personnalisables (dressage, obstacle, cross, western, etc.)
- **Créneaux de disponibilité** des enseignants
- **Système de réservation** avec verrouillage optimiste
- **Gestion des lieux** de cours avec calcul de trajets
- **API complète** pour la gestion des leçons

### 💳 Paiements & Facturation

- **Intégration Stripe** pour les paiements
- **Stripe Connect** pour les reversements aux enseignants
- **Génération automatique** de factures PDF
- **Gestion des abonnements** élèves
- **Historique des transactions**

### 🔍 API & Documentation

- **Documentation Swagger** interactive accessible sur `/docs`
- **API REST** avec conventions standard
- **Authentification Bearer Token**
- **Réponses JSON** standardisées
- **Tests automatisés** complets

## 🚀 Installation

### Avec Docker (Recommandé)

```bash
# Cloner le repository
git clone <repo-url>
cd bookyourcoach

# Démarrer l'environnement complet
./start.sh
```

L'application sera disponible sur :

- **Application Laravel** : http://localhost:8081
- **Documentation API Swagger** : http://localhost:8081/docs
- **PHPMyAdmin** : http://localhost:8082
  - Utilisateur : `laravel`
  - Mot de passe : `laravel_password`

### Installation manuelle

```bash
# Installer les dépendances
composer install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate

# Configurer la base de données
php artisan migrate

# Générer la documentation API
php artisan l5-swagger:generate

# Lancer le serveur de développement
php artisan serve
```

## 🐳 Configuration Docker

### Services inclus

L'environnement Docker inclut :

- **app** : Application Laravel (PHP 8.2-FPM)
- **webserver** : Nginx (proxy vers l'application)
- **mysql** : MySQL 8.0 avec base de données `bookyourcoach`
- **redis** : Cache et sessions Redis
- **phpmyadmin** : Interface web pour MySQL
- **queue** : Worker pour les tâches en arrière-plan
- **scheduler** : Tâches cron Laravel

### URLs de développement

- **Application principale** : http://localhost:8081
- **Documentation API** : http://localhost:8081/docs
- **PHPMyAdmin** : http://localhost:8082

### Commandes Docker Utiles

```bash
# Arrêter les services
./stop.sh

# Voir les logs
docker-compose logs -f

# Accéder au conteneur de l'application
docker-compose exec app bash

# Exécuter des commandes Artisan
docker-compose exec app php artisan migrate
docker-compose exec app php artisan tinker

# Lancer les tests
./run_tests.sh

# Redémarrer un service
docker-compose restart app

# Reconstruire les conteneurs
docker-compose up -d --build
```

## 🧪 Tests

Le projet inclut une suite complète de tests :

### Tests unitaires
- Tests des modèles (User, Profile, etc.)
- Tests des relations Eloquent
- Tests de validation des données

### Tests de fonctionnalité
- Tests des endpoints API
- Tests d'authentification
- Tests des contrôleurs

### Exécution des tests

```bash
# Tous les tests
./run_tests.sh

# Tests unitaires uniquement
docker-compose exec app php artisan test tests/Unit

# Tests de fonctionnalité uniquement
docker-compose exec app php artisan test tests/Feature

# Test spécifique
docker-compose exec app php artisan test tests/Feature/Api/AuthControllerTest.php
```

## 📖 API Documentation

### Endpoints principaux

#### Authentication
- `POST /api/auth/register` - Inscription
- `POST /api/auth/login` - Connexion
- `POST /api/auth/logout` - Déconnexion
- `GET /api/auth/user` - Utilisateur actuel

#### Users
- `GET /api/users` - Liste des utilisateurs
- `GET /api/users/{id}` - Détails d'un utilisateur
- `PUT /api/users/{id}` - Modifier un utilisateur
- `DELETE /api/users/{id}` - Supprimer un utilisateur

#### Profiles
- `GET /api/profiles` - Liste des profils
- `POST /api/profiles` - Créer un profil
- `GET /api/profiles/{id}` - Détails d'un profil
- `PUT /api/profiles/{id}` - Modifier un profil
- `DELETE /api/profiles/{id}` - Supprimer un profil

#### Lessons
- `GET /api/lessons` - Liste des cours
- `POST /api/lessons` - Créer un cours
- `GET /api/lessons/{id}` - Détails d'un cours
- `PUT /api/lessons/{id}` - Modifier un cours
- `DELETE /api/lessons/{id}` - Supprimer un cours

### Authentification

Toutes les routes protégées nécessitent un token Bearer :

```bash
# Exemple d'utilisation
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     http://localhost:8081/api/users
```

## 🏗️ Architecture

### Modèles principaux

- **User** : Utilisateurs avec rôles (admin/teacher/student)
- **Profile** : Profils utilisateurs avec informations personnelles
- **Teacher** : Enseignants avec leurs spécificités
- **Student** : Élèves avec leurs informations
- **CourseType** : Types de cours (dressage, obstacle, etc.)
- **Lesson** : Leçons/cours réservés
- **Location** : Lieux de cours
- **Payment** : Paiements via Stripe
- **Invoice** : Facturation
- **Subscription** : Abonnements élèves
- **Availability** : Disponibilités enseignants
- **TimeBlock** : Blocages de créneaux
- **Payout** : Reversements aux enseignants
- **AuditLog** : Journalisation des actions

### Structure des contrôleurs

```
app/Http/Controllers/Api/
├── AuthController.php      # Authentification
├── UserController.php      # Gestion des utilisateurs
├── ProfileController.php   # Gestion des profils
└── LessonController.php    # Gestion des cours
```

## 🔧 Configuration

### Base de données

- **Host** : mysql (dans Docker) / localhost:3307 (depuis l'hôte)
- **Base de données** : bookyourcoach
- **Utilisateur** : laravel
- **Mot de passe** : laravel_password

### Redis

- **Host** : redis (dans Docker) / localhost:6380 (depuis l'hôte)
- **Port** : 6379 (interne) / 6380 (externe)

### Variables d'environnement importantes

```env
APP_NAME="BookYourCoach"
APP_URL=http://localhost:8081
L5_SWAGGER_CONST_HOST=http://localhost:8081/api
DB_CONNECTION=mysql
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

## 🛠️ Développement

### Workflow recommandé

1. **Démarrer l'environnement** : `docker-compose up -d`
2. **Exécuter les migrations** : `docker-compose exec app php artisan migrate`
3. **Développer votre application**
4. **Lancer les tests** : `./run_tests.sh`
5. **Tester l'API** : http://localhost:8081/docs
6. **Gérer la base** : http://localhost:8082

### Tâches VS Code disponibles

- **Docker: Start All Services** - Démarre tous les conteneurs
- **Docker: Stop All Services** - Arrête tous les conteneurs
- **Laravel: Run Migrations** - Exécute les migrations
- **Tests: Run All Tests** - Lance tous les tests
- **Swagger: Generate Documentation** - Régénère la doc API

## 📋 Statut du projet

### ✅ Fonctionnalités implémentées

- ✅ Architecture Laravel complète
- ✅ Environnement Docker complet
- ✅ Authentification API avec Sanctum
- ✅ Modèles et migrations complets
- ✅ Contrôleurs API (Auth, Users, Profiles)
- ✅ Documentation Swagger interactive
- ✅ Suite de tests complète (25 tests)
- ✅ Base de données MySQL avec PHPMyAdmin
- ✅ Cache Redis configuré
- ✅ Système de rôles utilisateurs

### 🚧 À développer

- ⚠️ Implémentation complète du LessonController
- ⚠️ Contrôleurs pour CourseType, Location, Payment
- ⚠️ Intégration Stripe pour les paiements
- ⚠️ Système de notifications
- ⚠️ Gestion des fichiers et uploads
- ⚠️ Politiques d'autorisation (Policies)
- ⚠️ Resources API pour les réponses formatées

## 🤝 Contributing

Les contributions sont les bienvenues ! Merci de suivre ces étapes :

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 License

Ce projet est sous licence [MIT License](https://opensource.org/licenses/MIT).
