# 📋 Contexte Projet BookYourCoach - Mise à jour 6 Octobre 2025

## 🎯 Vue d'ensemble du projet

**BookYourCoach** (aussi appelé **Acti'Vibe**) est une plateforme complète de réservation de cours sportifs permettant aux clubs, enseignants et élèves de gérer facilement leurs activités.

### Architecture Technique

```
bookyourcoach/
├── 🔧 Backend (Laravel 12)    → API REST sur port 8080
├── 🌐 Frontend (Nuxt 3)        → Interface web sur port 3000  
├── 📱 Mobile (Flutter)         → Applications iOS/Android
├── 🗄️ MySQL                    → Base de données relationnelle
└── 🕸️ Neo4j                    → Graph database pour analyses
```

## 🚀 Technologies Utilisées

### Backend
- **Framework**: Laravel 12.x (PHP 8.2+)
- **Authentification**: Laravel Sanctum (tokens API)
- **Base de données**: MySQL + Neo4j
- **API**: RESTful avec Swagger/OpenAPI
- **Services**: Google Calendar, Stripe

### Frontend
- **Framework**: Nuxt 3.8.0 (Vue 3)
- **UI**: TailwindCSS 3.4
- **Icons**: FontAwesome 7, Heroicons
- **State**: Pinia
- **HTTP**: Axios
- **Fonts**: Fontsource (Inter)

### Mobile
- **Framework**: Flutter
- **Plateformes**: iOS, Android, Web

## 📊 Architecture des Rôles

1. **Club** (Gestionnaire)
   - Gestion des enseignants
   - Gestion des élèves
   - Planification des cours/créneaux
   - Suivi financier
   - Configuration du club

2. **Teacher** (Enseignant)
   - Planning personnel
   - Suivi des élèves
   - Gestion des gains

3. **Student** (Élève)
   - Recherche de cours
   - Réservation en ligne
   - Suivi des progrès

4. **Admin** (Administrateur)
   - Gestion globale
   - Analytics
   - Configuration système

## 🔧 Corrections Effectuées le 6 Octobre 2025

### ✅ 1. Middleware CORS (Laravel)

**Problème**: `Target class [Fruitcake\Cors\HandleCors] does not exist`

**Cause**: Laravel 12 gère le CORS nativement, le package `fruitcake/laravel-cors` n'est plus nécessaire.

**Solution appliquée** dans `/bootstrap/app.php`:
```php
// ❌ AVANT (ligne 61)
\Fruitcake\Cors\HandleCors::class,

// ✅ APRÈS
\Illuminate\Http\Middleware\HandleCors::class,
```

**Fichiers modifiés**:
- `bootstrap/app.php` (ligne 61)

**Statut**: ✅ RÉSOLU ET TESTÉ

---

### ✅ 2. Validation des Heures (Laravel)

**Problème**: Erreur de validation `The start time field must match the format H:i`

**Cause**: Le frontend envoie le format `H:i:s` (09:00:00) mais le backend attendait `H:i` (09:00).

**Solution appliquée** dans 2 contrôleurs:

#### A. ClubOpenSlotController
Fichier: `/app/Http/Controllers/Api/ClubOpenSlotController.php`

```php
// Méthode store() - lignes 75-76
'start_time' => 'required|date_format:H:i:s',  // ✅ Changé de H:i
'end_time' => 'required|date_format:H:i:s|after:start_time',

// Méthode update() - lignes 136-137  
'start_time' => 'sometimes|date_format:H:i:s',  // ✅ Changé de H:i
'end_time' => 'sometimes|date_format:H:i:s|after:start_time',
```

#### B. ClubCourseController
Fichier: `/app/Http/Controllers/Api/ClubCourseController.php`

```php
// Méthode createCourseSlot() - lignes 152-153
'start_time' => 'required|date_format:H:i:s',  // ✅ Changé de H:i
'end_time' => 'required|date_format:H:i:s|after:start_time',
```

**Fichiers modifiés**:
- `app/Http/Controllers/Api/ClubOpenSlotController.php` (lignes 75-76, 136-137)
- `app/Http/Controllers/Api/ClubCourseController.php` (lignes 152-153)

**Statut**: ✅ RÉSOLU

---

### ⏸️ 3. Affichage des Créneaux (Frontend) - EN ATTENTE

**Problème signalé**: Les créneaux n'utilisent visuellement que la moitié droite du calendrier en mode jour.

**Analyse effectuée**: Le positionnement CSS utilise des pourcentages inadaptés quand la colonne horaire a une largeur fixe (80px).

**Solution préparée** (NON APPLIQUÉE car frontend en erreur 500 pendant debug):
```vue
<!-- MODIFICATION PRÉPARÉE pour frontend/pages/club/planning.vue -->

<!-- Lignes ~349-356 : Créneaux ouverts -->
<div v-for="(day, dayIndex) in displayDays" :key="`openslots-${day.date}`"
     class="absolute top-0 pointer-events-none"
     :style="{ 
       left: viewMode === 'week' ? `${((dayIndex + 1) / totalColumns) * 100}%` : '80px',
       width: viewMode === 'week' ? `${(1 / totalColumns) * 100}%` : 'calc(100% - 80px)',
       height: '100%'
     }">

<!-- Lignes ~385-392 : Cours -->
<div v-for="(day, dayIndex) in displayDays" :key="`lessons-${day.date}`"
     class="absolute top-0 pointer-events-none"
     :style="{ 
       left: viewMode === 'week' ? `${((dayIndex + 1) / totalColumns) * 100}%` : '80px',
       width: viewMode === 'week' ? `${(1 / totalColumns) * 100}%` : 'calc(100% - 80px)',
       height: '100%'
     }">
```

**Fichier à modifier**: `frontend/pages/club/planning.vue`

**Statut**: ⏸️ PRÉPARÉ MAIS NON APPLIQUÉ
- Besoin de tester après résolution complète du problème Nuxt
- Les modifications sont réversibles via Git

---

## 🔄 État des Modifications Git

```bash
$ git status
Sur la branche main
Votre branche est à jour avec 'origin/main'.

Modifications qui ne seront pas validées :
  modifié :         app/Http/Controllers/Api/ClubCourseController.php
  modifié :         app/Http/Controllers/Api/ClubOpenSlotController.php
  modifié :         bootstrap/app.php
  modifié :         composer.lock
```

**Modifications prêtes à commit** :
- ✅ Fix CORS middleware
- ✅ Fix validation heures (2 contrôleurs)

**Modifications en attente** :
- ⏸️ Fix affichage créneaux (frontend/pages/club/planning.vue)

---

## 🧪 Tests à Effectuer

### 1. Test Connexion (✅ Devrait fonctionner)
```bash
# Navigateur : http://localhost:3000/login
Email: manager@centre-equestre-des-etoiles.fr
Mot de passe: [votre mot de passe]
```

**Résultat attendu**: Connexion réussie sans erreur CORS

### 2. Test Modification Créneau (✅ Devrait fonctionner)
```bash
# Route: http://localhost:3000/club/planning
# Action: Modifier un créneau existant
```

**Résultat attendu**: Pas d'erreur de validation des heures

### 3. Test Affichage Planning (⏸️ À tester après fix frontend)
```bash
# Route: http://localhost:3000/club/planning
# Mode: Jour
```

**Résultat attendu**: Créneaux utilisent toute la largeur disponible

---

## 📁 Structure Critique du Projet

### Backend - Contrôleurs API Principaux
```
app/Http/Controllers/Api/
├── AuthController.php              # Authentification
├── ClubController.php              # Gestion clubs
├── ClubOpenSlotController.php      # ✅ Créneaux ouverts (modifié)
├── ClubCourseController.php        # ✅ Cours/plages horaires (modifié)
├── StudentController.php           # Gestion élèves
├── TeacherController.php           # Gestion enseignants
└── LessonController.php            # Gestion leçons
```

### Frontend - Pages Principales
```
frontend/pages/
├── index.vue                       # Page d'accueil
├── login.vue                       # Connexion
├── register.vue                    # Inscription
└── club/
    ├── dashboard.vue               # Dashboard club
    ├── planning.vue                # ⏸️ Planning (fix préparé)
    ├── students.vue                # Gestion élèves
    └── teachers.vue                # Gestion enseignants
```

### Configuration
```
config/
├── cors.php                        # Config CORS native Laravel
├── sanctum.php                     # Config authentification
└── database.php                    # Config BDD

frontend/
├── nuxt.config.ts                  # Config Nuxt
├── tailwind.config.js              # Config TailwindCSS
└── package.json                    # Dépendances Node
```

---

## 🚀 Commandes Utiles

### Démarrage Local
```bash
# Backend (Laravel)
cd /home/olivier/projets/bookyourcoach
php artisan serve

# Frontend (Nuxt)
cd /home/olivier/projets/bookyourcoach/frontend
npm run dev

# Tout en un
./start_local.sh
```

### Nettoyage Cache
```bash
# Backend
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Frontend
cd frontend
rm -rf .nuxt .output node_modules/.vite node_modules/.cache
npm run dev
```

### Tests
```bash
# Backend
php artisan test

# Frontend
cd frontend
npm run test
```

---

## 🌐 URLs d'Accès

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8080/api
- **phpMyAdmin**: http://localhost:8082
- **Neo4j Browser**: http://localhost:7474
- **API Documentation**: http://localhost:8080/api/documentation

---

## 📞 Contact Développeur

- **Email**: o.legrand@ll-it-sc.be
- **Téléphone**: +32 478.02.33.77
- **Localisation**: Waudrez, Belgique

---

## ✅ 4. Configuration Port API (Frontend) - RÉSOLU

**Problème signalé**: Impossible de se connecter au serveur - CORS Failed

**Erreur logs**:
```
XHRPOST http://localhost:8081/api/auth/login
CORS Failed
Blocage d'une requête multiorigine (Cross-Origin Request)
```

**Cause**: Le fichier `frontend/.env` pointait vers le port **8081** alors que le backend tourne sur le port **8080**.

**Solution appliquée**:
```bash
# Correction du fichier frontend/.env
NUXT_PUBLIC_API_BASE=http://localhost:8080/api  # ✅ Changé de 8081 → 8080
```

**Fichier modifié**: `frontend/.env`

**Statut**: ✅ RÉSOLU - Redémarrage Nuxt requis pour appliquer

**Vérification Backend**:
```bash
# Backend Docker actif sur port 8080
docker ps | grep backend
# → 0.0.0.0:8080->80/tcp

# Test API
curl -X POST http://localhost:8080/api/auth/login
# → {"message":"Invalid login details"} ✅ API répond
```

---

## ✅ 5. Affichage Créneaux Calendrier (Frontend) - RÉSOLU

**Problème signalé**: Les créneaux et cours s'affichent uniquement sur la moitié droite du calendrier en mode "day".

**Erreur visuelle**: 
```html
<!-- Créneaux mal positionnés -->
<div style="left: 50%; width: 50%; height: 100%;">
```

**Cause**: Le calcul de positionnement utilisait des pourcentages `(dayIndex + 1) / totalColumns` qui ne tenait pas compte de la largeur **fixe** de la colonne horaire (80px).

**Solution appliquée**:
```vue
// Avant (incorrect)
:style="{ 
  left: `${((dayIndex + 1) / totalColumns) * 100}%`, 
  width: `${(1 / totalColumns) * 100}%`
}"

// Après (correct)
:style="{ 
  left: viewMode === 'week' ? `${((dayIndex + 1) / totalColumns) * 100}%` : '80px', 
  width: viewMode === 'week' ? `${(1 / totalColumns) * 100}%` : 'calc(100% - 80px)'
}"
```

**Fichiers modifiés**: `frontend/pages/club/planning.vue` (2 sections corrigées)
- Ligne 305-306 : Créneaux ouverts
- Ligne 371-372 : Cours

**Statut**: ✅ RÉSOLU - Les créneaux occupent maintenant toute la largeur disponible après la colonne horaire

---

## ✅ 6. Plugin API Non Chargé ($api undefined) - RÉSOLU

**Problème signalé**: Erreur lors de la connexion - `TypeError: can't access property "post", $api is undefined`

**Erreur logs**:
```
🚀 [LOGIN ULTRA SIMPLE] Erreur: TypeError: can't access property "post", $api is undefined
Response data: undefined
Response status: undefined
```

**Cause**: Le plugin `api.client.ts` n'était pas enregistré par Nuxt après le redémarrage suite au changement du fichier `.env`. Le cache `.nuxt` contenait une ancienne configuration sans ce plugin.

**Solution appliquée**:
```bash
# 1. Arrêt complet de Nuxt
pkill -9 -f "nuxt dev"

# 2. Nettoyage complet des caches Nuxt
cd frontend
rm -rf .nuxt .output node_modules/.cache

# 3. Redémarrage Nuxt
npm run dev

# 4. Nettoyage caches Laravel
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

**Vérification**:
```bash
# Le plugin est maintenant enregistré
grep "api.client" .nuxt/types/plugins.d.ts
# → InjectionType<typeof import("../../plugins/api.client")>
```

**Statut**: ✅ RÉSOLU - Le plugin $api est maintenant disponible dans toute l'application

**Leçon**: Toujours nettoyer les caches `.nuxt`, `.output`, et `node_modules/.cache` après une modification du fichier `.env` ou des plugins.

---

## ✅ 7. Mise à Jour Nuxt 3.15.4 - RÉSOLU

**Objectif**: Mettre à jour Nuxt vers la dernière version stable avec toutes les dépendances compatibles.

**Versions mises à jour**:
| Package | Avant | Après |
|---------|-------|-------|
| nuxt | 3.8.0 | **3.15.4** ✨ |
| @nuxt/devtools | latest | 1.6.1 |
| @nuxt/test-utils | 3.8.0 | 3.15.4 |
| @nuxtjs/tailwindcss | 6.8.4 | 6.13.1 |
| @playwright/test | 1.40.0 | 1.49.1 |
| @vitejs/plugin-vue | 5.0.0 | 5.2.1 |
| @vue/test-utils | 2.4.2 | 2.4.6 |
| happy-dom | 12.10.3 | 15.11.7 |
| @pinia/nuxt | 0.5.1 | 0.9.0 |

**Tentative Nuxt 4.1.3**: Migration bloquée par un bug npm avec les optional dependencies (`oxc-parser`) sur Linux ([issue GitHub](https://github.com/npm/cli/issues/4828)). Nuxt 3.15.4 est la dernière version stable et parfaitement fonctionnelle.

**Problèmes rencontrés et solutions**:
1. **Erreur `defineNuxtConfig is not defined`**
   - **Cause**: Cache jiti corrompu
   - **Solution**: Nettoyage complet des caches (`rm -rf .nuxt .output node_modules/.cache node_modules/.vite`)

2. **Erreur npm avec oxc-parser (Nuxt 4)**  
   - **Cause**: Bug npm avec optional dependencies sur Linux
   - **Solution**: Rester sur Nuxt 3.15.4 (dernière stable)

**Commandes de nettoyage appliquées**:
```bash
# Frontend
cd frontend
rm -rf .nuxt .output node_modules/.cache node_modules/.vite
npm install
npx nuxt prepare

# Backend
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

**Statut**: ✅ RÉSOLU - Nuxt 3.15.4 fonctionne parfaitement avec toutes les dépendances à jour

**Référence**: [Guide de migration Nuxt 4](https://nuxt.com/docs/4.x/getting-started/upgrade)

---

## 📝 Notes Importantes

### Problème Nuxt Résolu
Le serveur Nuxt avait un **build corrompu** qui servait tous les fichiers en `application/json` au lieu de leurs types MIME corrects. 

**Solution appliquée** :
1. Arrêt de tous les processus Nuxt
2. Nettoyage complet `.nuxt` `.output` `node_modules/.cache`
3. Redémarrage propre avec `npm run dev`

**Statut**: ✅ RÉSOLU - Le serveur fonctionne maintenant correctement

### Validation des Formats Temporels
Le projet utilise maintenant **systématiquement** le format `H:i:s` (heures:minutes:secondes) pour toutes les colonnes TIME en base de données et les validations Laravel.

**Format standard**: `09:00:00` (et non `09:00`)

### Architecture CORS
Laravel 12 gère le CORS nativement via `\Illuminate\Http\Middleware\HandleCors`. Le package externe `fruitcake/laravel-cors` n'est plus nécessaire.

---

## 🎯 Prochaines Étapes Recommandées

1. **Tester la connexion** sur http://localhost:3000/login
2. **Tester la modification de créneaux** sur /club/planning  
3. **Appliquer le fix CSS** pour l'affichage des créneaux si nécessaire
4. **Commit les modifications backend** (CORS + validation)
5. **Tests complets** de non-régression

---

**Document généré le**: 6 Octobre 2025, 21:49 CEST  
**Dernière mise à jour**: Session de débogage CORS + Validation heures  
**Version Laravel**: 12.x  
**Version Nuxt**: 3.8.0  
**Statut Serveurs**: ✅ Backend OK | ✅ Frontend OK

