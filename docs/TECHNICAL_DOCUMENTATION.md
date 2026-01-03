# Documentation Technique - BookYourCoach

## Vue d'ensemble

BookYourCoach est une plateforme de gestion de cours et clubs sportifs développée avec Laravel 11 et PHP 8.3, utilisant PHPUnit 12 pour les tests.

## Architecture Technique

### Stack Technologique

- **Backend:** Laravel 11.x
- **PHP:** 8.3.25 
- **Base de données:** MySQL 8.0
- **Cache:** Redis 7-alpine
- **Tests:** PHPUnit 12.3.8
- **Conteneurisation:** Docker & Docker Compose

### Structure du Projet

```
├── app/
│   ├── Models/              # Modèles Eloquent
│   ├── Http/Controllers/    # Contrôleurs API
│   ├── Services/           # Services métier
│   └── Http/Middleware/    # Middlewares personnalisés
├── database/
│   ├── migrations/         # Migrations de base de données
│   ├── factories/          # Factories pour les tests
│   └── seeders/           # Seeders de données
├── tests/
│   ├── Unit/              # Tests unitaires
│   └── Feature/           # Tests d'intégration
└── docker/                # Configuration Docker
```

## Modèles et Relations

### Modèles Principaux

#### User
- **Rôles:** admin, teacher, student, club
- **Relations:** 
  - `belongsToMany(Club::class)` via table pivot `club_user`
  - `hasOne(Teacher::class)` 
  - `hasOne(Student::class)`

#### Club
- **Relations:**
  - `belongsToMany(User::class)` via table pivot `club_user`
  - `belongsToMany(Teacher::class)` via table pivot `club_teachers`
  - `belongsToMany(Student::class)` via table pivot `club_students`
  - `hasMany(ClubSettings::class)`

#### Teacher
- **Attributs principaux:** `specialties`, `experience_years`, `hourly_rate`, `bio`
- **Relations:**
  - `belongsTo(User::class)`
  - `belongsToMany(Club::class)` via table pivot `club_teachers`
  - `hasMany(Lesson::class)`

#### Student
- **Attributs principaux:** `level`, `goals`, `medical_info`
- **Relations:**
  - `belongsTo(User::class)`
  - `belongsToMany(Club::class)` via table pivot `club_students`
  - `belongsToMany(Lesson::class)` via table pivot `lesson_student`

#### Lesson
- **Relations:**
  - `belongsTo(Teacher::class)`
  - `belongsTo(Student::class)` (relation directe)
  - `belongsToMany(Student::class)` (relation many-to-many)
  - `belongsTo(CourseType::class)`
  - `belongsTo(Location::class)`

## Configuration des Tests

### PHPUnit 12

Le projet utilise PHPUnit 12 avec les attributs PHP 8+ :

```php
use PHPUnit\Framework\Attributes\Test;

class ExampleTest extends TestCase
{
    #[Test]
    public function it_can_do_something(): void
    {
        // Test implementation
    }
}
```

### Configuration des Tests

```xml
<!-- phpunit.xml -->
<phpunit bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="mysql"/>
        <env name="DB_HOST" value="mysql"/>
        <env name="DB_PORT" value="3306"/>
        <env name="DB_DATABASE" value="activibe_test"/>
    </php>
</phpunit>
```

### Tests Unitaires

- **Nombre total:** 303 tests
- **Couverture:** Models, Services, Middleware
- **État:** ✅ Tous les tests passent

### Authentification dans les Tests

Pour les tests Feature nécessitant une authentification admin :

```php
protected function actingAsAdmin(): User
{
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => 'active',
        'is_active' => true,
    ]);

    // Créer un token Sanctum pour l'admin
    $token = $admin->createToken('test-token')->plainTextToken;
    
    // Définir l'en-tête Authorization pour le middleware admin
    $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
    ]);

    return $admin;
}
```

## Services

### Neo4jAnalysisService

Service d'analyse de données utilisant Neo4j pour les analyses graphiques :

- **Analyses disponibles:**
  - Relations utilisateurs-clubs
  - Enseignants par spécialité
  - Contrats par type et statut
  - Distribution géographique
  - Recommandations d'enseignants

### QrCodeService

Service de génération et gestion des QR codes :

- Génération de QR codes pour utilisateurs et clubs
- Lecture et validation des QR codes
- Intégration avec SimpleSoftwareIO/QrCode

## Base de Données

### Configuration MySQL

```yaml
# docker-compose.yml
mysql:
  image: mysql:8.0
  environment:
    MYSQL_DATABASE: activibe_prod
    MYSQL_USER: activibe_user
    MYSQL_PASSWORD: ${DB_PASSWORD}
  ports:
    - "3306:3306"
```

### Migrations Importantes

- **Tables principales:** users, clubs, teachers, students, lessons
- **Tables pivot:** club_user, club_teachers, club_students, lesson_student
- **Tables de configuration:** club_settings, app_settings

## Déploiement

### Docker

#### Environnement de Développement

```yaml
# docker-compose.yml
services:
  app:
    build: .
    ports:
      - "8080:80"
    volumes:
      - .:/var/www
```

#### Environnement de Production

```yaml
# docker-compose.prod.yml
services:
  app:
    image: docker.io/activibe/app:latest
    ports:
      - "80:80"
      - "443:443"
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
```

### Pipeline CI/CD GitHub Actions

