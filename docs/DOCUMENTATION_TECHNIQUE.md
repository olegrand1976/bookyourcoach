# 🔧 Documentation Technique - BookYourCoach

**Version :** 1.5.0  
**Date :** Janvier 2025  
**Plateforme :** activibe (BookYourCoach)

---

## 📋 Table des Matières

1. [Architecture Générale](#architecture-générale)
2. [Stack Technologique](#stack-technologique)
3. [Structure du Projet](#structure-du-projet)
4. [Modèles de Données](#modèles-de-données)
5. [API REST](#api-rest)
6. [Authentification et Sécurité](#authentification-et-sécurité)
7. [Services et Business Logic](#services-et-business-logic)
8. [Base de Données](#base-de-données)
9. [Tests](#tests)
10. [Déploiement](#déploiement)
11. [Configuration](#configuration)

---

## 🏗️ Architecture Générale

### Architecture Multi-Tenant

BookYourCoach utilise une architecture multi-tenant où chaque club est isolé mais partage la même infrastructure :

```
┌─────────────────────────────────────────┐
│         Frontend (Nuxt.js 3)           │
│         Mobile (Flutter)               │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│      API REST (Laravel 11)              │
│  ┌──────────────────────────────────┐   │
│  │  Middleware (Auth, Roles)       │   │
│  └──────────────────────────────────┘   │
│  ┌──────────────────────────────────┐   │
│  │  Controllers (API Endpoints)     │   │
│  └──────────────────────────────────┘   │
│  ┌──────────────────────────────────┐   │
│  │  Services (Business Logic)      │   │
│  └──────────────────────────────────┘   │
└──────────────┬──────────────────────────┘
               │
       ┌───────┴───────┐
       ▼               ▼
┌─────────────┐  ┌─────────────┐
│   MySQL     │  │   Neo4j     │
│  (Primary)  │  │  (Analytics)│
└─────────────┘  └─────────────┘
       │
       ▼
┌─────────────┐
│    Redis    │
│   (Cache)   │
└─────────────┘
```

### Principes d'Architecture

- **Séparation des responsabilités** : Controllers → Services → Models
- **Multi-tenant** : Isolation des données par club
- **API-First** : Toutes les fonctionnalités exposées via API REST
- **Service Layer** : Logique métier dans les services
- **Repository Pattern** : Accès aux données via Eloquent ORM

---

## 💻 Stack Technologique

### Backend

| Technologie | Version | Usage |
|------------|---------|-------|
| **PHP** | 8.3+ | Langage principal |
| **Laravel** | 12.x | Framework PHP |
| **MySQL** | 8.0 | Base de données principale |
| **Redis** | 7-alpine | Cache et sessions |
| **Neo4j** | Latest | Base de données graphique (analytics) |
| **PHPUnit** | 11+ | Framework de tests |

### Frontend Web

| Technologie | Version | Usage |
|------------|---------|-------|
| **Nuxt.js** | 3.x | Framework Vue.js |
| **Vue.js** | 3.x | Framework JavaScript |
| **Tailwind CSS** | 4.x | Framework CSS |
| **TypeScript** | Latest | Typage statique |

### Mobile

| Technologie | Version | Usage |
|------------|---------|-------|
| **Flutter** | Latest | Framework mobile |
| **Dart** | Latest | Langage Flutter |

### Infrastructure

| Technologie | Version | Usage |
|------------|---------|-------|
| **Docker** | Latest | Conteneurisation |
| **Docker Compose** | Latest | Orchestration |
| **Nginx** | Latest | Serveur web |
| **GitHub Actions** | Latest | CI/CD |

### Bibliothèques Principales

- **Laravel Sanctum** : Authentification API
- **Stripe PHP SDK** : Paiements en ligne
- **Google API Client** : Intégration Google Calendar
- **Neo4j PHP Client** : Connexion Neo4j
- **DomPDF** : Génération de PDF
- **SimpleSoftwareIO/QrCode** : Génération de QR codes

---

## 📁 Structure du Projet

```
bookyourcoach/
├── app/
│   ├── Console/
│   │   └── Commands/          # Commandes Artisan
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/          # Contrôleurs API
│   │   │   └── AdminController.php
│   │   ├── Middleware/       # Middlewares personnalisés
│   │   └── Requests/         # Form Requests (validation)
│   ├── Models/               # Modèles Eloquent
│   ├── Notifications/        # Notifications email
│   ├── Services/             # Services métier
│   │   ├── AI/              # Services IA
│   │   ├── Neo4jService.php
│   │   ├── GoogleCalendarService.php
│   │   └── StripeService.php
│   └── Jobs/                # Jobs de queue
├── bootstrap/
│   └── app.php              # Bootstrap Laravel
├── config/                  # Configuration Laravel
├── database/
│   ├── factories/          # Factories pour tests
│   ├── migrations/         # Migrations DB
│   └── seeders/           # Seeders de données
├── docker/                 # Configuration Docker
│   ├── nginx/             # Config Nginx
│   └── php/               # Config PHP
├── docs/                   # Documentation
├── frontend/               # Application Nuxt.js
│   ├── components/        # Composants Vue
│   ├── pages/            # Pages/routes
│   ├── composables/      # Composables Vue
│   └── stores/           # Stores Pinia
├── mobile/                # Application Flutter
├── public/                # Assets publics
├── resources/
│   ├── views/           # Vues Blade (emails)
│   └── js/             # Assets JS
├── routes/
│   ├── api.php        # Routes API
│   └── web.php        # Routes web
├── scripts/            # Scripts utilitaires
├── storage/           # Fichiers stockés
├── tests/            # Tests PHPUnit
└── vendor/           # Dépendances Composer
```

---

## 🗄️ Modèles de Données

### Modèles Principaux

#### User

**Table :** `users`

**Attributs principaux :**
- `id` : Identifiant unique
- `name` : Nom complet
- `first_name` : Prénom
- `last_name` : Nom
- `email` : Email (unique)
- `password` : Mot de passe hashé
- `role` : Rôle (admin, teacher, student, club)
- `phone` : Téléphone
- `birth_date` : Date de naissance
- `is_active` : Statut actif/inactif
- `status` : Statut (active, inactive, pending)

**Relations :**
```php
belongsToMany(Club::class)      // via club_user
hasOne(Teacher::class)          // Si role = 'teacher'
hasOne(Student::class)          // Si role = 'student'
```

#### Club

**Table :** `clubs`

**Attributs principaux :**
- `id` : Identifiant unique
- `name` : Nom du club
- `description` : Description
- `email` : Email du club
- `phone` : Téléphone
- `address` : Adresse complète
- `city` : Ville
- `postal_code` : Code postal
- `country` : Pays
- `is_active` : Statut actif/inactif
- `disciplines` : JSON - Liste des disciplines

**Relations :**
```php
belongsToMany(User::class)      // via club_user
belongsToMany(Teacher::class)   // via club_teachers
belongsToMany(Student::class)   // via club_students
hasMany(Lesson::class)
hasMany(SubscriptionTemplate::class)
hasMany(ClubOpenSlot::class)
hasOne(ClubSettings::class)
```

#### Teacher

**Table :** `teachers`

**Attributs principaux :**
- `id` : Identifiant unique
- `user_id` : Référence User
- `specialties` : JSON - Spécialités
- `experience_years` : Années d'expérience
- `hourly_rate` : Taux horaire
- `bio` : Biographie
- `birth_date` : Date de naissance

**Relations :**
```php
belongsTo(User::class)
belongsToMany(Club::class)      // via club_teachers
hasMany(Lesson::class)
hasMany(Certification::class)
```

#### Student

**Table :** `students`

**Attributs principaux :**
- `id` : Identifiant unique
- `user_id` : Référence User
- `first_name` : Prénom
- `last_name` : Nom
- `date_of_birth` : Date de naissance
- `phone` : Téléphone
- `goals` : Objectifs
- `medical_info` : Informations médicales

**Relations :**
```php
belongsTo(User::class)
belongsToMany(Club::class)      // via club_students
belongsToMany(Lesson::class)    // via lesson_student
hasMany(SubscriptionInstance::class)
hasMany(StudentPreference::class)
```

#### Lesson

**Table :** `lessons`

**Attributs principaux :**
- `id` : Identifiant unique
- `club_id` : Référence Club
- `teacher_id` : Référence Teacher
- `student_id` : Référence Student (principal)
- `course_type_id` : Référence CourseType
- `location_id` : Référence Location
- `start_time` : Date/heure de début
- `end_time` : Date/heure de fin
- `status` : Statut (planned, confirmed, completed, cancelled)
- `payment_status` : Statut paiement
- `price` : Prix
- `montant` : Montant réellement payé
- `est_legacy` : Booléen DCL/NDCL
- `date_paiement` : Date de paiement
- `deduct_from_subscription` : Déduire d'un abonnement

**Relations :**
```php
belongsTo(Club::class)
belongsTo(Teacher::class)
belongsTo(Student::class)        // Étudiant principal
belongsToMany(Student::class)   // Tous les étudiants
belongsTo(CourseType::class)
belongsTo(Location::class)
```

#### SubscriptionTemplate

**Table :** `subscription_templates`

**Attributs principaux :**
- `id` : Identifiant unique
- `club_id` : Référence Club
- `name` : Nom du modèle
- `description` : Description
- `total_lessons` : Nombre total de cours
- `free_lessons` : Nombre de cours gratuits
- `price` : Prix
- `validity_value` : Valeur de validité
- `validity_unit` : Unité (weeks, months)
- `is_active` : Statut actif/inactif

**Relations :**
```php
belongsTo(Club::class)
belongsToMany(CourseType::class) // via subscription_template_course_type
hasMany(Subscription::class)
```

#### SubscriptionInstance

**Table :** `subscription_instances`

**Attributs principaux :**
- `id` : Identifiant unique
- `subscription_id` : Référence Subscription
- `lessons_used` : Cours utilisés
- `started_at` : Date de début
- `expires_at` : Date d'expiration
- `status` : Statut (active, expired, closed)

**Relations :**
```php
belongsTo(Subscription::class)
belongsToMany(Student::class)    // via subscription_instance_student
hasMany(SubscriptionRecurringSlot::class)
```

### Tables Pivot

#### club_user
- `club_id` : Référence Club
- `user_id` : Référence User
- `role` : Rôle (owner, manager, staff)
- `is_admin` : Booléen admin
- `joined_at` : Date d'adhésion

#### club_teachers
- `club_id` : Référence Club
- `teacher_id` : Référence Teacher
- `is_active` : Statut actif/inactif
- `joined_at` : Date d'adhésion

#### club_students
- `club_id` : Référence Club
- `student_id` : Référence Student
- `is_active` : Statut actif/inactif
- `goals` : Objectifs spécifiques au club
- `medical_info` : Informations médicales spécifiques
- `joined_at` : Date d'adhésion

#### lesson_student
- `lesson_id` : Référence Lesson
- `student_id` : Référence Student
- `attended` : Présence
- `rating` : Note

---

## 🌐 API REST

### Structure des Routes

#### Authentification

```
POST   /api/auth/register          # Inscription
POST   /api/auth/login             # Connexion
POST   /api/auth/logout            # Déconnexion
POST   /api/auth/forgot-password   # Mot de passe oublié
POST   /api/auth/reset-password    # Réinitialisation
GET    /api/auth/user              # Utilisateur connecté
PUT    /api/auth/profile           # Mise à jour profil
```

#### Routes Publiques

```
GET    /api/health                 # Health check
GET    /api/activity-types        # Types d'activités
GET    /api/disciplines           # Disciplines
GET    /api/clubs/public          # Liste des clubs actifs
```

#### Routes Admin

```
GET    /api/admin/dashboard       # Dashboard admin
GET    /api/admin/stats           # Statistiques
GET    /api/admin/users           # Liste utilisateurs
POST   /api/admin/users           # Créer utilisateur
PUT    /api/admin/users/{id}      # Modifier utilisateur
GET    /api/admin/clubs           # Liste clubs
POST   /api/admin/clubs           # Créer club
GET    /api/admin/audit-logs     # Logs d'audit
```

#### Routes Club

```
GET    /api/club/dashboard                    # Dashboard club
GET    /api/club/profile                      # Profil club
PUT    /api/club/profile                      # Mettre à jour profil
GET    /api/club/teachers                     # Liste enseignants
POST   /api/club/teachers                     # Créer enseignant
PUT    /api/club/teachers/{id}                # Modifier enseignant
GET    /api/club/students                     # Liste étudiants
POST   /api/club/students                     # Créer étudiant
GET    /api/club/subscriptions                # Liste abonnements
POST   /api/club/subscriptions                # Créer abonnement
GET    /api/club/open-slots                   # Créneaux ouverts
POST   /api/club/open-slots                   # Créer créneau
GET    /api/club/subscription-templates        # Modèles d'abonnements
POST   /api/club/planning/suggest-optimal-slot # Suggestion IA
GET    /api/club/predictive-analysis           # Analyse prédictive
```

#### Routes Teacher

```
GET    /api/teacher/dashboard         # Dashboard enseignant
GET    /api/teacher/profile           # Profil enseignant
PUT    /api/teacher/profile           # Mettre à jour profil
GET    /api/teacher/lessons           # Liste cours
POST   /api/teacher/lessons           # Créer cours
GET    /api/teacher/earnings         # Revenus
GET    /api/teacher/lesson-replacements # Remplacements
```

#### Routes Student

```
GET    /api/student/dashboard              # Dashboard étudiant
GET    /api/student/available-lessons      # Cours disponibles
GET    /api/student/bookings               # Réservations
POST   /api/student/bookings               # Créer réservation
GET    /api/student/subscriptions          # Abonnements
POST   /api/student/subscriptions          # Souscrire abonnement
GET    /api/student/clubs                  # Clubs affiliés
POST   /api/student/clubs                  # Ajouter club
DELETE /api/student/clubs/{id}            # Retirer club
```

### Format des Réponses

**Succès :**
```json
{
  "success": true,
  "data": { ... },
  "message": "Opération réussie"
}
```

**Erreur :**
```json
{
  "success": false,
  "message": "Message d'erreur",
  "errors": {
    "field": ["Erreur de validation"]
  }
}
```

### Codes HTTP

- `200` : Succès
- `201` : Créé
- `400` : Requête invalide
- `401` : Non authentifié
- `403` : Non autorisé
- `404` : Non trouvé
- `422` : Erreur de validation
- `500` : Erreur serveur

---

## 🔐 Authentification et Sécurité

### Laravel Sanctum

**Configuration :**
- Tokens pour API
- Sessions pour SPA
- CSRF protection

**Utilisation :**
```php
// Création de token
$token = $user->createToken('token-name')->plainTextToken;

// Vérification dans les requêtes
Authorization: Bearer {token}
```

### Middleware

**AdminMiddleware :**
```php
// Vérifie que l'utilisateur est admin
if ($user->role !== 'admin') {
    return response()->json(['message' => 'Unauthorized'], 403);
}
```

**ClubMiddleware :**
```php
// Vérifie que l'utilisateur est un club
if ($user->role !== 'club') {
    return response()->json(['message' => 'Unauthorized'], 403);
}
```

**TeacherMiddleware / StudentMiddleware :** Similaire

### Sécurité des Données

- **Validation stricte** : Form Requests Laravel
- **Protection CSRF** : Tokens CSRF pour les formulaires
- **Chiffrement** : Mots de passe hashés avec bcrypt
- **Audit logs** : Enregistrement des actions importantes
- **Isolation multi-tenant** : Filtrage par club_id

---

## ⚙️ Services et Business Logic

### Services Principaux

#### Neo4jService

**Fonctionnalités :**
- Synchronisation MySQL → Neo4j
- Analyses de relations complexes
- Métriques globales
- Recommandations

**Utilisation :**
```php
$service = app(Neo4jService::class);
$metrics = $service->getGlobalMetrics();
```

#### GoogleCalendarService

**Fonctionnalités :**
- Synchronisation OAuth2
- Export de cours vers Google Calendar
- Import d'événements
- Gestion des conflits

#### StripeService

**Fonctionnalités :**
- Traitement des paiements
- Gestion des webhooks
- Abonnements récurrents
- Remboursements

#### TeacherAssignmentService

**Fonctionnalités :**
- Attribution automatique enseignants-étudiants
- Matching par spécialités
- Optimisation des assignations

#### RecurringSlotValidator

**Fonctionnalités :**
- Validation disponibilité sur 26 semaines
- Détection de conflits
- Suggestions alternatives

#### RecurringSlotSuggestionService

**Fonctionnalités :**
- Suggestions IA via Gemini
- Analyse des contraintes
- Optimisation des créneaux

---

## 🗄️ Base de Données

### MySQL (Base Principale)

**Configuration :**
```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=activibe_prod
DB_USERNAME=activibe_user
DB_PASSWORD=...
```

**Tables Principales :**
- `users` : Utilisateurs
- `clubs` : Clubs
- `teachers` : Enseignants
- `students` : Étudiants
- `lessons` : Cours
- `subscriptions` : Abonnements
- `subscription_templates` : Modèles d'abonnements
- `subscription_instances` : Instances d'abonnements
- `payments` : Paiements
- `transactions` : Transactions

### Redis (Cache)

**Configuration :**
```env
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**Utilisation :**
- Cache des requêtes fréquentes
- Sessions utilisateurs
- Queue jobs

### Neo4j (Analytics)

**Configuration :**
```env
NEO4J_HOST=neo4j
NEO4J_PORT=7687
NEO4J_USER=neo4j
NEO4J_PASSWORD=...
```

**Utilisation :**
- Analyses de relations
- Métriques complexes
- Recommandations

---

## 🧪 Tests

### Configuration PHPUnit

**phpunit.xml :**
```xml
<phpunit bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### Exécution des Tests

```bash
# Tous les tests
php artisan test

# Tests unitaires uniquement
php artisan test --testsuite=Unit

# Tests avec couverture
php artisan test --coverage

# Tests spécifiques
php artisan test tests/Feature/Api/AuthControllerTest.php
```

### Structure des Tests

**Tests Unitaires :**
- Modèles
- Services
- Middleware
- Helpers

**Tests Feature :**
- Contrôleurs API
- Flux complets
- Authentification
- Permissions

### Fixtures et Factories

**Factories :**
```php
User::factory()->create(['role' => 'admin']);
Club::factory()->create();
```

**Seeders pour tests :**
```php
$this->seed(ClubTestDataSeeder::class);
```

---

## 🚀 Déploiement

### Docker

**docker-compose.yml :**
```yaml
services:
  app:
    build: .
    volumes:
      - .:/var/www/html
    depends_on:
      - mysql
      - redis
  
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: activibe_prod
  
  redis:
    image: redis:7-alpine
  
  nginx:
    image: nginx:alpine
    ports:
      - "8080:80"
```

### Commandes de Déploiement

```bash
# Démarrer
docker-compose up -d

# Reconstruire
docker-compose build --no-cache
docker-compose up -d

# Logs
docker-compose logs -f app

# Arrêter
docker-compose down
```

### Variables d'Environnement

**Fichier .env :**
```env
APP_NAME=BookYourCoach
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.activibe.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=activibe_prod

REDIS_HOST=redis

NEO4J_HOST=neo4j
NEO4J_USER=neo4j
```

---

## ⚙️ Configuration

### Configuration Laravel

**config/app.php :**
- Nom de l'application
- Timezone
- Locale

**config/database.php :**
- Connexions MySQL, Redis, Neo4j

**config/sanctum.php :**
- Configuration Sanctum
- Domaines autorisés

**config/queue.php :**
- Configuration des queues
- Workers

### Configuration Frontend

**nuxt.config.ts :**
- API base URL
- Variables d'environnement
- Modules Nuxt

### Configuration Mobile

**pubspec.yaml :**
- Dépendances Flutter
- Configuration Android/iOS

---

## 📚 Ressources Additionnelles

- [Documentation Fonctionnelle](DOCUMENTATION_FONCTIONNELLE.md)
- [Guide de Déploiement](PRODUCTION_DEPLOYMENT.md)
- [Configuration GitHub Actions](GITHUB_ACTIONS_CONFIG.md)
- [Index de la Documentation](INDEX.md)

---

**Dernière mise à jour :** Janvier 2025  
**Version de la documentation :** 1.5.0
