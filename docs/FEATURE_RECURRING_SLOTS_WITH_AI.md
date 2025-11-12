# 🤖 Feature : Récurrence des Créneaux avec Suggestions IA

**Date de création :** 4 novembre 2025  
**Branche :** `feature/subscription-recurring-slots`  
**Statut :** ✅ Implémenté

---

## 📋 Vue d'Ensemble

Cette feature permet de **réserver automatiquement un créneau récurrent sur 6 mois** lors de la création d'un cours pour un élève ayant un abonnement actif. En cas de conflit, l'**IA Gemini propose automatiquement des créneaux alternatifs** optimaux.

---

## 🎯 Objectifs

1. **Valider** la disponibilité d'un créneau sur 26 semaines (6 mois)
2. **Bloquer** les créneaux récurrents pour éviter les double-réservations
3. **Détecter** les conflits (créneau plein, enseignant occupé)
4. **Proposer** des alternatives intelligentes via IA
5. **Optimiser** le choix avec analyse des pour/contre

---

## 🏗️ Architecture

### **Composants Backend**

```
app/
├── Models/
│   └── SubscriptionRecurringSlot.php     # Modèle de récurrence
├── Services/
│   ├── RecurringSlotValidator.php        # Validation disponibilité
│   └── RecurringSlotSuggestionService.php # Suggestions IA
├── Services/AI/
│   └── GeminiService.php                 # Service IA (existant)
└── Http/Controllers/Api/
    └── LessonController.php              # Intégration

database/
└── subscription_recurring_slots          # Table de récurrence
```

---

## 🗄️ Base de Données

### **Table `subscription_recurring_slots`**

```sql
CREATE TABLE subscription_recurring_slots (
  id BIGINT UNSIGNED PRIMARY KEY,
  subscription_instance_id BIGINT UNSIGNED NOT NULL,
  open_slot_id BIGINT UNSIGNED NULL,
  teacher_id BIGINT UNSIGNED NOT NULL,
  student_id BIGINT UNSIGNED NOT NULL,
  day_of_week TINYINT UNSIGNED NOT NULL,  -- 0=Dimanche, 6=Samedi
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status ENUM('active', 'cancelled', 'expired', 'completed') DEFAULT 'active',
  notes TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  
  FOREIGN KEY (subscription_instance_id) REFERENCES subscription_instances(id) ON DELETE CASCADE,
  FOREIGN KEY (open_slot_id) REFERENCES club_open_slots(id) ON DELETE SET NULL,
  FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  
  INDEX recurring_slots_schedule_idx (day_of_week, start_time, end_time, status),
  INDEX recurring_slots_teacher_idx (teacher_id, status),
  INDEX recurring_slots_subscription_idx (subscription_instance_id, status)
);
```

---

## 🔍 Validation de Récurrence

### **Service : `RecurringSlotValidator`**

#### **Méthode `validateRecurringAvailability()`**

Vérifie la disponibilité sur **26 semaines** (6 mois) :

```php
$validator = new RecurringSlotValidator();

$validation = $validator->validateRecurringAvailability(
    $openSlotId,    // ID du créneau
    $teacherId,     // ID de l'enseignant
    $studentId,     // ID de l'élève
    $startDate      // Date de début (Y-m-d)
);

// Résultat
[
    'valid' => false,
    'conflicts' => [
        ['type' => 'slot_capacity', 'date' => '2025-11-12', 'message' => 'Capacité max atteinte (5/5)'],
        ['type' => 'teacher_unavailable', 'date' => '2025-11-19', 'message' => 'Enseignant déjà occupé'],
        // ... jusqu'à 26 occurrences
    ],
    'message' => 'Conflits détectés sur 8 occurrence(s)'
]
```

#### **Vérifications effectuées**

Pour chaque occurrence (semaine 0 à 25) :

1. **Capacité du créneau**
   ```php
   $existingLessons + $recurringSlots < $openSlot->max_capacity
   ```

2. **Disponibilité de l'enseignant**
   ```php
   // Pas de cours existant ni récurrence en conflit
   WHERE teacher_id = X
     AND day_of_week = Y
     AND (start_time < end_time AND end_time > start_time)
   ```

---

## 🤖 Suggestions Intelligentes (IA)

### **Service : `RecurringSlotSuggestionService`**

En cas de conflit, l'IA **Gemini** analyse les données et propose des alternatives.

#### **Méthode `suggestAlternatives()`**

