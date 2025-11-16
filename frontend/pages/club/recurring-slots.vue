<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Créneaux Récurrents</h1>
            <p class="text-gray-600">Gérez les créneaux récurrents réservés pour les abonnements</p>
          </div>
          <NuxtLink
            to="/club/subscriptions"
            class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors flex items-center space-x-2"
          >
            <span>←</span>
            <span>Abonnements</span>
          </NuxtLink>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-12">
        <p class="text-gray-500">Chargement des créneaux récurrents...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="recurringSlots.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
        <div class="text-6xl mb-4">🕐</div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Aucun créneau récurrent</h3>
        <p class="text-gray-600 mb-4">
          Les créneaux récurrents sont créés automatiquement lorsque vous créez un cours pour un élève avec un abonnement actif.
        </p>
        <NuxtLink
          to="/club/planning"
          class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
        >
          Créer un cours
        </NuxtLink>
      </div>

      <!-- Liste des créneaux récurrents -->
      <div v-else class="space-y-4">
        <div 
          v-for="slot in recurringSlots" 
          :key="slot.id"
          class="bg-white rounded-lg shadow-sm p-6"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <!-- Jour et heure -->
              <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                  <span class="text-2xl">{{ getDayEmoji(slot.day_of_week) }}</span>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">
                    {{ getDayName(slot.day_of_week) }}
                  </h3>
                  <p class="text-sm text-gray-600">
                    {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
                  </p>
                </div>
              </div>

              <!-- Informations -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                  <label class="text-xs font-medium text-gray-500 uppercase">Élève</label>
                  <p class="text-sm font-semibold text-gray-900">
                    {{ slot.student?.user?.name || 'Non défini' }}
                  </p>
                </div>
                <div>
                  <label class="text-xs font-medium text-gray-500 uppercase">Enseignant</label>
                  <p class="text-sm font-semibold text-gray-900">
                    {{ slot.teacher?.user?.name || 'Non défini' }}
                  </p>
                </div>
                <div>
                  <label class="text-xs font-medium text-gray-500 uppercase">Abonnement</label>
                  <p class="text-sm font-semibold text-gray-900">
                    {{ slot.subscription_instance?.subscription?.name || 'N/A' }}
                  </p>
                </div>
                <div>
                  <label class="text-xs font-medium text-gray-500 uppercase">Période</label>
                  <p class="text-sm font-semibold text-gray-900">
                    {{ formatDate(slot.start_date) }} → {{ formatDate(slot.end_date) }}
                  </p>
                </div>
              </div>

              <!-- Statut -->
              <div class="mt-4">
                <span 
                  :class="getStatusClass(slot.status)"
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                >
                  {{ getStatusLabel(slot.status) }}
                </span>
              </div>

              <!-- Notes -->
              <div v-if="slot.notes" class="mt-4 p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-600">{{ slot.notes }}</p>
              </div>
            </div>

            <!-- Actions -->
            <div class="ml-4 flex flex-col gap-2">
              <button 
                v-if="slot.status === 'active'"
                @click="releaseSlot(slot.id)"
                :disabled="processing"
                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 text-sm"
              >
                Libérer
              </button>
              <button 
                v-if="slot.status === 'cancelled'"
                @click="reactivateSlot(slot.id)"
                :disabled="processing"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 text-sm"
              >
                Réactiver
              </button>
              <button 
                @click="viewDetails(slot.id)"
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm"
              >
                Détails
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from '~/composables/useToast'

const { $api } = useNuxtApp()
const { success, error: showError } = useToast()

const recurringSlots = ref([])
const loading = ref(true)
const processing = ref(false)

onMounted(async () => {
  await loadRecurringSlots()
})

async function loadRecurringSlots() {
  try {
    loading.value = true
    const response = await $api.get('/recurring-slots')
    if (response.data.success) {
      recurringSlots.value = response.data.data || []
    } else {
      showError('Erreur lors du chargement des créneaux récurrents')
    }
  } catch (err) {
    console.error('Erreur:', err)
    showError('Erreur lors du chargement des créneaux récurrents')
  } finally {
    loading.value = false
  }
}

async function releaseSlot(id) {
  if (!confirm('Êtes-vous sûr de vouloir libérer ce créneau récurrent ?')) {
    return
  }

  try {
    processing.value = true
    const response = await $api.post(`/recurring-slots/${id}/release`, {
      reason: 'Libération manuelle depuis l\'interface'
    })
    
    if (response.data.success) {
      success('Créneau libéré avec succès')
      await loadRecurringSlots()
    } else {
      showError(response.data.message || 'Erreur lors de la libération')
    }
  } catch (err) {
    console.error('Erreur:', err)
    showError('Erreur lors de la libération du créneau')
  } finally {
    processing.value = false
  }
}

async function reactivateSlot(id) {
  if (!confirm('Êtes-vous sûr de vouloir réactiver ce créneau récurrent ?')) {
    return
  }

  try {
    processing.value = true
    const response = await $api.post(`/recurring-slots/${id}/reactivate`, {
      reason: 'Réactivation manuelle depuis l\'interface'
    })
    
    if (response.data.success) {
      success('Créneau réactivé avec succès')
      await loadRecurringSlots()
    } else {
      showError(response.data.message || 'Erreur lors de la réactivation')
    }
  } catch (err) {
    console.error('Erreur:', err)
    showError('Erreur lors de la réactivation du créneau')
  } finally {
    processing.value = false
  }
}

function viewDetails(id) {
  // TODO: Ouvrir une modale avec les détails
  alert(`Détails du créneau #${id} - À implémenter`)
}

function getDayName(dayOfWeek) {
  const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
  return days[dayOfWeek] || 'Inconnu'
}

function getDayEmoji(dayOfWeek) {
  const emojis = ['☀️', '📅', '📅', '📅', '📅', '📅', '🎉']
  return emojis[dayOfWeek] || '📅'
}

function formatTime(time) {
  if (!time) return 'N/A'
  return time.substring(0, 5) // HH:mm
}

function formatDate(date) {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

function getStatusClass(status) {
  const classes = {
    'active': 'bg-green-100 text-green-800',
    'cancelled': 'bg-red-100 text-red-800',
    'expired': 'bg-gray-100 text-gray-800',
    'paused': 'bg-yellow-100 text-yellow-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function getStatusLabel(status) {
  const labels = {
    'active': 'Actif',
    'cancelled': 'Annulé',
    'expired': 'Expiré',
    'paused': 'En pause'
  }
  return labels[status] || status
}
</script>

