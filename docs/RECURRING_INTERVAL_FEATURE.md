# Gestion des Récurrences Complexes - Feature Documentation

## 📋 Vue d'ensemble

Cette fonctionnalité permet aux clubs de créer des cours avec des récurrences plus flexibles que le simple "chaque semaine". Les options disponibles sont :

- **Chaque semaine** (interval = 1) - par défaut
- **Toutes les 2 semaines** (interval = 2)
- **Toutes les 3 semaines** (interval = 3)
- **Toutes les 4 semaines** (interval = 4)

## 🎯 Cas d'usage

### Exemple 1 : Cours bi-hebdomadaires
Un club propose des cours d'équitation toutes les deux semaines le mercredi à 14h00.
- **Configuration :** interval = 2
- **Résultat :** Cours créés le 13 nov, 27 nov, 11 déc, 25 déc, etc.

### Exemple 2 : Cours mensuels
Un élève suit un cours de perfectionnement une fois par mois le samedi.
- **Configuration :** interval = 4
- **Résultat :** Cours créés le 16 nov, 14 déc, 11 jan, 8 fév, etc.

## 💾 Structure de données

### Base de données

**Table : `subscription_recurring_slots`**

Nouveau champ ajouté :
```sql
recurring_interval INT DEFAULT 1 COMMENT 'Intervalle en semaines (1=hebdo, 2=bi-hebdo, etc.)'
```

### Modèle PHP

**Fichier : `app/Models/SubscriptionRecurringSlot.php`**

```php
protected $fillable = [
    // ... autres champs
    'recurring_interval',  // Nouveau champ
    // ...
];

protected $casts = [
    'recurring_interval' => 'integer',
    // ...
];
```

## 🔄 Flux de traitement

### 1. Création d'un cours (Frontend)

**Fichier : `frontend/components/planning/CreateLessonModal.vue`**

```vue
<!-- Sélecteur d'intervalle (affiché si élève + abonnement) -->
<select v-model.number="form.recurring_interval">
  <option :value="1">Chaque semaine</option>
  <option :value="2">Toutes les 2 semaines</option>
  <option :value="3">Toutes les 3 semaines</option>
  <option :value="4">Toutes les 4 semaines</option>
</select>
```

### 2. Envoi au backend

**Fichier : `frontend/pages/club/planning.vue`**

```javascript
const payload = {
  // ... autres champs
  recurring_interval: lessonForm.value.recurring_interval || 1
}

await $api.post('/lessons', payload)
```

### 3. Validation et traitement (Backend)

**Fichier : `app/Http/Controllers/Api/LessonController.php`**

```php
$validated = $request->validate([
    // ... autres champs
    'recurring_interval' => 'nullable|integer|min:1|max:52',
]);

// Passer l'intervalle au job
$recurringInterval = $request->input('recurring_interval', 1);
ProcessLessonPostCreationJob::dispatch($lesson, $recurringInterval);
```

### 4. Création du créneau récurrent

**Fichier : `app/Jobs/ProcessLessonPostCreationJob.php`**

```php
$recurringSlot = SubscriptionRecurringSlot::create([
    // ... autres champs
    'recurring_interval' => $this->recurringInterval,
    // ...
]);
```

### 5. Génération automatique des cours

**Fichier : `app/Services/LegacyRecurringSlotService.php`**

```php
// Utilisation de l'intervalle pour générer les dates
$recurringInterval = $recurringSlot->recurring_interval ?? 1;

while ($currentDate->lte($endDate) && $currentDate->lte($recurringEndDate)) {
    $dates[] = $currentDate->copy();
    $currentDate->addWeeks($recurringInterval); // ← Utilise l'intervalle
}
```

## 🎨 Interface utilisateur

### Affichage conditionnel

Le sélecteur d'intervalle s'affiche uniquement si :
1. ✅ Mode création (pas en édition)
2. ✅ Un élève est sélectionné
3. ✅ Déduction d'abonnement est activée

### Exemple visuel dans la modale

