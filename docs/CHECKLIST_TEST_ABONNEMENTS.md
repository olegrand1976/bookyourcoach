# ✅ Checklist de Test - Gestion des Abonnements

## 🔴 Tests Critiques (À faire en premier)

### Création et Affichage
- [ ] **CRITIQUE** : Créer un abonnement avec "cours utilisés" = 5
- [ ] **CRITIQUE** : Vérifier l'affichage dans la liste : **5/11** (pas 0/11)
- [ ] **CRITIQUE** : Ouvrir l'historique : **5/11** (pas 0/11)
- [ ] **CRITIQUE** : Rafraîchir la page : toujours **5/11** (valeur préservée)
- [ ] **CRITIQUE** : Ajouter 1 cours → doit afficher **6/11** (5+1, pas 1)
- [ ] **CRITIQUE** : Ajouter 2 cours supplémentaires → doit afficher **8/11** (5+1+2, pas 3)

### Annulations et Réouvertures
- [ ] **CRITIQUE** : Créer un abonnement 11/11 → doit passer en `completed`
- [ ] **CRITIQUE** : Annuler 1 cours → doit revenir à 10/11 et `active` (réouvert)
- [ ] **CRITIQUE** : Créer un nouveau cours → doit passer à 11/11 et `completed`

### Premier Cours
- [ ] **CRITIQUE** : Créer un abonnement sans cours
- [ ] **CRITIQUE** : Créer le premier cours → `started_at` doit être mise à jour

---

## 🟡 Tests Importants

### Modèles
- [ ] Créer un modèle avec validité en semaines
- [ ] Créer un modèle avec validité en mois
- [ ] Vérifier l'affichage de la validité dans les abonnements (cohérence)

### Abonnements
- [ ] Créer un abonnement familial (plusieurs élèves)
- [ ] Vérifier le filtrage par statut (Normal, Approchant, Urgent)
- [ ] Vérifier la recherche par nom d'élève

### Cours
- [ ] Créer plusieurs cours consécutifs
- [ ] Vérifier l'ordre chronologique (plus vieil abonnement en premier)
- [ ] Créer un cours sans abonnement disponible (pas d'erreur)

---

## 🟢 Tests Complémentaires

### Modèles
- [ ] Modifier un modèle
- [ ] Désactiver un modèle
- [ ] Essayer de supprimer un modèle avec abonnements actifs (doit échouer)

### Abonnements
- [ ] Vérifier l'affichage du prix
- [ ] Vérifier l'affichage des types de cours inclus
- [ ] Vérifier les dates de début et d'expiration

### Cours
- [ ] Annuler plusieurs cours
- [ ] Supprimer un cours
- [ ] Créer un cours avec type non inclus dans l'abonnement

---

## 📝 Notes de Test

**Date** : _______________
**Testeur** : _______________
**Version** : _______________

### Bugs trouvés :
1. 
2. 
3. 

### Observations :
- 
- 
- 

---

## 🎯 Scénario Complet à Tester

1. [ ] Créer modèle (10 cours, 1 gratuit, 12 semaines)
2. [ ] Créer abonnement avec 3 cours utilisés → **3/11**
3. [ ] Créer 5 cours → **8/11**
4. [ ] Annuler 2 cours → **6/11**
5. [ ] Créer 3 cours → **9/11**
6. [ ] Créer 2 cours → **11/11** → `completed`
7. [ ] Annuler 1 cours → **10/11** → `active` (réouvert)
8. [ ] Créer 1 cours → **11/11** → `completed`

**Résultat attendu** : Toutes les étapes fonctionnent sans erreur ✅

---

## 🔍 Vérifications Finales

- [ ] Aucune erreur dans les logs Laravel
- [ ] Tous les compteurs sont cohérents
- [ ] Les statuts sont corrects
- [ ] Les dates sont correctes
- [ ] L'affichage est cohérent entre liste et historique

---

**Statut Global** : ⬜ En cours | ⬜ Réussi | ⬜ Échec

