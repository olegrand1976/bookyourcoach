# ✨ Feature : Initialisation d'Abonnements en Batch

**Date** : 4 novembre 2025  
**Feature** : Bouton "Initialiser des Abonnements" sur `/club/subscriptions`

---

## 📋 FONCTIONNALITÉ

### Description
Permet aux clubs de créer plusieurs abonnements "ouverts" (non assignés) en une seule opération. Ces abonnements peuvent ensuite être attribués aux élèves au moment opportun.

### Cas d'usage
- **Préparation de session** : Créer 10 abonnements avant le début d'une nouvelle session
- **Événements** : Préparer des abonnements pour un événement ponctuel
- **Gestion anticipée** : Avoir un stock d'abonnements disponibles pour les nouvelles inscriptions

---

## 🎨 INTERFACE UTILISATEUR

### Bouton d'accès
**Page** : `/club/subscriptions`  
**Position** : Header, entre "Modèles" et "Créer un Abonnement"  
**Couleur** : Violet (`bg-purple-600`)  
**Label** : "Initialiser des Abonnements"

### Modal d'initialisation
Le modal permet de configurer :

1. **Modèle d'abonnement** * (obligatoire)
   - Liste déroulante des modèles actifs du club
   - Affichage : `Nom - Nombre de cours - Prix`
   - Prévisualisation des types de cours inclus

2. **Nombre d'abonnements** * (obligatoire)
   - Champ numérique
   - Min : 1, Max : 50
   - Par défaut : 1

3. **Date d'ouverture** (optionnel)
   - Champ date
   - Min : Aujourd'hui
   - Par défaut : Aujourd'hui
   - Indique quand les abonnements deviennent disponibles

4. **Résumé en temps réel**
   - Modèle sélectionné
   - Quantité d'abonnements
   - Prix unitaire
   - **Prix total** (quantité × prix unitaire)
   - Date d'ouverture

---

## ⚙️ BACKEND

### Endpoint
```http
POST /api/club/subscriptions/initialize
Authorization: Bearer {token}
Content-Type: application/json
```

### Payload
```json
{
  "subscription_template_id": 1,
  "quantity": 10,
  "opened_at": "2025-11-05"  // Optionnel
}
```

### Validation
- `subscription_template_id` : requis, doit exister, doit appartenir au club
- `quantity` : requis, entier, min:1, max:50
- `opened_at` : optionnel, date, >= aujourd'hui

### Réponse succès (201)
```json
{
  "success": true,
  "message": "10 abonnement(s) initialisé(s) avec succès",
  "data": {
    "subscriptions": [
      {
        "id": 123,
        "subscription_number": "SUB-2025-11-0123",
        "subscription_template_id": 1,
        "club_id": 11,
        "template": { ... },
        "instances": [
          {
            "id": 456,
            "subscription_id": 123,
            "status": "open",
            "started_at": "2025-11-05",
            "expires_at": null,
            "lessons_used": 0,
            "students": []
          }
        ]
      },
      // ... 9 autres abonnements
    ],
    "template": {
      "id": 1,
      "model_number": "MOD-01-Natation - Cours standard",
      "total_lessons": 10,
      "free_lessons": 1,
      "price": 180.00,
      "course_types": [ ... ]
    },
    "summary": {
      "total_created": 10,
      "template_name": "MOD-01-Natation - Cours standard",
      "opened_at": "2025-11-05",
      "subscription_numbers": [
        "SUB-2025-11-0123",
        "SUB-2025-11-0124",
        // ...
      ]
    }
  }
}
```

### Réponse erreur (422)
```json
{
  "success": false,
  "message": "Erreur de validation",
  "errors": {
    "quantity": ["Le champ quantity ne peut pas être supérieur à 50."]
  }
}
```

---

## 🔧 IMPLÉMENTATION TECHNIQUE

### Fichiers modifiés

1. **Backend**
   - `app/Http/Controllers/Api/SubscriptionController.php`
     - Nouvelle méthode `initializeBatch()`
   - `routes/api.php`
     - Nouvelle route `POST /club/subscriptions/initialize`

2. **Frontend**
   - `frontend/pages/club/subscriptions.vue`
     - Ajout du bouton "Initialiser des Abonnements"
     - Import du nouveau modal
     - États : `showInitializeModal`, `subscriptionTemplates`
     - Méthodes : `loadSubscriptionTemplates()`, `handleInitializeSubmit()`
   - `frontend/components/subscriptions/InitializeSubscriptionsModal.vue` (NOUVEAU)
     - Modal complet avec formulaire
     - Validation côté client
     - Résumé en temps réel

### Logique backend

```php
public function initializeBatch(Request $request): JsonResponse
{
    // 1. Valider les données
    $validated = $request->validate([
        'subscription_template_id' => 'required|exists:subscription_templates,id',
        'quantity' => 'required|integer|min:1|max:50',
        'opened_at' => 'nullable|date|after_or_equal:today'
    ]);
    
    // 2. Vérifier que le template appartient au club
    $template = SubscriptionTemplate::where('club_id', $club->id)
        ->where('is_active', true)
        ->findOrFail($validated['subscription_template_id']);
    
    // 3. Créer N abonnements
    for ($i = 0; $i < $validated['quantity']; $i++) {
        $subscription = Subscription::createSafe([...]);
        $subscriptionInstance = SubscriptionInstance::create([
            'status' => 'open',  // ← Nouveau statut
            'started_at' => $openedAt,
            'expires_at' => null  // ← Calculé lors de l'assignation
        ]);
    }
    
    // 4. Retourner les abonnements créés
    return response()->json([...], 201);
}
```

