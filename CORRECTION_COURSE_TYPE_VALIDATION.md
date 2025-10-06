# Correction du problème de validation course_type_id

**Date:** 6 octobre 2025  
**Problème:** Erreur "The selected course type id is invalid" lors de la création d'un cours

## 🔍 Diagnostic du problème

### Symptômes
- Lors de la création d'un cours sur `/club/planning`, erreur de validation : `The selected course type id is invalid`
- Le frontend envoyait `course_type_id: 11` (qui était en réalité un `discipline_id`)
- Les types de cours chargés par l'API étaient vides : `[]`

### Cause racine
**Confusion entre 3 concepts différents :**

1. **`activity_type`** : Type d'activité sportive (ex: Équitation = 2, Natation = 3)
2. **`discipline`** : Discipline spécifique (ex: Dressage = 11, CSO = 12, Endurance = 13)
3. **`course_type`** : **Type de cours** (ex: Cours individuel, Cours collectif 2-4 pers, etc.)

Le frontend envoyait un `discipline_id` (11 = Dressage) en tant que `course_type_id`, alors que l'API attendait un ID de la table `course_types`.

**La table `course_types` existait mais était complètement vide.**

---

## ✅ Solution mise en place

### 1. Backend : Création de la table et des données

#### a) Migration de la table `course_types`
**Fichier:** `database/migrations/2025_10_06_201808_create_course_types_table_if_not_exists.php`

```php
Schema::create('course_types', function (Blueprint $table) {
    $table->id();
    $table->foreignId('discipline_id')->nullable()->constrained()->onDelete('cascade');
    $table->string('name');
    $table->text('description')->nullable();
    $table->integer('duration_minutes')->default(60);
    $table->boolean('is_individual')->default(true);
    $table->integer('max_participants')->default(1);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    
    $table->index(['discipline_id', 'is_active']);
});
```

#### b) Seeder pour peupler les types de cours
**Fichier:** `database/seeders/CourseTypesSeeder.php`

Le seeder crée:
- **6 types génériques** (sans discipline spécifique):
  - Cours individuel
  - Cours collectif (2 élèves)
  - Cours collectif (3-4 élèves)
  - Cours collectif (5-8 élèves)
  - Stage découverte
  - Session intensive

- **Types par discipline** (pour chaque discipline existante):
  - Cours individuel de [Discipline]
  - Cours collectif de [Discipline]

**Commandes exécutées:**
```bash
php artisan migrate
php artisan db:seed --class=CourseTypesSeeder
```

**Résultat:**
```
✅ Types de cours créés avec succès!
   - 6 types génériques
   - 40 types par discipline
```

---

### 2. Frontend : Ajout du sélecteur de type de cours

#### a) Modification du formulaire "Nouveau cours"
**Fichier:** `frontend/pages/club/planning.vue`

**Avant:** Champ en lecture seule affichant seulement la discipline
```vue
<div class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 font-medium">
  {{ getSelectedSlotDisciplineName() || 'Défini par le créneau' }}
</div>
```

**Après:** Sélecteur interactif des types de cours
```vue
<select 
  v-model="lessonForm.courseTypeId"
  class="w-full border border-gray-300 rounded-lg px-3 py-2"
  required
>
  <option value="">Sélectionner un type</option>
  <option v-for="type in availableCourseTypesForLesson" :key="type.id" :value="type.id">
    {{ type.name }} 
    <template v-if="type.is_individual">(individuel)</template>
    <template v-else>({{ type.max_participants }} pers. max)</template>
    - {{ type.duration_minutes }}min
  </option>
</select>
```

#### b) Computed property pour filtrer les types disponibles
```typescript
const availableCourseTypesForLesson = computed(() => {
  // Récupérer la discipline du créneau sélectionné
  const disciplineId = selectedSlot.value?.slot?.discipline_id
  
  if (!disciplineId) {
    // Si pas de discipline spécifique, retourner les types génériques
    return availableCourseTypes.value.filter(type => !type.discipline_id)
  }
  
  // Filtrer les types de cours pour cette discipline + les types génériques
  return availableCourseTypes.value.filter(type => 
    !type.discipline_id || type.discipline_id === parseInt(disciplineId)
  )
})
```

#### c) Correction de l'envoi à l'API
**Avant:**
```typescript
const disciplineId = selectedSlot.value?.slot?.discipline_id || lessonForm.value.courseTypeId
course_type_id: parseInt(disciplineId), // ❌ Envoyait discipline_id
```

**Après:**
```typescript
if (!lessonForm.value.courseTypeId) {
  alert('Veuillez sélectionner un type de cours.')
  return
}
course_type_id: parseInt(lessonForm.value.courseTypeId), // ✅ Envoie course_type_id
```

#### d) Pré-sélection automatique du type de cours
Lors de l'ouverture de la modale depuis un créneau, le premier type de cours compatible est automatiquement pré-sélectionné:

```typescript
// Pré-sélectionner le premier type de cours disponible pour cette discipline
const availableTypes = availableCourseTypes.value.filter(type => 
  !type.discipline_id || type.discipline_id === parseInt(slot.discipline_id)
)
lessonForm.value.courseTypeId = availableTypes.length > 0 ? availableTypes[0].id.toString() : ''
```

---

