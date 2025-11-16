# Gestion des Récurrences d'Abonnements

## 📍 Où sont gérées les récurrences ?

### Backend (API)

#### 1. **Ancien système** : `SubscriptionRecurringSlot` (Legacy)
- **Contrôleur** : `app/Http/Controllers/Api/RecurringSlotController.php`
- **Modèle** : `app/Models/SubscriptionRecurringSlot.php`
- **Routes API** :
  - `GET /api/recurring-slots` - Liste des créneaux récurrents
  - `GET /api/recurring-slots/{id}` - Détails d'un créneau
  - `POST /api/recurring-slots/{id}/release` - Libérer un créneau
  - `POST /api/recurring-slots/{id}/reactivate` - Réactiver un créneau

#### 2. **Création automatique** : `LessonController`
- **Méthode** : `createRecurringSlotIfSubscription()` (ligne 1233)
- **Déclenchement** : Automatique lors de la création d'un cours si l'élève a un abonnement actif
- **Logique** :
  - Détecte si l'élève a un abonnement actif
  - Extrait le jour de la semaine et l'heure du cours
  - Crée un `SubscriptionRecurringSlot` pour réserver le créneau
  - Durée : 6 mois ou jusqu'à l'expiration de l'abonnement

#### 3. **Nouveau système** : `RecurringSlot` (RRULE)
- **Service** : `app/Services/RecurringSlotService.php`
- **Modèle** : `app/Models/RecurringSlot.php`
- **Commandes** :
  - `php artisan recurring-slots:migrate` - Migrer les anciens créneaux
  - `php artisan recurring-slots:generate-lessons` - Générer les lessons automatiquement
  - `php artisan recurring-slots:expire-subscriptions` - Expirer les liaisons

### Frontend

**⚠️ Actuellement, il n'existe PAS d'interface frontend dédiée pour gérer les créneaux récurrents.**

Les récurrences sont créées automatiquement en arrière-plan lors de la création d'un cours avec abonnement.

## 🧪 Comment tester l'interface actuelle ?

### Test 1 : Création automatique d'une récurrence

1. **Créer un abonnement pour un élève** :
   - Aller sur `/club/subscriptions`
   - Cliquer sur "Nouvel abonnement"
   - Sélectionner un élève et un modèle d'abonnement
   - Créer l'abonnement

2. **Créer un cours pour cet élève** :
   - Aller sur `/club/planning`
   - Sélectionner un créneau horaire
   - Cliquer sur "Créer un cours"
   - Sélectionner l'élève avec l'abonnement
   - Créer le cours

3. **Vérifier la récurrence créée** :
   ```bash
   # Via l'API
   curl -H "Authorization: Bearer YOUR_TOKEN" \
        http://localhost:8080/api/recurring-slots
   ```

### Test 2 : Via l'API directement

#### Lister les créneaux récurrents

```bash
curl -X GET \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  http://localhost:8080/api/recurring-slots
```

#### Voir les détails d'un créneau

```bash
curl -X GET \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  http://localhost:8080/api/recurring-slots/1
```

#### Libérer un créneau récurrent

```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"reason": "Abonnement terminé"}' \
  http://localhost:8080/api/recurring-slots/1/release
```

#### Réactiver un créneau annulé

```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"reason": "Nouvel abonnement"}' \
  http://localhost:8080/api/recurring-slots/1/reactivate
```

### Test 3 : Via Tinker (Laravel)

```bash
docker compose exec backend php artisan tinker
```

```php
// Lister les créneaux récurrents
\App\Models\SubscriptionRecurringSlot::with(['student.user', 'teacher.user', 'subscriptionInstance.subscription'])->get();

// Voir un créneau spécifique
$slot = \App\Models\SubscriptionRecurringSlot::find(1);
$slot->student->user->name;
$slot->teacher->user->name;
$slot->day_of_week; // 0 = Dimanche, 6 = Samedi
$slot->start_time;
$slot->end_time;
$slot->status;

// Libérer un créneau
$slot->release('Test de libération');

// Réactiver un créneau
$slot->reactivate('Test de réactivation');
```

## 🎨 Créer une interface frontend pour tester

### Option 1 : Page dédiée aux créneaux récurrents

Créer `frontend/pages/club/recurring-slots.vue` :

