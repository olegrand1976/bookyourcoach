# Agents.md - Frontend BookYourCoach

## 📋 Vue d'ensemble du projet

-   **Nom**: BookYourCoach
-   **Type**: Plateforme de réservation de coaching
-   **Framework**: Nuxt 3.17.7 avec Vue.js 3 et TypeScript
-   **Port**: 3000 (développement)
-   **Environnement**: Docker Compose avec backend Laravel

## 🏗️ Architecture technique

### Stack technologique

-   **Frontend**: Nuxt 3.17.7
-   **UI**: Vue 3 + TypeScript + Tailwind CSS
-   **State Management**: Pinia stores
-   **API Client**: Custom $api plugin
-   **Authentication**: Laravel Sanctum tokens
-   **Build**: Vite
-   **Testing**: Vitest + Playwright

### Structure des dossiers

```
frontend/
├── components/          # Composants Vue réutilisables
│   └── Logo.vue        # Header avec nom de plateforme dynamique
├── composables/        # Logique réutilisable
│   └── useSettings.ts  # Gestion des paramètres système
├── stores/             # Pinia stores
│   └── auth.ts         # Authentification et état utilisateur
├── pages/              # Pages de l'application
│   ├── admin/          # Interface d'administration
│   │   └── settings.vue # Paramètres système
│   ├── test-auth.vue   # Page de debug authentification
│   └── test-api-direct.vue # Tests API directs
├── middleware/         # Middlewares de navigation
│   ├── auth.ts         # Vérification authentification
│   └── admin.ts        # Vérification droits admin
├── plugins/            # Plugins Nuxt
└── layouts/            # Layouts de page
```

## ✅ Statut et Progrès

-   **FIXED**: Le bug des paramètres système est résolu. La page `admin/settings.vue` utilise maintenant le composable `useSettings` pour une mise à jour dynamique et centralisée. Le `readonly()` a été retiré pour permettre la liaison `v-model`.
-   **FIXED**: Les erreurs de compilation liées à `definePageMeta` et aux auto-imports Nuxt ont été résolues en retirant `lang="ts"` des balises `<script setup>`.
-   **IMPROVED**: Le composant `Logo.vue` réagit maintenant correctement aux changements de `useSettings`.

## 🎯 Tâches Actuelles

1.  **FEATURE**: Modifier le menu de navigation pour ajouter un lien "Mon Espace" (ou similaire) pour les administrateurs, leur permettant de basculer entre la vue admin et leur vue utilisateur standard.
2.  **TESTS**: Mettre à jour les tests Vitest/Playwright pour valider le bon fonctionnement de la page des paramètres et du nouveau lien de navigation pour les admins.

## 🔐 Système d'authentification

### Fonctionnement

-   **Type**: Laravel Sanctum avec tokens
-   **Storage**: LocalStorage + cookies
-   **Auto-refresh**: Vérification au démarrage
-   **Middleware**: Protection des routes admin

### Store d'authentification (`stores/auth.ts`)

```typescript
// État principal
interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  isAdmin: boolean
}

// Méthodes principales
- login(email, password) : Connexion utilisateur
- logout() : Déconnexion et nettoyage
- fetchUser() : Récupération profil utilisateur
- initializeAuth() : Initialisation au démarrage
```

### Middleware de protection

-   `auth.ts`: Vérifie si l'utilisateur est connecté
-   `admin.ts`: Vérifie si l'utilisateur a les droits admin
-   Usage: `middleware: ['auth', 'admin']`

## ⚙️ Gestion des paramètres système

### Composable useSettings (`composables/useSettings.ts`)

```typescript
// État réactif des paramètres
const settings = ref({
  platform_name: 'BookYourCoach',
  contact_email: 'contact@bookyourcoach.com',
  contact_phone: '+32 475 12 34 56',
  timezone: 'Europe/Brussels',
  company_address: 'BookYourCoach\nBelgique',
  logo_url: '/logo.svg'
})

// API Endpoints
- GET /admin/settings/general : Chargement paramètres
- PUT /admin/settings/general : Sauvegarde paramètres
```

### Composant Logo (`components/Logo.vue`)

-   Affiche le nom de la plateforme depuis useSettings()
-   Réactif aux changements de paramètres
-   Fallback vers 'BookYourCoach' par défaut

## 🌐 API Client

### Configuration ($api plugin)

-   Base URL: http://localhost:8081/api
-   Headers automatiques: Accept, Content-Type, Authorization
-   Gestion des tokens Sanctum
-   Support CORS avec credentials

### Endpoints principaux

```
Authentication:
- POST /auth/login
- POST /auth/logout
- GET /auth/user

Admin Settings:
- GET /admin/settings/general
- PUT /admin/settings/general
- GET /admin/stats

Upload:
- POST /admin/upload-logo
```

## 🧪 Debug et tests

### Pages de debug

-   `test-auth.vue`: Tests d'authentification
-   `test-api-direct.vue`: Tests API directs
-   Logging détaillé avec préfixes 🔐, 🔧, ✅, ❌

### Configuration de test

-   Vitest pour les tests unitaires
-   Playwright pour les tests E2E
-   Scripts: `npm run test`, `npm run test:e2e`

## 🚀 Commandes de développement

```bash
# Démarrage
npm run dev              # Dev server sur port 3000
npm run build            # Build production
npm run preview          # Preview build

# Tests
npm run test             # Tests unitaires
npm run test:e2e         # Tests E2E

# Docker
docker-compose up frontend  # Démarrage conteneur
```

## 🔧 Configuration importante

### nuxt.config.ts

