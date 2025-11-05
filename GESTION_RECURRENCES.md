# 📅 GESTION DES CRÉNEAUX RÉCURRENTS - RÉSERVATIONS FLEXIBLES

## 🎯 PRINCIPE FONDAMENTAL

Les créneaux récurrents sont des **RÉSERVATIONS FLEXIBLES**, pas des blocages durs.

### ✅ Ce qu'ils FONT :
- **Réservent** un créneau pour un élève avec un abonnement actif
- **Avertissent** s'il y a un conflit potentiel
- **Se gèrent automatiquement** avec le cycle de vie de l'abonnement

### ❌ Ce qu'ils NE FONT PAS :
- Ils n'**empêchent PAS** la création d'autres cours
- Ils ne **bloquent PAS** définitivement le créneau
- Ils ne sont **pas rigides** - ils peuvent être libérés manuellement

---

## 🔄 COMPORTEMENT AUTOMATIQUE

### 1️⃣ **Création Automatique**

Quand un cours est créé pour un élève avec un abonnement actif :

```
✅ Réservation créée automatiquement pour 6 mois (ou fin d'abonnement)
⚠️ Conflits détectés si enseignant ou élève déjà occupé
📝 Log avec possibilité de libération manuelle
```

**Exemple :**
```
Gabriel Moreau a un abonnement actif
➡️ Création d'un cours le Mercredi 13 Nov à 14:00
➡️ Réservation automatique : Tous les mercredis 14:00
➡️ Du 13 Nov 2025 au 13 Mai 2026 (6 mois)
```

---

### 2️⃣ **Annulation Automatique**

Quand un abonnement se termine :

```
✅ Si abonnement passe à 'completed' → Annuler récurrences
✅ Si abonnement passe à 'cancelled' → Annuler récurrences
✅ Si abonnement passe à 'expired' → Annuler récurrences
✅ Si abonnement supprimé → Annuler récurrences
```

**Exemple :**
```
Gabriel termine son abonnement le 15 Déc
➡️ Toutes ses récurrences passent automatiquement à 'cancelled'
➡️ Les créneaux sont libérés pour d'autres élèves
```

---

### 3️⃣ **Prolongation Automatique**

Quand un abonnement est prolongé :

```
✅ Si expires_at est prolongé → Mettre à jour end_date des récurrences
⚠️ Maximum 6 mois depuis start_date de la récurrence
```

**Exemple :**
```
Gabriel renouvelle son abonnement jusqu'au 30 Juin
➡️ Les récurrences sont automatiquement prolongées
➡️ Nouvelle end_date : 30 Juin (ou 6 mois max)
```

---

## 🛠️ GESTION MANUELLE VIA API

### **Endpoints Disponibles**

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/club/recurring-slots` | GET | Liste des créneaux récurrents |
| `/club/recurring-slots/{id}` | GET | Détails d'un créneau |
| `/club/recurring-slots/{id}/release` | POST | Libérer un créneau |
| `/club/recurring-slots/{id}/reactivate` | POST | Réactiver un créneau |

---

### **1. Lister les Créneaux Récurrents**

```bash
GET /club/recurring-slots
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "subscription_instance_id": 42,
      "teacher_id": 3,
      "student_id": 54,
      "day_of_week": 3,
      "start_time": "14:00:00",
      "end_time": "15:00:00",
      "start_date": "2025-11-13",
      "end_date": "2026-05-13",
      "status": "active",
      "notes": "Créneau RÉSERVÉ automatiquement..."
    }
  ]
}
```

---

### **2. Libérer un Créneau Manuellement**

**Cas d'usage :** On sait que l'abonnement va se terminer, ou on veut libérer le créneau

```bash
POST /club/recurring-slots/{id}/release
Content-Type: application/json

{
  "reason": "L'abonnement se termine fin décembre"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Créneau libéré avec succès",
  "data": {
    "id": 1,
    "status": "cancelled",
    "notes": "... Annulé : Libération manuelle du créneau - L'abonnement se termine fin décembre"
  }
}
```

**Log généré :**
```
🔓 Créneau récurrent libéré manuellement
- recurring_slot_id: 1
- subscription_instance_id: 42
- club_id: 1
- user_id: 5
- reason: L'abonnement se termine fin décembre
```

---

### **3. Réactiver un Créneau**

**Cas d'usage :** On avait libéré un créneau par erreur, ou l'abonnement a été prolongé

```bash
POST /club/recurring-slots/{id}/reactivate
Content-Type: application/json

{
  "reason": "Abonnement finalement prolongé"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Créneau réactivé avec succès",
  "data": {
    "id": 1,
    "status": "active",
    "notes": "... Réactivé : Abonnement finalement prolongé"
  }
}
```

**Log généré :**
```
🔄 Créneau récurrent réactivé manuellement
- recurring_slot_id: 1
- subscription_instance_id: 42
- club_id: 1
- user_id: 5
- reason: Abonnement finalement prolongé
```

---

## ⚠️ DÉTECTION DE CONFLITS

### **Types de Conflits Détectés**

#### 1️⃣ **Conflit Enseignant**
```
❌ L'enseignant a déjà un créneau récurrent avec un autre élève
```

**Exemple :**
```
Prof. Martin a déjà :
- Lundi 14:00 avec Marie Dupont

