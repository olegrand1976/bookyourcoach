# Analyse détaillée : Liaison de comptes étudiants (Famille)

## 📋 Vue d'ensemble

Cette fonctionnalité permet de lier plusieurs comptes étudiants ensemble, créant ainsi un groupe familial. Un compte avec une adresse email peut être lié à d'autres comptes étudiants, permettant à un parent ou tuteur de gérer plusieurs comptes enfants depuis un seul compte de connexion.

## 🎯 Objectifs

1. **Liaison de comptes** : Un administrateur peut lier plusieurs comptes étudiants ensemble
2. **Gestion centralisée** : Un compte parent peut accéder aux comptes enfants liés
3. **Sécurité** : Seul l'administrateur peut créer/modifier les liens
4. **Expérience utilisateur** : L'étudiant peut basculer entre les comptes liés via un select dans son dashboard

## 🏗️ Architecture technique

### 1. Modèle de données

#### Table `student_family_links`
Table pivot pour gérer les relations entre étudiants.

**Structure proposée :**
```sql
CREATE TABLE student_family_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    primary_student_id BIGINT UNSIGNED NOT NULL,  -- Compte principal (celui avec email)
    linked_student_id BIGINT UNSIGNED NOT NULL,   -- Compte lié
    relationship_type VARCHAR(50) NULL,           -- Optionnel: 'parent', 'guardian', 'sibling', etc.
    created_by BIGINT UNSIGNED NULL,              -- ID de l'admin qui a créé le lien
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (primary_student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (linked_student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    UNIQUE KEY unique_link (primary_student_id, linked_student_id),
    INDEX idx_primary (primary_student_id),
    INDEX idx_linked (linked_student_id)
);
```

**Règles métier :**
- Un étudiant peut être lié à plusieurs autres étudiants (relation symétrique)
- Un étudiant ne peut être lié qu'à des comptes ayant un `user_id` (compte avec email)
- Les liens sont bidirectionnels : si A est lié à B, alors B est lié à A
- Un étudiant ne peut pas être lié à lui-même

#### Modifications des modèles existants

**Student.php :**
```php
// Relation pour récupérer les étudiants liés
public function linkedStudents(): BelongsToMany
{
    return $this->belongsToMany(
        Student::class,
        'student_family_links',
        'primary_student_id',
        'linked_student_id'
    )->withPivot(['relationship_type', 'created_by', 'created_at'])
     ->withTimestamps();
}

// Relation inverse (pour récupérer les étudiants qui ont ce compte comme lié)
public function linkedFromStudents(): BelongsToMany
{
    return $this->belongsToMany(
        Student::class,
        'student_family_links',
        'linked_student_id',
        'primary_student_id'
    )->withPivot(['relationship_type', 'created_by', 'created_at'])
     ->withTimestamps();
}

// Méthode helper pour récupérer tous les étudiants liés (bidirectionnel)
public function getAllLinkedStudents(): Collection
{
    $linked = $this->linkedStudents;
    $linkedFrom = $this->linkedFromStudents;
    return $linked->merge($linkedFrom)->unique('id');
}
```

**User.php :**
```php
// Méthode pour récupérer les étudiants liés via le compte student
public function getLinkedStudents(): Collection
{
    if (!$this->student) {
        return collect();
    }
    return $this->student->getAllLinkedStudents();
}
```

### 2. Backend - API Routes

#### Routes Admin (middleware: `auth:sanctum`, `admin`)

```php
// Dans routes/api.php, groupe admin
Route::prefix('admin/students')->group(function () {
    // Récupérer les étudiants liés à un étudiant
    Route::get('/{studentId}/linked', [AdminController::class, 'getLinkedStudents']);
    
    // Lier un étudiant à un autre
    Route::post('/{studentId}/link', [AdminController::class, 'linkStudent']);
    
    // Délier un étudiant d'un autre
    Route::delete('/{studentId}/unlink/{linkedStudentId}', [AdminController::class, 'unlinkStudent']);
    
    // Récupérer tous les étudiants disponibles pour liaison (avec email)
    Route::get('/available-for-linking', [AdminController::class, 'getStudentsAvailableForLinking']);
});
```