```php
$suggestionService = new RecurringSlotSuggestionService(
    new GeminiService(),
    new RecurringSlotValidator()
);

$suggestions = $suggestionService->suggestAlternatives(
    $originalOpenSlotId,
    $teacherId,
    $studentId,
    $startDate,
    $conflicts
);

// Résultat
[
    'suggestions' => [
        [
            'slot_id' => 2,
            'teacher_id' => 3,
            'teacher_name' => 'Marie Dupont',
            'day_of_week' => 3,
            'day_name' => 'Mercredi',
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'type' => 'same_slot_different_teacher',
            'score' => 90,
            'conflicts_count' => 0,
            'ai_reason' => 'Créneau identique avec un enseignant disponible',
            'ai_pros' => ['Même horaire', 'Disponibilité complète sur 6 mois'],
            'ai_cons' => ['Changement d'enseignant']
        ],
        // ... jusqu'à 10 suggestions
    ],
    'ai_analysis' => 'Le créneau demandé présente 8 conflits dus à une surcharge...',
    'total_alternatives' => 5
]
```

#### **Algorithme de recherche**

1. **Recherche d'alternatives**
   - Autres créneaux du club (même discipline)
   - Autres enseignants disponibles
   - Calcul d'un score (0-100)

2. **Consultation de l'IA**
   - Prompt structuré avec contexte
   - Analyse des pour/contre
   - Recommandations personnalisées

3. **Tri et sélection**
   - Tri par score (meilleur en premier)
   - Limite à 10 suggestions max

#### **Types d'alternatives**

| Type | Description | Score | Exemple |
|------|-------------|-------|---------|
| `same_slot_different_teacher` | Même créneau, enseignant différent | 90 | Mercredi 14h avec Marie au lieu de Jean |
| `same_slot_different_time` | Créneau différent, même enseignant | 100 | Jeudi 10h avec Jean |
| `same_slot_partial` | Disponibilité partielle | 50-95 | Mercredi 14h mais 3 conflits sur 26 |

---

## 📡 API

### **Endpoint : POST /api/lessons**

#### **Paramètres**

```json
{
  "teacher_id": 1,
  "student_id": 2,
  "course_type_id": 3,
  "open_slot_id": 4,
  "start_time": "2025-11-05 09:00:00",
  "duration": 60,
  "price": 18.00,
  "with_recurring_check": true,      // Activer validation récurrence
  "with_ai_suggestions": true        // Activer suggestions IA (défaut: true)
}
```

#### **Réponse en cas de conflit (HTTP 422)**

```json
{
  "success": false,
  "message": "Conflits détectés sur 8 occurrence(s)",
  "conflicts": [
    {
      "type": "slot_capacity",
      "date": "2025-11-12",
      "message": "Capacité max atteinte (5/5)"
    },
    {
      "type": "teacher_unavailable",
      "date": "2025-11-19",
      "message": "Enseignant déjà occupé"
    }
  ],
  "suggestions": [
    {
      "slot_id": 2,
      "teacher_id": 3,
      "teacher_name": "Marie Dupont",
      "day_name": "Mercredi",
      "start_time": "14:00:00",
      "end_time": "15:00:00",
      "type": "same_slot_different_teacher",
      "score": 90,
      "conflicts_count": 0,
      "ai_reason": "Créneau identique avec un enseignant disponible",
      "ai_pros": ["Même horaire", "Disponibilité complète"],
      "ai_cons": ["Changement d'enseignant"]
    }
  ],
  "ai_analysis": "Le créneau demandé présente 8 conflits sur 26 semaines. Je vous recommande le créneau du mercredi 14h avec Marie Dupont car il offre la même plage horaire tout en garantissant une disponibilité complète sur les 6 prochains mois."
}
```

#### **Réponse en cas de succès (HTTP 201)**

```json
{
  "success": true,
  "data": {
    "id": 123,
    "teacher_id": 1,
    "student_id": 2,
    "start_time": "2025-11-05 09:00:00",
    // ... (détails du cours créé)
  },
  "message": "Cours créé avec succès"
}
```

---

## 🧪 Tests

### **Scénario 1 : Tout disponible**

```bash
curl -X POST http://localhost:8000/api/lessons \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "teacher_id": 1,
    "student_id": 1,
    "open_slot_id": 1,
    "start_time": "2025-11-05 09:00:00",
    "with_recurring_check": true
  }'
```

**Résultat attendu :** HTTP 201, cours créé + récurrence enregistrée

```sql
SELECT * FROM subscription_recurring_slots WHERE student_id = 1;
```

