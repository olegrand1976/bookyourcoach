# Tests - Amélioration Suppression de Cours

**Branche** : `feature/improve-lesson-deletion`  
**Commit amélioration** : `5461c5efc` (10 janvier 2026)  
**Base stable** : `879a4992a` (29 décembre 2025)

## Résumé de l'Amélioration

### Fonctionnalités Ajoutées

1. **Distinction Annuler vs Supprimer**
   - **Annuler** : Met le cours en statut `cancelled` (conservé en base)
   - **Supprimer** : Suppression définitive du cours

2. **Gestion Cours Récurrents**
   - Option "Cette séance uniquement"
   - Option "Toutes les séances futures" (même créneau + abonnement)
   - Compteur automatique des séances affectées

3. **Filtrage Strict par Créneau**
   - Même jour de semaine
   - Même plage horaire
   - Même élève
   - Même club

4. **Interface Améliorée**
   - Badge ⚠️ pour cours annulés
   - Bouton suppression rouge foncé si déjà annulé
   - Modale de confirmation avec options claires
   - Champ raison (optionnel)

## Checklist de Tests

### Pré-requis
- [ ] Backend déployé avec API `/club/lessons/:id` (action + scope)
- [ ] Backend a route `/club/subscription-instances/:id/future-lessons`
- [ ] Frontend build réussi sans erreurs
- [ ] Aucune erreur linter

### Tests Fonctionnels

#### Test 1 : Suppression Cours Unique (Sans Abonnement)
**Données** :
- Cours unique non lié à un abonnement
- Statut : confirmed

**Actions** :
1. [ ] Aller sur `/club/planning`
2. [ ] Cliquer sur un cours unique
3. [ ] Cliquer bouton "Supprimer"
4. [ ] Vérifier : modale s'ouvre
5. [ ] Vérifier : affiche infos cours
6. [ ] Vérifier : pas d'option "Toutes séances futures" (pas d'abonnement)
7. [ ] Cliquer "Annuler" (bouton orange)
8. [ ] Vérifier : cours passe en statut `cancelled`
9. [ ] Vérifier : badge ⚠️ affiché
10. [ ] Vérifier : bouton suppression devient rouge foncé

**Résultat attendu** : ✅ Cours annulé, visible avec badge

#### Test 2 : Suppression Définitive Cours Annulé
**Données** :
- Même cours que Test 1 (maintenant annulé)

**Actions** :
1. [ ] Cliquer sur le cours annulé (badge ⚠️)
2. [ ] Cliquer bouton "Supprimer" (rouge foncé)
3. [ ] Vérifier : modale dit "Ce cours est déjà annulé"
4. [ ] Vérifier : tooltip bouton = "Supprimer définitivement ce cours annulé"
5. [ ] Cliquer "Supprimer définitivement" (bouton rouge)
6. [ ] Vérifier : cours supprimé de la liste
7. [ ] Vérifier : message succès affiché

**Résultat attendu** : ✅ Cours supprimé définitivement de la base

#### Test 3 : Annuler Cours Unique avec Abonnement
**Données** :
- Cours lié à un abonnement
- Pas d'autres cours futurs dans ce créneau
- Statut : confirmed

**Actions** :
1. [ ] Cliquer sur le cours
2. [ ] Cliquer "Supprimer"
3. [ ] Vérifier : modale affiche "0 cours futur" ou "aucune détectée"
4. [ ] Vérifier : option "Toutes séances futures" présente mais à 0
5. [ ] Cliquer "Annuler" (cette séance uniquement)
6. [ ] Vérifier : seul ce cours passe en cancelled

**Résultat attendu** : ✅ Cours annulé, compteur à 0

#### Test 4 : Annuler Toutes Séances Futures (Abonnement Récurrent)
**Pré-requis** :
- Créer un abonnement avec cours récurrents (ex: 5 séances)
- Même jour/horaire/élève/club

**Actions** :
1. [ ] Cliquer sur le 1er cours de la série
2. [ ] Cliquer "Supprimer"
3. [ ] Vérifier : compteur affiche "4 cours futur(s)"
4. [ ] Vérifier : texte "Cette séance et 4 séance(s) future(s)"
5. [ ] Saisir raison : "Test annulation série"
6. [ ] Cliquer "Annuler" dans section "Toutes séances futures"
7. [ ] Vérifier : message "Cours et 4 séance(s) future(s) annulé avec succès"
8. [ ] Vérifier : les 5 cours ont statut `cancelled`
9. [ ] Vérifier : tous ont badge ⚠️

**Résultat attendu** : ✅ Série complète annulée

#### Test 5 : Supprimer Définitivement Toutes Séances Futures
**Données** :
- Série de cours créée dans Test 4 (5 cours annulés)

**Actions** :
1. [ ] Cliquer sur le 1er cours annulé
2. [ ] Cliquer "Supprimer" (rouge foncé)
3. [ ] Vérifier : modale dit "Ce cours est déjà annulé"
4. [ ] Vérifier : compteur "4 cours futur(s) également annulés"
5. [ ] Cliquer "Supprimer définitivement" dans "Toutes séances futures"
6. [ ] Vérifier : message succès
7. [ ] Vérifier : les 5 cours ont disparu de la base
8. [ ] Vérifier : planning n'affiche plus ces cours

**Résultat attendu** : ✅ Série complète supprimée définitivement

#### Test 6 : Filtrage Strict par Créneau
**Pré-requis** :
- Élève avec 2 abonnements :
  - Abonnement A : Lundi 14h-15h (5 cours)
  - Abonnement B : Mercredi 10h-11h (3 cours)

**Actions** :
1. [ ] Supprimer 1er cours Abonnement A (lundi 14h)
2. [ ] Vérifier : compteur affiche 4 (pas 7)
3. [ ] Annuler toutes séances futures
4. [ ] Vérifier : seuls les cours lundi 14h sont annulés
5. [ ] Vérifier : cours mercredi 10h restent confirmed

