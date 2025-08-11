# BookYourCoach - Plateforme de Réservation de Cours

## 📝 Description

Application complète de réservation de cours avec coaches (équestres ou autres). Stack complet avec **Laravel API** (backend) + **NuxtJS** (frontend) pour une expérience utilisateur moderne. L'application gère trois types d'utilisateurs : Administrateurs, Enseignants et Élèves avec un système complet de gestion des réservations, paiements et facturation.

## ✨ Fonctionnalités Principales

### 🔐 Gestion des Utilisateurs

-   **Authentification multi-rôles** (Admin, Teacher, Student)
-   **Profils utilisateurs** avec informations personnelles complètes
-   **Système RBAC** (Role-Based Access Control)
-   **API d'authentification** avec tokens Sanctum

### 📚 Gestion des Cours

-   **Types de cours** personnalisables (dressage, obstacle, cross, western, etc.)
-   **Créneaux de disponibilité** des enseignants
-   **Système de réservation** avec verrouillage optimiste
-   **Gestion des lieux** de cours avec calcul de trajets
-   **API complète** pour la gestion des leçons

### 💳 Paiements & Facturation

-   **Intégration Stripe** complète pour les paiements
-   **Stripe Connect** pour les reversements aux enseignants
-   **Génération automatique** de factures PDF
-   **Gestion des abonnements** élèves
-   **Historique des transactions**
-   **Webhooks Stripe** pour la synchronisation

### 🎨 Système de Rebranding

-   **Personnalisation des couleurs** (3 couleurs : primaire, secondaire, accent)
-   **Gestion des logos** (URL et chemin local)
-   **Informations de contact** personnalisables
-   **Liens réseaux sociaux** configurables
-   **API dédiée** pour la gestion du branding
-   **Thèmes multiples** avec activation/désactivation

### �️ Données de Démonstration

-   **Jeux de données complets** pour le développement
-   **10 types de cours** équestres prédéfinis
-   **10 centres équestres** avec coordonnées réelles
-   **Utilisateurs de test** (admin, enseignants, élèves)
-   **41 leçons** avec historique réaliste
-   **Disponibilités** et **paiements** générés

### 🎨 Frontend NuxtJS

-   **Vue 3** avec TypeScript
-   **Tailwind CSS** pour le design
-   **Pinia Store** pour la gestion d'état
-   **Authentification JWT** complète
-   **Interface admin** dédiée
-   **Design responsive** mobile-first
-   **SSR** (Server-Side Rendering)

### 🔍 API & Documentation

-   **Documentation Swagger** interactive et personnalisée accessible sur `/docs`
-   **API REST** avec conventions standard
-   **Authentification Bearer Token**
-   **Réponses JSON** standardisées
-   **127 tests automatisés** ✅

## 🚀 Installation

### Démarrage Rapide (Stack Complète)

```bash
# Cloner le repository
git clone <repo-url>
cd bookyourcoach

# Démarrer backend + frontend avec Docker
./start-full-stack.sh
```

**Accès aux services :**

-   **Frontend (NuxtJS)** : http://localhost:3000
-   **API Laravel** : http://localhost:8081
-   **Documentation API** : http://localhost:8081/api/documentation
-   **PHPMyAdmin** : http://localhost:8082

### Développement Frontend Uniquement

```bash
# Mode développement frontend avec hot-reload
./dev-frontend.sh
```

### Installation avec Docker (Backend seulement)

```bash
# Démarrer l'environnement backend
./start.sh
```

L'application sera disponible sur :

-   **Application Laravel** : http://localhost:8081
-   **Documentation API Swagger** : http://localhost:8081/docs
-   **PHPMyAdmin** : http://localhost:8082
    -   Utilisateur : `laravel`
    -   Mot de passe : `laravel_password`

### Installation manuelle

```bash
# Installer les dépendances
composer install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate

# Configurer la base de données et peupler avec des données de test
php artisan migrate --seed

# Générer la documentation API
php artisan l5-swagger:generate

# Lancer le serveur de développement
php artisan serve
```

## 🐳 Configuration Docker

### Services inclus

L'environnement Docker inclut :

