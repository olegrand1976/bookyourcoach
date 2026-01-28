# Analyse de Régression - Planning Page

## Problème Actuel (28 janvier 2026)

### Symptômes rapportés
1. **Erreurs JavaScript en production** :
   - `TypeError: s.formatDate is not a function`
   - `TypeError: can't access property "start_time", s.lesson is undefined`
   - `TypeError: s.getLessonClass is not a function`

2. **Fonctionnalité cassée** :
   - Encodage de nouveaux cours non fonctionnel
   - Affichage planning incohérent

## Historique Git des Modifications

### Période Stable (avant 22 janvier)
- **Commit `5461c5efc`** (feat: Amélioration suppression cours) - Version différente avec composants
- Planning fonctionnait correctement

### Période de Régression (22-28 janvier)
Les commits suivants ont introduit des problèmes :

1. **22 jan - `44bd455a2`** : test commit - **SUPPRIME planning.vue (3493 lignes)**
2. **27 jan - `afc0e885b`** : Rajoute planning (résolution conflits)
3. **27 jan - `d2eabc2e6`** : Fix node_modules tracking
4. **27-28 jan - Série de fix** :
   - `47f26e48f` : lessonForm unique + loadClubDisciplines
   - `73b0917c8` : clubProfile, availableSlots, loadOpenSlots
   - `52c70f450` : viewMode, loadLessons, availableDisciplines
   - `244ad6eaf` : isToday, loadCourseTypes
   - `442942139` : nextDay, availableHours, revue
   - `4c82a596f` : formatDate + cache frontend
   - `17529d44d` : lesson undefined + getLessonClass

## Cause Racine

Le fichier planning.vue a été **complètement supprimé puis recréé** le 22 janvier, probablement suite à :
- Un conflit de merge mal résolu
- Une opération Git incorrecte
- Un rebase qui a écrasé le fichier

La version recréée (afc0e885b) était **incomplète** et manquait de nombreuses fonctions et refs.

## Corrections Tentées (27-28 janvier)

Série de 9 commits pour rajouter manuellement les éléments manquants :
- Refs manquantes (viewMode, currentWeek, currentDay, etc.)
- Fonctions manquantes (formatDate, formatDateForInput, nextDay, etc.)
- Loaders manquants (loadLessons, loadClubDisciplines, etc.)

**Problème** : Cette approche fragmentaire a introduit de nouveaux bugs à chaque ajout.

## Solution Recommandée

### Option 1 : Restaurer version stable (RECOMMANDÉ)
Revenir au commit **avant la suppression** et fusionner proprement :

```bash
# Identifier le dernier commit stable avant 44bd455a2
git log --before="2025-01-22" --oneline -- frontend/pages/club/planning.vue

# Restaurer cette version
git checkout <commit-stable> -- frontend/pages/club/planning.vue

# Tester, valider, puis commit
```

### Option 2 : Audit complet version actuelle
1. Comparer version actuelle vs version stable (diff complet)
2. Identifier TOUTES les fonctions/refs manquantes
3. Les ajouter en un seul commit cohérent
4. Tests exhaustifs

## Prochaines Étapes

1. **IMMÉDIAT** : Restaurer version stable avant 44bd455a2
2. **COURT TERME** : Tests complets de non-régression
3. **MOYEN TERME** : Documentation des fonctionnalités critiques
4. **LONG TERME** : 
   - Tests E2E sur la page planning
   - Revue de code avant merge sur fichiers critiques
   - Protection branche main (revue obligatoire)

## Leçons Apprises

1. Ne jamais supprimer/recréer un fichier critique en production
2. Résoudre les conflits de merge avec attention
3. Tests avant chaque push
4. Commits atomiques (1 fonctionnalité = 1 commit)
5. Revue de code obligatoire sur fichiers > 1000 lignes
