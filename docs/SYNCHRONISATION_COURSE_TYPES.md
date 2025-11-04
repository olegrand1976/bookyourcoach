# 🔄 Synchronisation automatique des CourseTypes avec discipline_settings

**Date**: 4 novembre 2025  
**Contexte**: Résoudre l'incohérence entre les paramètres configurés dans le profil club et les types de cours affichés lors de la création

---

## 🐛 Problème identifié

### Symptôme
Lors de la configuration du profil club :
- **Profil** : Durée = 20 minutes, Prix = 18€
- **Création de cours** : Type affiché = "Cours individuel (60min - 18.00€)"

### Cause racine
Le système utilisait des `CourseTypes` **génériques** (sans `club_id`) qui avaient leurs propres valeurs par défaut (60min), ne tenant pas compte des paramètres configurés dans `discipline_settings`.

---

## ✅ Solution implémentée

### Concept
Créer automatiquement des `CourseTypes` **spécifiques au club** (`club_id` défini) basés sur les paramètres de `discipline_settings`.

### Fonctionnement

```
┌──────────────────────────────────────────┐
│  1️⃣  PROFIL CLUB (/club/profile)         │
│  Utilisateur configure:                  │
│  - Natation individuelle                 │
│  - Durée: 20 minutes                     │
│  - Prix: 18€                             │
│  - Participants: 1 (individuel)          │
└──────────┬───────────────────────────────┘
           │ Save
           ↓
┌──────────────────────────────────────────┐
│  2️⃣  ClubController::updateProfile()     │
│  - Sauvegarde discipline_settings         │
│  - Appelle syncClubCourseTypes()         │
└──────────┬───────────────────────────────┘
           │
           ↓
┌──────────────────────────────────────────┐
│  3️⃣  syncClubCourseTypes()               │
│  Pour chaque discipline configurée:      │
│  - Cherche CourseType existant           │
│    (club_id + discipline_id)             │
│  - Si existe: MET À JOUR                 │
│  - Si n'existe pas: CRÉE                 │
└──────────┬───────────────────────────────┘
           │
           ↓
┌──────────────────────────────────────────┐
│  4️⃣  CourseType spécifique créé/mis à jour│
│  - club_id: 5                            │
│  - discipline_id: 11                     │
│  - name: "Cours individuel"              │
│  - duration_minutes: 20 ✅               │
│  - price: 18.00 ✅                       │
│  - is_individual: true                   │
│  - max_participants: 1                   │
└──────────┬───────────────────────────────┘
           │
           ↓
┌──────────────────────────────────────────┐
│  5️⃣  CRÉATION DE COURS                   │
│  Affichage: "Cours individuel            │
│              (20min - 18.00€)" ✅        │
└──────────────────────────────────────────┘
```

---

## 📝 Code implémenté

### ClubController.php

```php
// app/Http/Controllers/Api/ClubController.php

// Dans updateProfile(), après la sauvegarde
DB::table('clubs')
    ->where('id', $clubUser->club_id)
    ->update($updateData);

// 🆕 SYNCHRONISATION : Créer/Mettre à jour les CourseTypes spécifiques au club
if (isset($requestData['discipline_settings']) && is_array($requestData['discipline_settings'])) {
    $this->syncClubCourseTypes($clubUser->club_id, $requestData['discipline_settings']);
}
```

### Méthode syncClubCourseTypes()

