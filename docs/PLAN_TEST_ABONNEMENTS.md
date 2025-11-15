# Plan de Test Complet - Gestion des Abonnements

## 📋 Objectif
Vérifier l'absence de régression dans la gestion des modèles d'abonnements, des abonnements et des cours après les corrections apportées.

## 🎯 Scopes de Test

### 1. Gestion des Modèles d'Abonnements (Templates)
### 2. Gestion des Abonnements (Instances)
### 3. Gestion des Cours et Liaison avec les Abonnements
### 4. Gestion des Annulations et Réouvertures

---

## 1. GESTION DES MODÈLES D'ABONNEMENTS

### 1.1 Création de Modèle

#### Test 1.1.1 : Création basique
- **Prérequis** : Utilisateur club connecté, disciplines configurées
- **Actions** :
  1. Aller sur `/club/subscription-templates`
  2. Cliquer sur "Nouveau Modèle"
  3. Remplir le formulaire :
     - Nombre de cours : 10
     - Cours gratuits : 1
     - Prix : 180€
     - Validité : 12 semaines
     - Types de cours : Sélectionner au moins 1 type
     - Statut actif : ✅
  4. Cliquer sur "Créer"
- **Résultat attendu** :
  - ✅ Modèle créé avec succès
  - ✅ Numéro de modèle généré automatiquement (format MOD-XX-...)
  - ✅ Modèle visible dans la liste
  - ✅ Modèle actif et utilisable

#### Test 1.1.2 : Création avec validité en mois
- **Actions** : Même que 1.1.1 mais avec validité en mois (ex: 3 mois)
- **Résultat attendu** :
  - ✅ Validité correctement enregistrée en mois
  - ✅ Affichage correct dans la liste

#### Test 1.1.3 : Validation des champs obligatoires
- **Actions** : Essayer de créer un modèle sans remplir les champs obligatoires
- **Résultat attendu** :
  - ✅ Messages d'erreur de validation affichés
  - ✅ Impossible de créer le modèle

#### Test 1.1.4 : Validation du nombre de cours
- **Actions** : Créer un modèle avec 0 cours ou nombre négatif
- **Résultat attendu** :
  - ✅ Erreur de validation
  - ✅ Impossible de créer

### 1.2 Modification de Modèle

#### Test 1.2.1 : Modification basique
- **Prérequis** : Modèle existant
- **Actions** :
  1. Cliquer sur "Modifier" sur un modèle
  2. Modifier le prix (ex: 200€)
  3. Sauvegarder
- **Résultat attendu** :
  - ✅ Modifications sauvegardées
  - ✅ Affichage mis à jour dans la liste

#### Test 1.2.2 : Modification des types de cours
- **Actions** : Modifier les types de cours inclus
- **Résultat attendu** :
  - ✅ Types de cours mis à jour
  - ✅ Vérifier que les abonnements existants ne sont pas affectés

#### Test 1.2.3 : Désactiver un modèle
- **Actions** : Décocher "Modèle actif"
- **Résultat attendu** :
  - ✅ Modèle marqué comme inactif
  - ✅ Impossible de créer de nouveaux abonnements avec ce modèle
  - ✅ Les abonnements existants restent fonctionnels

### 1.3 Suppression de Modèle

#### Test 1.3.1 : Suppression sans abonnements actifs
- **Prérequis** : Modèle sans abonnements actifs
- **Actions** : Supprimer le modèle
- **Résultat attendu** :
  - ✅ Modèle supprimé avec succès
  - ✅ Disparu de la liste

#### Test 1.3.2 : Suppression avec abonnements actifs
- **Prérequis** : Modèle avec abonnements actifs
- **Actions** : Essayer de supprimer le modèle
- **Résultat attendu** :
  - ✅ Erreur : "Impossible de supprimer ce modèle car des abonnements l'utilisent"
  - ✅ Modèle non supprimé

---

## 2. GESTION DES ABONNEMENTS

### 2.1 Création d'Abonnement

