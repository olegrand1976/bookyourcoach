<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" @click.self="$emit('close')">
    <div class="flex items-center justify-center min-h-screen px-4 py-12">
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="$emit('close')"></div>
      
      <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-500 to-indigo-600">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-bold text-white">Abonnements de {{ student.name }}</h2>
              <p class="text-sm text-blue-100 mt-1">Gérez les abonnements de cet élève</p>
            </div>
            <button 
              @click="$emit('close')"
              class="text-white hover:text-gray-200 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="overflow-y-auto max-h-[calc(90vh-180px)]">
          <!-- Liste des abonnements existants -->
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Abonnements actifs</h3>
              <button
                @click="showAssignModal = true"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2 text-sm"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>Assigner un abonnement</span>
              </button>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex justify-center items-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>

            <!-- Liste des abonnements -->
            <div v-else-if="subscriptions.length > 0" class="space-y-4">
              <div 
                v-for="subscription in subscriptions" 
                :key="subscription.id"
                class="bg-gray-50 rounded-lg p-4 border border-gray-200"
              >
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                      <h4 class="font-semibold text-gray-900">
                        {{ subscription.subscription?.template?.model_number || subscription.subscription?.name || 'Abonnement' }}
                      </h4>
                      <span 
                        :class="{
                          'bg-green-100 text-green-800': subscription.status === 'active',
                          'bg-gray-100 text-gray-800': subscription.status === 'completed',
                          'bg-red-100 text-red-800': subscription.status === 'expired',
                          'bg-yellow-100 text-yellow-800': subscription.status === 'cancelled'
                        }"
                        class="px-2 py-1 text-xs font-medium rounded-full"
                      >
                        {{ getStatusLabel(subscription.status) }}
                      </span>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                      <div>
                        <span class="text-gray-600">Cours utilisés:</span>
                        <span class="font-semibold text-gray-900 ml-1">
                          {{ subscription.lessons_used }} / {{ getTotalLessons(subscription) }}
                        </span>
                      </div>
                      <div>
                        <span class="text-gray-600">Restants:</span>
                        <span class="font-semibold text-gray-900 ml-1">{{ subscription.remaining_lessons }}</span>
                      </div>
                      <div>
                        <span class="text-gray-600">Début:</span>
                        <span class="font-medium text-gray-900 ml-1">{{ formatDate(subscription.started_at) }}</span>
                      </div>
                      <div>
                        <span class="text-gray-600">Expiration:</span>
                        <span 
                          :class="{
                            'text-red-600 font-semibold': isExpiringSoon(subscription.expires_at)
                          }"
                          class="font-medium ml-1"
                        >
                          {{ subscription.expires_at ? formatDate(subscription.expires_at) : 'Non définie' }}
                        </span>
                      </div>
                    </div>

                    <!-- Types de cours inclus -->
                    <div v-if="getCourseTypes(subscription)?.length" class="mt-3">
                      <span class="text-xs font-medium text-gray-500 uppercase">Types de cours:</span>
                      <div class="flex flex-wrap gap-1 mt-1">
                        <span 
                          v-for="courseType in getCourseTypes(subscription)" 
                          :key="courseType.id"
                          class="bg-white text-gray-700 px-2 py-1 rounded text-xs border border-gray-200"
                        >
                          {{ courseType.name }}
                        </span>
                      </div>
                    </div>

                    <!-- Élèves partagés (si abonnement familial) -->
                    <div v-if="subscription.students?.length > 1" class="mt-3">
                      <span class="text-xs font-medium text-blue-700 uppercase">Abonnement familial:</span>
                      <div class="flex flex-wrap gap-1 mt-1">
                        <span 
                          v-for="student in subscription.students" 
                          :key="student.id"
                          class="bg-blue-50 text-blue-700 px-2 py-1 rounded text-xs border border-blue-200"
                        >
                          {{ student.user?.first_name }} {{ student.user?.last_name }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="ml-4 flex flex-col space-y-2">
                    <button
                      v-if="subscription.status === 'active' && canRenew(subscription)"
                      @click="renewSubscription(subscription.id)"
                      class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-xs font-medium"
                    >
                      Renouveler
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Aucun abonnement -->
            <div v-else class="text-center py-8 bg-gray-50 rounded-lg border border-gray-200">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun abonnement</h3>
              <p class="mt-1 text-sm text-gray-500">Cet élève n'a pas encore d'abonnement actif.</p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end">
          <button
            @click="$emit('close')"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>

    <!-- Modal d'assignation d'abonnement -->
    <AssignSubscriptionModal
      v-if="showAssignModal"
      :student="student"
      @close="showAssignModal = false"
      @success="loadSubscriptions"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AssignSubscriptionModal from './AssignSubscriptionModal.vue'

const props = defineProps({
  student: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'success'])

const subscriptions = ref([])
const loading = ref(false)
const showAssignModal = ref(false)

// Charger les abonnements de l'élève
const loadSubscriptions = async () => {
  try {
    loading.value = true
    const { $api } = useNuxtApp()
    const response = await $api.get(`/club/students/${props.student.id}/subscriptions`)
    
    if (response.data.success) {
      subscriptions.value = response.data.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement des abonnements:', error)
  } finally {
    loading.value = false
  }
}

// Renouveler un abonnement
const renewSubscription = async (instanceId) => {
  if (!confirm('Voulez-vous vraiment renouveler cet abonnement ?')) {
    return
  }

  try {
    const { $api } = useNuxtApp()
    const response = await $api.post(`/club/subscriptions/${instanceId}/renew`)
    
    if (response.data.success) {
      alert('Abonnement renouvelé avec succès')
      await loadSubscriptions()
      emit('success')
    } else {
      alert(response.data.message || 'Erreur lors du renouvellement')
    }
  } catch (error) {
    console.error('Erreur lors du renouvellement:', error)
    alert(error.response?.data?.message || 'Erreur lors du renouvellement de l\'abonnement')
  }
}

// Helpers
const getStatusLabel = (status) => {
  const labels = {
    'active': 'Actif',
    'completed': 'Terminé',
    'expired': 'Expiré',
    'cancelled': 'Annulé'
  }
  return labels[status] || status
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const isExpiringSoon = (expiresAt) => {
  if (!expiresAt) return false
  const now = new Date()
  const expiry = new Date(expiresAt)
  const diffDays = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24))
  return diffDays <= 30 && diffDays >= 0
}

const canRenew = (subscription) => {
  const isNearingExpiry = subscription.expires_at && isExpiringSoon(subscription.expires_at)
  const totalLessons = getTotalLessons(subscription)
  const isAlmostUsed = subscription.remaining_lessons <= (totalLessons * 0.2)
  return isNearingExpiry || isAlmostUsed
}

// Obtenir les types de cours (supporte template.courseTypes et course_types legacy)
const getCourseTypes = (subscription) => {
  // Nouveau système via template (camelCase ou snake_case)
  if (subscription?.subscription?.template) {
    return subscription.subscription.template.courseTypes || 
           subscription.subscription.template.course_types || 
           []
  }
  // Legacy direct
  if (subscription?.subscription?.courseTypes) {
    return subscription.subscription.courseTypes
  }
  if (subscription?.subscription?.course_types) {
    return subscription.subscription.course_types
  }
  return []
}

// Obtenir le total de cours disponibles
const getTotalLessons = (subscription) => {
  // Via template (nouveau système)
  if (subscription?.subscription?.template) {
    const template = subscription.subscription.template
    return (template.total_lessons || 0) + (template.free_lessons || 0)
  }
  // Legacy
  return (subscription.subscription?.total_lessons || 0) + (subscription.subscription?.free_lessons || 0)
}

onMounted(() => {
  loadSubscriptions()
})
</script>

