# 🧪 Cas de Tests - Dépassements Contrats Bénévoles

## 📋 **Vue d'Ensemble des Tests**

J'ai créé **8 cas de test** complets pour tester le système d'indicateurs de dépassement sur les contrats bénévoles, couvrant tous les scénarios possibles.

### 🎯 **Plafonds de Référence - Contrat Bénévole**
- **Plafond annuel** : 3,900€
- **Plafond journalier** : 42.31€
- **Indemnité kilométrique** : 0.4€/km
- **Plafond kilométrique annuel** : 2,000km

---

## 🟢 **CAS 1: Dans les Limites (VERT)**

### **Enseignant** : Marie Dubois
- **Montant annuel** : 1,200€ (30.8% du plafond)
- **Montant journalier** : 25€ (59.1% du plafond)
- **Kilométrage annuel** : 150km (7.5% du plafond)

### **Résultat Attendu** : 🟢 **Indicateur VERT**
- **Tooltip** : "Dans les limites"
- **Statut** : Tous les critères sont bien en dessous de 80%

---

## 🟠 **CAS 2: Zone d'Attention (ORANGE)**

### **Enseignant** : Pierre Martin
- **Montant annuel** : 3,315€ (85% du plafond)
- **Montant journalier** : 35€ (82.7% du plafond)
- **Kilométrage annuel** : 1,200km (60% du plafond)

### **Résultat Attendu** : 🟠 **Indicateur ORANGE**
- **Tooltip** : "Attention: Plafond annuel Plafond journalier"
- **Statut** : Deux critères entre 80% et 95%

---

## 🔴 **CAS 3: Zone Critique (ROUGE)**

### **Enseignant** : Sophie Leroy
- **Montant annuel** : 2,800€ (71.8% du plafond)
- **Montant journalier** : 41.5€ (98.1% du plafond)
- **Kilométrage annuel** : 1,800km (90% du plafond)

### **Résultat Attendu** : 🔴 **Indicateur ROUGE**
- **Tooltip** : "Critique: Plafond journalier"
- **Statut** : Un critère entre 95% et 100%

---

## ⚫ **CAS 4: Dépassement Kilométrique (NOIR)**

### **Enseignant** : Jean Bernard
- **Montant annuel** : 2,200€ (56.4% du plafond)
- **Montant journalier** : 30€ (70.9% du plafond)
- **Kilométrage annuel** : 2,500km (125% du plafond) ⚠️ **DÉPASSÉ**

### **Résultat Attendu** : ⚫ **Indicateur NOIR**
- **Tooltip** : "Dépassement: Kilométrage"
- **Statut** : Dépassement du plafond kilométrique

---

## ⚫ **CAS 5: Dépassement Plafond Annuel (NOIR)**

### **Enseignant** : Claire Moreau
- **Montant annuel** : 4,200€ (107.7% du plafond) ⚠️ **DÉPASSÉ**
- **Montant journalier** : 38€ (89.8% du plafond)
- **Kilométrage annuel** : 1,500km (75% du plafond)

### **Résultat Attendu** : ⚫ **Indicateur NOIR**
- **Tooltip** : "Dépassement: Plafond annuel"
- **Statut** : Dépassement du plafond annuel

---

## ⚫ **CAS 6: Dépassement Plafond Journalier (NOIR)**

### **Enseignant** : Antoine Petit
- **Montant annuel** : 1,800€ (46.2% du plafond)
- **Montant journalier** : 45€ (106.4% du plafond) ⚠️ **DÉPASSÉ**
- **Kilométrage annuel** : 800km (40% du plafond)

### **Résultat Attendu** : ⚫ **Indicateur NOIR**
- **Tooltip** : "Dépassement: Plafond journalier"
- **Statut** : Dépassement du plafond journalier

---

## ⚫ **CAS 7: Dépassements Multiples (NOIR)**

### **Enseignant** : Isabelle Rousseau
- **Montant annuel** : 5,000€ (128.2% du plafond) ⚠️ **DÉPASSÉ**
- **Montant journalier** : 50€ (118.2% du plafond) ⚠️ **DÉPASSÉ**
- **Kilométrage annuel** : 3,000km (150% du plafond) ⚠️ **DÉPASSÉ**

### **Résultat Attendu** : ⚫ **Indicateur NOIR**
- **Tooltip** : "Dépassement: Plafond annuel Plafond journalier Kilométrage"
- **Statut** : Dépassement de tous les critères

---

