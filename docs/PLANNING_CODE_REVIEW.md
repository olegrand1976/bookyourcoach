# Revue de code – Page Planning (`/club/planning`)

**Date :** octobre 2025  
**Fichier principal :** `frontend/pages/club/planning.vue`  
**Contexte :** corrections post-conflits / déploiement (refs et fonctions manquantes).

---

## 1. Analyse des derniers changements (Git)

### Historique récent (planning.vue)

| Commit | Description |
|--------|-------------|
| `244ad6eaf` | isToday, loadCourseTypes, availableCourseTypes manquants |
| `52c70f450` | viewMode, loadLessons, availableDisciplines, goToToday, etc. |
| `73b0917c8` | clubProfile, availableSlots, loadOpenSlots manquants |
| `47f26e48f` | Une seule déclaration lessonForm + loadClubDisciplines + nettoyage blocs corrompus |
| `afc0e885b` | Ajout page /club/planning (résolution conflits) |

**Constat :** Plusieurs commits ont réintroduit des **refs** et **fonctions** manquantes (probablement perdus lors de merges ou résolutions de conflits). La page est devenue dépendante d’un état et de loaders qui n’étaient plus déclarés.

---

## 2. Bugs corrigés lors de cette revue

### 2.1 `nextDay()` manquant

- **Symptôme :** Le bouton « jour suivant » (navigation) appelle `nextDay()` alors que seule `previousDay()` existait.
- **Correction :** Ajout de `nextDay()` qui incrémente `currentDay` d’un jour et appelle `loadPlanningData()`.

### 2.2 `availableHours` : référence à `hours` non définie

- **Symptôme :** En absence de `schedule_config` ou quand `minHour === 24`, le computed retournait `hours`, variable jamais déclarée → risque d’erreur à l’exécution.
- **Correction :** Introduction de `defaultHours` (6h–22h, tableau de strings) et retour de `defaultHours` à la place de `hours`.

### 2.3 `availableHours` : code parasite dans le `forEach`

- **Symptôme :** Dans le `forEach` sur `schedule_config`, une ligne `return dayMatch && timeMatch && dateMatch` référençait des variables inexistantes (résidu de merge).
- **Correction :** Suppression de cette ligne ; le `forEach` ne fait plus que mettre à jour `minHour` / `maxHour`.

---

## 3. État actuel du code – Points d’attention

### 3.1 Taille et structure

- **~2900 lignes** dans un seul fichier Vue (template + script).
- **Beaucoup de logique inline** : chargement des données, formatage, calculs, modales.
- **Composables planning non utilisés :**  
  `useDateHelpers.ts`, `usePlanningCalculations.ts`, `usePlanningFormatters.ts`, `usePlanningColors.ts` existent mais **ne sont pas importés** dans `planning.vue`. La page réimplémente des helpers (ex. `isToday`, `getMondayOfWeek`, `formatDayTitle`, `formatWeekRange`) en local.

**Recommandation :** À moyen terme, déplacer la logique métier vers les composables et éventuellement des composants (ex. bloc navigation, grille jour/semaine) pour alléger la page et réutiliser la logique.

### 3.2 Doublons et cohérence

- **`openSlots` vs `availableSlots` :** `availableSlots = openSlots` (même ref, deux noms). À terme, un seul nom (par ex. `availableSlots`) simplifierait la lecture.
- **`courseTypes` vs `availableCourseTypes` :** Deux refs distinctes ; `loadCourseTypes` remplit `availableCourseTypes`. Vérifier que partout où il faut « les types de cours pour le planning » on utilise bien `availableCourseTypes` et pas `courseTypes` par erreur.

### 3.3 Chargement et ordre

- **onMounted** appelle en parallèle :  
  `loadClubDisciplines`, `loadOpenSlots`, `loadLessons`, `loadTeachers`, `loadStudents`, `loadCourseTypes`, puis `updateAvailableDays()`.
- **loadClubDisciplines** met `loading.value = false` dans son `finally` ; les autres loaders ne touchent pas à `loading`. L’UI peut donc considérer le chargement terminé alors qu’un autre appel est encore en cours.
- **Recommandation :** Soit un seul `loading` géré au niveau du `onMounted` (true au début, false à la fin du `Promise.all`), soit des états de chargement par ressource (ex. `loadingLessons`, `loadingSlots`) pour un feedback plus précis.

### 3.4 Gestion d’erreurs

- Les loaders (loadOpenSlots, loadLessons, loadCourseTypes, loadClubDisciplines, etc.) font des `console.error` et parfois vident les refs en cas d’erreur.
- Peu ou pas de retours utilisateur (toast / message) en cas d’échec réseau ou API.
- **Recommandation :** Utiliser le composable `useToast` (déjà importé) pour afficher un message en cas d’erreur de chargement.

### 3.5 Logs de debug

- Nombreux `console.log` (ex. `filteredCourseTypes`, chargements, etc.) en production.
- **Recommandation :** Les conditionner à un flag ou à `import.meta.dev` pour ne pas polluer la console en prod.

---

## 4. Checklist de cohérence (état après corrections)

| Élément | Déclaré / Défini | Utilisé dans |
|--------|-------------------|--------------|
| clubProfile | ✅ ref(null) | loadClubDisciplines, hourRanges, availableHours, etc. |
| availableSlots (= openSlots) | ✅ | Template, script |
| availableDisciplines | ✅ ref([]) | loadClubDisciplines, template, script |
| availableCourseTypes | ✅ ref([]) | loadCourseTypes, updateLessonPrice, filteredCourseTypes |
| viewMode | ✅ ref('week') | Template, displayDays, loadLessons, etc. |
| currentWeek / currentDay | ✅ refs | weekDays, singleDay, loadLessons, navigation |
| getMondayOfWeek | ✅ | currentWeek init, goToToday |
| loadOpenSlots | ✅ | onMounted, après create/update/delete slot |
| loadLessons | ✅ | onMounted, loadPlanningData, checkAndReloadLessonsIfNeeded |
| loadClubDisciplines | ✅ | onMounted |
| loadCourseTypes | ✅ | onMounted |
| loadPlanningData | ✅ | previousWeek, nextWeek, previousDay, nextDay, goToToday, createLesson |
| updateAvailableDays | ✅ | onMounted |
| goToToday | ✅ | Template |
| previousWeek / nextWeek | ✅ | Template |
| previousDay / nextDay | ✅ | Template (nextDay ajouté en revue) |
| isToday | ✅ | Template |
| defaultHours / availableHours | ✅ | availableHours corrigé (plus de `hours` indéfini) |

---

## 5. Résumé

- **Derniers changements :** Plusieurs corrections ont rétabli des refs et fonctions manquantes (lessonForm unique, loadClubDisciplines, loadOpenSlots, clubProfile, availableSlots, viewMode, loadLessons, availableDisciplines, loadCourseTypes, isToday, availableCourseTypes).
- **Revue :** Trois bugs supplémentaires ont été corrigés : **nextDay()** manquant, **availableHours** qui utilisait une variable **hours** non définie, et un **return** parasite dans le **forEach** de **availableHours**.
- **Recommandations :** Réutiliser les composables planning, clarifier openSlots/availableSlots et courseTypes/availableCourseTypes, améliorer la gestion du loading et des erreurs, et réduire les console.log en production.

Si tu veux, on peut enchaîner sur un commit + push de ces corrections (nextDay, defaultHours, availableHours) ou sur un refactor ciblé (ex. extraction de la navigation dans un composant).