-   SSR désactivé pour SPA
-   Modules: Pinia, Tailwind
-   Configuration CORS et API

### Variables d'environnement

-   API_BASE_URL: URL du backend Laravel
-   Port par défaut: 3000

## 🐛 Points d'attention et debugging

### Problèmes courants

1. **CORS**: Vérifier configuration backend Laravel
2. **Tokens**: Vérification expiration et refresh
3. **Réactivité**: S'assurer que les stores Pinia sont bien liés
4. **Middleware**: Ordre d'exécution auth puis admin

### Logs de debug

-   Préfixes standardisés pour filtrage console
-   État d'authentification loggé au démarrage
-   Réponses API loggées en détail

### État actuel

-   ✅ Authentification fonctionnelle
-   ✅ API backend connectée
-   ✅ Tests de connexion validés
-   🔄 Paramètres système en cours de finalisation
-   🔄 Header dynamique en développement

## 📝 Notes pour les agents

### Conventions de code

-   TypeScript strict
-   Composition API Vue 3
-   Nommage en français pour l'UI
-   Logs en français avec emojis

### Répertoires sensibles

-   Ne pas modifier `/vendor/` ou `/node_modules/`
-   Attention aux fichiers de config Docker
-   Logs de développement dans `/storage/logs/`

### Dépendances clés

-   `@nuxt/ui` pour les composants
-   `@pinia/nuxt` pour le state management
-   `@nuxtjs/tailwindcss` pour le styling

## 🚨 Problèmes critiques et solutions

### 1. Paramètres système - BACKEND OPÉRATIONNEL ✅ / FRONTEND EN COURS 🔄

**Backend** : ✅ COMPLÈTEMENT FONCTIONNEL

-   ✅ Endpoints GET/PUT /admin/settings/general opérationnels
-   ✅ Validation des données d'entrée correcte
-   ✅ Sauvegarde et récupération testées avec succès
-   ✅ Sécurité admin middleware fonctionnelle

**Frontend** : 🔄 EN COURS DE FINALISATION

-   **Problème restant** : Erreurs TypeScript dans `pages/admin/settings.vue`
-   **Problème restant** : Header ne se met pas à jour dynamiquement

**Tests backend confirmés** :

```bash
# Test sauvegarde (✅ FONCTIONNE)
curl -X PUT "http://localhost:8081/api/admin/settings/general" \
  -H "Authorization: Bearer {token}" \
  -d '{"platform_name":"Test Platform","contact_email":"test@test.com","timezone":"Europe/Brussels"}'
# Réponse: {"message":"Paramètres mis à jour avec succès","settings":{...}}

# Test récupération (✅ FONCTIONNE)
curl -X GET "http://localhost:8081/api/admin/settings/general" \
  -H "Authorization: Bearer {token}"
# Réponse: {"platform_name":"Test Platform",...}
```

**Solution frontend en cours** :

```typescript
// Dans pages/admin/settings.vue
const { saveSettings } = useSettings(); // Utiliser le composable global
const success = await saveSettings(settings.value); // Au lieu d'API directe
```

**Étapes de résolution** :

1. ✅ Corriger les endpoints API (GET/PUT /admin/settings/general)
2. ✅ Modifier useSettings.ts pour utiliser PUT au lieu de POST
3. ✅ Backend testé et fonctionnel
4. 🔄 Corriger les erreurs TypeScript dans settings.vue
5. 🔄 Tester la sauvegarde frontend et mise à jour du header

### 2. Authentification - RÉSOLU ✅

**Problème résolu** : Admin status perdu au refresh de page

**Solution appliquée** :

-   Amélioration du store auth.ts avec initializeAuth()
-   Debug complet avec logs détaillés
-   Gestion robuste des tokens Sanctum

### 3. Communication API - RÉSOLU ✅

**Problème résolu** : Erreurs CORS et connexion frontend/backend

**Solution appliquée** :

-   Configuration CORS backend correcte
-   Frontend sur port 3000, backend sur 8081
-   Tests de connexion validés

## 🛠️ Outils de debug disponibles

### Pages de test créées

1. **`/test-auth`** : Test complet authentification

    - Vérification token, user, admin status
    - Logs détaillés des requêtes API
    - Interface de debug interactive

2. **`/test-api-direct`** : Tests API directs
    - Tests indépendants des stores
    - Validation endpoints backend
    - Debugging connexion réseau

### Commandes utiles

```bash
# Démarrage développement
npm run dev                    # Frontend port 3000
docker-compose up backend      # Backend port 8081

# Debug
npm run build                  # Vérifier erreurs build
npm run typecheck             # Vérifier erreurs TypeScript
```

## 🎯 Prochaines tâches

1. **Urgent - Paramètres système**

    - Finir correction erreurs TypeScript settings.vue
    - Tester sauvegarde complète
    - Valider mise à jour header en temps réel

2. **Améliorations**
    - Système de notifications toast (remplacer alert())
    - Validation formulaires côté client
    - Tests automatisés Playwright
    - Gestion d'erreurs plus fine

## 💡 Bonnes pratiques Frontend

### State Management

-   Utiliser Pinia stores pour état global
-   Composables pour logique réutilisable
-   Éviter les appels API directs dans les pages

### TypeScript

-   Types stricts pour toutes les interfaces
-   Éviter `any`, préférer types spécifiques
-   Validation runtime avec Zod si nécessaire

### Performance

-   Lazy loading des composants lourds
-   SSR pour SEO et performance initiale
-   Optimisation images avec Nuxt Image

### Debugging

-   Logs préfixés pour identification rapide
-   Console groups pour organisation
-   Environment variables pour niveaux de log
