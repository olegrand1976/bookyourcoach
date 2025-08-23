# Agents.md - Backend BookYourCoach

## 📋 Vue d'ensemble du projet

-   **Nom**: BookYourCoach
-   **Type**: API Backend pour plateforme de coaching
-   **Framework**: Laravel 11 avec PHP 8.3
-   **Port**: 8081 (développement)
-   **Database**: SQLite (dev) / MySQL (production)
-   **Authentification**: Laravel Sanctum

## 🏗️ Architecture technique

### Stack technologique

-   **Backend**: Laravel 11
-   **PHP**: Version 8.3
-   **Database**: SQLite (development), MySQL (production)
-   **Authentication**: Laravel Sanctum
-   **API**: RESTful JSON API
-   **Documentation**: L5-Swagger (OpenAPI)
-   **Testing**: PHPUnit

### Structure des dossiers

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php      # Authentification
│   │   ├── AdminController.php     # Administration
│   │   └── UserController.php      # Gestion utilisateurs
│   └── Middleware/
│       ├── Authenticate.php        # Middleware auth
│       └── AdminMiddleware.php     # Middleware admin
├── Models/
│   ├── User.php                    # Modèle utilisateur
│   └── Setting.php                 # Paramètres système
├── Services/                       # Services métier
└── Providers/                      # Service providers

config/
├── auth.php                        # Configuration auth
├── cors.php                        # Configuration CORS
├── sanctum.php                     # Configuration Sanctum
└── database.php                    # Configuration DB

routes/
├── api.php                         # Routes API
└── web.php                         # Routes web
```

## ✅ Statut et Progrès

-   **STABLE**: L'API pour les paramètres système (`/api/admin/settings/general`) est entièrement fonctionnelle (GET et PUT).
-   **STABLE**: L'authentification via Sanctum fonctionne comme prévu.
-   **CONFIRMED**: Les tests `curl` ont validé que le backend n'était pas la cause du bug des paramètres.

## 🎯 Tâches Actuelles

1.  **REFACTOR**: Aucune tâche de refactoring majeure n'est nécessaire pour le moment. Le backend est stable.
2.  **TESTS**: Mettre à jour les tests PHPUnit pour s'assurer que les endpoints des paramètres sont bien couverts et que la logique des rôles (admin vs utilisateur) est correctement testée, notamment pour les futures fonctionnalités.

## 🔐 Système d'authentification

### Laravel Sanctum

```php
// Configuration dans config/sanctum.php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1')),
'expiration' => 60 * 24 * 7, // 7 jours
```

### Modèle User

```php
// app/Models/User.php
class User extends Authenticatable implements HasApiTokens
{
    protected $fillable = [
        'name', 'email', 'password', 'role'
    ];

    // Méthodes importantes
    public function isAdmin(): bool
    public function createToken(string $name): string
}
```

### Routes d'authentification

```php
// routes/api.php
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
});
```

## 🔧 Configuration CORS

### Configuration critique (`config/cors.php`)

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### Headers requis

-   `Origin: http://localhost:3000`
-   `Accept: application/json`
-   `Content-Type: application/json`
-   `Authorization: Bearer {token}`

## ⚙️ Gestion des paramètres système

### Modèle Settings

```php
// app/Models/Setting.php
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    // Méthodes utiles
    public static function get(string $key, $default = null)
    public static function set(string $key, $value): bool
    public static function getAll(): array
}
```

### API Endpoints Settings

```php
// Routes admin protégées
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/settings/general', [AdminController::class, 'getGeneralSettings']);
    Route::put('/settings/general', [AdminController::class, 'updateGeneralSettings']);
    Route::get('/stats', [AdminController::class, 'getStats']);
    Route::post('/upload-logo', [AdminController::class, 'uploadLogo']);
});
```

### Structure des paramètres

```php
// Paramètres généraux stockés
[
    'platform_name' => 'BookYourCoach',
    'contact_email' => 'contact@bookyourcoach.com',
    'contact_phone' => '+32 475 12 34 56',
    'timezone' => 'Europe/Brussels',
    'company_address' => 'BookYourCoach\nBelgique',
    'logo_url' => '/logo.svg'
]
```

## 🗄️ Base de données

### Migrations importantes

```php
// Migration Users
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->enum('role', ['student', 'teacher', 'admin'])->default('student');
    $table->rememberToken();
    $table->timestamps();
});

// Migration Settings
Schema::create('settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->text('value');
    $table->string('type')->default('string');
    $table->timestamps();
});
```

### Seeders

```php
// database/seeders/AdminSeeder.php
User::create([
    'name' => 'Admin Secours',
    'email' => 'admin.secours@bookyourcoach.com',
    'password' => Hash::make('secours123'),
    'role' => 'admin'
]);
```

