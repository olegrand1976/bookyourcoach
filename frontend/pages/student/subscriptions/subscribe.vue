<template>
  <div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Souscrire à un abonnement</h1>
            <p class="text-gray-600 mt-1">Choisissez un abonnement parmi ceux proposés par vos clubs</p>
          </div>
          <NuxtLink 
            to="/student/subscriptions"
            class="text-gray-600 hover:text-gray-900 flex items-center space-x-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Retour</span>
          </NuxtLink>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <p class="text-red-800">{{ error }}</p>
      </div>

      <!-- Liste des abonnements disponibles -->
      <div v-else-if="availableSubscriptions.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div 
          v-for="subscription in availableSubscriptions" 
          :key="subscription.id"
          class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden"
        >
          <!-- Header -->
          <div class="p-6 border-b border-gray-200">
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                  {{ subscription.name }}
                </h3>
                <p v-if="subscription.club" class="text-sm text-gray-600">
                  Club: {{ subscription.club.name }}
                </p>
                <p v-if="subscription.description" class="text-sm text-gray-600 mt-2">
                  {{ subscription.description }}
                </p>
              </div>
            </div>

            <!-- Détails -->
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Nombre de cours</span>
                <span class="text-sm font-semibold text-gray-900">
                  {{ subscription.total_lessons }}
                  <span v-if="subscription.free_lessons > 0" class="text-green-600">
                    + {{ subscription.free_lessons }} gratuit{{ subscription.free_lessons > 1 ? 's' : '' }}
                  </span>
                </span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Prix</span>
                <span class="text-lg font-bold text-green-600">
                  {{ subscription.price }} €
                </span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Prix par cours</span>
                <span class="text-sm font-medium text-gray-700">
                  {{ (subscription.price / subscription.total_lessons).toFixed(2) }} €
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Validité</span>
                <span class="text-sm font-medium text-gray-700">
                  {{ subscription.validity_months || 12 }} mois
                </span>
              </div>
            </div>
          </div>

          <!-- Types de cours inclus -->
          <div v-if="subscription.course_types?.length" class="p-4 bg-gray-50">
            <div class="text-xs font-medium text-gray-500 uppercase mb-2">Types de cours inclus</div>
            <div class="flex flex-wrap gap-1">
              <span 
                v-for="courseType in subscription.course_types" 
                :key="courseType.id"
                class="bg-white text-gray-700 px-2 py-1 rounded text-xs border border-gray-200"
              >
                {{ courseType.name }}
              </span>
            </div>
          </div>

          <!-- Action -->
          <div class="p-4 bg-blue-50 border-t border-blue-100">
            <button
              @click="subscribeToSubscription(subscription.id)"
              :disabled="subscribing"
              class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium"
            >
              <span v-if="!subscribing">Souscrire</span>
              <span v-else class="flex items-center justify-center">
                <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Souscription en cours...
              </span>
            </button>
          </div>
        </div>
      </div>

      <!-- Aucun abonnement disponible -->
      <div v-else class="bg-white rounded-lg shadow-sm p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900">Aucun abonnement disponible</h3>
        <p class="mt-2 text-sm text-gray-500">
          Aucun abonnement n'est actuellement proposé par vos clubs.
        </p>
        <div class="mt-6">
          <NuxtLink 
            to="/student/subscriptions"
            class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
          >
            Retour à mes abonnements
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: ['auth', 'student'],
  layout: 'student'
})

const { $api } = useNuxtApp()
const router = useRouter()
const availableSubscriptions = ref([])
const loading = ref(true)
const error = ref(null)
const subscribing = ref(false)

// Charger les abonnements disponibles
const loadAvailableSubscriptions = async () => {
  try {
    loading.value = true
    error.value = null
    
    const response = await $api.get('/student/subscriptions/available')
    if (response.data.success) {
      availableSubscriptions.value = response.data.data
    } else {
      error.value = response.data.message || 'Erreur lors du chargement des abonnements'
    }
  } catch (err) {
    console.error('Erreur lors du chargement des abonnements:', err)
    error.value = err.response?.data?.message || 'Erreur lors du chargement des abonnements'
  } finally {
    loading.value = false
  }
}

// Souscrire à un abonnement
const subscribeToSubscription = async (subscriptionId) => {
  if (!confirm('Voulez-vous vraiment souscrire à cet abonnement ?')) {
    return
  }

  try {
    subscribing.value = true
    
    const response = await $api.post('/student/subscriptions', {
      subscription_id: subscriptionId,
      started_at: new Date().toISOString().split('T')[0] // Aujourd'hui par défaut
    })
    
    if (response.data.success) {
      alert('Abonnement souscrit avec succès !')
      router.push('/student/subscriptions')
    } else {
      alert(response.data.message || 'Erreur lors de la souscription')
    }
  } catch (err) {
    console.error('Erreur lors de la souscription:', err)
    alert(err.response?.data?.message || 'Erreur lors de la souscription à l\'abonnement')
  } finally {
    subscribing.value = false
  }
}

// Charger au montage
onMounted(() => {
  loadAvailableSubscriptions()
})

useHead({
  title: 'Souscrire à un abonnement | BookYourCoach'
})
</script>

