# Récapitulatif des modifications depuis le dernier commit

## Date: 2025-11-02

### 1. Optimisation du chargement des cours dans le dashboard enseignant

**Problème**: Chargement lent des cours dans l'espace enseignant

**Fichiers modifiés**:
- `app/Http/Controllers/Api/TeacherController.php`
- `app/Http/Controllers/Api/LessonController.php`
- `frontend/pages/teacher/dashboard.vue`

**Modifications**:
- **Backend - TeacherController**:
  - Optimisation des requêtes statistiques avec `baseQuery` clonée au lieu de requêtes séparées
  - Augmentation des limites: 20 prochains cours et 10 récents (au lieu de 5 chacun)
  - Chargement optimisé des clubs: sélection uniquement des colonnes nécessaires
  - Optimisation du calcul des heures hebdomadaires: chargement uniquement de `start_time` et `end_time`

- **Backend - LessonController**:
  - Eager loading optimisé: chargement uniquement des colonnes nécessaires (`id`, `name`, `email`, etc.)
  - Limite par défaut: maximum 100 cours chargés
  - Filtrage optimisé pour enseignants: utilisation de `where('teacher_id')` directement au lieu de `whereHas`

- **Frontend - dashboard.vue**:
  - Chargement en parallèle des demandes de remplacement et enseignants avec `Promise.all()`
  - Déduplication des cours lors de la fusion des listes
  - Limite explicite: 50 cours max en fallback

### 2. Correction de l'affichage du profil enseignant

**Problème**: Profil vide pour Sophie Martin

**Fichiers modifiés**:
- `frontend/pages/teacher/profile.vue`

**Modifications**:
- Parsing amélioré pour gérer différents formats de réponse API
- Ajout de logs console pour diagnostiquer les problèmes
- Message d'erreur amélioré si aucune donnée n'est disponible
- Gestion des cas où `teacher` est dans les relations de `profile`

### 3. Ajout de champs pour les enseignants (NISS, adresse, compte bancaire)

**Fichiers modifiés**:
- `app/Http/Controllers/Api/ClubController.php`
- `app/Models/User.php`
- `app/Models/Teacher.php`
- `frontend/components/AddTeacherAdvancedModal.vue`
- `frontend/components/AddTeacherModal.vue`
- `frontend/pages/club/teachers/add.vue`
- `database/migrations/2025_11_02_111356_add_niss_bank_account_and_experience_start_to_users_table.php`

**Modifications**:
- Ajout des champs `niss`, `bank_account_number`, `experience_start_date` dans la table `users`
- Calcul automatique de la date de naissance depuis le NISS (affichage uniquement)
- Calcul automatique des années d'expérience basé sur `experience_start_date` ou date de création du profil
- Accessor `getExperienceYearsAttribute` dans le modèle `Teacher` pour calcul dynamique
- Formulaire complet dans `club/teachers/add.vue` avec tous les nouveaux champs

### 4. Valeurs par défaut pour les enseignants

**Fichiers modifiés**:
- `frontend/components/AddTeacherAdvancedModal.vue`
- `frontend/components/AddTeacherModal.vue`

**Modifications**:
- Tarif horaire par défaut: 24 €
- Type de contrat par défaut: "bénévole" (volunteer)

### 5. Mise à jour du footer et des icônes

**Fichiers modifiés**:
- `frontend/layouts/default.vue`
- `frontend/layouts/minimal.vue`
- `frontend/pages/coaches.vue`
- `frontend/pages/disciplines.vue`
- `frontend/pages/profile.vue`

**Modifications**:
- Remplacement des icônes football (⚽) par icône sportive générale (🏃)
- Modification de l'email de contact: `o.legrand@ll-it-sc.be` → `info@activibe.be`
- Vérification de la présence du lien "Centres équestres" dans le footer

### 6. Amélioration de l'affichage du titre dans le dashboard enseignant

**Fichiers modifiés**:
- `frontend/pages/teacher/dashboard.vue`

**Modifications**:
- Ajout du titre "Personne de contact club" au-dessus du bloc de contact pour toutes les tailles d'écran (mobile, tablette, desktop)

### 7. Correction de l'erreur 500 sur les notifications

**Problème**: Erreur 500 sur `/teacher/notifications/unread-count`

**Fichiers modifiés**:
- `app/Http/Controllers/Api/NotificationController.php`
- `app/Services/NotificationService.php`

**Modifications**:
- Gestion d'erreur améliorée: retourne 0 au lieu d'une erreur 500 pour ne pas bloquer l'interface
- Vérification de l'existence de la table `notifications` avant la requête
- Logs améliorés pour diagnostiquer les problèmes
- Vérification de l'authentification de l'utilisateur

### 8. Corrections diverses

**Fichiers modifiés**:
- `app/Http/Controllers/Api/SubscriptionTemplateController.php`
- `frontend/pages/club/subscription-templates.vue`

**Modifications**:
- Petites corrections pour améliorer la cohérence du code

## Statistiques

- **19 fichiers modifiés**
- **+610 lignes ajoutées**
- **-166 lignes supprimées**
- **1 migration ajoutée**

## Tests recommandés

1. Vérifier le chargement rapide du dashboard enseignant
2. Vérifier que le profil enseignant s'affiche correctement
3. Tester l'ajout d'un enseignant avec les nouveaux champs (NISS, adresse, compte bancaire)
4. Vérifier que les notifications ne provoquent plus d'erreur 500
5. Vérifier l'affichage correct des icônes et du footer

