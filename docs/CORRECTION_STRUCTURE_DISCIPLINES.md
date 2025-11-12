# 🔧 Correction de la structure Disciplines / Spécialisations / Types de cours

**Date**: 4 novembre 2025  
**Contexte**: Analyse et correction de la structure de données pour les disciplines et types de cours

---

## 🐛 Problème identifié

### Symptôme initial
Lors de la création d'un cours dans `/club/planning`, **aucun type de cours n'était disponible** dans le sélecteur, même si le club avait configuré ses disciplines dans son profil.

### Analyse en détail

#### 1. **Structure legacy vs nouvelle structure**

| Ancienne structure | Nouvelle structure |
|-------------------|-------------------|
| Colonne `clubs.disciplines` | Colonne `clubs.discipline_settings` |
| Tableau de **strings** (noms) | Objet JSON avec **IDs** et paramètres |
| Ex: `["dressage", "saut d'obstacles"]` | Ex: `{"1": {"price": 50, "duration_minutes": 60}}` |

**Problème**: Les clubs avec l'ancienne structure n'étaient pas reconnus par le frontend, car celui-ci cherche les `discipline_settings` (avec IDs) et non les `disciplines` (avec noms).

#### 2. **Absence de créneaux configurés**

Même avec des disciplines configurées, **aucun créneau** n'existait pour le Club 1, donc :
- Pas de créneaux → Pas d'horaires disponibles
- Pas d'horaires → Pas de types de cours à sélectionner

#### 3. **Incohérences dans les associations créneau ↔ type de cours**

Certains créneaux étaient associés à des types de cours avec une discipline différente :
- Créneau discipline_id = 11 (Natation individuel)
- Type de cours discipline_id = 2 (Natation standard)
- **INCOHÉRENCE** → Impossible de créer un cours

---

## ✅ Corrections appliquées

### 1. Migration automatique des disciplines legacy

**Fichier**: `database/migrations/2025_11_04_134407_migrate_legacy_disciplines_to_discipline_settings.php`

**Fonctionnement**:
- Parcourt tous les clubs de la base
- Si `discipline_settings` est vide ET `disciplines` (legacy) existe
- Convertit chaque nom de discipline en ID via un mapping
- Crée un objet `discipline_settings` avec prix et durée par défaut
- Sauvegarde dans la base

**Mapping des disciplines** (extrait):
```php
$disciplineMapping = [
    'dressage' => 1,
    'saut d\'obstacles' => 2,
    'équitation de loisir' => 7,
    'natation enfant' => 11,
    'musculation' => 21,
    'football' => 31,
    // ... etc (98 mappings au total)
];
```

**Résultat** :
- Club 1 : 3 disciplines migrées ✅
- Club 2 : 5 disciplines migrées ✅
- Clubs 3 & 4 : Aucune discipline legacy (skipped)

---

### 2. Création de créneaux d'exemple

**Pour Club 1** (test):
- 6 créneaux créés (2 par discipline)
- Lundi et Mardi
- Horaires : 09:00 et 14:00
- Chaque créneau associé aux types de cours de sa discipline

**Résumé**:
```
Dressage : Lundi 09:00 → 2 types de cours
Dressage : Mardi 14:00 → 2 types de cours
Saut d'obstacles : Lundi 09:00 → 2 types de cours
Saut d'obstacles : Mardi 14:00 → 2 types de cours
Équitation de loisir : Lundi 09:00 → 2 types de cours
Équitation de loisir : Mardi 14:00 → 2 types de cours
```

---

### 3. Correction des incohérences créneau ↔ type de cours

**Fichier**: `database/migrations/2025_11_03_220000_fix_club_open_slot_course_types_discipline_mismatch.php` (modifié)

**Correction appliquée**:
- Ajout d'une vérification de doublon avant update
- Si l'association (slot + course_type) existe déjà → Supprimer l'ancienne
- Sinon → Update avec le nouveau type de cours

**Évite l'erreur**:
```
SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry
```

---

## 📊 Structure des données

### Hiérarchie complète