#### Routes Student (middleware: `auth:sanctum`, `student`)

```php
// Dans routes/api.php, groupe student
Route::prefix('student')->group(function () {
    // Récupérer les comptes étudiants liés au compte actuel
    Route::get('/linked-accounts', [StudentController::class, 'getLinkedAccounts']);
    
    // Changer de compte étudiant actif (contexte de session)
    Route::post('/switch-account/{studentId}', [StudentController::class, 'switchAccount']);
    
    // Récupérer le compte étudiant actuellement actif
    Route::get('/active-account', [StudentController::class, 'getActiveAccount']);
});
```

### 3. Backend - Contrôleurs

#### AdminController - Nouvelles méthodes

**`getLinkedStudents($studentId)`**
- Récupère tous les étudiants liés à un étudiant donné
- Retourne la liste avec les informations de base (nom, email, etc.)

**`linkStudent($studentId, Request $request)`**
- Valide que les deux étudiants existent
- Vérifie qu'ils ont un `user_id` (compte avec email)
- Vérifie qu'ils ne sont pas déjà liés
- Vérifie qu'on ne lie pas un étudiant à lui-même
- Crée le lien bidirectionnel dans la table pivot
- Log l'action dans AuditLog

**`unlinkStudent($studentId, $linkedStudentId)`**
- Supprime le lien entre les deux étudiants
- Log l'action dans AuditLog

**`getStudentsAvailableForLinking()`**
- Retourne la liste des étudiants ayant un `user_id` (compte avec email)
- Exclut les étudiants déjà liés au compte en cours de modification
- Format: `{id, name, email, user_id}`

#### StudentController - Nouvelles méthodes

**`getLinkedAccounts()`**
- Récupère tous les comptes étudiants liés au compte actuel de l'utilisateur
- Inclut le compte actuel dans la liste
- Retourne: `{id, name, email, is_active: true/false}`

**`switchAccount($studentId)`**
- Vérifie que l'étudiant demandé est bien lié au compte actuel
- Stocke l'ID de l'étudiant actif dans la session ou dans un token personnalisé
- Retourne les informations du compte activé

**`getActiveAccount()`**
- Retourne le compte étudiant actuellement actif
- Par défaut, retourne le compte principal de l'utilisateur

### 4. Gestion de session/contexte

#### Option 1 : Session Laravel (recommandé pour début)
- Stocker `active_student_id` dans la session
- Middleware pour injecter le contexte dans les requêtes

#### Option 2 : Token Sanctum personnalisé
- Ajouter un champ `active_student_id` dans la table `personal_access_tokens`
- Modifier le token lors du switch

#### Option 3 : Header personnalisé
- Utiliser un header `X-Active-Student-Id` dans les requêtes
- Middleware pour parser et valider

**Recommandation : Option 1 (Session)** pour la simplicité initiale.

### 5. Middleware personnalisé

Créer `app/Http/Middleware/SetActiveStudentContext.php` :

```php
public function handle(Request $request, Closure $next)
{
    $user = $request->user();
    
    if ($user && $user->role === 'student' && $user->student) {
        // Récupérer l'ID de l'étudiant actif depuis la session
        $activeStudentId = session('active_student_id', $user->student->id);
        
        // Vérifier que l'étudiant est bien lié au compte
        $linkedStudents = $user->getLinkedStudents();
        $isLinked = $linkedStudents->contains('id', $activeStudentId) 
                 || $user->student->id === $activeStudentId;
        
        if ($isLinked) {
            // Injecter dans la requête pour utilisation dans les contrôleurs
            $request->merge(['active_student_id' => $activeStudentId]);
        } else {
            // Réinitialiser au compte principal
            session(['active_student_id' => $user->student->id]);
            $request->merge(['active_student_id' => $user->student->id]);
        }
    }
    
    return $next($request);
}
```

Enregistrer dans `bootstrap/app.php` ou `app/Http/Kernel.php`.

### 6. Frontend - Interface Admin

#### Page/Modal de gestion des liens

**Localisation :** `frontend/pages/admin/users/[id].vue` ou modal dans la liste des utilisateurs