### **Scénario 2 : Enseignant occupé**

```bash
# Créer un cours en conflit
curl -X POST ... # Cours pour le même enseignant au même moment

# Essayer de créer avec récurrence
curl -X POST ... # avec with_recurring_check=true
```

**Résultat attendu :** HTTP 422 avec conflits + suggestions IA

### **Scénario 3 : Créneau plein**

```bash
# Remplir le créneau jusqu'à max_capacity
for i in {1..5}; do
  curl -X POST ... # Créer 5 cours (si max_capacity=5)
done

# Essayer d'ajouter un 6ème
curl -X POST ... # avec with_recurring_check=true
```

**Résultat attendu :** HTTP 422 avec type `slot_capacity` + suggestions

---

## 📊 Logs

Les logs sont disponibles dans `storage/logs/laravel.log` :

```
[2025-11-04 16:30:00] 🔍 Validation récurrence
  open_slot_id: 1
  teacher_id: 1
  student_id: 1
  weeks_to_check: 26

[2025-11-04 16:30:05] ❌ Récurrence invalide
  conflicts_count: 8
  conflicts: [{...}]

[2025-11-04 16:30:06] 🤖 Recherche de créneaux alternatifs avec IA
  original_slot: 1
  teacher_id: 1
  conflicts_count: 8

[2025-11-04 16:30:12] ✅ Suggestions générées
  suggestions_count: 5
  ai_used: true
```

---

## 🚀 Déploiement

### **1. Mise à jour du code**

```bash
git checkout feature/subscription-recurring-slots
git pull
```

### **2. Installation des dépendances**

```bash
composer install
```

### **3. Configuration de l'IA**

Ajouter dans `.env` :

```bash
GEMINI_API_KEY=votre_clé_api_gemini
GEMINI_MODEL=gemini-2.5-flash
```

### **4. Vérifier la structure de la table**

La table `subscription_recurring_slots` doit déjà exister. Vérifier :

```bash
php artisan tinker
DB::select('DESCRIBE subscription_recurring_slots');
```

### **5. Tester l'intégration**

```bash
# Test manuel
curl -X POST http://localhost:8000/api/lessons \
  -H "Authorization: Bearer TOKEN" \
  -d '{"with_recurring_check": true, ...}'
```

---

## 🔧 Configuration

### **Variables d'environnement**

```bash
# IA Gemini (requis pour les suggestions)
GEMINI_API_KEY=AIzaSy...
GEMINI_MODEL=gemini-2.5-flash

# Période de validation (par défaut 26 semaines = 6 mois)
RECURRING_VALIDATION_WEEKS=26
```

### **Paramètres par défaut**

Dans `RecurringSlotValidator` :

```php
const VALIDATION_WEEKS = 26;  // 6 mois
```

---

## 💡 Améliorations Futures

### **Frontend**

- [ ] Checkbox "Réserver créneau récurrent" dans formulaire de cours
- [ ] Modal d'affichage des conflits détectés
- [ ] Sélection interactive des suggestions IA
- [ ] Visualisation calendrier des 6 prochains mois

### **Backend**

- [ ] Cache des suggestions IA (éviter appels multiples)
- [ ] Annulation automatique des récurrences si abonnement annulé
- [ ] Webhook pour notifier les changements de disponibilité
- [ ] API pour gérer manuellement les récurrences
- [ ] Statistiques sur les taux de conflit par créneau

### **IA**

- [ ] Apprentissage des préférences utilisateur
- [ ] Suggestions basées sur l'historique de l'élève
- [ ] Prédiction des créneaux à risque de conflit
- [ ] Optimisation multi-objectifs (prix, distance, popularité)

---

## 📚 Documentation Complémentaire

- [Guide IA Gemini](./AI_PREDICTIVE_ANALYSIS_GUIDE.md)
- [API Reference](./API_REFERENCE.md)
- [Structure Base de Données](./DATABASE_SCHEMA.md)

---

## 🤝 Contribution

Pour ajouter une feature ou corriger un bug :

1. Créer une branche depuis `feature/subscription-recurring-slots`
2. Implémenter les changements
3. Tester localement
4. Créer une PR vers `feature/subscription-recurring-slots`

---

## 📞 Support

En cas de problème :

- **Logs :** `storage/logs/laravel.log`
- **Debugging :** Ajouter `Log::info()` dans les services
- **Tests :** `php artisan test --filter Recurring`

---

**Feature créée par :** Assistant IA Claude  
**Date :** 4 novembre 2025  
**Version :** 1.0.0

