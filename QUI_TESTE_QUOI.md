# 🎯 Qui Teste Quoi - Demandes de Remplacement

**Dernière mise à jour**: 24 octobre 2025

---

## 📥 QUI DOIT ACCEPTER/REFUSER QUOI ?

### 🔴 Sophie Rousseau - 2 DEMANDES À TRAITER

**Connexion**:
```
Email: sophie.rousseau@centre-equestre-des-etoiles.fr
Mot de passe: password
```

**Demandes reçues** (elle doit les accepter ou refuser):

#### Demande A
- **De**: Marie Leroy
- **Cours**: Lundi 28/10/2025 à 09:00
- **Raison**: Problème de santé
- **Action**: ⚠️ **SOPHIE doit accepter ou refuser**

#### Demande B
- **De**: Jean Moreau
- **Cours**: Lundi 28/10/2025 à 09:00 (même horaire!)
- **Raison**: Urgence familiale
- **Action**: ⚠️ **SOPHIE doit accepter ou refuser**

---

### 🟡 Jean Moreau - 1 DEMANDE À TRAITER

**Connexion**:
```
Email: jean.moreau@centre-Équestre-des-Étoiles.fr
Mot de passe: password
```

**Demande reçue** (il doit l'accepter ou refuser):

#### Demande C
- **De**: Marie Leroy
- **Cours**: Samedi 26/10/2025 à 09:00
- **Raison**: Rendez-vous médical
- **Action**: ⚠️ **JEAN doit accepter ou refuser**

---

### 🟡 Marie Leroy - 1 DEMANDE À TRAITER

**Connexion**:
```
Email: marie.leroy@centre-Équestre-des-Étoiles.fr
Mot de passe: password
```

**Demande reçue** (elle doit l'accepter ou refuser):

#### Demande D
- **De**: Sophie Rousseau
- **Cours**: Lundi 28/10/2025 à 15:30
- **Raison**: Indisponibilité personnelle
- **Action**: ⚠️ **MARIE doit accepter ou refuser**

---

## 📊 Schéma Visuel des Demandes

```
┌─────────────────────────────────────────────────────────────┐
│                    DEMANDES EN ATTENTE                      │
└─────────────────────────────────────────────────────────────┘

    Marie Leroy ──────────────> Jean Moreau
         │                          │
         │ (RDV médical)            │
         │                          │
         └─────> 📧 Jean reçoit 1 demande


    Marie Leroy ──────────────> Sophie Rousseau
         │                          │
         │ (Santé)                  │
         │                          │
         └─────> 📧 Sophie reçoit demande A


    Jean Moreau ──────────────> Sophie Rousseau
         │                          │
         │ (Urgence)                │
         │                          │
         └─────> 📧 Sophie reçoit demande B


    Sophie Rousseau ──────────> Marie Leroy
         │                          │
         │ (Indisponibilité)        │
         │                          │
         └─────> 📧 Marie reçoit 1 demande


┌─────────────────────────────────────────────────────────────┐
│                    DEMANDE DÉJÀ ACCEPTÉE                    │
└─────────────────────────────────────────────────────────────┘

    Jean Moreau ──────✅──────> Marie Leroy
         │                          │
         │ (Conflit horaire)        │
         │                          │
         └─────> ✅ Marie a ACCEPTÉ (cours transféré)
```

---

## 🧪 Ordre de Test Recommandé

### Test 1: Sophie (la plus chargée)
```
1. Se connecter avec sophie.rousseau@centre-equestre-des-etoiles.fr
2. Voir le bandeau orange: "2 demandes de remplacement en attente"
3. Voir les détails des 2 demandes (Marie et Jean)
4. Accepter la demande de Marie (ou refuser)
5. Accepter la demande de Jean (ou refuser)
6. Vérifier que les cours apparaissent dans sa liste (si acceptés)
```

### Test 2: Jean
```
1. Se connecter avec jean.moreau@centre-Équestre-des-Étoiles.fr
2. Voir le bandeau orange: "1 demande de remplacement en attente"
3. Voir les détails de la demande de Marie
4. Accepter ou refuser
5. Vérifier le résultat
```

### Test 3: Marie
```
1. Se connecter avec marie.leroy@centre-Équestre-des-Étoiles.fr
2. Voir le bandeau orange: "1 demande de remplacement en attente"
3. Voir les détails de la demande de Sophie
4. Accepter ou refuser
5. Vérifier ses demandes envoyées (2 en attente de réponse)
6. Vérifier la demande acceptée par Jean (cours transféré)
```

---

## 🎬 Script de Test Complet (10 minutes)

### Minute 0-3: Sophie accepte tout
```
1. Login Sophie
2. Dashboard → Bandeau "2 demandes"
3. Accepter demande de Marie ✅
4. Accepter demande de Jean ✅
5. Vérifier: Sophie a maintenant 2 nouveaux cours le 28/10
```

### Minute 3-6: Jean refuse
```
1. Login Jean
2. Dashboard → Bandeau "1 demande"
3. Refuser demande de Marie ❌
4. Vérifier: Le cours reste à Marie
```

### Minute 6-10: Marie accepte
```
1. Login Marie
2. Dashboard → Bandeau "1 demande"
3. Accepter demande de Sophie ✅
4. Vérifier: Marie a le cours de Sophie
5. Vérifier: Marie ne voit plus les 2 cours qu'elle avait demandés
   (1 transféré à Sophie, 1 refusé par Jean donc toujours à elle)
```

---

## 📋 Résultat Final Attendu

Après tous les tests (si tout accepté sauf Jean qui refuse):

### Sophie
- ✅ Cours de Marie (28/10 09:00) - REÇU
- ✅ Cours de Jean (28/10 09:00) - REÇU
- ❌ Son propre cours (28/10 15:30) - TRANSFÉRÉ à Marie

### Jean
- ✅ Son cours demandé à Marie (28/10 14:30) - DÉJÀ transféré
- ✅ Son cours demandé à Sophie (28/10 09:00) - TRANSFÉRÉ à Sophie
- ✅ Cours de Marie (26/10 09:00) - REFUSÉ, reste à Marie

### Marie
- ✅ Cours de Jean (28/10 14:30) - REÇU (déjà accepté avant)
- ✅ Cours de Sophie (28/10 15:30) - REÇU
- ❌ Son cours à Jean (26/10 09:00) - REFUSÉ, reste à elle
- ❌ Son cours à Sophie (28/10 09:00) - TRANSFÉRÉ à Sophie

---

## 🔍 Vérification Rapide en DB

```sql
-- Voir toutes les demandes et leur statut
SELECT 
  ut1.name as de,
  ut2.name as vers,
  lr.status,
  DATE_FORMAT(l.start_time, '%d/%m %H:%i') as cours
FROM lesson_replacements lr
INNER JOIN teachers t1 ON lr.original_teacher_id = t1.id
INNER JOIN users ut1 ON t1.user_id = ut1.id
INNER JOIN teachers t2 ON lr.replacement_teacher_id = t2.id
INNER JOIN users ut2 ON t2.user_id = ut2.id
INNER JOIN lessons l ON lr.lesson_id = l.id
ORDER BY lr.id;
```

**Résultat actuel**:
```
de           | vers           | status   | cours
-------------|----------------|----------|------------
Marie Leroy  | Jean Moreau    | pending  | 26/10 09:00
Jean Moreau  | Sophie Rousseau| pending  | 28/10 09:00
Sophie R.    | Marie Leroy    | pending  | 28/10 15:30
Marie Leroy  | Sophie Rousseau| pending  | 28/10 09:00
Jean Moreau  | Marie Leroy    | accepted | 28/10 14:30
```

---

## ✅ Checklist par Enseignant

### Sophie (la plus importante à tester)
- [ ] Se connecter
- [ ] Voir bandeau "2 demandes"
- [ ] Voir détails demande Marie (santé)
- [ ] Voir détails demande Jean (urgence)
- [ ] Cliquer "Accepter" ou "Refuser" pour Marie
- [ ] Cliquer "Accepter" ou "Refuser" pour Jean
- [ ] Vérifier cours dans son tableau
- [ ] Vérifier statistiques mises à jour

### Jean
- [ ] Se connecter
- [ ] Voir bandeau "1 demande"
- [ ] Voir détails demande Marie (RDV médical)
- [ ] Cliquer "Accepter" ou "Refuser"
- [ ] Vérifier résultat

### Marie
- [ ] Se connecter
- [ ] Voir bandeau "1 demande"
- [ ] Voir détails demande Sophie (indisponibilité)
- [ ] Cliquer "Accepter" ou "Refuser"
- [ ] Vérifier ses demandes envoyées (statut)
- [ ] Vérifier cours reçu de Jean (déjà accepté)

---

## 💡 Conseil

**Commencez par Sophie** car elle a le plus de demandes à traiter et c'est elle qui vous donnera le meilleur aperçu du système !

---

**Tous les mots de passe sont "password"** 🔐

