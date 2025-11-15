# Règle de Gestion : Valeur Manuelle des Abonnements

## 📋 Problème Identifié

Lors de la création d'un abonnement avec une valeur manuelle (ex: 5 cours déjà utilisés), l'ajout d'un nouveau cours écrasait la valeur manuelle :
- **Avant** : Abonnement créé avec 5 cours utilisés → Affichage **5/11** ✅
- **Problème** : Ajout d'un cours → Affichage **1/11** ❌ (au lieu de **6/11**)
- **Cause** : Le recalcul automatique comptait uniquement les cours attachés, ignorant la valeur manuelle

## ✅ Solution Implémentée

### Règle de Gestion

**La valeur manuelle initiale doit être préservée et les cours attachés/détachés s'ajoutent/soustraient à cette base.**

### Principe

```
lessons_used = valeur_manuelle_initial + cours_attachés - cours_annulés
```

### Exemple

1. **Création** : Abonnement avec 5 cours utilisés (manuel) → `lessons_used = 5`
2. **Ajout cours 1** : `lessons_used = 5 + 1 = 6` ✅
3. **Ajout cours 2** : `lessons_used = 6 + 1 = 7` ✅
4. **Annulation cours 1** : `lessons_used = 7 - 1 = 6` ✅
5. **Résultat** : Toujours **6/11** (et non 1/11) ✅

## 🔧 Implémentation Technique

### 1. Modification de `consumeLesson()` (SubscriptionInstance.php)

**Avant** : Recalculait `lessons_used` en comptant tous les cours attachés
```php
$this->recalculateLessonsUsed(); // ❌ Écrasait la valeur manuelle
```

**Après** : Incrémente directement `lessons_used`
```php
$this->lessons_used = $this->lessons_used + 1; // ✅ Préserve la valeur manuelle
```

### 2. Modification de `handleLessonCancellation()` (LessonObserver.php)

**Avant** : Recalculait `lessons_used` en comptant les cours restants
```php
$instance->recalculateLessonsUsed(); // ❌ Écrasait la valeur manuelle
```

**Après** : Décrémente directement `lessons_used`
```php
$instance->lessons_used = $instance->lessons_used - 1; // ✅ Préserve la valeur manuelle
```

### 3. Modification de `deleted()` (LessonObserver.php)

**Avant** : Recalculait `lessons_used`
```php
$instance->recalculateLessonsUsed(); // ❌ Écrasait la valeur manuelle
```

**Après** : Décrémente directement `lessons_used`
```php
$instance->lessons_used = $instance->lessons_used - 1; // ✅ Préserve la valeur manuelle
```

### 4. Amélioration de `recalculateLessonsUsed()`

La méthode `recalculateLessonsUsed()` est maintenant utilisée uniquement pour :
- Vérifier la cohérence (cours déjà attaché)
- Cas spéciaux (recalcul manuel via endpoint `/recalculate`)

Elle ne doit **PAS** être appelée lors de l'ajout/suppression normale de cours.

## 📊 Flux de Données

### Scénario : Abonnement avec valeur manuelle = 5

```
État Initial
├─ lessons_used = 5 (manuel)
├─ cours attachés = 0
└─ Affichage : 5/11 ✅

Ajout Cours 1
├─ consumeLesson() appelé
├─ Incrémentation : 5 + 1 = 6
├─ cours attachés = 1
└─ Affichage : 6/11 ✅

Ajout Cours 2
├─ consumeLesson() appelé
├─ Incrémentation : 6 + 1 = 7
├─ cours attachés = 2
└─ Affichage : 7/11 ✅

Annulation Cours 1
├─ handleLessonCancellation() appelé
├─ Décrémentation : 7 - 1 = 6
├─ cours attachés = 1 (cours 1 détaché)
└─ Affichage : 6/11 ✅
```

## 🧪 Tests de Validation

### Test Critique 1 : Ajout de cours
- **Prérequis** : Abonnement avec `lessons_used = 5` (manuel)
- **Action** : Ajouter 1 cours
- **Résultat attendu** : `lessons_used = 6` (5 + 1) ✅
- **Résultat incorrect** : `lessons_used = 1` ❌

### Test Critique 2 : Annulation de cours
- **Prérequis** : Abonnement avec `lessons_used = 6` (5 manuel + 1 cours)
- **Action** : Annuler 1 cours
- **Résultat attendu** : `lessons_used = 5` (6 - 1) ✅
- **Résultat incorrect** : `lessons_used = 0` ❌

### Test Critique 3 : Cycle complet
- **Prérequis** : Abonnement avec `lessons_used = 5` (manuel)
- **Actions** :
  1. Ajouter 3 cours → `lessons_used = 8` ✅
  2. Annuler 2 cours → `lessons_used = 6` ✅
  3. Ajouter 1 cours → `lessons_used = 7` ✅
- **Vérification** : Valeur manuelle toujours préservée ✅

## 📝 Logs de Débogage

Les logs suivants sont générés pour le suivi :

### Ajout de cours
```
➕ Cours {id} ajouté à l'abonnement {id} (incrémentation directe)
- old_lessons_used: 5
- new_lessons_used: 6
- calculation: "5 + 1 = 6"
```

### Annulation de cours
```
🚫 Cours {id} détaché de l'abonnement {id} (annulé, décrémentation)
- old_lessons_used: 6
- new_lessons_used: 5
- calculation: "6 - 1 = 5"
```

## ⚠️ Points d'Attention

1. **Recalcul manuel** : L'endpoint `/recalculate` peut toujours être utilisé pour forcer un recalcul complet, mais il écrasera la valeur manuelle
2. **Cohérence** : La valeur manuelle + cours attachés doit toujours être <= total disponible
3. **Validation** : Le système valide que `lessons_used` ne dépasse pas `total_available_lessons` lors de la création

## 🔄 Cas Limites

### Cas 1 : Annulation de tous les cours
- **Scénario** : Abonnement 5 (manuel) + 3 cours = 8, annuler les 3 cours
- **Résultat** : `lessons_used = 5` (retour à la valeur manuelle) ✅

### Cas 2 : Abonnement plein avec valeur manuelle
- **Scénario** : Abonnement 5 (manuel) + 6 cours = 11/11
- **Résultat** : Abonnement passe en `completed` ✅

### Cas 3 : Réouverture après annulation
- **Scénario** : Abonnement 11/11 `completed`, annuler 1 cours
- **Résultat** : `lessons_used = 10`, statut → `active` (réouvert) ✅

## 📚 Fichiers Modifiés

1. `app/Models/SubscriptionInstance.php`
   - `consumeLesson()` : Incrémentation directe
   - `recalculateLessonsUsed()` : Amélioration de la logique

2. `app/Observers/LessonObserver.php`
   - `handleLessonCancellation()` : Décrémentation directe
   - `deleted()` : Décrémentation directe

3. `docs/PLAN_TEST_ABONNEMENTS.md`
   - Ajout de tests spécifiques pour la valeur manuelle

4. `docs/CHECKLIST_TEST_ABONNEMENTS.md`
   - Ajout de tests critiques pour la valeur manuelle

## ✅ Validation

Pour valider que la règle fonctionne :

1. Créer un abonnement avec `lessons_used = 5`
2. Vérifier l'affichage : **5/11**
3. Ajouter 1 cours
4. Vérifier l'affichage : **6/11** (et non 1/11) ✅
5. Annuler 1 cours
6. Vérifier l'affichage : **5/11** (et non 0/11) ✅

---

**Date de mise en place** : 2025-11-15
**Version** : 1.0