### Statut "open"

Un nouveau statut `'open'` est utilisé pour les abonnements non assignés :
- `'open'` : Abonnement disponible, non assigné
- `'active'` : Abonnement assigné et en cours
- `'completed'` : Abonnement terminé
- `'expired'` : Abonnement expiré
- `'cancelled'` : Abonnement annulé

**Note** : Le champ `expires_at` reste `NULL` tant que l'abonnement n'est pas assigné. Il sera calculé lors de l'assignation à un élève.

---

## 🧪 TESTS

### Test 1 : Initialisation simple
1. Aller sur `/club/subscriptions`
2. Cliquer sur "Initialiser des Abonnements" (bouton violet)
3. Sélectionner un modèle d'abonnement
4. Saisir une quantité (ex: 5)
5. Valider

**Résultat attendu** :
- ✅ Message de succès : "5 abonnement(s) initialisé(s) avec succès"
- ✅ 5 nouveaux abonnements apparaissent dans la liste
- ✅ Statut = "open"
- ✅ Numéros d'abonnement générés automatiquement

### Test 2 : Validation des limites
1. Ouvrir le modal
2. Essayer de saisir `51` dans le champ quantité
3. Valider

**Résultat attendu** :
- ❌ Erreur de validation : "Maximum : 50 abonnements par batch"

### Test 3 : Date d'ouverture future
1. Ouvrir le modal
2. Sélectionner un modèle
3. Quantité : 3
4. Date d'ouverture : Dans 7 jours
5. Valider

**Résultat attendu** :
- ✅ 3 abonnements créés avec `started_at` = date choisie
- ✅ Visible dans la liste immédiatement

### Test 4 : Résumé en temps réel
1. Ouvrir le modal
2. Sélectionner un modèle à 180€
3. Saisir quantité : 10

**Résultat attendu** :
- ✅ Résumé affiche :
  - Prix unitaire : 180€
  - Total : 1 800€
  - Date d'ouverture : Aujourd'hui (par défaut)

---

## 📊 WORKFLOW COMPLET

```
1. Club : "Initialiser des Abonnements"
   ↓
2. Modal : Sélectionner modèle + quantité + date
   ↓
3. Backend : Créer N abonnements avec statut "open"
   ↓
4. Abonnements disponibles dans la liste
   ↓
5. Plus tard : Assigner un abonnement "open" à un élève
   ↓
6. Statut passe de "open" → "active"
   ↓
7. expires_at calculé automatiquement
```

---

## ⚠️ NOTES IMPORTANTES

1. **Numéros d'abonnement uniques**
   - Chaque abonnement reçoit un numéro unique : `SUB-YYYY-MM-####`
   - Générés automatiquement via le modèle `Subscription`

2. **Types de cours inclus**
   - Les abonnements héritent des types de cours du modèle
   - Affichés dans le modal pour information

3. **Assignation ultérieure**
   - Les abonnements "open" peuvent être assignés depuis :
     - La liste des abonnements
     - La fiche élève
   - Lors de l'assignation :
     - Statut → `'active'`
     - `expires_at` calculé depuis `started_at` + validité

4. **Performance**
   - Limite de 50 abonnements par batch pour éviter les timeouts
   - Transaction DB : Tout ou rien (rollback si erreur)

---

## 🚀 DÉPLOIEMENT

```bash
# 1. Commit et push
git add .
git commit -m "feat: Initialisation d'abonnements en batch sur club/subscriptions"
git push

# 2. En production
# Les migrations ne sont pas nécessaires (utilise les tables existantes)

# 3. Test manuel
# - Créer des abonnements en batch
# - Vérifier les numéros d'abonnement
# - Vérifier le statut "open"
```

---

## 💡 AMÉLIORATIONS FUTURES

### Option 1 : Gestion des abonnements "open"
- Filtrer par statut dans la liste
- Vue dédiée aux abonnements disponibles
- Alerte quand stock faible

### Option 2 : Template de nommage
- Permettre de nommer un batch (ex: "Session Janvier 2025")
- Ajouter un préfixe personnalisé aux numéros

### Option 3 : Assignation rapide
- Bouton "Assigner" directement sur un abonnement "open"
- Modal simplifié pour assignation rapide

### Option 4 : Statistiques
- Tableau de bord : Abonnements ouverts / actifs / expirés
- Taux d'utilisation des abonnements
- Recommandations d'initialisation

---

## ✅ CHECKLIST DE VALIDATION

- [x] Backend : Endpoint `/api/club/subscriptions/initialize` créé
- [x] Backend : Validation des données
- [x] Backend : Transaction DB
- [x] Backend : Logs d'initialisation
- [x] Frontend : Bouton ajouté sur `/club/subscriptions`
- [x] Frontend : Modal fonctionnel
- [x] Frontend : Formulaire avec validation
- [x] Frontend : Résumé en temps réel
- [x] Route API ajoutée dans `routes/api.php`
- [x] Tests définis
- [x] Documentation complète
- [ ] **À TESTER EN PRODUCTION**

---

**Auteur** : Assistant IA  
**Validé par** : Olivier (à venir)