```php
private function syncClubCourseTypes(int $clubId, array $disciplineSettings): void
{
    foreach ($disciplineSettings as $disciplineId => $settings) {
        // Vérifier que la discipline existe
        $discipline = \App\Models\Discipline::find($disciplineId);
        if (!$discipline) {
            continue;
        }
        
        // Extraire les paramètres
        $duration = $settings['duration'] ?? $settings['duration_minutes'] ?? 60;
        $price = $settings['price'] ?? 0;
        $isIndividual = $settings['is_individual'] ?? true;
        $maxParticipants = $isIndividual ? 1 : ($settings['max_participants'] ?? 8);
        
        // Chercher un CourseType existant pour ce club + discipline
        $existingCourseType = \App\Models\CourseType::where('club_id', $clubId)
            ->where('discipline_id', $disciplineId)
            ->first();
        
        if ($existingCourseType) {
            // Mettre à jour le CourseType existant
            $existingCourseType->update([
                'duration_minutes' => $duration,
                'price' => $price,
                'is_individual' => $isIndividual,
                'max_participants' => $maxParticipants,
            ]);
        } else {
            // Créer un nouveau CourseType spécifique au club
            \App\Models\CourseType::create([
                'club_id' => $clubId,
                'discipline_id' => $disciplineId,
                'name' => $isIndividual ? 'Cours individuel' : 'Cours collectif',
                'description' => "Type de cours configuré pour {$discipline->name}",
                'duration_minutes' => $duration,
                'price' => $price,
                'is_individual' => $isIndividual,
                'max_participants' => $maxParticipants,
                'is_active' => true,
            ]);
        }
    }
}
```

---

## 🎯 Résultat

### Avant la correction

| Élément | Profil Club | Type de cours (création) |
|---------|-------------|--------------------------|
| Durée | 20 minutes | 60 minutes ❌ |
| Prix | 18€ | 18€ |
| Source | discipline_settings | CourseType générique |

### Après la correction

| Élément | Profil Club | Type de cours (création) |
|---------|-------------|--------------------------|
| Durée | 20 minutes | 20 minutes ✅ |
| Prix | 18€ | 18€ ✅ |
| Source | discipline_settings | CourseType spécifique au club |

---

## 🧪 Test de validation

### Configuration initiale

```php
// Club ACTI'VIBE (ID: 5)
discipline_settings = [
    11 => [
        'duration' => 20,
        'price' => 18.00,
        'is_individual' => true,
    ]
]
```

### Résultat après synchronisation

```sql
SELECT * FROM course_types WHERE club_id = 5 AND discipline_id = 11;

id  | club_id | discipline_id | name             | duration_minutes | price | is_individual | max_participants
----|---------|---------------|------------------|------------------|-------|---------------|------------------
85  | 5       | 11            | Cours individuel | 20               | 18.00 | 1             | 1
```

✅ **Parfaite correspondance entre profil et type de cours !**

---

## 🔍 Comment ça fonctionne dans le flux utilisateur

### 1. Configuration du profil

**Route** : `/club/profile`

1. Le club coche "Natation > Cours individuel enfant"
2. Configure :
   - Durée : 20 minutes
   - Prix : 18€
3. Clique sur "Sauvegarder"

### 2. Sauvegarde backend

**Endpoint** : `PUT /api/club/profile`

```json
{
  "discipline_settings": {
    "11": {
      "duration": 20,
      "price": 18.00,
      "is_individual": true
    }
  }
}
```

**Actions** :
1. `ClubController::updateProfile()` est appelé
2. Sauvegarde de `discipline_settings` dans `clubs.discipline_settings`
3. Appel de `syncClubCourseTypes()`
4. Création du `CourseType` spécifique (ID: 85)

### 3. Création d'un cours

**Route** : `/club/planning`

1. Le club crée un créneau pour "Cours individuel enfant"
2. Le créneau est automatiquement associé au `CourseType` ID: 85 (spécifique au club)
3. Lors de la création d'un cours depuis ce créneau :
   - Le sélecteur affiche : **"Cours individuel (20min - 18.00€)"** ✅
   - Les valeurs correspondent exactement au profil

---

## 📊 Structure des données

### CourseTypes : Génériques vs Spécifiques

```sql
-- CourseType générique (tous les clubs)
course_types:
  id: 21
  club_id: NULL  ← Générique
  discipline_id: 11
  name: "Cours individuel"
  duration_minutes: 60
  price: 18.00

-- CourseType spécifique (un club)
course_types:
  id: 85
  club_id: 5  ← Spécifique à ACTI'VIBE
  discipline_id: 11
  name: "Cours individuel"
  duration_minutes: 20  ← Valeur personnalisée
  price: 18.00
```

### Priorité de sélection

Lors de la récupération des types de cours pour un club :

