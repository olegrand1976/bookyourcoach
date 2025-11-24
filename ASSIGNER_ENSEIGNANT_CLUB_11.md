# Assigner un enseignant au club 11

## 📋 Méthodes disponibles

### Méthode 1: Commande Artisan (Recommandée)

```bash
php artisan teacher:assign-to-club {teacher_id} {club_id} [--hourly-rate=VALUE]
```

**Exemples:**

```bash
# Assigner Sophie Martin (ID 1) au club 11
php artisan teacher:assign-to-club 1 11

# Assigner avec un tarif horaire spécifique
php artisan teacher:assign-to-club 1 11 --hourly-rate=65.00

# Assigner Jean Dubois (ID 2) au club 11
php artisan teacher:assign-to-club 2 11
```

### Méthode 2: Script SQL direct

Exécutez le fichier `assign_teacher_to_club_11.sql` dans votre base de données :

```bash
mysql -u votre_user -p votre_database < assign_teacher_to_club_11.sql
```

Ou via phpMyAdmin / Adminer, copiez-collez le contenu du fichier SQL.

### Méthode 3: Via Tinker (Laravel)

```php
php artisan tinker

// Dans Tinker:
$teacher = App\Models\Teacher::whereHas('user', function($q) {
    $q->where('email', 'sophie.martin@activibe.com');
})->first();

$club = App\Models\Club::find(11);

$teacher->clubs()->attach($club->id, [
    'is_active' => true,
    'joined_at' => now(),
    'hourly_rate' => $teacher->hourly_rate
]);
```

## 🔍 Vérifier l'assignation

### Via SQL
```sql
SELECT 
    ct.id,
    u.name as teacher_name,
    u.email as teacher_email,
    c.name as club_name,
    ct.is_active,
    ct.joined_at
FROM club_teachers ct
INNER JOIN teachers t ON ct.teacher_id = t.id
INNER JOIN users u ON t.user_id = u.id
INNER JOIN clubs c ON ct.club_id = c.id
WHERE ct.club_id = 11;
```

### Via Tinker
```php
$club = App\Models\Club::find(11);
$club->teachers()->with('user')->get();
```

## 📝 Comptes enseignants disponibles

| ID | Nom | Email | Mot de passe |
|----|-----|-------|--------------|
| 1 | Sophie Martin | sophie.martin@activibe.com | password123 |
| 2 | Jean Dubois | jean.dubois@activibe.com | password123 |
| 3 | Marie Leroy | marie.leroy@activibe.com | password123 |
| 4 | Pierre Bernard | pierre.bernard@activibe.com | password123 |

## ⚠️ Notes importantes

1. **Table pivot**: `club_teachers` avec les colonnes:
   - `club_id` - ID du club
   - `teacher_id` - ID de l'enseignant
   - `is_active` - Statut actif (par défaut: true)
   - `joined_at` - Date d'assignation
   - `hourly_rate` - Tarif horaire spécifique au club (optionnel)
   - `allowed_disciplines` - Disciplines autorisées (JSON, optionnel)
   - `restricted_disciplines` - Disciplines restreintes (JSON, optionnel)

2. **Contrainte unique**: Un enseignant ne peut être assigné qu'une seule fois au même club (contrainte unique sur `club_id` + `teacher_id`)

3. **Si déjà assigné**: La commande artisan détectera si l'enseignant est déjà assigné et proposera de mettre à jour l'assignation