## 🛡️ Middleware de sécurité

### AdminMiddleware

```php
// app/Http/Middleware/AdminMiddleware.php
public function handle(Request $request, Closure $next)
{
    if (!$request->user() || !$request->user()->isAdmin()) {
        return response()->json([
            'message' => 'Accès non autorisé'
        ], 403);
    }

    return $next($request);
}
```

### Protection des routes

```php
// Toutes les routes admin sont protégées
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Routes admin ici
});
```

## 📊 API Responses

### Format standardisé

```php
// Succès
{
    "success": true,
    "data": { ... },
    "message": "Opération réussie"
}

// Erreur
{
    "success": false,
    "message": "Message d'erreur",
    "errors": { ... }
}
```

### Codes de statut

-   `200`: Succès
-   `201`: Création réussie
-   `400`: Erreur de validation
-   `401`: Non authentifié
-   `403`: Non autorisé
-   `404`: Ressource non trouvée
-   `500`: Erreur serveur

## 🧪 Tests et debug

### Tests PHPUnit

```bash
# Exécution des tests
php artisan test

# Tests spécifiques
php artisan test --filter AuthTest
php artisan test --filter AdminTest
```

### Logs de debug

```php
// Dans les contrôleurs
Log::info('User login attempt', ['email' => $email]);
Log::error('Login failed', ['error' => $e->getMessage()]);
```

### Fichiers de logs

-   `storage/logs/laravel.log`
-   Rotation automatique par jour
-   Niveaux: emergency, alert, critical, error, warning, notice, info, debug

## 🚀 Commandes de développement

```bash
# Serveur de développement
php artisan serve --host=0.0.0.0 --port=8081

# Base de données
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed

# Cache et optimisation
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear

# Queue et jobs
php artisan queue:work
php artisan schedule:run
```

## 🔧 Configuration importante

### .env variables clés

```env
APP_NAME=BookYourCoach
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8081

DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite

SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000
SESSION_DRIVER=cookie
SESSION_DOMAIN=localhost
```

### Configuration des services

```php
// config/app.php
'timezone' => 'Europe/Brussels',
'locale' => 'fr',
'fallback_locale' => 'en',
```

## 🐛 Points d'attention et debugging

### Problèmes courants

1. **CORS**: Vérifier domaines autorisés dans `config/cors.php`
2. **Tokens Sanctum**: Expiration et nettoyage des tokens
3. **Permissions**: Vérifier middleware admin sur routes protégées
4. **Database**: Migrations et seeders pour données de base

### Debug utile

```php
// Vérifier utilisateur connecté
dd(auth('sanctum')->user());

// Vérifier tokens actifs
dd(auth()->user()->tokens);

// Debug requête
DB::enableQueryLog();
// ... code ...
dd(DB::getQueryLog());
```

### État actuel

-   ✅ API d'authentification fonctionnelle
-   ✅ CORS configuré pour frontend
-   ✅ Middleware admin opérationnel
-   ✅ Endpoints de paramètres créés
-   ✅ Base de données initialisée
-   🔄 Tests unitaires à compléter

## 📝 Notes pour les agents

### Conventions de code

-   PSR-12 pour le style de code
-   Validation des données d'entrée systématique
-   Gestion d'erreurs avec try-catch
-   Logs détaillés pour le debug

### Sécurité

-   Tous les endpoints sensibles protégés par auth:sanctum
-   Validation des rôles utilisateurs
-   Hash des mots de passe avec bcrypt
-   Limitation du taux de requêtes (rate limiting)

### Performance

-   Cache des configurations en production
-   Optimisation des requêtes Eloquent
-   Index sur les colonnes fréquemment requêtées
-   Pagination sur les listes longues

### Dépendances clés

-   `laravel/sanctum`: Authentification API
-   `darkaonline/l5-swagger`: Documentation API
-   `fruitcake/laravel-cors`: Gestion CORS

## 🚨 Problèmes critiques et solutions

### 1. Authentification Sanctum - RÉSOLU ✅

**Problème résolu** : Tokens non reconnus, CORS bloquant les requêtes

**Solutions appliquées** :

```php
// config/sanctum.php - Domaines autorisés
'stateful' => explode(',', env(
    'SANCTUM_STATEFUL_DOMAINS',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1'
)),

// config/cors.php - Origins autorisées
'allowed_origins' => ['http://localhost:3000'],
```

### 2. Paramètres système - COMPLÈTEMENT OPÉRATIONNEL ✅

**Status** : ✅ TOUS LES ENDPOINTS FONCTIONNELS ET TESTÉS