```
Club
  └─ discipline_settings (JSON)
       ├─ discipline_id: 1 (Dressage)
       │    ├─ price: 50.00
       │    ├─ duration_minutes: 60
       │    └─ is_active: true
       └─ discipline_id: 2 (Saut d'obstacles)
            ├─ price: 50.00
            ├─ duration_minutes: 60
            └─ is_active: true

ClubOpenSlot (Créneaux)
  ├─ club_id
  ├─ discipline_id (ex: 1 = Dressage)
  ├─ day_of_week
  ├─ start_time
  └─ courseTypes (relation many-to-many)
       ├─ CourseType: "Cours individuel" (discipline_id: 1)
       └─ CourseType: "Cours collectif" (discipline_id: 1)

Lesson (Cours)
  ├─ club_id
  ├─ open_slot_id
  ├─ course_type_id
  ├─ teacher_id
  ├─ student_id (optionnel)
  └─ date + time
```

### Règles de cohérence

✅ **OBLIGATOIRE**: Pour créer un cours, il faut :
1. Le club ait configuré `discipline_settings` (avec IDs)
2. Des créneaux existent pour au moins une discipline du club
3. Chaque créneau ait au moins un type de cours associé
4. Le `discipline_id` du créneau = `discipline_id` du type de cours
5. Le type de cours soit dans les `courseTypes` du créneau

---

## 🎯 Résultat final

### État des clubs après migration

| Club | Disciplines | Créneaux | Types de cours | Statut |
|------|-------------|----------|----------------|--------|
| Club 1 | 3 | 6 | 6 (2 par discipline) | ✅ Opérationnel |
| Club 2 | 5 | 30 | ~10 | ✅ Opérationnel |
| Club 3 | 0 | 0 | 0 | ⚠️ À configurer |
| Club 4 | 0 | 0 | 0 | ⚠️ À configurer |

### Test de création de cours

**Maintenant, lors de la création d'un cours** :

1. ✅ Le frontend lit `discipline_settings` du club
2. ✅ Il affiche les créneaux correspondants
3. ✅ Pour chaque créneau, il filtre les types de cours :
   - Qui correspondent à la discipline du créneau
   - Qui sont dans les disciplines configurées du club
4. ✅ Les types de cours s'affichent correctement dans le sélecteur
5. ✅ La création de cours fonctionne

---

## 🚀 Pour les nouveaux clubs

### Configuration recommandée

1. **Dans le profil club** (`/club/profile`):
   - Sélectionner les disciplines (automatiquement stocké dans `discipline_settings`)
   - Définir prix et durée par défaut pour chaque discipline

2. **Dans le planning** (`/club/planning`):
   - Créer des créneaux pour chaque discipline
   - Sélectionner la discipline du créneau
   - Les types de cours sont **automatiquement associés** au créneau

3. **Vérification**:
   - Cliquer sur un créneau
   - "Créer un nouveau cours"
   - Les types de cours doivent s'afficher ✅

---

## 🔧 Migrations exécutées

```bash
✅ 2025_11_03_220000_fix_club_open_slot_course_types_discipline_mismatch
   → Correction des incohérences créneau ↔ type de cours

✅ 2025_11_03_230000_fix_course_types_prices_from_club_settings
   → Backfill des prix des types de cours depuis club.discipline_settings

✅ 2025_11_04_134407_migrate_legacy_disciplines_to_discipline_settings
   → Migration automatique des anciennes structures vers la nouvelle
```

---

## 📝 Notes importantes

### Pour l'équipe de développement

1. **Toujours utiliser `discipline_settings`** (avec IDs), jamais `disciplines` (legacy)
2. **Lors de la création d'un créneau**, auto-associer les types de cours de la discipline
3. **Lors de la création d'un cours**, valider la cohérence discipline → créneau → type de cours
4. Les types de cours génériques (sans `discipline_id`) **ne sont plus acceptés** pour garantir la cohérence

### Pour la production

- La migration est **idempotente** (peut être relancée sans problème)
- Les clubs sans `discipline_settings` seront automatiquement migrés
- Les clubs avec `discipline_settings` existant sont **ignorés** (pas de modification)

---

## ✨ Fonctionnalités garanties

Avec cette structure corrigée :

✅ Les types de cours dans le sélecteur correspondent aux disciplines du club  
✅ Les créneaux sont cohérents avec leurs types de cours  
✅ La création de cours valide la cohérence à chaque étape  
✅ Les anciens clubs sont automatiquement migrés  
✅ Les logs détaillés facilitent le debugging  

---

**Dernière mise à jour**: 4 novembre 2025