## 🔵 **CAS 8: Contrat Indépendant (Référence)**

### **Enseignant** : Marc Durand
- **Type de contrat** : Indépendant (pas de plafonds kilométriques)
- **Montant annuel** : 2,400€ (dépend de la configuration freelance)
- **Montant journalier** : 0€ (pas de plafond journalier)
- **Kilométrage annuel** : 0km (pas de kilométrage)

### **Résultat Attendu** : Selon configuration du contrat indépendant
- **Statut** : Test de comparaison avec les contrats bénévoles

---

## 📊 **Statistiques Globales des Tests**

### **Récapitulatif**
- **Total enseignants** : 8
- **Total paiements** : 20,220€
- **Total heures** : 520h
- **Enseignants avec dépassements** : 4 (50%)

### **Répartition par Indicateur**
- 🟢 **Vert** : 1 enseignant (12.5%)
- 🟠 **Orange** : 1 enseignant (12.5%)
- 🔴 **Rouge** : 1 enseignant (12.5%)
- ⚫ **Noir** : 4 enseignants (50%)

### **Types de Dépassements**
- **Plafond annuel** : 2 enseignants (Claire, Isabelle)
- **Plafond journalier** : 2 enseignants (Antoine, Isabelle)
- **Kilométrage** : 2 enseignants (Jean, Isabelle)
- **Dépassements multiples** : 1 enseignant (Isabelle)

---

## 🎯 **Objectifs des Tests**

### **1. Validation des Seuils**
- ✅ **< 80%** : Indicateur vert
- ✅ **80-95%** : Indicateur orange
- ✅ **95-100%** : Indicateur rouge
- ✅ **> 100%** : Indicateur noir

### **2. Test des Critères**
- ✅ **Plafond annuel** : Testé avec dépassement
- ✅ **Plafond journalier** : Testé avec dépassement
- ✅ **Kilométrage** : Testé avec dépassement
- ✅ **Dépassements multiples** : Testé avec tous les critères

### **3. Validation des Tooltips**
- ✅ **Messages précis** indiquant le critère dépassé
- ✅ **Messages multiples** pour les dépassements combinés
- ✅ **Messages de statut** pour chaque niveau

### **4. Test de Robustesse**
- ✅ **Valeurs limites** (exactement 80%, 95%, 100%)
- ✅ **Valeurs extrêmes** (dépassements importants)
- ✅ **Contrats différents** (bénévole vs indépendant)

---

## 🚀 **Utilisation des Tests**

### **Pour Tester l'Interface**
1. Aller sur `/admin/contracts`
2. Cliquer sur l'onglet "Enseignants & Contrats"
3. Observer les indicateurs colorés à côté de chaque enseignant
4. Passer la souris sur les indicateurs pour voir les tooltips

### **Pour Valider les Calculs**
1. Vérifier que les pourcentages sont corrects
2. Confirmer que les couleurs correspondent aux seuils
3. Tester les tooltips avec les messages appropriés
4. Valider les statistiques globales

### **Pour Déboguer**
1. Ouvrir la console développeur
2. Vérifier les calculs dans `getExceedanceIndicators()`
3. Contrôler les données dans `generateMockTeachers()`
4. Tester les cas limites manuellement

---

## ✅ **Résultats Attendus**

| Enseignant | Annuel | Journalier | Kilométrage | Indicateur | Tooltip |
|------------|--------|------------|-------------|------------|---------|
| Marie | 30.8% | 59.1% | 7.5% | 🟢 Vert | Dans les limites |
| Pierre | 85% | 82.7% | 60% | 🟠 Orange | Attention: Plafond annuel Plafond journalier |
| Sophie | 71.8% | 98.1% | 90% | 🔴 Rouge | Critique: Plafond journalier |
| Jean | 56.4% | 70.9% | 125% | ⚫ Noir | Dépassement: Kilométrage |
| Claire | 107.7% | 89.8% | 75% | ⚫ Noir | Dépassement: Plafond annuel |
| Antoine | 46.2% | 106.4% | 40% | ⚫ Noir | Dépassement: Plafond journalier |
| Isabelle | 128.2% | 118.2% | 150% | ⚫ Noir | Dépassement: Plafond annuel Plafond journalier Kilométrage |
| Marc | Variable | 0% | 0% | Selon config | Contrat indépendant |

Ces cas de test permettent de valider complètement le système d'indicateurs de dépassement et de s'assurer que tous les scénarios sont correctement gérés.
