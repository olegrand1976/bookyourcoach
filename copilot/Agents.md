# Agents.md - BookYourCoach Project

## 📋 Vue d'ensemble du projet

**BookYourCoach** est une plateforme de réservation de coaching développée avec une architecture moderne frontend/backend séparée.

### Architecture globale

-   **Frontend**: Nuxt 3.17.7 (Vue.js + TypeScript) - Port 3000
-   **Backend**: Laravel 11 (PHP 8.3) - Port 8081
-   **Database**: SQLite (dev), MySQL (prod)
-   **Auth**: Laravel Sanctum
-   **Deployment**: Docker Compose

## 🏗️ Structure du projet

```
bookyourcoach/
├── copilot/                    # Dossier principal de développement
│   ├── frontend/              # Application Nuxt.js
│   │   ├── components/        # Composants Vue
│   │   ├── pages/            # Pages de l'application
│   │   ├── stores/           # Stores Pinia
│   │   ├── composables/      # Logique réutilisable
│   │   └── Agents.md         # Documentation frontend
│   ├── backend/              # API Laravel (symlink vers app/)
│   │   └── Agents.md         # Documentation backend
│   ├── app/                  # Code Laravel
│   ├── config/               # Configuration Laravel
│   ├── database/             # Migrations, seeders
│   ├── docker-compose.yml    # Configuration Docker
│   └── scripts/              # Scripts utilitaires
└── README.md                 # Documentation principale
```

## 🎯 Statut et Tâches Actuelles

### Statut

-   ✅ **FIXED**: Le bug des paramètres système est résolu. La sauvegarde depuis la page admin met correctement à jour le header de manière dynamique.
-   ✅ **FIXED**: Les erreurs de compilation et d'auto-import sur le frontend (Nuxt/TypeScript) sont résolues.
-   ✅ **DONE**: La documentation `Agents.md` a été créée et est maintenue à jour.

### Tâches en cours

1.  **FEATURE**: Ajouter un lien dans le menu de navigation pour que les administrateurs puissent accéder à leur "tableau de bord utilisateur" standard, en plus de la vue "Admin".
2.  **DOCS**: Mettre à jour le `README.md` principal.
3.  **TESTS**: Mettre à jour les tests (frontend et backend) pour couvrir les récents changements et la nouvelle fonctionnalité du menu admin.

## 🔗 Communication Frontend/Backend

### Endpoints principaux

```
Authentication:
POST /api/auth/login          # Connexion utilisateur
GET  /api/auth/user           # Profil utilisateur
POST /api/auth/logout         # Déconnexion

Admin Settings:
GET  /api/admin/settings/general    # Récupération paramètres
PUT  /api/admin/settings/general    # Sauvegarde paramètres
GET  /api/admin/stats               # Statistiques système
POST /api/admin/upload-logo         # Upload logo

User Management:
GET  /api/users                     # Liste utilisateurs
POST /api/users                     # Création utilisateur
PUT  /api/users/{id}                # Modification utilisateur
```

### Configuration CORS

-   **Frontend Origin**: http://localhost:3000
-   **Backend API**: http://localhost:8081/api
-   **Credentials**: Support des cookies Sanctum

## 🔐 Système d'authentification

### Flow d'authentification

1. **Login**: POST /api/auth/login avec email/password
2. **Token**: Réception token Sanctum
3. **Storage**: Token stocké dans localStorage + header Authorization
4. **Validation**: Middleware frontend/backend pour vérification
5. **Admin**: Vérification rôle pour accès administration

### Utilisateurs de test

```
Admin: admin.secours@bookyourcoach.com / secours123
```

## ⚙️ Fonctionnalités principales

### Gestion des paramètres système

-   **Interface**: Page admin pour modification paramètres
-   **API**: Endpoints CRUD pour paramètres généraux
-   **Réactivité**: Header mis à jour automatiquement
-   **Stockage**: Base de données avec modèle Settings

### Paramètres configurables

-   Nom de la plateforme
-   Email de contact
-   Téléphone de contact
-   Fuseau horaire
-   Adresse de la société
-   Logo de la plateforme