```
┌─────────────────────────────────────────────┐
│ Fréquence de récurrence                     │
│ ┌─────────────────────────────────────────┐ │
│ │ Chaque semaine                      ▼   │ │
│ └─────────────────────────────────────────┘ │
│                                             │
│ ℹ️ Exemple : Si vous créez un cours le     │
│ 13 novembre 2025 avec "Toutes les 2        │
│ semaines", les prochains cours seront      │
│ automatiquement créés le 27 nov, le 11     │
│ déc, etc.                                   │
└─────────────────────────────────────────────┘
```

## 🧪 Tests

### Test manuel

1. **Créer un cours bi-hebdomadaire**
   - Aller sur `/club/planning`
   - Sélectionner un créneau
   - Cliquer "Créer un cours"
   - Sélectionner un élève avec abonnement
   - Choisir "Toutes les 2 semaines"
   - Créer le cours

2. **Vérifier la génération**
   - Attendre que le job asynchrone se termine
   - Vérifier dans la base de données :
     ```sql
     SELECT * FROM subscription_recurring_slots 
     WHERE recurring_interval = 2
     LIMIT 1;
     ```
   - Vérifier les cours générés :
     ```sql
     SELECT DATE(start_time), student_id, teacher_id 
     FROM lessons 
     WHERE student_id = [ID_ELEVE]
     ORDER BY start_time;
     ```

### Résultat attendu

Pour un cours créé le 13 nov avec interval=2 :
- ✅ Créneau récurrent créé avec `recurring_interval = 2`
- ✅ Cours générés : 20 nov, 4 déc, 18 déc, 1 jan, etc.

## 📊 Compatibilité

### Rétrocompatibilité

Les créneaux existants sans `recurring_interval` défini auront la valeur par défaut `1`, ce qui maintient le comportement hebdomadaire actuel.

```php
// Dans LegacyRecurringSlotService.php
$recurringInterval = $recurringSlot->recurring_interval ?? 1;
```

### Migration

```bash
# Exécuter la migration
php artisan migrate

# Vérifier
php artisan migrate:status | grep recurring_interval
```

## ⚠️ Limitations actuelles

1. **Maximum 52 semaines** : L'intervalle est limité à 52 pour éviter les configurations irréalistes
2. **Pas d'intervalles personnalisés** : Seules les options 1, 2, 3, 4 sont proposées dans l'UI (mais le backend accepte jusqu'à 52)
3. **Modification impossible** : Une fois créé, l'intervalle d'un créneau récurrent ne peut pas être modifié (il faut le supprimer et en créer un nouveau)

## 🔮 Améliorations futures possibles

1. **Intervalles personnalisés** : Permettre de saisir n'importe quel nombre de semaines (5, 6, 8, etc.)
2. **Modification d'intervalle** : Permettre de modifier l'intervalle d'un créneau existant
3. **Patterns complexes** : Ajouter des patterns comme "première semaine de chaque mois" ou "semaines paires/impaires"
4. **Prévisualisation** : Afficher un calendrier visuel avec les dates qui seront générées

## 📝 Notes pour les développeurs

- Le champ `recurring_interval` est toujours en **semaines**
- La valeur par défaut est **1** (chaque semaine)
- Le service utilise `addWeeks($interval)` de Carbon pour calculer les dates
- L'intervalle est passé du frontend au backend via le payload de création
- Le job asynchrone `ProcessLessonPostCreationJob` reçoit l'intervalle en paramètre

## 📚 Références

- Migration : `database/migrations/2025_12_11_010000_add_recurring_interval_to_subscription_recurring_slots_table.php`
- Modèle : `app/Models/SubscriptionRecurringSlot.php`
- Contrôleur : `app/Http/Controllers/Api/LessonController.php`
- Job : `app/Jobs/ProcessLessonPostCreationJob.php`
- Service : `app/Services/LegacyRecurringSlotService.php`
- Composant : `frontend/components/planning/CreateLessonModal.vue`
- Page : `frontend/pages/club/planning.vue`

---

**Date de création :** 2025-12-11  
**Version :** 1.0  
**Auteur :** GitHub Copilot Workspace
