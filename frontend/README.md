# activibe - Frontend

Interface utilisateur Nuxt.js pour la plateforme de réservation de cours équestres activibe.

## 🚀 Démarrage rapide

### Prérequis

-   Node.js 18+
-   npm ou yarn
-   Serveur backend Laravel en cours d'exécution

### Installation

```bash
npm install
```

### Développement

```bash
npm run dev
```

Le serveur de développement sera disponible sur `http://localhost:3000` (ou un autre port si 3000 est occupé).

### Production

```bash
npm run build
npm run preview
```

## 🔑 Identifiants de test

Pour tester l'application, utilisez ces comptes de démonstration :

### 👩‍💼 Administrateur

-   **Email** : `admin@activibe.fr`
-   **Mot de passe** : `admin123`
-   **Rôle** : Accès complet à l'administration et dashboard général
-   **Redirection** : `/admin`
-   **Fonctionnalités** : Statistiques globales, CRUD des données, gestion des clubs

### 🏇 Coach/Enseignant

-   **Email** : `coach@activibe.fr`
-   **Mot de passe** : `coach123`
-   **Rôle** : Gestion des cours et disponibilités
-   **Redirection** : `/teacher`

### 🎓 Élève/Étudiant

-   **Email** : `eleve@activibe.fr`
-   **Mot de passe** : `eleve123`
-   **Rôle** : Réservation de cours
-   **Redirection** : `/dashboard`

### 🏠 Club (Nouveau rôle)

-   **Fonctionnalité** : Gestion de plusieurs enseignants et élèves
-   **Calendrier** : Vue globale des cours de tous les coaches du club
-   **Administration** : Gestion centralisée des ressources équestres

## 🏢 Gestion des Clubs

### Nouveau modèle de données

Le système intègre maintenant la notion de **Club** :

-   **Clubs** peuvent gérer plusieurs enseignants
-   **Enseignants** peuvent être affiliés à un club
-   **Élèves** peuvent être membres d'un club
-   **Calendrier centralisé** pour les clubs
-   **Gestionnaires de club** avec différents niveaux d'accès

### Structure de base de données

-   `clubs` table avec informations complètes (nom, contact, adresse, installations)
-   Relations : `teachers.club_id`, `students.club_id`
-   Table de liaison `club_managers` pour les gestionnaires
-   Nouveau rôle `club` dans la table `users`

## 🎨 Thème équestre

L'application utilise un thème complet autour de l'équitation avec :

### Icônes personnalisées

-   🐎 Cheval
-   🪑 Selle
-   ⛑️ Casque
-   🏆 Trophée
-   🥾 Fer à cheval
-   🚧 Obstacle

### Palette de couleurs

-   **Primaire** : Tons bruns équestres
-   **Secondaire** : Couleurs cuir et or
-   **Accent** : Vert forêt et crème

## 🔧 Configuration

### Variables d'environnement

Créez un fichier `.env` avec :

```
API_BASE_URL=http://localhost:8081/api
```

### Structure des pages

-   `/` - Page d'accueil avec présentation
-   `/login` - Connexion utilisateur
-   `/register` - Inscription
-   `/dashboard` - Tableau de bord élève
-   `/admin` - Panel administrateur
-   `/teacher` - Interface enseignant

## 🧪 Tests

### Tests unitaires

```bash
npm run test
```

### Tests E2E

```bash
npm run test:e2e
```

### Mode watch

```bash
npm run test:watch
```

## 📚 Composants

### EquestrianIcon

Composant d'icônes SVG personnalisées pour le thème équestre.

```vue
<EquestrianIcon icon="horse" :size="32" class="text-primary-600" />
```

**Props disponibles :**

-   `icon` : 'horse', 'saddle', 'helmet', 'trophy', 'horseshoe', 'jump'
-   `size` : Taille en pixels (défaut: 24)
-   `strokeWidth` : Épaisseur du trait (défaut: 2)
-   `class` : Classes CSS additionnelles

## 🔗 Services

### API

Configuration automatique via `plugins/api.client.ts` :

-   Base URL configurable
-   Authentification Bearer Token
-   Gestion des erreurs CORS
-   Intercepteurs pour l'authentification

### Store (Pinia)

-   **AuthStore** : Gestion de l'authentification et des rôles
-   Auto-persistance des tokens
-   Redirection selon les rôles

## 🛠️ Développement

### Structure recommandée

```
pages/           # Pages de l'application
components/      # Composants réutilisables
layouts/         # Layouts de page
stores/          # État global (Pinia)
plugins/         # Plugins Nuxt
composables/     # Fonctions composables
assets/          # Assets statiques
```

### Conventions de nommage

-   Pages : `kebab-case`
-   Composants : `PascalCase`
-   Composables : `camelCase` avec préfixe `use`

## 🐛 Dépannage

### Port déjà utilisé

Si le port 3000 est occupé, Nuxt utilisera automatiquement le port suivant disponible.

### Erreurs CORS

Vérifiez que l'API backend est configurée pour accepter les requêtes depuis `http://localhost:3000`.

### Erreurs de compilation

Vérifiez que tous les composants Vue ont au minimum une balise `<template>` ou `<script>`.

## 📖 Documentation

-   [Nuxt.js](https://nuxt.com/)
-   [Vue.js](https://vuejs.org/)
-   [Tailwind CSS](https://tailwindcss.com/)
-   [Pinia](https://pinia.vuejs.org/)
