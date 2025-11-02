# Correction du filtrage des types de cours - Route club/planning

**Date**: 2 novembre 2025  
**Problème identifié**: Les types de cours listés lors de la création d'un nouveau cours ne sont pas correctement filtrés selon les disciplines assignées au club et au créneau.

## 🔍 Analyse du problème

### Contexte
Sur la route `club/planning`, lors de la création d'un nouveau cours, les types de cours affichés dans le formulaire doivent être filtrés pour ne montrer que ceux qui correspondent :
1. Aux types de cours associés au créneau sélectionné (via la table pivot `club_open_slot_course_type`)
2. ET aux disciplines activées pour le club (stockées dans `clubs.disciplines`)

### Problème identifié
Le filtrage côté backend existait déjà dans `ClubOpenSlotController::index()` (lignes 228-238), mais il présentait plusieurs faiblesses :

1. **Parsing des disciplines du club** : Le champ `disciplines` du modèle Club est casté en `array`, mais le parsing n'était pas robuste et ne gérait pas tous les cas (string JSON, array, null).

2. **Conversion de types** : Les comparaisons d'IDs n'utilisaient pas de conversion stricte en entiers, ce qui pouvait causer des problèmes de comparaison entre strings et integers.

3. **Gestion du cas "pas de disciplines"** : Si un club n'avait pas encore configuré ses disciplines, le code ne gérait pas ce cas spécifique.

4. **Logs insuffisants** : Les logs existants ne permettaient pas de diagnostiquer facilement les problèmes de filtrage.

## ✅ Corrections apportées

### Backend : `app/Http/Controllers/Api/ClubOpenSlotController.php`

#### 1. Amélioration du parsing des disciplines du club (lignes 206-244)

```php
// 🔧 CORRECTION : Parser correctement les disciplines du club
$rawDisciplines = $club->disciplines;

// Si c'est une string JSON, la parser
if (is_string($rawDisciplines)) {
    try {
        $clubDisciplineIds = json_decode($rawDisciplines, true) ?? [];
    } catch (\Exception $e) {
        Log::warning('ClubOpenSlotController::index - Erreur parsing disciplines JSON', [
            'club_id' => $club->id,
            'raw_value' => $rawDisciplines,
            'error' => $e->getMessage()
        ]);
        $clubDisciplineIds = [];
    }
} elseif (is_array($rawDisciplines)) {
    $clubDisciplineIds = $rawDisciplines;
} else {
    $clubDisciplineIds = [];
}

// S'assurer que les IDs sont des entiers
$clubDisciplineIds = array_map('intval', array_filter($clubDisciplineIds));
```

**Avantages** :
- Gère tous les formats possibles (string JSON, array, null)
- Convertit explicitement les IDs en entiers pour des comparaisons fiables
- Ajoute des logs détaillés pour le diagnostic

#### 2. Gestion du cas "club sans disciplines" (lignes 252-262)

```php
// ⚠️ Si le club n'a pas de disciplines configurées, logger un warning
if (empty($clubDisciplineIds)) {
    Log::warning("ClubOpenSlotController::index - Club sans disciplines configurées", [
        'slot_id' => $slot->id,
        'message' => 'Le club n\'a pas de disciplines configurées. Seuls les types génériques seront affichés.'
    ]);
    
    // Ne garder que les types génériques (sans discipline)
    $courseTypes = $courseTypes->filter(function($courseType) {
        return !$courseType->discipline_id;
    })->values();
}
```

**Avantages** :
- Prévient l'affichage de types de cours non pertinents
- Informe l'administrateur via les logs qu'une configuration est nécessaire

#### 3. Amélioration de la logique de filtrage (lignes 264-303)

```php
$courseTypes = $courseTypes->filter(function($courseType) use ($clubDisciplineIds, $slot) {
    // Conversion en entier pour comparaison sûre
    $courseTypeDisciplineId = $courseType->discipline_id ? intval($courseType->discipline_id) : null;
    $slotDisciplineId = $slot->discipline_id ? intval($slot->discipline_id) : null;
    
    // 🎯 LOGIQUE DE FILTRAGE :
    // 1. Si le type de cours n'a pas de discipline (générique) → GARDER
    // 2. Si le type de cours a une discipline qui est dans celles du club → GARDER
    // 3. Sinon → REJETER
    
    $isGeneric = !$courseTypeDisciplineId;
    $isInClubDisciplines = $courseTypeDisciplineId && in_array($courseTypeDisciplineId, $clubDisciplineIds, true);
    $keep = $isGeneric || $isInClubDisciplines;
    
    Log::debug("Slot {$slot->id} - Type {$courseType->id} ({$courseType->name})", [
        'course_type_discipline' => $courseTypeDisciplineId,
        'slot_discipline' => $slotDisciplineId,
        'is_generic' => $isGeneric,
        'is_in_club' => $isInClubDisciplines,
        'keep' => $keep
    ]);
    
    return $keep;
})->values();
```

**Avantages** :
- Logique claire et explicite avec des variables nommées
- Comparaison stricte avec `in_array(..., true)` pour éviter les faux positifs
- Logs détaillés pour chaque type de cours filtré

### Frontend : `frontend/components/planning/CreateLessonModal.vue`