```vue
<template>
  <div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4">
      <h1 class="text-2xl font-bold mb-6">Créneaux Récurrents</h1>
      
      <div v-if="loading" class="text-center py-12">
        <p>Chargement...</p>
      </div>
      
      <div v-else-if="recurringSlots.length === 0" class="text-center py-12">
        <p class="text-gray-500">Aucun créneau récurrent</p>
      </div>
      
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div 
          v-for="slot in recurringSlots" 
          :key="slot.id"
          class="bg-white rounded-lg shadow p-4"
        >
          <h3 class="font-semibold">{{ getDayName(slot.day_of_week) }}</h3>
          <p>{{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}</p>
          <p>Élève: {{ slot.student?.user?.name }}</p>
          <p>Enseignant: {{ slot.teacher?.user?.name }}</p>
          <p>Statut: {{ slot.status }}</p>
          
          <div class="mt-4 flex gap-2">
            <button 
              v-if="slot.status === 'active'"
              @click="releaseSlot(slot.id)"
              class="px-3 py-1 bg-red-500 text-white rounded text-sm"
            >
              Libérer
            </button>
            <button 
              v-if="slot.status === 'cancelled'"
              @click="reactivateSlot(slot.id)"
              class="px-3 py-1 bg-green-500 text-white rounded text-sm"
            >
              Réactiver
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const { $api } = useNuxtApp()
const recurringSlots = ref([])
const loading = ref(true)

onMounted(async () => {
  await loadRecurringSlots()
})

async function loadRecurringSlots() {
  try {
    loading.value = true
    const response = await $api.get('/recurring-slots')
    if (response.data.success) {
      recurringSlots.value = response.data.data || []
    }
  } catch (error) {
    console.error('Erreur:', error)
  } finally {
    loading.value = false
  }
}

async function releaseSlot(id) {
  if (!confirm('Libérer ce créneau récurrent ?')) return
  
  try {
    await $api.post(`/recurring-slots/${id}/release`, { reason: 'Libération manuelle' })
    await loadRecurringSlots()
  } catch (error) {
    console.error('Erreur:', error)
    alert('Erreur lors de la libération')
  }
}

async function reactivateSlot(id) {
  if (!confirm('Réactiver ce créneau récurrent ?')) return
  
  try {
    await $api.post(`/recurring-slots/${id}/reactivate`, { reason: 'Réactivation manuelle' })
    await loadRecurringSlots()
  } catch (error) {
    console.error('Erreur:', error)
    alert('Erreur lors de la réactivation')
  }
}

function getDayName(dayOfWeek) {
  const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
  return days[dayOfWeek] || 'Inconnu'
}

function formatTime(time) {
  return time.substring(0, 5) // HH:mm
}
</script>
```

### Option 2 : Ajouter une section dans la page Abonnements

Ajouter dans `frontend/pages/club/subscriptions.vue` une section pour afficher les créneaux récurrents de chaque abonnement.

## 🔄 Nouveau système (RRULE) - Tests

### Tester la migration

```bash
# Dry-run
docker compose exec backend php artisan recurring-slots:migrate --dry-run

# Migration réelle
docker compose exec backend php artisan recurring-slots:migrate
```

### Tester la génération automatique

```bash
# Dry-run
docker compose exec backend php artisan recurring-slots:generate-lessons --dry-run

# Génération réelle
docker compose exec backend php artisan recurring-slots:generate-lessons

# Pour un créneau spécifique
docker compose exec backend php artisan recurring-slots:generate-lessons --slot=1

# Pour une période spécifique
docker compose exec backend php artisan recurring-slots:generate-lessons \
  --start-date=2025-11-20 \
  --end-date=2025-12-20
```

### Tester l'expiration

```bash
# Dry-run
docker compose exec backend php artisan recurring-slots:expire-subscriptions --dry-run

# Expiration réelle
docker compose exec backend php artisan recurring-slots:expire-subscriptions
```

## 📊 Vérifier les données

### Via Tinker

```php
// Ancien système
\App\Models\SubscriptionRecurringSlot::count();
\App\Models\SubscriptionRecurringSlot::where('status', 'active')->count();

// Nouveau système
\App\Models\RecurringSlot::count();
\App\Models\RecurringSlot::active()->count();
\App\Models\RecurringSlotSubscription::where('status', 'active')->count();
\App\Models\LessonRecurringSlot::where('generated_by', 'auto')->count();
```

## 🚀 Prochaines étapes pour l'interface

1. **Créer une page `/club/recurring-slots`** pour visualiser et gérer les créneaux récurrents
2. **Ajouter une section dans `/club/subscriptions`** pour voir les créneaux récurrents par abonnement
3. **Créer un composant pour créer/modifier un créneau récurrent** (avec sélection de RRULE)
4. **Afficher les lessons générées automatiquement** dans le planning avec un indicateur visuel