#### Test 2.1.1 : Création basique depuis un modèle
- **Prérequis** : Modèle actif, au moins 1 élève
- **Actions** :
  1. Aller sur `/club/subscriptions`
  2. Cliquer sur "Créer un Abonnement"
  3. Sélectionner un élève
  4. Sélectionner un modèle
  5. Vérifier la date de début (aujourd'hui par défaut)
  6. Vérifier la date d'expiration (calculée automatiquement)
  7. **IMPORTANT** : Entrer "Nombre de cours déjà utilisés" : 5
  8. Cliquer sur "Assigner"
- **Résultat attendu** :
  - ✅ Abonnement créé avec succès
  - ✅ Numéro d'abonnement généré
  - ✅ Affichage : **5/11** (et non 0/11)
  - ✅ Date de début : Aujourd'hui (sera mise à jour au premier cours)
  - ✅ Date d'expiration : Calculée correctement

#### Test 2.1.1b : **CRITIQUE** - Ajout de cours avec valeur manuelle
- **Prérequis** : Abonnement créé avec "cours utilisés" = 5 (affichage 5/11)
- **Actions** :
  1. Créer un cours pour l'élève de cet abonnement
  2. Attendre le traitement asynchrone (job)
  3. Vérifier l'affichage de l'abonnement
- **Résultat attendu** :
  - ✅ **CRITIQUE** : Affichage : **6/11** (5 manuel + 1 nouveau cours)
  - ✅ **PAS** : 1/11 (valeur manuelle écrasée)
  - ✅ Cours visible dans l'historique
  - ✅ Logs montrent "incrémentation directe"

#### Test 2.1.2 : Création avec date de début personnalisée
- **Actions** : Créer un abonnement avec une date de début future
- **Résultat attendu** :
  - ✅ Date de début respectée
  - ✅ Date d'expiration calculée depuis cette date

#### Test 2.1.3 : Création d'abonnement familial
- **Actions** : Créer un abonnement avec plusieurs élèves
- **Résultat attendu** :
  - ✅ Tous les élèves attachés à l'abonnement
  - ✅ Abonnement partagé visible pour tous les élèves

#### Test 2.1.4 : Validation du nombre de cours utilisés
- **Actions** : Essayer de créer un abonnement avec "cours utilisés" > total disponible
- **Résultat attendu** :
  - ✅ Erreur de validation
  - ✅ Impossible de créer

### 2.2 Affichage des Abonnements

#### Test 2.2.1 : Liste des abonnements
- **Actions** : Consulter `/club/subscriptions`
- **Résultat attendu** :
  - ✅ Tous les abonnements affichés
  - ✅ Affichage correct : X/Y cours utilisés
  - ✅ Statut visible (Actif, Terminé, Expiré)
  - ✅ Validité affichée dans le bon format (semaines ou mois selon le modèle)

#### Test 2.2.2 : Filtrage par statut
- **Actions** : Utiliser le filtre par statut (Normal, Approchant, Urgent)
- **Résultat attendu** :
  - ✅ Filtrage correct selon le pourcentage d'utilisation
  - ✅ Tri par urgence (urgent en premier)

#### Test 2.2.3 : Recherche par nom d'élève
- **Actions** : Rechercher un élève par nom/prénom
- **Résultat attendu** :
  - ✅ Filtrage correct
  - ✅ Affichage uniquement des abonnements de cet élève

#### Test 2.2.4 : Historique d'un abonnement
- **Actions** : Cliquer sur une carte d'abonnement
- **Résultat attendu** :
  - ✅ Modal d'historique s'ouvre
  - ✅ Affichage : **5/11** (et non 0/11) ✅ **CRITIQUE**
  - ✅ Liste des cours consommés
  - ✅ Détails de chaque instance

### 2.3 Valeurs Manuelles

#### Test 2.3.1 : Préservation de la valeur manuelle
- **Prérequis** : Abonnement créé avec "cours utilisés" = 5
- **Actions** :
  1. Créer l'abonnement avec 5 cours utilisés
  2. Rafraîchir la page
  3. Vérifier l'affichage
- **Résultat attendu** :
  - ✅ Toujours **5/11** (valeur préservée)
  - ✅ Pas de retour à 0/11

#### Test 2.3.2 : Affichage dans l'historique
- **Actions** : Ouvrir l'historique d'un abonnement avec valeur manuelle
- **Résultat attendu** :
  - ✅ Affichage correct : **5/11** dans l'historique
  - ✅ Pas de 0/11

---

## 3. GESTION DES COURS ET LIAISON AVEC ABONNEMENTS

### 3.1 Création de Cours

#### Test 3.1.1 : Liaison automatique à un abonnement
- **Prérequis** : 
  - Abonnement actif pour un élève
  - Type de cours correspondant
- **Actions** :
  1. Créer un cours pour cet élève avec le type de cours de l'abonnement
  2. Attendre le traitement asynchrone (job)
- **Résultat attendu** :
  - ✅ Cours automatiquement lié à l'abonnement
  - ✅ `lessons_used` incrémenté : **6/11** (5 initial + 1 nouveau)
  - ✅ Cours visible dans l'historique de l'abonnement

#### Test 3.1.2 : Premier cours et date de début
- **Prérequis** : Abonnement créé mais aucun cours encore pris
- **Actions** :
  1. Créer le premier cours pour cet élève
  2. Vérifier la date de début de l'abonnement
- **Résultat attendu** :
  - ✅ `started_at` mise à jour avec la date du premier cours
  - ✅ Date d'expiration recalculée depuis cette nouvelle date

#### Test 3.1.3 : Plusieurs cours consécutifs
- **Actions** : Créer plusieurs cours pour le même élève
- **Résultat attendu** :
  - ✅ Chaque cours lié à l'abonnement
  - ✅ `lessons_used` incrémenté correctement
  - ✅ Ordre chronologique respecté (plus vieil abonnement utilisé en premier)

#### Test 3.1.4 : Cours sans abonnement disponible
- **Prérequis** : Élève sans abonnement actif
- **Actions** : Créer un cours
- **Résultat attendu** :
  - ✅ Cours créé mais non lié à un abonnement
  - ✅ Pas d'erreur

#### Test 3.1.5 : Cours avec type non inclus dans l'abonnement
- **Prérequis** : Abonnement actif mais type de cours différent
- **Actions** : Créer un cours avec un type non inclus
- **Résultat attendu** :
  - ✅ Cours créé mais non lié à l'abonnement
  - ✅ Pas d'erreur

### 3.2 Consommation d'Abonnement

#### Test 3.2.1 : Abonnement qui atteint 100%
- **Prérequis** : Abonnement avec 10/11 cours utilisés
- **Actions** : Créer un nouveau cours
- **Résultat attendu** :
  - ✅ Cours lié : **11/11**
  - ✅ Abonnement passe automatiquement en `completed`
  - ✅ Abonnement archivé

#### Test 3.2.2 : Tentative de cours sur abonnement plein
- **Prérequis** : Abonnement `completed` (11/11)
- **Actions** : Essayer de créer un nouveau cours
- **Résultat attendu** :
  - ✅ Cours créé mais non lié (abonnement plein)
  - ✅ Pas d'erreur système

---

## 4. GESTION DES ANNULATIONS ET RÉOUVERTURES

### 4.1 Annulation de Cours

#### Test 4.1.1 : Annulation simple avec valeur manuelle
- **Prérequis** : Abonnement avec valeur manuelle 5 + 1 cours = 6/11
- **Actions** :
  1. Annuler le cours (statut → cancelled)
  2. Vérifier l'abonnement
- **Résultat attendu** :
  - ✅ Cours détaché de l'abonnement
  - ✅ **CRITIQUE** : `lessons_used` décrémenté : **5/11** (6 - 1 = 5, valeur manuelle préservée)
  - ✅ **PAS** : 0/11 (valeur manuelle écrasée)
  - ✅ Cours annulé non compté dans `lessons_used`
  - ✅ Logs montrent "décrémentation directe"

#### Test 4.1.2 : Annulation d'un cours sur abonnement completed
- **Prérequis** : Abonnement `completed` (11/11)
- **Actions** :
  1. Annuler un cours de cet abonnement
- **Résultat attendu** :
  - ✅ Cours détaché
  - ✅ `lessons_used` : **10/11**
  - ✅ **CRITIQUE** : Abonnement réouvert automatiquement (`completed` → `active`)
  - ✅ Abonnement réutilisable pour de nouveaux cours

#### Test 4.1.3 : Nouveau cours après annulation
- **Prérequis** : Abonnement réouvert après annulation (10/11)
- **Actions** : Créer un nouveau cours
- **Résultat attendu** :
  - ✅ Cours lié à l'abonnement
  - ✅ `lessons_used` : **11/11**
  - ✅ Abonnement repasse en `completed`

#### Test 4.1.4 : Annulation de plusieurs cours
- **Actions** : Annuler plusieurs cours d'un même abonnement
- **Résultat attendu** :
  - ✅ Tous les cours annulés détachés
  - ✅ `lessons_used` recalculé correctement
  - ✅ Réouverture si nécessaire

### 4.2 Suppression de Cours

#### Test 4.2.1 : Suppression simple
- **Prérequis** : Cours lié à un abonnement
- **Actions** : Supprimer le cours
- **Résultat attendu** :
  - ✅ Cours détaché de l'abonnement
  - ✅ `lessons_used` recalculé
  - ✅ Comportement identique à l'annulation

#### Test 4.2.2 : Suppression sur abonnement completed
- **Prérequis** : Abonnement `completed`
- **Actions** : Supprimer un cours
- **Résultat attendu** :
  - ✅ Abonnement réouvert (`completed` → `active`)
  - ✅ Réutilisable

### 4.3 Réouverture Automatique

#### Test 4.3.1 : Réouverture après annulation
- **Prérequis** : Abonnement `completed` (11/11)
- **Actions** :
  1. Annuler 1 cours
  2. Vérifier le statut
- **Résultat attendu** :
  - ✅ Statut : `active` (réouvert)
  - ✅ `lessons_used` : 10/11
  - ✅ Logs de réouverture présents

#### Test 4.3.2 : Réouverture après suppression
- **Actions** : Même scénario mais avec suppression
- **Résultat attendu** : Identique à 4.3.1

#### Test 4.3.3 : Nouveau cours sur abonnement réouvert
- **Prérequis** : Abonnement réouvert (10/11)
- **Actions** : Créer un nouveau cours
- **Résultat attendu** :
  - ✅ Cours lié sans problème
  - ✅ Abonnement peut consommer le cours
  - ✅ Pas d'erreur "abonnement completed"

---

## 5. SCÉNARIOS COMPLEXES

### 5.1 Scénario Complet : Cycle de Vie d'un Abonnement

#### Test 5.1.1 : Cycle complet avec valeur manuelle
- **Actions** :
  1. Créer un modèle (10 cours, 1 gratuit = 11 total)
  2. Créer un abonnement avec 5 cours déjà utilisés → **5/11** ✅
  3. Créer 1 cours → **6/11** (5+1, pas 1) ✅ **CRITIQUE**
  4. Créer 2 cours supplémentaires → **8/11** (5+1+2, pas 3) ✅
  5. Annuler 1 cours → **7/11** (8-1, pas 0) ✅ **CRITIQUE**
  6. Créer 2 cours → **9/11** (7+2)
  7. Créer 2 cours → **11/11** → `completed`
  8. Annuler 1 cours → **10/11** → `active` (réouvert) ✅
  9. Créer 1 cours → **11/11** → `completed`
- **Résultat attendu** :
  - ✅ Toutes les transitions fonctionnent correctement
  - ✅ **CRITIQUE** : Valeur manuelle préservée à chaque étape
  - ✅ Compteurs toujours cohérents (valeur manuelle + cours attachés - cours annulés)
  - ✅ Statuts corrects à chaque étape
  - ✅ Logs montrent "incrémentation directe" et "décrémentation directe"

### 5.2 Scénario Multi-Élèves

#### Test 5.2.1 : Abonnement familial
- **Prérequis** : Abonnement avec 2 élèves
- **Actions** :
  1. Créer des cours pour élève 1
  2. Créer des cours pour élève 2
- **Résultat attendu** :
  - ✅ Tous les cours comptabilisés dans le même abonnement
  - ✅ `lessons_used` reflète tous les cours des deux élèves

### 5.3 Scénario Multi-Abonnements

#### Test 5.3.1 : Plusieurs abonnements actifs
- **Prérequis** : Élève avec 2 abonnements actifs
- **Actions** : Créer plusieurs cours
- **Résultat attendu** :
  - ✅ Ordre chronologique respecté (plus vieil abonnement utilisé en premier)
  - ✅ Premier abonnement se remplit avant le second

---

## 6. TESTS DE RÉGRESSION

### 6.1 Affichage et Format

#### Test 6.1.1 : Format de validité
- **Actions** : Vérifier l'affichage de la validité dans les cartes
- **Résultat attendu** :
  - ✅ Semaines affichées si modèle en semaines
  - ✅ Mois affichés si modèle en mois
  - ✅ Cohérence entre modèle et abonnement

#### Test 6.1.2 : Calcul du prix par cours
- **Actions** : Vérifier l'affichage du prix par cours dans les modèles
- **Résultat attendu** :
  - ✅ Calcul correct : prix total / nombre de cours

### 6.2 Performance

#### Test 6.2.1 : Chargement de la liste
- **Actions** : Charger la liste avec beaucoup d'abonnements
- **Résultat attendu** :
  - ✅ Chargement rapide (< 2 secondes)
  - ✅ Pas d'erreur

#### Test 6.2.2 : Recalcul automatique
- **Actions** : Vérifier que les recalculs ne sont pas trop fréquents
- **Résultat attendu** :
  - ✅ Pas de boucle infinie
  - ✅ Logs propres

### 6.3 Logs et Debug

#### Test 6.3.1 : Vérification des logs
- **Actions** : Consulter les logs Laravel après les tests
- **Résultat attendu** :
  - ✅ Logs clairs et informatifs
  - ✅ Pas d'erreurs critiques
  - ✅ Traces des réouvertures, détachements, etc.

---

## 7. CHECKLIST DE VALIDATION FINALE

### ✅ Fonctionnalités Critiques
- [ ] Création de modèle fonctionne
- [ ] Création d'abonnement avec valeur manuelle fonctionne
- [ ] Valeur manuelle préservée après rafraîchissement
- [ ] Affichage correct dans la liste : X/Y
- [ ] Affichage correct dans l'historique : X/Y (pas 0/Y)
- [ ] Liaison automatique des cours fonctionne
- [ ] Date de début mise à jour au premier cours
- [ ] Clôture automatique à 100% fonctionne
- [ ] Annulation détache le cours
- [ ] Réouverture automatique fonctionne
- [ ] Nouveau cours après réouverture fonctionne

### ✅ Format et Affichage
- [ ] Validité affichée correctement (semaines/mois)
- [ ] Prix calculé correctement
- [ ] Filtres fonctionnent
- [ ] Recherche fonctionne

### ✅ Edge Cases
- [ ] Abonnement sans cours
- [ ] Abonnement avec valeur manuelle = 0
- [ ] Abonnement avec valeur manuelle = total
- [ ] Annulation de tous les cours
- [ ] Plusieurs annulations consécutives

---

## 8. ORDRE DE PRIORITÉ DES TESTS

### 🔴 Priorité CRITIQUE (À tester en premier)
1. Test 2.1.1 : Création avec valeur manuelle
2. Test 2.2.4 : Affichage dans l'historique (5/11 et non 0/11)
3. Test 2.3.1 : Préservation de la valeur manuelle
4. Test 4.1.2 : Réouverture après annulation
5. Test 4.3.1 : Réouverture automatique

### 🟡 Priorité HAUTE
6. Test 3.1.2 : Premier cours et date de début
7. Test 3.2.1 : Clôture automatique
8. Test 4.1.1 : Annulation simple
9. Test 5.1.1 : Cycle complet

### 🟢 Priorité MOYENNE
10. Tous les autres tests de création/modification
11. Tests de filtrage et recherche
12. Tests multi-élèves/multi-abonnements

---

## 9. DONNÉES DE TEST RECOMMANDÉES

### Modèles à créer
- Modèle A : 10 cours, 1 gratuit, 180€, 12 semaines
- Modèle B : 5 cours, 0 gratuit, 100€, 3 mois
- Modèle C : 20 cours, 2 gratuits, 300€, 24 semaines

### Abonnements à créer
- Abonnement 1 : Modèle A, élève 1, 0 cours utilisés
- Abonnement 2 : Modèle A, élève 2, 5 cours utilisés
- Abonnement 3 : Modèle B, élève 1, 0 cours utilisés
- Abonnement 4 : Modèle A, élève 3, 10 cours utilisés (presque plein)

### Cours à créer
- Cours pour élève 1 avec type de Modèle A
- Cours pour élève 2 avec type de Modèle A
- Cours pour élève 3 avec type de Modèle A
- Cours avec type différent (non inclus)

---

## 10. RAPPORT DE TEST

### Template de Rapport
```
Date : [DATE]
Testeur : [NOM]
Version : [VERSION]

Résultats :
- Tests Critiques : X/Y réussis
- Tests Hauts : X/Y réussis
- Tests Moyens : X/Y réussis

Bugs trouvés :
1. [Description]
2. [Description]

Recommandations :
- [Recommandation]
```

---

## 📝 Notes Importantes

1. **Tester dans l'ordre de priorité** pour identifier rapidement les régressions critiques
2. **Vérifier les logs** après chaque test important
3. **Documenter les bugs** avec captures d'écran si possible
4. **Tester sur données réelles** après tests sur données de test
5. **Vérifier la cohérence** entre frontend et backend

---

**Dernière mise à jour** : 2025-11-15
**Version** : 1.0