-   **app** : Application Laravel (PHP 8.2-FPM)
-   **webserver** : Nginx (proxy vers l'application)
-   **mysql** : MySQL 8.0 avec base de données `bookyourcoach`
-   **redis** : Cache et sessions Redis
-   **phpmyadmin** : Interface web pour MySQL
-   **queue** : Worker pour les tâches en arrière-plan
-   **scheduler** : Tâches cron Laravel

### URLs de développement

-   **Application principale** : http://localhost:8081
-   **Documentation API** : http://localhost:8081/docs
-   **PHPMyAdmin** : http://localhost:8082

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

-   Tests des modèles (User, Profile, etc.)
-   Tests des relations Eloquent
-   Tests de validation des données

### Tests de fonctionnalité

-   Tests des endpoints API
-   Tests d'authentification
-   Tests des contrôleurs

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

-   `POST /api/auth/register` - Inscription
-   `POST /api/auth/login` - Connexion
-   `POST /api/auth/logout` - Déconnexion
-   `GET /api/auth/user` - Utilisateur actuel

#### App Settings (Rebranding)

-   `GET /api/app-settings/public` - Paramètres publics (thème actif)
-   `GET /api/app-settings` - Liste complète (admin)
-   `POST /api/app-settings` - Créer des paramètres (admin)
-   `PUT /api/app-settings/{id}` - Modifier (admin)
-   `DELETE /api/app-settings/{id}` - Supprimer (admin)
-   `POST /api/app-settings/{id}/activate` - Activer un thème (admin)

#### Users & Profiles

-   `GET /api/users` - Liste des utilisateurs
-   `GET /api/profiles` - Liste des profils
-   `POST /api/profiles` - Créer un profil
-   `PUT /api/profiles/{id}` - Modifier un profil

#### Course Management

-   `GET /api/course-types` - Types de cours
-   `POST /api/course-types` - Créer un type de cours (admin)
-   `GET /api/locations` - Lieux de cours
-   `POST /api/locations` - Créer un lieu (admin)
-   `GET /api/lessons` - Liste des cours
-   `POST /api/lessons` - Réserver un cours

#### Notifications & Communication

-   `POST /api/notifications/send` - Envoyer une notification personnalisée
-   `GET /api/notifications` - Historique des notifications

#### File Upload & Gestion

-   `POST /api/upload/avatar` - Upload d'avatar utilisateur
-   `POST /api/upload/certificate` - Upload de certificat enseignant
-   `POST /api/upload/logo` - Upload de logo application (admin)
-   `DELETE /api/upload/{path}` - Supprimer un fichier

#### Administration (Admin uniquement)

-   `GET /api/admin/dashboard` - Tableau de bord avec statistiques
-   `GET /api/admin/users` - Gestion complète des utilisateurs
-   `PUT /api/admin/users/{id}/status` - Modifier statut utilisateur

#### Stripe Connect & Revenus

-   `POST /api/stripe/connect/account` - Créer compte Connect enseignant
-   `GET /api/payouts` - Historique des reversements

### Authentification

Toutes les routes protégées nécessitent un token Bearer :

```bash
# Connexion pour obtenir un token
curl -X POST http://localhost:8081/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@bookyourcoach.com", "password": "password123"}'

# Utilisation du token pour les requêtes protégées
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     http://localhost:8081/api/users
```

### Exemples d'utilisation de l'API

```bash
# Récupérer les paramètres de rebranding (public)
curl http://localhost:8081/api/app-settings/public

# Lister les types de cours disponibles
curl -H "Authorization: Bearer TOKEN" \
     http://localhost:8081/api/course-types

# Lister les centres équestres
curl -H "Authorization: Bearer TOKEN" \
     http://localhost:8081/api/locations

# Tester l'upload d'avatar
curl -X POST http://localhost:8081/api/upload/avatar \
  -H "Authorization: Bearer TOKEN" \
  -F "avatar=@/path/to/image.jpg"

# Accéder au tableau de bord admin
curl -H "Authorization: Bearer ADMIN_TOKEN" \
     http://localhost:8081/api/admin/dashboard
```

## 🏗️ Architecture

### Modèles principaux

-   **User** : Utilisateurs avec rôles (admin/teacher/student)
-   **Profile** : Profils utilisateurs avec informations personnelles
-   **Teacher** : Enseignants avec leurs spécificités
-   **Student** : Élèves avec leurs informations
-   **CourseType** : Types de cours (dressage, obstacle, etc.)
-   **Lesson** : Leçons/cours réservés
-   **Location** : Lieux de cours
-   **Payment** : Paiements via Stripe
-   **Invoice** : Facturation
-   **Subscription** : Abonnements élèves
-   **Availability** : Disponibilités enseignants
-   **TimeBlock** : Blocages de créneaux
-   **Payout** : Reversements aux enseignants
-   **AuditLog** : Journalisation des actions
-   **AppSetting** : Paramètres de rebranding de l'application

### Structure des contrôleurs

```
app/Http/Controllers/Api/
├── AuthController.php           # Authentification
├── UserController.php           # Gestion des utilisateurs
├── ProfileController.php        # Gestion des profils
├── LessonController.php         # Gestion des cours
├── CourseTypeController.php     # Types de cours
├── LocationController.php       # Lieux de cours
├── PaymentController.php        # Paiements
├── AppSettingController.php     # Paramètres d'application (rebranding)
└── StripeWebhookController.php  # Webhooks Stripe
```

## 🔧 Configuration

### Base de données

-   **Host** : mysql (dans Docker) / localhost:3307 (depuis l'hôte)
-   **Base de données** : bookyourcoach
-   **Utilisateur** : laravel
-   **Mot de passe** : laravel_password

### Redis

-   **Host** : redis (dans Docker) / localhost:6380 (depuis l'hôte)
-   **Port** : 6379 (interne) / 6380 (externe)

### Variables d'environnement importantes

```env
APP_NAME="BookYourCoach"
APP_URL=http://localhost:8081
L5_SWAGGER_CONST_HOST=http://localhost:8081/api

# Base de données
DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=bookyourcoach
DB_USERNAME=laravel
DB_PASSWORD=laravel_password

# Cache et sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# Stripe (environnement de test)
STRIPE_MODEL=App\\Models\\Payment
STRIPE_KEY=pk_test_51RuhSvRnsLIgFLxIec...
STRIPE_SECRET=sk_test_51RuhSvRnsLIgFLxIsa...
STRIPE_WEBHOOK_SECRET=whsec_...
```

## 👥 Comptes de Test Disponibles

Après avoir exécuté les seeders (`php artisan db:seed`), vous disposerez de comptes de test :

### Administrateur

-   **Email** : admin@bookyourcoach.com
-   **Mot de passe** : password123
-   **Rôle** : Accès complet à toutes les fonctionnalités

### Enseignants

-   **Sophie Martin** : sophie.martin@bookyourcoach.com / password123 (Dressage, Saut)
-   **Jean Dubois** : jean.dubois@bookyourcoach.com / password123 (Cross-country)
-   **Marie Leroy** : marie.leroy@bookyourcoach.com / password123 (Western)
-   **Pierre Bernard** : pierre.bernard@bookyourcoach.com / password123 (Poney club)

### Élèves

-   **Alice Durand** : alice.durand@email.com / password123 (Niveau intermédiaire)
-   **Bob Martin** : bob.martin@email.com / password123 (Niveau avancé)
-   **Charlotte Dupont** : charlotte.dupont@email.com / password123 (Débutante)
-   **David Laurent** : david.laurent@email.com / password123 (Western débutant)
-   **Emma Rousseau** : emma.rousseau@email.com / password123 (Cross-country)

## 🎨 Personnalisation du Thème

L'application dispose d'un système de rebranding complet :

### Configuration par défaut

```json
{
    "app_name": "BookYourCoach",
    "primary_color": "#2563eb",
    "secondary_color": "#1e40af",
    "accent_color": "#3b82f6",
    "contact_email": "contact@bookyourcoach.com",
    "social_links": {
        "facebook": "https://facebook.com/bookyourcoach",
        "instagram": "https://instagram.com/bookyourcoach",
        "linkedin": "https://linkedin.com/company/bookyourcoach"
    }
}
```

### Personnalisation via API

```bash
# Récupérer les paramètres actuels (public)
curl http://localhost:8081/api/app-settings/public

# Modifier les couleurs (admin requis)
curl -X PUT http://localhost:8081/api/app-settings/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"primary_color": "#ff6b6b", "secondary_color": "#4ecdc4"}'
```

## 🛠️ Développement

### 📊 Statistiques du Projet

-   **114 tests automatisés** ✅ (100% de réussite)
-   **15 modèles Eloquent** avec relations complètes
-   **9 contrôleurs API** documentés
-   **19 migrations** de base de données
-   **6 seeders** pour les données de test
-   **10 types de cours** équestres prédéfinis
-   **10 centres équestres** géolocalisés
-   **9 utilisateurs de test** (1 admin, 4 enseignants, 5 élèves)
-   **41 leçons générées** avec historique réaliste
-   **Intégration Stripe** complète (test)

### 🎯 Données de Test Générées

Après `php artisan db:seed`, votre base contient :

-   **10 types de cours** : Dressage, Saut d'obstacles, Cross-country, Western, etc.
-   **10 centres équestres** répartis en Belgique avec coordonnées GPS
-   **4 enseignants spécialisés** avec leurs créneaux de disponibilité
-   **5 élèves** avec différents niveaux et objectifs
-   **Leçons sur 45 jours** (passées et futures) avec statuts réalistes
-   **Paiements Stripe** associés aux leçons confirmées
-   **Paramètres de rebranding** par défaut configurés

### Workflow recommandé

1. **Démarrer l'environnement** : `docker-compose up -d`
2. **Exécuter les migrations** : `docker-compose exec app php artisan migrate`
3. **Développer votre application**
4. **Lancer les tests** : `./run_tests.sh`
5. **Tester l'API** : http://localhost:8081/docs
6. **Gérer la base** : http://localhost:8082

### Tâches VS Code disponibles

-   **Laravel Serve** - Serveur de développement Laravel (port 8000)
-   **Docker: Start All Services** - Démarre tous les conteneurs
-   **Docker: Stop All Services** - Arrête tous les conteneurs
-   **Laravel: Run Migrations** - Exécute les migrations
-   **Tests: Run All Tests** - Lance tous les tests
-   **Swagger: Generate Documentation** - Régénère la doc API

## 🔧 Dépannage

### Problèmes courants

**Erreur "Port already in use"**

```bash
# Vérifier les ports occupés
lsof -i :8081
# Arrêter les services Docker
docker-compose down
```

**Base de données vide après migration**

```bash
# Réexécuter les migrations avec les seeders
docker-compose exec app php artisan migrate:fresh --seed
```

**Documentation Swagger non accessible**

```bash
# Régénérer la documentation
docker-compose exec app php artisan l5-swagger:generate
```

**Tests qui échouent**

```bash
# Nettoyer le cache et relancer
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan test
```

### Logs utiles

```bash
# Logs de l'application Laravel
docker-compose logs app

# Logs de la base de données
docker-compose logs mysql

# Logs du serveur web
docker-compose logs webserver

# Logs en temps réel
docker-compose logs -f
```

## 📋 Statut du projet

### ✅ Fonctionnalités implémentées

-   ✅ Architecture Laravel complète
-   ✅ Environnement Docker complet
-   ✅ Authentification API avec Sanctum
-   ✅ Modèles et migrations complets
-   ✅ API complète (Auth, Users, Profiles, Lessons, CourseTypes, Locations)
-   ✅ Documentation Swagger interactive et personnalisée
-   ✅ Suite de tests complète (114 tests) ✅
-   ✅ Base de données MySQL avec PHPMyAdmin
-   ✅ Cache Redis configuré
-   ✅ Système de rôles utilisateurs (RBAC)
-   ✅ **Intégration Stripe complète** (paiements + webhooks)
-   ✅ **Système de rebranding** (3 couleurs + logo + contact)
-   ✅ **Jeux de données de test** (10 cours, 10 lieux, utilisateurs complets)
-   ✅ **41 leçons de démonstration** avec historique réaliste
-   ✅ **API de gestion des paiements** avec Stripe
-   ✅ **Contrôleurs API complets** pour toutes les entités
-   ✅ **🆕 Système de notifications** (email confirmations, rappels, annulations)
-   ✅ **🆕 Jobs et queues** (génération factures, rappels automatiques)
-   ✅ **🆕 Gestion des fichiers** (upload avatars, certificats, logos)
-   ✅ **🆕 Interface d'administration** (dashboard, statistiques, gestion utilisateurs)
-   ✅ **🆕 Génération automatique de factures** PDF
-   ✅ **🆕 Calcul des temps de trajet** pour les enseignants

### 🚧 Améliorations possibles

-   ⚠️ Application web front-end (React/Vue.js)
-   ⚠️ Notifications push et SMS
-   ⚠️ Calendrier intégré avancé pour les disponibilités
-   ⚠️ Système de rating et reviews
-   ⚠️ Rapports PDF détaillés
-   ⚠️ Intégration avec des services de cartographie
-   ⚠️ Application mobile (React Native / Flutter)
-   ⚠️ Système de chat en temps réel

## 🤝 Contributing

Les contributions sont les bienvenues ! Merci de suivre ces étapes :

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 License

Ce projet est sous licence [MIT License](https://opensource.org/licenses/MIT).