**Endpoints implémentés et testés** :

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/settings/general', [AdminController::class, 'getGeneralSettings']);
    Route::put('/admin/settings/general', [AdminController::class, 'saveGeneralSettings']);
    Route::get('/admin/stats', [AdminController::class, 'getStats']);
    Route::post('/admin/upload-logo', [AdminController::class, 'uploadLogo']);
});
```

**Tests de validation réussis** :

```bash
# ✅ Test sauvegarde (FONCTIONNE PARFAITEMENT)
curl -X PUT "http://localhost:8081/api/admin/settings/general" \
  -H "Authorization: Bearer 53|qnZnhJm9pamYufX5tBQOK7eFnkWIIaD9DDG92Vaw7182a620" \
  -H "Content-Type: application/json" \
  -d '{
    "platform_name": "Test Platform Update",
    "contact_email": "test@bookyourcoach.com",
    "contact_phone": "+33 1 23 45 67 89",
    "timezone": "Europe/Brussels",
    "company_address": "Test Address\nBelgique",
    "logo_url": "/logo.svg"
  }'

# Réponse: {"message":"Paramètres mis à jour avec succès","settings":{...}}

# ✅ Test récupération (FONCTIONNE PARFAITEMENT)
curl -X GET "http://localhost:8081/api/admin/settings/general" \
  -H "Authorization: Bearer 53|qnZnhJm9pamYufX5tBQOK7eFnkWIIaD9DDG92Vaw7182a620"

# Réponse: {"platform_name":"Test Platform Update",...}
```

**Validation des données** : ✅ Opérationnelle

-   Champs obligatoires validés (contact_email, timezone)
-   Formats email validés
-   Messages d'erreur appropriés

**Structure base de données** :

```sql
CREATE TABLE settings (
    id INTEGER PRIMARY KEY,
    key VARCHAR(255) UNIQUE,
    value TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Sécurité** : ✅ Opérationnelle

-   Middleware admin fonctionnel
-   Authentification Sanctum validée
-   Protection CORS configurée

### 3. Middleware de sécurité - OPÉRATIONNEL ✅

**AdminMiddleware** :

```php
public function handle(Request $request, Closure $next)
{
    if (!auth()->user()?->isAdmin()) {
        return response()->json(['error' => 'Admin access required'], 403);
    }
    return $next($request);
}
```

## 🛠️ Debugging et tests

### Commandes de test créées

```bash
# Test authentification admin
curl -X GET "http://localhost:8081/api/auth/user" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Test sauvegarde paramètres
curl -X PUT "http://localhost:8081/api/admin/settings/general" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"platform_name":"Test Platform"}'
```

### Logs importants

```bash
# Logs Laravel
tail -f storage/logs/laravel.log

# Logs requêtes SQL (en mode debug)
# Activer dans AppServiceProvider::boot()
DB::enableQueryLog();
```

### Utilisateur de test

```php
// Création via Tinker ou Seeder
User::create([
    'name' => 'Admin Secours',
    'email' => 'admin.secours@bookyourcoach.com',
    'password' => Hash::make('secours123'),
    'role' => 'admin'
]);
```

## 🔧 Configuration critique

### Variables d'environnement

```env
# .env - Configuration critique
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite

SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000
FRONTEND_URL=http://localhost:3000

APP_DEBUG=true  # Développement uniquement
APP_LOG_LEVEL=debug
```

### Base de données

```bash
# Initialisation
php artisan migrate:fresh --seed
php artisan db:seed --class=AdminUserSeeder
```

## 🎯 Prochaines tâches Backend

### 1. Paramètres système - TERMINÉ ✅

-   ✅ Endpoints créés et testés
-   ✅ Validation des données d'entrée opérationnelle
-   ✅ Sécurité middleware admin fonctionnelle
-   ✅ Tests manuels validés avec succès
-   🔄 Tests unitaires pour AdminController (recommandé)
-   🔄 Documentation Swagger (recommandé)

### 2. Améliorations sécurité

-   Rate limiting sur auth endpoints
-   Validation plus stricte des uploads
-   Audit logs des actions admin
-   Rotation automatique des tokens

### 3. Performance

-   Cache des paramètres système
-   Optimisation requêtes N+1
-   Index base de données
-   Monitoring APM

## 💡 Bonnes pratiques Backend

### API Design

-   Codes de statut HTTP appropriés
-   Réponses JSON consistantes
-   Pagination pour listes
-   Versioning API

### Sécurité

-   Validation systématique des inputs
-   Middleware de protection
-   Logs des actions sensibles
-   Rate limiting

### Code Quality

-   Tests unitaires et feature
-   Documentation inline
-   Type hints PHP
-   Static analysis avec PHPStan

### Database

-   Migrations versionnées
-   Seeders pour données de test
-   Relations Eloquent optimisées
-   Backup automatique production