**Résultat attendu** : ✅ Filtrage correct par créneau (jour + horaire)

#### Test 7 : Cours Sans Subscription_Instances
**Données** :
- Cours créé manuellement (pas via abonnement)
- Pas de subscription_instances

**Actions** :
1. [ ] Cliquer sur le cours
2. [ ] Cliquer "Supprimer"
3. [ ] Vérifier : modale s'ouvre
4. [ ] Vérifier : PAS d'option "Toutes séances futures"
5. [ ] Cliquer "Supprimer définitivement"
6. [ ] Vérifier : cours supprimé

**Résultat attendu** : ✅ Suppression simple sans options récurrence

### Tests d'Erreur

#### Test 8 : API Indisponible
**Actions** :
1. [ ] Simuler erreur API (déconnecter backend temporairement)
2. [ ] Cliquer "Supprimer" sur un cours
3. [ ] Vérifier : compteur à 0 (fallback)
4. [ ] Vérifier : message d'erreur clair
5. [ ] Vérifier : modale reste fonctionnelle

**Résultat attendu** : ✅ Dégradation gracieuse

#### Test 9 : Cours Sans start_time
**Actions** :
1. [ ] Si possible, créer cours avec start_time invalide
2. [ ] Essayer de supprimer
3. [ ] Vérifier : pas de crash
4. [ ] Vérifier : message erreur approprié

**Résultat attendu** : ✅ Gestion erreur sans crash

### Tests Interface

#### Test 10 : Badges Visuels
**Actions** :
1. [ ] Afficher liste cours avec mix confirmed/cancelled
2. [ ] Vérifier : cours cancelled ont badge ⚠️
3. [ ] Vérifier : bouton suppression rouge foncé si cancelled
4. [ ] Vérifier : tooltip "Supprimer définitivement ce cours annulé"

**Résultat attendu** : ✅ Distinction visuelle claire

#### Test 11 : Navigation Entre Options
**Actions** :
1. [ ] Ouvrir modale suppression (cours avec abonnement)
2. [ ] Vérifier : 2 sections visibles (Cette séance / Toutes futures)
3. [ ] Vérifier : 4 boutons au total (2 par section)
4. [ ] Cliquer "Annuler" (bouton gris en bas)
5. [ ] Vérifier : modale se ferme sans action
6. [ ] Vérifier : cours inchangé

**Résultat attendu** : ✅ Navigation modale fluide

### Tests de Régression

#### Test 12 : Création Cours Toujours Fonctionnelle
**Actions** :
1. [ ] Cliquer "Nouveau cours"
2. [ ] Remplir formulaire
3. [ ] Créer cours
4. [ ] Vérifier : cours créé et affiché

**Résultat attendu** : ✅ Création non affectée

#### Test 13 : Édition Cours Toujours Fonctionnelle
**Actions** :
1. [ ] Cliquer sur un cours
2. [ ] Modifier date/heure
3. [ ] Sauvegarder
4. [ ] Vérifier : cours modifié

**Résultat attendu** : ✅ Édition non affectée

#### Test 14 : Navigation Planning Fonctionnelle
**Actions** :
1. [ ] Basculer vue jour/semaine
2. [ ] Naviguer semaine précédente/suivante
3. [ ] Aller à aujourd'hui
4. [ ] Vérifier : affichage correct

**Résultat attendu** : ✅ Navigation non affectée

## Console Logs Attendus

### Lors d'une Suppression avec Abonnement
```
🗑️ [confirmAndDeleteLesson] Demande de suppression pour cours ID: 123
🚀 [checkFutureLessonsForDelete] DÉBUT - Cours ID: 123, start_time: 2026-01-30T14:00:00
🔍 [checkFutureLessonsForDelete] Chargement des détails du cours ID 123
📥 [checkFutureLessonsForDelete] Réponse /lessons/123: {...}
📋 [checkFutureLessonsForDelete] Cours chargé: {id: 123, subscription_instances_count: 1}
✅ [checkFutureLessonsForDelete] Abonnement trouvé: ID 456
🔍 [checkFutureLessonsForDelete] Appel API future-lessons pour abonnement 456
📥 [checkFutureLessonsForDelete] Réponse API future-lessons: {...}
✅ [checkFutureLessonsForDelete] Cours futurs trouvés: 4
```

### Lors de l'Exécution
```
🗑️ [executeDeleteLesson] Exécution - ID: 123, scope: all_future, action: cancel
```

## Métriques de Succès

- [ ] **0 erreur** JavaScript console
- [ ] **0 erreur** linter
- [ ] **Build frontend** réussi
- [ ] **Tous les tests** passent
- [ ] **UX améliorée** (retour utilisateur positif)

## Rollback Plan

Si problème en production :

```bash
# Retour version stable
git checkout main
git revert <commit-improvement>
git push origin main
```

Ou restauration rapide :

```bash
git checkout 547795566 -- frontend/pages/club/planning.vue
git commit -m "revert: rollback amélioration suppression (problème prod)"
git push origin main
```

## Validation Finale

### Avant Merge
- [ ] Tous les tests fonctionnels passent
- [ ] Tests de régression passent
- [ ] Console sans erreurs
- [ ] Build production réussi
- [ ] Review code effectuée
- [ ] Documentation à jour

### Après Merge
- [ ] Déploiement production réussi
- [ ] Tests smoke en production
- [ ] Validation utilisateur (Barbara MURGO)
- [ ] Monitoring 24h sans incidents

---

**Créé** : 28 janvier 2026  
**Status** : 🟡 Prêt pour tests  
**Branche** : feature/improve-lesson-deletion