Tentative de créer :
- Lundi 14:00 avec Gabriel Moreau

➡️ Conflit détecté mais réservation créée quand même
⚠️ Log d'avertissement pour information
```

#### 2️⃣ **Conflit Élève**
```
❌ L'élève a déjà un créneau récurrent avec un autre enseignant
```

**Exemple :**
```
Gabriel Moreau a déjà :
- Lundi 14:00 Natation avec Prof. Martin

Tentative de créer :
- Lundi 14:00 Tennis avec Prof. Dupont

➡️ Conflit détecté mais réservation créée quand même
⚠️ Log d'avertissement pour information
```

### **Comportement en Cas de Conflit**

```
✅ La réservation est TOUJOURS créée
⚠️ Un warning est loggé avec détails du conflit
💡 Des créneaux alternatifs sont suggérés
📧 (Futur) Notification au club pour décision
```

**Les conflits ne bloquent JAMAIS la création**, ils servent juste d'avertissement.

---

## 💡 CAS D'USAGE TYPIQUES

### **Cas 1 : Abonnement Normal**

```
1. Élève réserve un cours → Réservation créée automatiquement
2. Abonnement se termine → Réservation annulée automatiquement
3. Élève renouvelle → Nouvelle réservation créée
```

### **Cas 2 : Abonnement à Terme Connu**

```
1. Élève réserve un cours → Réservation créée automatiquement
2. On sait qu'il ne renouvelle pas → Libérer manuellement via API
3. Créneau disponible pour nouveaux élèves
```

### **Cas 3 : Changement de Planning**

```
1. Élève veut changer d'horaire
2. Libérer ancienne réservation via API
3. Créer nouveau cours au nouvel horaire
4. Nouvelle réservation créée automatiquement
```

### **Cas 4 : Conflit Détecté**

```
1. Réservation créée malgré conflit
2. Vérifier les logs pour voir suggestions alternatives
3. Décider si on garde ou si on libère
4. Gérer via API si nécessaire
```

---

## 📊 STATUTS DES RÉCURRENCES

| Statut | Signification | Créneau |
|--------|---------------|---------|
| `active` | Réservé pour l'élève | RÉSERVÉ (flexible) |
| `cancelled` | Libéré manuellement ou automatiquement | LIBRE |
| `expired` | Date de fin dépassée | LIBRE |
| `completed` | Abonnement terminé normalement | LIBRE |

---

## 🔍 LOGS GÉNÉRÉS

### **Création Réussie Sans Conflit**
```log
✅ Créneau récurrent RÉSERVÉ sans conflit
{
  "recurring_slot_id": 1,
  "subscription_instance_id": 42,
  "lesson_id": 1234,
  "student_id": 54,
  "teacher_id": 3,
  "day_of_week": 3,
  "start_time": "14:00:00",
  "end_time": "15:00:00",
  "start_date": "2025-11-13",
  "end_date": "2026-05-13",
  "conflicts_detected": false,
  "note": "Réservation flexible - libérable via POST /club/recurring-slots/1/release"
}
```

### **Création Avec Avertissement**
```log
⚠️ Conflits détectés lors de la réservation du créneau récurrent
{
  "lesson_id": 1234,
  "student_id": 54,
  "conflicts_count": 1,
  "conflicts": [
    {
      "type": "teacher_recurring",
      "message": "L'enseignant a déjà un créneau récurrent avec Marie Dupont",
      "conflicting_student": "Marie Dupont"
    }
  ],
  "note": "Créneaux RÉSERVÉS (pas bloqués) - Peuvent être libérés manuellement"
}

⚠️ Créneau récurrent RÉSERVÉ avec avertissements
{
  "recurring_slot_id": 1,
  ...
  "conflicts_detected": true
}
```

### **Annulation Automatique**
```log
🔄 Récurrences annulées automatiquement
{
  "subscription_instance_id": 42,
  "reason": "completed",
  "cancelled_count": 1
}
```

### **Libération Manuelle**
```log
🔓 Créneau récurrent libéré manuellement
{
  "recurring_slot_id": 1,
  "subscription_instance_id": 42,
  "club_id": 1,
  "user_id": 5,
  "reason": "L'abonnement se termine fin décembre"
}
```

---

## ✅ RÉSUMÉ

| Aspect | Comportement |
|--------|--------------|
| **Nature** | Réservation FLEXIBLE (pas blocage) |
| **Création** | Automatique à chaque cours |
| **Annulation** | Automatique quand abonnement termine |
| **Prolongation** | Automatique quand abonnement renouvelé |
| **Libération manuelle** | Via API `/club/recurring-slots/{id}/release` |
| **Réactivation** | Via API `/club/recurring-slots/{id}/reactivate` |
| **Conflits** | Détectés mais n'empêchent PAS la création |
| **Durée** | 6 mois max ou date d'expiration abonnement |

---

**Date :** 2025-11-05  
**Version :** 1.0  
**Documentation complète des créneaux récurrents flexibles**