**Fonctionnalités :**
1. **Section "Comptes liés"** dans le formulaire de création/modification d'un compte étudiant
2. **Liste des comptes actuellement liés** avec possibilité de supprimer
3. **Recherche/Select pour ajouter de nouveaux liens** :
   - Champ de recherche pour trouver des étudiants par nom/email
   - Liste déroulante avec les étudiants disponibles (ayant un email)
   - Bouton "Lier" pour créer le lien
4. **Indicateur visuel** : Badge "Compte principal" vs "Compte lié"

**Composant proposé :** `frontend/components/admin/LinkedStudentsManager.vue`

**Props :**
- `studentId` : ID de l'étudiant en cours de modification
- `linkedStudents` : Liste des étudiants déjà liés

**Événements :**
- `@link-created` : Quand un nouveau lien est créé
- `@link-removed` : Quand un lien est supprimé

### 7. Frontend - Dashboard Étudiant

#### Select de changement de compte

**Localisation :** `frontend/pages/student/dashboard.vue`

**Position :** En haut du dashboard, à côté du nom de l'utilisateur

**Fonctionnalités :**
1. **Select dropdown** avec les comptes liés
2. **Affichage du compte actif** : Nom + email (ou prénom si disponible)
3. **Changement de contexte** : Au changement, appel API + rechargement des données
4. **Indicateur visuel** : Badge ou icône pour montrer qu'on est sur un compte lié

**Composant proposé :** `frontend/components/student/AccountSwitcher.vue`

**Logique :**
```javascript
const switchAccount = async (studentId) => {
  try {
    await $api.post(`/student/switch-account/${studentId}`)
    // Recharger toutes les données du dashboard
    await Promise.all([
      loadUpcomingLessons(),
      loadActiveSubscriptions(),
      loadStudentProfile()
    ])
    // Mettre à jour le store auth si nécessaire
    await authStore.fetchUser()
  } catch (error) {
    toast.error('Erreur lors du changement de compte')
  }
}
```

### 8. Sécurité et validations

#### Validations backend

1. **Liaison de comptes (Admin) :**
   - Les deux étudiants doivent exister
   - Les deux étudiants doivent avoir un `user_id` (compte avec email)
   - Pas de lien dupliqué
   - Pas de lien vers soi-même
   - Seul un admin peut créer/modifier les liens

2. **Changement de compte (Student) :**
   - L'étudiant demandé doit être lié au compte actuel
   - L'étudiant demandé doit exister et être actif
   - L'utilisateur doit être authentifié avec le rôle 'student'

3. **Accès aux données :**
   - Toutes les requêtes doivent vérifier que l'étudiant actif est bien lié au compte
   - Les données retournées doivent être filtrées selon le contexte actif

#### Permissions

- **Admin** : Peut créer, modifier, supprimer tous les liens
- **Student** : Peut uniquement voir et basculer entre ses comptes liés
- **Club/Teacher** : Pas d'accès à cette fonctionnalité

### 9. Impact sur les fonctionnalités existantes

#### Endpoints à adapter

Tous les endpoints qui retournent des données d'étudiant doivent utiliser le contexte actif :

1. **`/student/lessons`** : Retourner les cours de l'étudiant actif
2. **`/student/subscriptions`** : Retourner les abonnements de l'étudiant actif
3. **`/student/profile`** : Retourner le profil de l'étudiant actif
4. **`/student/bookings`** : Retourner les réservations de l'étudiant actif

**Solution :** Utiliser le middleware `SetActiveStudentContext` pour injecter `active_student_id` dans toutes les requêtes.

#### Modifications des contrôleurs existants

Dans `StudentController`, remplacer :
```php
$student = $user->student;
```

Par :
```php
$activeStudentId = $request->input('active_student_id', $user->student->id);
$student = Student::findOrFail($activeStudentId);

// Vérifier que l'étudiant est bien lié
$linkedStudents = $user->getLinkedStudents();
if (!$linkedStudents->contains('id', $activeStudentId) && $user->student->id !== $activeStudentId) {
    return response()->json(['error' => 'Unauthorized'], 403);
}
```