Le projet utilise GitHub Actions pour l'intégration continue et le déploiement automatique :

#### Jobs Principaux

1. **Tests:** Exécution des tests unitaires avec PHP 8.3 et MySQL 8.0
2. **Security:** Audit de sécurité avec Composer
3. **Build:** Construction de l'image Docker multi-architecture
4. **Deploy:** Déploiement automatique production
5. **Notify:** Notifications GitHub et Slack

#### Configuration des Tests en CI

```yaml
- name: Setup PHP
  uses: shivammathur/setup-php@v2
  with:
    php-version: 8.3
    extensions: mbstring, dom, fileinfo, mysql, redis

- name: Execute tests
  run: php artisan test --testsuite=Unit --stop-on-failure
  env:
    APP_NAME: BookYourCoach
    APP_ENV: testing
    DB_CONNECTION: mysql
    DB_HOST: 127.0.0.1
    DB_DATABASE: activibe_test
    REDIS_HOST: 127.0.0.1
```

#### GitHub Container Registry

- **Registry:** `ghcr.io`
- **Authentification:** Automatique avec `GITHUB_TOKEN`
- **Images:** `ghcr.io/owner/repository:latest`

#### Environnements de Déploiement

- **Production:** Branche `main` → `docker-compose.prod.yml`

#### Secrets GitHub Requis

- `PRODUCTION_HOST`, `PRODUCTION_USERNAME`, `PRODUCTION_SSH_KEY`
- `SLACK_WEBHOOK` (optionnel)

Voir [Configuration GitHub Actions](GITHUB_ACTIONS_CONFIG.md) pour plus de détails.

## Sécurité

### Authentification

- **Laravel Sanctum:** Pour l'authentification API
- **Middleware personnalisé:** `AdminMiddleware` pour éviter les problèmes SIGSEGV avec Sanctum
- **Rôles utilisateur:** admin, teacher, student, club

### Middleware AdminMiddleware

```php
public function handle(Request $request, Closure $next): Response
{
    $token = $request->header('Authorization');
    
    if (!$token || !str_starts_with($token, 'Bearer ')) {
        return response()->json(['message' => 'Token manquant'], 401);
    }
    
    $token = substr($token, 7);
    $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
    
    if (!$personalAccessToken) {
        return response()->json(['message' => 'Token invalide'], 401);
    }
    
    $user = $personalAccessToken->tokenable;
    
    if (!$user || $user->role !== 'admin') {
        return response()->json(['message' => 'Accès refusé'], 403);
    }
    
    return $next($request);
}
```

## Performances

### Configuration PHP

```ini
# docker/php.ini
memory_limit = 2G
max_execution_time = 300
upload_max_filesize = 10M
post_max_size = 10M
```

### Cache Redis

- **Sessions:** Stockage des sessions utilisateur
- **Cache application:** Cache des données fréquemment utilisées
- **Queues:** Gestion des tâches asynchrones

## API Documentation

### Endpoints Principaux

#### Authentification
- `POST /api/auth/login` - Connexion utilisateur
- `POST /api/auth/logout` - Déconnexion utilisateur
- `GET /api/auth/user` - Informations utilisateur connecté

#### Administration
- `GET /api/admin/stats` - Statistiques plateforme
- `GET /api/admin/users` - Liste des utilisateurs
- `POST /api/admin/users` - Création d'utilisateur

#### Clubs
- `GET /api/club/dashboard` - Tableau de bord club
- `GET /api/club/teachers` - Enseignants du club
- `GET /api/club/students` - Étudiants du club

## Maintenance

### Commandes Artisan Utiles

```bash
# Tests
php artisan test --testsuite=Unit
php artisan test --coverage

# Base de données
php artisan migrate
php artisan db:seed

# Cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Logs

Les logs sont configurés dans `config/logging.php` et stockés dans `storage/logs/`.

## Troubleshooting

### Problèmes Courants

1. **Tests Feature avec erreur 401:**
   - Vérifier l'authentification dans TestCase
   - S'assurer que le middleware admin est configuré

2. **Erreurs de mémoire PHPUnit:**
   - Augmenter `memory_limit` dans php.ini
   - Utiliser `php -d memory_limit=2G artisan test`

3. **Problèmes Docker:**
   - Vérifier que MySQL est démarré
   - Reconstruire les containers : `docker-compose build --no-cache`

### Versions Supportées

- **PHP:** 8.3+
- **Laravel:** 11.x
- **MySQL:** 8.0+
- **PHPUnit:** 12.x

## Déploiement Production

Voir [Guide de Déploiement Production](PRODUCTION_DEPLOYMENT.md) pour les instructions complètes de déploiement.

### Configuration Rapide

```bash
# Cloner le repository
git clone https://github.com/owner/bookyourcoach.git
cd bookyourcoach

# Configuration production
cp .env.example .env.production
# Éditer .env.production avec vos valeurs

# Déploiement
docker-compose -f docker-compose.prod.yml up -d
```

## Contribuer

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit les changements (`git commit -am 'Ajouter nouvelle fonctionnalité'`)
4. Push vers la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Créer une Pull Request

### Standards de Code

- **PSR-12:** Standard de codage PHP
- **Tests:** Chaque nouvelle fonctionnalité doit avoir des tests
- **Documentation:** Documenter les nouvelles API et fonctionnalités
- **CI/CD:** Les tests doivent passer avant le merge