## 🐳 Environnement Docker

### Services

```yaml
# docker-compose.yml
services:
    frontend: # Nuxt.js sur port 3000
    backend: # Laravel sur port 8081
    mysql: # Base de données (production)
```

### Commandes utiles

```bash
# Démarrage complet
docker-compose up -d

# Frontend uniquement
docker-compose up frontend

# Logs
docker-compose logs -f frontend
docker-compose logs -f backend

# Rebuild
docker-compose build --no-cache
```

## 🧪 Tests et Debug

### Frontend

-   **Pages debug**: test-auth.vue, test-api-direct.vue
-   **Tests**: Vitest + Playwright
-   **Logs**: Console avec préfixes emojis

### Backend

-   **Tests**: PHPUnit
-   **Logs**: Laravel logs dans storage/logs/
-   **Debug**: Artisan commands, tinker

### Scripts de test

```bash
# Frontend
cd frontend && npm run test
cd frontend && npm run test:e2e

# Backend
php artisan test
```

## 🚀 Développement

### Démarrage rapide

```bash
# 1. Cloner le projet
git clone [repo-url]
cd bookyourcoach/copilot

# 2. Backend
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --host=0.0.0.0 --port=8081

# 3. Frontend (nouveau terminal)
cd frontend
npm install
npm run dev
```

### Scripts utilitaires

-   `setup-master.sh`: Configuration complète du projet
-   `start-full-stack.sh`: Démarrage frontend + backend
-   `test_api.sh`: Tests de validation API
-   `cleanup.sh`: Nettoyage des containers

## 📊 État du projet

### ✅ Fonctionnalités complètes

-   Architecture Docker complète
-   Authentification Sanctum fonctionnelle
-   Interface d'administration
-   Gestion des paramètres système
-   Tests de validation API
-   Documentation agents complète

### 🔄 En développement

-   Finalisation sauvegarde paramètres avec mise à jour header
-   Tests unitaires complets
-   Interface utilisateur étudiants/enseignants

### 📋 À développer

-   Système de réservation
-   Gestion des créneaux
-   Interface de paiement
-   Notifications
-   Rapports et analytics

## 🔧 Configuration importante

### Variables d'environnement

```env
# Backend (.env)
APP_URL=http://localhost:8081
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000
CORS_ALLOWED_ORIGINS=http://localhost:3000

# Frontend (.env)
API_BASE_URL=http://localhost:8081/api
```

### Ports utilisés

-   **3000**: Frontend Nuxt.js
-   **8081**: Backend Laravel API
-   **3306**: MySQL (production)

## 🐛 Troubleshooting

### Problèmes courants

1. **CORS**: Vérifier configuration backend + origin frontend
2. **Tokens**: Expiration et nettoyage Sanctum
3. **Docker**: Rebuild si problèmes de cache
4. **Permissions**: Vérifier rôles utilisateurs

### Commandes de diagnostic

```bash
# Vérifier services
docker-compose ps

# Test API direct
curl -X GET "http://localhost:8081/api/auth/user" \
     -H "Authorization: Bearer [token]"

# Logs frontend
docker-compose logs frontend

# Logs backend
tail -f storage/logs/laravel.log
```

## 📝 Notes pour développeurs

### Conventions

-   **Français**: Interface utilisateur et commentaires
-   **Anglais**: Code et documentation technique
-   **Logs**: Préfixes avec emojis pour identification rapide
-   **Git**: Branches par fonctionnalité

### Architecture recommandée

-   **Composables**: Logique réutilisable côté frontend
-   **Services**: Logique métier côté backend
-   **Middleware**: Protection et validation des routes
-   **Events**: Communication entre composants

### Ressources