### 10. Tests

#### Tests unitaires

1. **Modèle Student :**
   - Test de la relation `linkedStudents()`
   - Test de la méthode `getAllLinkedStudents()`
   - Test de la création/suppression de liens

2. **AdminController :**
   - Test de `linkStudent()` : succès, échecs (déjà lié, pas d'email, etc.)
   - Test de `unlinkStudent()` : succès, échecs
   - Test de `getLinkedStudents()` : retour correct

3. **StudentController :**
   - Test de `getLinkedAccounts()` : retourne les comptes liés
   - Test de `switchAccount()` : changement de contexte
   - Test de validation : ne peut pas switcher vers un compte non lié

4. **Middleware :**
   - Test que le contexte est correctement injecté
   - Test que le contexte par défaut est le compte principal

#### Tests d'intégration

1. **Scénario complet :**
   - Admin crée un lien entre deux comptes
   - Étudiant se connecte et voit les comptes liés
   - Étudiant bascule vers un compte lié
   - Les données affichées correspondent au compte actif

## 📝 Plan d'implémentation détaillé

### Phase 1 : Base de données et modèles (Backend)
1. Créer la migration `create_student_family_links_table`
2. Ajouter les relations dans `Student.php`
3. Ajouter les méthodes helper dans `User.php`
4. Créer les tests unitaires pour les modèles

### Phase 2 : API Admin (Backend)
1. Créer les routes admin pour la gestion des liens
2. Implémenter `AdminController::getLinkedStudents()`
3. Implémenter `AdminController::linkStudent()`
4. Implémenter `AdminController::unlinkStudent()`
5. Implémenter `AdminController::getStudentsAvailableForLinking()`
6. Créer les tests unitaires pour les endpoints admin

### Phase 3 : API Student et contexte (Backend)
1. Créer le middleware `SetActiveStudentContext`
2. Enregistrer le middleware dans les routes student
3. Implémenter `StudentController::getLinkedAccounts()`
4. Implémenter `StudentController::switchAccount()`
5. Implémenter `StudentController::getActiveAccount()`
6. Adapter les endpoints existants pour utiliser le contexte actif
7. Créer les tests unitaires pour les endpoints student

### Phase 4 : Interface Admin (Frontend)
1. Créer le composant `LinkedStudentsManager.vue`
2. Intégrer le composant dans la page de création/modification d'utilisateur
3. Ajouter la recherche d'étudiants disponibles
4. Ajouter la gestion des liens (ajout/suppression)
5. Tester l'interface admin

### Phase 5 : Dashboard Étudiant (Frontend)
1. Créer le composant `AccountSwitcher.vue`
2. Intégrer le composant dans `student/dashboard.vue`
3. Implémenter la logique de changement de compte
4. Adapter le rechargement des données après changement
5. Tester le changement de contexte

### Phase 6 : Tests et validation
1. Tests d'intégration complets
2. Tests de sécurité (tentatives d'accès non autorisés)
3. Validation UX avec utilisateurs réels
4. Documentation utilisateur

## ⚠️ Points d'attention

1. **Performance** : Les requêtes avec plusieurs liens peuvent être lentes. Utiliser `with()` pour eager loading.

2. **Cohérence des données** : S'assurer que les liens bidirectionnels sont toujours cohérents.

3. **Suppression en cascade** : Définir le comportement si un compte est supprimé (supprimer les liens ?).

4. **Limite de liens** : Définir une limite maximale de comptes liés par compte principal.

5. **Notifications** : Considérer l'envoi de notifications quand un compte est lié/délié.

6. **Historique** : Logger toutes les actions de liaison/déliaison dans `audit_logs`.

## 🔄 Évolutions futures possibles

1. **Types de relations** : Ajouter des types (parent, tuteur, frère/sœur)
2. **Permissions par relation** : Donner des permissions différentes selon le type de relation
3. **Gestion par le parent** : Permettre au parent de gérer les comptes enfants directement
4. **Notifications partagées** : Notifier le parent des activités des enfants
5. **Facturation groupée** : Grouper les factures des comptes liés
