<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" @click.self="$emit('close')">
    <div class="flex items-center justify-center min-h-screen px-4 py-12">
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="$emit('close')"></div>
      
      <div class="relative bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-500 to-indigo-600">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-bold text-white">Historique de {{ getStudentName(student) }}</h2>
              <p class="text-sm text-purple-100 mt-1">Abonnements et cours</p>
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
          <!-- Loading -->
          <div v-if="loading" class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
          </div>

          <!-- Error -->
          <div v-else-if="error" class="p-6">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
              <p class="text-red-800">{{ error }}</p>
            </div>
          </div>

          <!-- Data -->
          <div v-else class="p-6 space-y-6">
            <!-- Statistiques -->
            <div v-if="historyData?.stats" class="grid grid-cols-2 md:grid-cols-5 gap-4">
              <div class="bg-blue-50 rounded-lg p-4">
                <div class="text-sm text-blue-600 font-medium">Abonnements</div>
                <div class="text-2xl font-bold text-blue-900 mt-1">{{ historyData.stats.total_subscriptions }}</div>
              </div>
              <div class="bg-green-50 rounded-lg p-4">
                <div class="text-sm text-green-600 font-medium">Actifs</div>
                <div class="text-2xl font-bold text-green-900 mt-1">{{ historyData.stats.active_subscriptions }}</div>
              </div>
              <div class="bg-purple-50 rounded-lg p-4">
                <div class="text-sm text-purple-600 font-medium">Cours</div>
                <div class="text-2xl font-bold text-purple-900 mt-1">{{ historyData.stats.total_lessons }}</div>
              </div>
              <div class="bg-emerald-50 rounded-lg p-4">
                <div class="text-sm text-emerald-600 font-medium">Terminés</div>
                <div class="text-2xl font-bold text-emerald-900 mt-1">{{ historyData.stats.completed_lessons }}</div>
              </div>
              <div class="bg-amber-50 rounded-lg p-4">
                <div class="text-sm text-amber-600 font-medium">Dépensé</div>
                <div class="text-2xl font-bold text-amber-900 mt-1">{{ formatPrice(historyData.stats.total_spent) }} €</div>
              </div>
            </div>

            <!-- Abonnements -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Abonnements ({{ historyData?.subscriptions?.length || 0 }})
              </h3>
              
              <div v-if="!historyData?.subscriptions || historyData.subscriptions.length === 0" class="bg-gray-50 rounded-lg p-6 text-center">
                <p class="text-gray-500">Aucun abonnement pour cet élève</p>
              </div>
              
              <div v-else class="space-y-4">
                <div 
                  v-for="subscription in historyData.subscriptions" 
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
                          <span class="text-gray-600">Début:</span>
                          <span class="font-medium text-gray-900 ml-1">{{ formatDate(subscription.started_at) }}</span>
                        </div>
                        <div>
                          <span class="text-gray-600">Expiration:</span>
                          <span class="font-medium text-gray-900 ml-1">
                            {{ subscription.expires_at ? formatDate(subscription.expires_at) : 'Non définie' }}
                          </span>
                        </div>
                        <div>
                          <span class="text-gray-600">Créé le:</span>
                          <span class="font-medium text-gray-900 ml-1">{{ formatDate(subscription.created_at) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Cours -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                Cours ({{ historyData?.lessons?.length || 0 }})
              </h3>
              
              <div v-if="!historyData?.lessons || historyData.lessons.length === 0" class="bg-gray-50 rounded-lg p-6 text-center">
                <p class="text-gray-500">Aucun cours pour cet élève</p>
              </div>
              
              <div v-else class="space-y-3">
                <div 
                  v-for="lesson in historyData.lessons" 
                  :key="lesson.id"
                  class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:bg-gray-100 transition-colors"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="flex items-center space-x-3 mb-2">
                        <h4 class="font-semibold text-gray-900">
                          {{ lesson.course_type?.name || 'Cours' }}
                        </h4>
                        <span 
                          :class="{
                            'bg-green-100 text-green-800': lesson.status === 'completed',
                            'bg-blue-100 text-blue-800': lesson.status === 'confirmed',
                            'bg-yellow-100 text-yellow-800': lesson.status === 'pending',
                            'bg-red-100 text-red-800': lesson.status === 'cancelled'
                          }"
                          class="px-2 py-1 text-xs font-medium rounded-full"
                        >
                          {{ getLessonStatusLabel(lesson.status) }}
                        </span>
                      </div>
                      
                      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                          <span class="text-gray-600">Date:</span>
                          <span class="font-medium text-gray-900 ml-1">{{ formatDateTime(lesson.start_time) }}</span>
                        </div>
                        <div v-if="lesson.teacher?.user">
                          <span class="text-gray-600">Enseignant:</span>
                          <span class="font-medium text-gray-900 ml-1">{{ lesson.teacher.user.name }}</span>
                        </div>
                        <div>
                          <span class="text-gray-600">Prix:</span>
                          <span class="font-medium text-gray-900 ml-1">{{ formatPrice(lesson.price || 0) }} €</span>
                        </div>
                        <div v-if="lesson.location">
                          <span class="text-gray-600">Lieu:</span>
                          <span class="font-medium text-gray-900 ml-1">{{ lesson.location.name }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end">
          <button 
            @click="$emit('close')"
            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'

const props = defineProps({
  student: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'success'])

const loading = ref(false)
const error = ref(null)
const historyData = ref(null)

// Helper pour obtenir le nom de l'élève
const getStudentName = (student) => {
  if (student?.name) return student.name
  if (student?.first_name || student?.last_name) {
    const name = ((student.first_name || '') + ' ' + (student.last_name || '')).trim()
    return name || 'Élève sans nom'
  }
  return 'Élève sans nom'
}

// Charger l'historique
const loadHistory = async () => {
  try {
    loading.value = true
    error.value = null
    
    const { $api } = useNuxtApp()
    const response = await $api.get(`/club/students/${props.student.id}/history`)
    
    if (response.data.success) {
      historyData.value = response.data.data
    } else {
      error.value = response.data.message || 'Erreur lors du chargement de l\'historique'
    }
  } catch (err) {
    console.error('Erreur chargement historique:', err)
    error.value = err.response?.data?.message || 'Erreur lors du chargement de l\'historique'
  } finally {
    loading.value = false
  }
}

// Formatters
const formatDate = (date) => {
  if (!date) return 'Non définie'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateTime = (dateTime) => {
  if (!dateTime) return 'Non définie'
  return new Date(dateTime).toLocaleString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatPrice = (price) => {
  return parseFloat(price || 0).toFixed(2)
}

const getStatusLabel = (status) => {
  const labels = {
    active: 'Actif',
    completed: 'Terminé',
    expired: 'Expiré',
    cancelled: 'Annulé'
  }
  return labels[status] || status
}

const getLessonStatusLabel = (status) => {
  const labels = {
    completed: 'Terminé',
    confirmed: 'Confirmé',
    pending: 'En attente',
    cancelled: 'Annulé'
  }
  return labels[status] || status
}

const getTotalLessons = (subscription) => {
  const template = subscription.subscription?.template
  if (template) {
    return (template.total_lessons || 0) + (template.free_lessons || 0)
  }
  return subscription.subscription?.total_lessons || 0
}

// Charger l'historique quand le composant est monté
onMounted(() => {
  loadHistory()
})

// Recharger si le student change
watch(() => props.student, () => {
  loadHistory()
}, { immediate: true })
</script>