-   [Documentation Nuxt 3](https://nuxt.com/docs)
-   [Documentation Laravel 11](https://laravel.com/docs/11.x)
-   [Laravel Sanctum](https://laravel.com/docs/11.x/sanctum)
-   [Pinia Store](https://pinia.vuejs.org/)

## 🚨 Points critiques à retenir

### Problèmes résolus récemment

1. **Authentification persistence** ✅

    - Admin status perdu au refresh de page → RÉSOLU
    - Debug complet avec logs détaillés
    - Store auth.ts optimisé avec initializeAuth()

2. **Connexion Frontend/Backend** ✅

    - Problèmes CORS → RÉSOLU
    - Configuration Sanctum → RÉSOLU
    - Frontend fonctionne sur port 3000, backend sur 8081

3. **Paramètres système** ✅ BACKEND OPÉRATIONNEL / 🔄 FRONTEND EN COURS
    - ✅ Backend: API complètement fonctionnelle et testée
    - ✅ Endpoints GET/PUT /admin/settings/general opérationnels
    - ✅ Validation des données et sécurité admin OK
    - 🔄 Frontend: Erreurs TypeScript à corriger dans settings.vue
    - 🔄 Frontend: Header non mis à jour dynamiquement

**Tests backend validés** :

```bash
# Sauvegarde testée avec succès
curl -X PUT "http://localhost:8081/api/admin/settings/general" \
  -H "Authorization: Bearer {token}" \
  -d '{"platform_name":"Test Platform","contact_email":"test@test.com","timezone":"Europe/Brussels"}'
# ✅ Réponse: {"message":"Paramètres mis à jour avec succès"}

# Récupération testée avec succès
curl -X GET "http://localhost:8081/api/admin/settings/general" \
  -H "Authorization: Bearer {token}"
# ✅ Réponse: {"platform_name":"Test Platform",...}
```

### Configuration critique

```bash
# Commandes de démarrage
docker-compose up -d          # Démarre tous les services
docker-compose up frontend    # Frontend seul
docker-compose up backend     # Backend seul

# Tests importants
curl "http://localhost:8081/api/auth/login" -X POST
  -H "Content-Type: application/json"
  -d '{"email": "admin.secours@bookyourcoach.com", "password": "secours123"}'
```

### Utilisateurs de test

```
Admin de secours:
- Email: admin.secours@bookyourcoach.com
- Password: secours123
- Role: admin
```

## 🛠️ Debug et développement

### Outils de debug créés

1. **Pages de test Frontend**

    - `/test-auth` : Test authentification complète
    - `/test-api-direct` : Tests API directs avec logs

2. **Scripts de test Backend**
    - `test_admin_debug.sh` : Vérification accès admin
    - `test_auth_flow.cjs` : Test complet auth flow
    - `test_connexion.sh` : Test connexion API

### Logs importants

```bash
# Frontend (Nuxt)
Logs dans la console navigateur avec préfixes:
🔧 [AUTH] : Authentification
🔧 [SETTINGS] : Paramètres système
✅ : Succès
❌ : Erreurs

# Backend (Laravel)
tail -f storage/logs/laravel.log
```

## 📁 Fichiers clés modifiés

### Frontend

-   `stores/auth.ts` : Store authentification avec debug complet
-   `composables/useSettings.ts` : Gestion paramètres système
-   `components/Logo.vue` : Header avec nom dynamique
-   `pages/admin/settings.vue` : Interface paramètres admin

### Backend

-   `app/Http/Controllers/AuthController.php` : API authentification
-   `config/cors.php` : Configuration CORS critique
-   `config/sanctum.php` : Configuration tokens
-   `routes/api.php` : Définition routes API

## 🎯 Prochaines étapes

1. **Finir correction paramètres système**

    - Corriger erreurs TypeScript dans settings.vue
    - Tester sauvegarde et mise à jour header
    - Valider réactivité du composant Logo

2. **Optimisations possibles**
    - Système de notifications toast
    - Validation côté client des formulaires
    - Gestion d'erreurs plus fine
    - Tests automatisés

## 💡 Bonnes pratiques établies

-   **Debugging** : Logs détaillés avec préfixes colorés
-   **API** : Endpoints RESTful consistants
-   **Auth** : Middleware de protection systématique
-   **State** : Composables réactifs pour logique partagée
-   **Types** : TypeScript strict pour la fiabilité