#### Amélioration du message d'erreur (lignes 70-78)

```vue
<p v-if="selectedSlot && courseTypes.length === 0" class="text-xs text-red-600 mt-1">
  ⚠️ Aucun type de cours disponible pour ce créneau
  <br>
  <span class="text-xs">
    Vérifiez que :
    <br>• Des types de cours sont associés à ce créneau
    <br>• Ces types correspondent aux disciplines activées pour votre club
  </span>
</p>
```

**Avantages** :
- Message d'erreur plus explicite
- Guide l'utilisateur vers les points à vérifier

### Frontend : `frontend/pages/club/planning.vue`

#### Amélioration des logs de diagnostic (lignes 608-662)

```typescript
const filteredCourseTypes = computed(() => {
  console.log('🔄 [filteredCourseTypes] Computed appelé', {
    hasSlot: !!selectedSlotForLesson.value,
    slotId: selectedSlotForLesson.value?.id,
    slotDisciplineId: selectedSlotForLesson.value?.discipline_id,
    slotHasCourseTypes: !!selectedSlotForLesson.value?.course_types,
    modalOpen: showCreateLessonModal.value,
    clubDisciplinesCount: clubDisciplines.value.length,
    clubDisciplineIds: clubDisciplines.value.map(d => d.id)
  })
  
  // ... filtrage ...
  
  // ⚠️ Si aucun type de cours n'est disponible, afficher un avertissement
  if (slotCourseTypes.length === 0) {
    console.warn('⚠️ [filteredCourseTypes] Aucun type de cours disponible !', {
      slotId: selectedSlotForLesson.value.id,
      slotDisciplineId: selectedSlotForLesson.value.discipline_id,
      clubDisciplines: clubDisciplines.value.map(d => ({ id: d.id, name: d.name })),
      message: 'Vérifiez que des types de cours sont associés à ce créneau et correspondent aux disciplines du club'
    })
  }
  
  return slotCourseTypes
})
```

**Avantages** :
- Logs console détaillés pour faciliter le débogage
- Affichage des disciplines du club et des types de cours disponibles
- Avertissement explicite en cas de liste vide

## 🔧 Comment tester la correction

### Prérequis
1. Un club avec des disciplines configurées dans `clubs.disciplines`
2. Des créneaux horaires créés avec des disciplines assignées
3. Des types de cours existants avec des `discipline_id` correspondant aux disciplines du club

### Étapes de test

1. **Se connecter en tant que club**
   ```
   Se rendre sur /club/planning
   ```

2. **Sélectionner un créneau**
   - Cliquer sur un créneau dans la liste
   - Le bouton "Créer un cours" devrait apparaître

3. **Créer un nouveau cours**
   - Cliquer sur "Créer un cours"
   - La modale s'ouvre avec le champ "Type de cours"

4. **Vérifier le filtrage**
   - Vérifier que seuls les types de cours pertinents sont affichés
   - Vérifier les logs dans la console du navigateur (F12)
   - Vérifier les logs Laravel (`storage/logs/laravel.log`)

### Logs à surveiller

#### Console navigateur
```
🔄 [filteredCourseTypes] Computed appelé
🎯 [filteredCourseTypes] Types de cours du créneau (déjà filtrés par le backend)
```

#### Logs Laravel
```
ClubOpenSlotController::index - Filtrage par disciplines du club
ClubOpenSlotController::index - Types filtrés pour slot X
```

## 📝 Points d'attention

1. **Disciplines du club** : S'assurer que le champ `clubs.disciplines` est correctement rempli. C'est un JSON array d'IDs de disciplines : `[1, 3, 5]`

2. **Types de cours génériques** : Les types de cours avec `discipline_id = NULL` sont considérés comme génériques et sont toujours affichés.

3. **Association créneau ↔ types de cours** : Vérifier que les types de cours sont bien associés aux créneaux via la table pivot `club_open_slot_course_type`.

4. **Configuration club** : Si un club n'a pas de disciplines configurées, seuls les types génériques seront affichés (comportement par défaut sécurisé).

## 🎯 Résultat attendu

Après ces corrections :
- ✅ Les types de cours affichés correspondent exactement aux disciplines du club
- ✅ Les types génériques (sans discipline) sont toujours affichés
- ✅ Un message clair s'affiche si aucun type n'est disponible
- ✅ Les logs permettent un diagnostic rapide en cas de problème
- ✅ Le comportement est cohérent entre le frontend et le backend

## 🔄 Prochaines étapes recommandées

1. **Migration des données** : S'assurer que tous les clubs ont leur champ `disciplines` correctement rempli
2. **Documentation** : Ajouter dans la documentation administrateur les étapes de configuration des disciplines
3. **Interface admin** : Créer une interface pour gérer facilement l'association types de cours ↔ créneaux
4. **Tests automatisés** : Ajouter des tests unitaires et d'intégration pour le filtrage

## 📚 Références

- Modèle Club : `app/Models/Club.php` (ligne 81 : cast `disciplines` en array)
- Contrôleur créneaux : `app/Http/Controllers/Api/ClubOpenSlotController.php`
- Page planning : `frontend/pages/club/planning.vue`
- Modale création cours : `frontend/components/planning/CreateLessonModal.vue`

