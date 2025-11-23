# Impacts de la migration `modify_users_email_unique_constraint`

## 📋 Résumé de la migration

**Avant** : Contrainte unique sur `email` seule
- Un email ne peut exister qu'une seule fois dans la table `users`

**Après** : Contrainte unique composite sur `(email, role)`
- Un email peut exister plusieurs fois avec des rôles différents
- Un email ne peut exister qu'une seule fois avec le même rôle

## ⚠️ Impacts identifiés

### 1. **Authentification (Login) - CRITIQUE** 🔴

**Fichier** : `app/Http/Controllers/Api/AuthController.php:124`

**Problème** :
```php
$user = User::where('email', $request['email'])->firstOrFail();
```

Si plusieurs utilisateurs ont le même email avec des rôles différents, cette requête retournera seulement le premier trouvé, ce qui peut causer des problèmes d'authentification.

**Solution recommandée** :
- Option 1 : Demander le rôle lors du login
- Option 2 : Retourner tous les utilisateurs avec cet email et laisser le frontend choisir
- Option 3 : Utiliser le premier utilisateur trouvé mais ajouter un warning dans les logs

### 2. **Inscription (Register) - CRITIQUE** 🔴

**Fichier** : `app/Http/Controllers/Api/AuthController.php:25`

**Problème** :
```php
'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
```

Cette validation vérifie l'unicité sur toute la table, pas par rôle. Elle bloquera la création même si l'email existe avec un autre rôle.

**Solution** : Modifier la validation pour vérifier uniquement l'unicité pour le rôle spécifié :
```php
'email' => [
    'required',
    'string',
    'lowercase',
    'email',
    'max:255',
    Rule::unique('users')->where(function ($query) use ($request) {
        return $query->where('role', $request->role);
    }),
],
```

### 3. **Création d'utilisateur par Admin - CRITIQUE** 🔴

**Fichier** : `app/Http/Controllers/AdminController.php:222`

**Problème** :
```php
'email' => 'required|string|email|max:255|unique:users',
```

Même problème que l'inscription.

**Solution** : Même correction que pour l'inscription.

### 4. **Mise à jour d'utilisateur par Admin - CRITIQUE** 🔴

**Fichier** : `app/Http/Controllers/AdminController.php:296`

**Problème** :
```php
'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
```

Cette validation ne prend pas en compte le rôle lors de la vérification d'unicité.

**Solution** : Modifier pour vérifier l'unicité par rôle :
```php
'email' => [
    'sometimes',
    'required',
    'string',
    'email',
    'max:255',
    Rule::unique('users')->where(function ($query) use ($request) {
        return $query->where('role', $request->role);
    })->ignore($id),
],
```

### 5. **Création d'élève par Club - CRITIQUE** 🔴

**Fichier** : `app/Http/Controllers/Api/StudentController.php:184`

**Problème** :
```php
'email' => 'required|email|unique:users,email',
```

Même problème.

**Solution** : Vérifier uniquement l'unicité pour le rôle `student` :
```php
'email' => [
    'required',
    'email',
    Rule::unique('users')->where(function ($query) {
        return $query->where('role', 'student');
    }),
],
```

### 6. **Mise à jour d'élève par Club - CRITIQUE** 🔴

**Fichier** : `app/Http/Controllers/Api/StudentController.php:368, 370`

**Problème** :
```php
$validationRules['email'] = 'nullable|email|unique:users,email';
// ou
$validationRules['email'] = 'sometimes|email|unique:users,email,' . $student->user_id;
```

Même problème.

**Solution** : Même correction que pour la création.

### 7. **Mise à jour d'enseignant par Club - CRITIQUE** 🔴

**Fichier** : `app/Http/Controllers/Api/ClubController.php:1332`

**Problème** :
```php
'email' => 'sometimes|email|unique:users,email,' . $teacher->user_id,
```

Même problème.

**Solution** : Vérifier uniquement l'unicité pour le rôle `teacher` :
```php
'email' => [
    'sometimes',
    'email',
    Rule::unique('users')->where(function ($query) {
        return $query->where('role', 'teacher');
    })->ignore($teacher->user_id),
],
```

## ✅ Fichiers déjà corrigés

- `app/Http/Controllers/Api/ClubController.php::createTeacher()` - ✅ Corrigé

## 📝 Actions à effectuer

1. **Corriger AuthController::login()** - Gérer l'ambiguïté si plusieurs utilisateurs ont le même email
2. **Corriger AuthController::register()** - Vérifier l'unicité par rôle
3. **Corriger AdminController::createUser()** - Vérifier l'unicité par rôle
4. **Corriger AdminController::updateUser()** - Vérifier l'unicité par rôle
5. **Corriger StudentController::store()** - Vérifier l'unicité pour le rôle student
6. **Corriger StudentController::update()** - Vérifier l'unicité pour le rôle student
7. **Corriger ClubController::updateTeacher()** - Vérifier l'unicité pour le rôle teacher

## 🧪 Tests à effectuer

1. Créer un utilisateur avec un email existant (autre rôle) → devrait fonctionner
2. Créer un utilisateur avec un email existant (même rôle) → devrait échouer
3. Se connecter avec un email qui existe avec plusieurs rôles → vérifier le comportement
4. Mettre à jour un utilisateur avec un email existant (autre rôle) → devrait fonctionner
5. Mettre à jour un utilisateur avec un email existant (même rôle) → devrait échouer

## 🔍 Points d'attention

- **Authentification** : Le système d'authentification doit être adapté pour gérer les cas où plusieurs utilisateurs partagent le même email
- **Notifications** : Vérifier que les notifications sont envoyées au bon utilisateur
- **Relations** : Vérifier que les relations Eloquent fonctionnent correctement avec cette nouvelle contrainte
- **Seeders** : Vérifier que les seeders ne créent pas de conflits