## 📊 Structure des données

### Table `course_types`
| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint | Identifiant unique |
| `discipline_id` | bigint (nullable) | Référence à la discipline (null = générique) |
| `name` | string | Nom du type de cours |
| `description` | text | Description détaillée |
| `duration_minutes` | integer | Durée en minutes (défaut: 60) |
| `is_individual` | boolean | Cours individuel ou collectif |
| `max_participants` | integer | Nombre max de participants |
| `is_active` | boolean | Type actif ou non |

### Exemples de données
```json
[
  {
    "id": 1,
    "name": "Cours individuel",
    "discipline_id": null,
    "is_individual": true,
    "max_participants": 1,
    "duration_minutes": 60
  },
  {
    "id": 7,
    "name": "Cours individuel",
    "discipline_id": 11,  // Dressage
    "is_individual": true,
    "max_participants": 1,
    "duration_minutes": 60
  }
]
```

---

## 🧪 Tests à effectuer

### Test 1: Chargement des types de cours
1. Se connecter en tant que club
2. Aller sur `/club/planning`
3. Vérifier dans la console: `✅ Types de cours chargés: Proxy { <target>: (X) [...] }`
4. ✅ Le tableau ne doit pas être vide

### Test 2: Création d'un cours
1. Cliquer sur un créneau ouvert
2. Cliquer sur "Ajouter un cours"
3. Vérifier que le sélecteur "Type de cours" affiche des options
4. Sélectionner un type (ex: "Cours individuel")
5. Remplir les autres champs (enseignant, élève)
6. Valider
7. ✅ Le cours doit se créer sans erreur de validation

### Test 3: Types de cours filtrés par discipline
1. Créer un créneau pour "Dressage"
2. Essayer d'ajouter un cours depuis ce créneau
3. ✅ Le sélecteur doit afficher:
   - Les types génériques (Cours individuel, Cours collectif...)
   - Les types spécifiques au Dressage

---

## 📝 Fichiers modifiés

### Backend
- ✅ `database/migrations/2025_10_06_201808_create_course_types_table_if_not_exists.php` (créé)
- ✅ `database/seeders/CourseTypesSeeder.php` (créé)

### Frontend
- ✅ `frontend/pages/club/planning.vue` (modifié)
  - Ajout du sélecteur `<select>` pour les types de cours
  - Ajout de la computed `availableCourseTypesForLesson`
  - Correction de l'envoi du `course_type_id` à l'API
  - Pré-sélection automatique du premier type compatible

---

## 🚀 Commandes de déploiement

```bash
# Backend
php artisan migrate
php artisan db:seed --class=CourseTypesSeeder
php artisan route:clear
php artisan config:clear
php artisan cache:clear

# Frontend
cd frontend
# Pas de build nécessaire en dev, Nuxt recharge automatiquement
```

---

## 📖 Références

- **Modèle:** `app/Models/CourseType.php`
- **Controller:** `app/Http/Controllers/Api/CourseTypeController.php`
- **Route API:** `GET /api/course-types` (authentification requise)
- **Validation:** `app/Http/Controllers/Api/LessonController.php:181`
  ```php
  'course_type_id' => 'required|exists:course_types,id',
  ```

---

## ✨ Améliorations futures possibles

1. **Interface d'administration** pour gérer les types de cours:
   - Créer/éditer/supprimer des types
   - Définir les prix par défaut
   - Activer/désactiver des types

2. **Prix dynamiques** selon le type de cours:
   - Charger les prix depuis `course_types.price` (à ajouter)
   - Calculer automatiquement selon durée et type

3. **Règles métier** avancées:
   - Limiter les types selon l'heure (ex: pas de collectif après 18h)
   - Types réservés à certains niveaux d'élèves
   - Compatibilité type de cours ↔ enseignant

---

---

## 🐛 Erreur secondaire découverte et corrigée

### Erreur 500 : Champ `end_time` manquant

**Symptôme:**  
Après correction du problème de validation, une erreur 500 se produisait lors de la création du cours.

**Cause:**  
Le champ `end_time` est **requis (NOT NULL)** dans la table `lessons`, mais le controller ne le calculait pas à partir de `start_time` + `duration`.

**Solution appliquée:**  
**Fichier:** `app/Http/Controllers/Api/LessonController.php` (ligne 225-234)

```php
// Calculer end_time si duration est fourni
if (isset($validated['duration'])) {
    $startTime = \Carbon\Carbon::parse($validated['start_time']);
    $validated['end_time'] = $startTime->copy()->addMinutes($validated['duration'])->format('Y-m-d H:i:s');
} else {
    // Durée par défaut de 60 minutes si non fournie
    $startTime = \Carbon\Carbon::parse($validated['start_time']);
    $validated['end_time'] = $startTime->copy()->addMinutes(60)->format('Y-m-d H:i:s');
    $validated['duration'] = 60;
}
```

Cette correction permet de :
- ✅ Calculer automatiquement `end_time = start_time + duration`
- ✅ Utiliser une durée par défaut de 60 minutes si non fournie
- ✅ Éviter l'erreur SQL lors de l'insertion dans la table `lessons`

---

## 🎯 Statut

✅ **RÉSOLU** - Les problèmes de validation `course_type_id` et de calcul `end_time` sont corrigés.