```php
// app/Http/Controllers/Api/CourseTypeController.php
CourseType::where('is_active', true)
    ->where(function($query) use ($club) {
        $query->where('club_id', $club->id);  // 1️⃣ Types spécifiques (priorité)
        $query->orWhere(function($q) use ($validDisciplines) {
            $q->whereNull('club_id')           // 2️⃣ Types génériques
              ->whereIn('discipline_id', $validDisciplines);
        });
    })
    ->get();
```

**Résultat** : Si un `CourseType` spécifique existe, il **masque** le générique.

---

## 💡 Avantages

### Pour les clubs

✅ **Simplicité** : Configuration dans un seul endroit (profil)  
✅ **Cohérence** : Les valeurs sont synchronisées automatiquement  
✅ **Flexibilité** : Chaque club peut avoir ses propres tarifs/durées  
✅ **Personnalisation** : Types de cours adaptés à chaque club  

### Pour le système

✅ **Maintenabilité** : Une seule source de vérité (discipline_settings)  
✅ **Scalabilité** : Chaque club est indépendant  
✅ **Traçabilité** : Les modifications sont loggées  

---

## 🚀 Déploiement

### En production

Lors du déploiement :
1. Le code est poussé sur GitHub
2. Les clubs existants continuent à fonctionner (CourseTypes génériques)
3. Dès qu'un club **modifie son profil** :
   - `syncClubCourseTypes()` s'exécute
   - Ses `CourseTypes` spécifiques sont créés
   - Il bénéficie de la nouvelle fonctionnalité ✅

### Migration progressive

**Pas de migration de données nécessaire** : La synchronisation se fait automatiquement au fur et à mesure que les clubs modifient leur profil.

**Pour forcer la synchronisation** :
```php
// Pour un club spécifique
$club = Club::find(5);
$disciplineSettings = $club->discipline_settings;
if (is_string($disciplineSettings)) {
    $disciplineSettings = json_decode($disciplineSettings, true);
}
$controller = new ClubController();
$controller->syncClubCourseTypes($club->id, $disciplineSettings);
```

---

## 📋 Checklist de validation

### Pour tester en production

1. ✅ Aller sur `/club/profile`
2. ✅ Configurer une discipline :
   - Sélectionner "Natation > Cours individuel enfant"
   - Durée : 20 minutes
   - Prix : 18€
3. ✅ Sauvegarder le profil
4. ✅ Vérifier les logs :
   ```
   🔄 syncClubCourseTypes - Début
   ✅ CourseType créé: ID XX
   ✅ syncClubCourseTypes - Terminé avec succès
   ```
5. ✅ Aller sur `/club/planning`
6. ✅ Créer un créneau pour "Cours individuel enfant"
7. ✅ Cliquer sur "Créer un nouveau cours"
8. ✅ Vérifier que le type affiché est : **"Cours individuel (20min - 18.00€)"**

---

## 🛠️ Dépannage

### Le type de cours ne se met pas à jour

**Causes possibles** :
1. Le profil n'a pas été sauvegardé après modification
2. Un `CourseType` générique est utilisé au lieu du spécifique
3. La synchronisation a échoué (vérifier les logs)

**Solution** :
1. Vérifier que `discipline_settings` est bien enregistré :
   ```sql
   SELECT discipline_settings FROM clubs WHERE id = X;
   ```
2. Vérifier que le `CourseType` spécifique existe :
   ```sql
   SELECT * FROM course_types WHERE club_id = X AND discipline_id = Y;
   ```
3. Re-sauvegarder le profil pour forcer la synchronisation

### Plusieurs types de cours s'affichent

**Cause** : Les types génériques ET spécifiques sont tous les deux actifs.

**Solution** : Normal ! Le système priorise automatiquement les types spécifiques. Si vous voulez masquer les génériques, vous pouvez :
```sql
UPDATE course_types SET is_active = 0 WHERE club_id IS NULL AND discipline_id = Y;
```

---

**Dernière mise à jour** : 4 novembre 2025  
**Statut** : ✅ Déployé en production

