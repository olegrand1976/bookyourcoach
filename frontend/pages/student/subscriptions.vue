<template>
  <div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Mes Abonnements</h1>
            <p class="text-gray-600 mt-1">Visualisez et gérez vos abonnements de cours</p>
          </div>
          <NuxtLink 
            to="/student/subscriptions/subscribe"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span>Souscrire à un abonnement</span>
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

      <!-- Liste des abonnements -->
      <div v-else-if="subscriptions.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div 
          v-for="subscription in subscriptions" 
          :key="subscription.id"
          class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden"
        >
          <!-- Header -->
          <div class="p-6 border-b border-gray-200">
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                  {{ subscription.subscription?.template?.model_number || 'Abonnement' }}
                </h3>
                <p v-if="subscription.subscription?.club" class="text-sm text-gray-600">
                  Club: {{ subscription.subscription.club.name }}
                </p>
                <!-- Référence de l'abonnement -->
                <p v-if="subscription.subscription?.subscription_number" class="text-xs text-gray-500 mt-1">
                  Référence: {{ subscription.subscription.subscription_number }}
                </p>
              </div>
              <span 
                :class="{
                  'bg-green-100 text-green-800': subscription.status === 'active',
                  'bg-gray-100 text-gray-800': subscription.status === 'completed',
                  'bg-red-100 text-red-800': subscription.status === 'expired',
                  'bg-yellow-100 text-yellow-800': subscription.status === 'cancelled'
                }"
                class="px-3 py-1 text-xs font-medium rounded-full"
              >
                {{ getStatusLabel(subscription.status) }}
              </span>
            </div>

            <!-- Statistiques -->
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Cours utilisés</span>
                <span class="text-sm font-semibold text-gray-900">
                  {{ subscription.lessons_used || 0 }} / {{ getTotalLessons(subscription) }}
                </span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div 
                  :class="{
                    'bg-green-600': getRemainingLessons(subscription) > getTotalLessons(subscription) * 0.5,
                    'bg-yellow-600': getRemainingLessons(subscription) > getTotalLessons(subscription) * 0.2 && getRemainingLessons(subscription) <= getTotalLessons(subscription) * 0.5,
                    'bg-red-600': getRemainingLessons(subscription) <= getTotalLessons(subscription) * 0.2
                  }"
                  class="h-2 rounded-full transition-all"
                  :style="{ width: `${getUsagePercentage(subscription)}%` }"
                ></div>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Cours restants</span>
                <span class="font-semibold" :class="{
                  'text-green-600': getRemainingLessons(subscription) > getTotalLessons(subscription) * 0.5,
                  'text-yellow-600': getRemainingLessons(subscription) > getTotalLessons(subscription) * 0.2 && getRemainingLessons(subscription) <= getTotalLessons(subscription) * 0.5,
                  'text-red-600': getRemainingLessons(subscription) <= getTotalLessons(subscription) * 0.2
                }">
                  {{ getRemainingLessons(subscription) }}
                </span>
              </div>
              <!-- Total de cours utilisés -->
              <div class="flex items-center justify-between text-sm pt-2 border-t border-gray-200">
                <span class="text-gray-600 font-medium">Total cours utilisés</span>
                <span class="font-semibold text-gray-900">
                  {{ subscription.lessons_used || 0 }}
                </span>
              </div>
            </div>
          </div>

          <!-- Dates -->
          <div class="p-4 bg-gray-50 space-y-2">
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">Date de début</span>
              <span class="font-medium text-gray-900">
                {{ formatDate(subscription.started_at) }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">Date d'expiration</span>
              <span 
                :class="{
                  'text-red-600 font-semibold': isExpiringSoon(subscription.expires_at),
                  'text-gray-900': !isExpiringSoon(subscription.expires_at)
                }"
                class="font-medium"
              >
                {{ subscription.expires_at ? formatDate(subscription.expires_at) : 'Non définie' }}
              </span>
            </div>
            <div v-if="subscription.subscription?.template?.validity_months" class="flex items-center justify-between text-sm">
              <span class="text-gray-600">Validité</span>
              <span class="font-medium text-gray-900">
                {{ subscription.subscription.template.validity_months }} mois
              </span>
            </div>
          </div>

          <!-- Types de cours inclus -->
          <div v-if="subscription.subscription?.template?.course_types?.length" class="p-4 border-t border-gray-200">
            <div class="text-xs font-medium text-gray-500 uppercase mb-2">Types de cours inclus</div>
            <div class="flex flex-wrap gap-1">
              <span 
                v-for="courseType in subscription.subscription.template.course_types" 
                :key="courseType.id"
                class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs"
              >
                {{ courseType.name }}
              </span>
            </div>
          </div>

          <!-- Élèves partagés (si abonnement familial) -->
          <div v-if="subscription.students?.length > 1" class="p-4 bg-blue-50 border-t border-blue-100">
            <div class="text-xs font-medium text-blue-700 uppercase mb-2">Abonnement familial</div>
            <div class="flex flex-wrap gap-1">
              <span 
                v-for="student in subscription.students" 
                :key="student.id"
                class="bg-white text-blue-700 px-2 py-1 rounded text-xs border border-blue-200"
              >
                {{ student.user?.first_name }} {{ student.user?.last_name }}
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="p-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <button
              v-if="subscription.status === 'active' && canRenew(subscription)"
              @click="renewSubscription(subscription.id)"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm"
            >
              Renouveler
            </button>
            <button
              v-else-if="subscription.status === 'expired' && subscription.subscription?.template?.is_active"
              @click="renewSubscription(subscription.id)"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm"
            >
              Renouveler
            </button>
          </div>
        </div>
      </div>

      <!-- Aucun abonnement -->
      <div v-else class="bg-white rounded-lg shadow-sm p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900">Aucun abonnement</h3>
        <p class="mt-2 text-sm text-gray-500">
          Vous n'avez pas encore souscrit à un abonnement.
        </p>
        <div class="mt-6">
          <NuxtLink 
            to="/student/subscriptions/subscribe"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Souscrire à un abonnement
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- Modale de souscription Stripe -->
    <StripeSubscribeModal 
      v-if="showSubscribeModal"
      @close="showSubscribeModal = false"
      @success="handleSubscriptionSuccess"
    />
  </div>
</template>

<script setup>
definePageMeta({
  middleware: ['auth', 'student'],
  layout: 'student'
})

const { $api } = useNuxtApp()
const subscriptions = ref([])
const loading = ref(true)
const error = ref(null)
const showSubscribeModal = ref(false)

// Charger les abonnements
const loadSubscriptions = async () => {
  try {
    loading.value = true
    error.value = null
    
    const response = await $api.get('/student/subscriptions')
    if (response.data.success) {
      subscriptions.value = response.data.data
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

// Renouveler un abonnement
const renewSubscription = async (instanceId) => {
  if (!confirm('Voulez-vous vraiment renouveler cet abonnement ?')) {
    return
  }

  try {
    const response = await $api.post(`/student/subscriptions/${instanceId}/renew`)
    if (response.data.success) {
      alert('Abonnement renouvelé avec succès')
      await loadSubscriptions()
    } else {
      alert(response.data.message || 'Erreur lors du renouvellement')
    }
  } catch (err) {
    console.error('Erreur lors du renouvellement:', err)
    alert(err.response?.data?.message || 'Erreur lors du renouvellement de l\'abonnement')
  }
}

// Gérer le succès de la souscription
const handleSubscriptionSuccess = () => {
  showSubscribeModal.value = false
  loadSubscriptions()
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
  return diffDays <= 30 && diffDays >= 0 // Expire dans moins de 30 jours
}

const canRenew = (subscription) => {
  const isNearingExpiry = subscription.expires_at && isExpiringSoon(subscription.expires_at)
  const totalLessons = getTotalLessons(subscription)
  const remainingLessons = getRemainingLessons(subscription)
  const isAlmostUsed = remainingLessons <= totalLessons * 0.2
  
  return isNearingExpiry || isAlmostUsed
}

const getTotalLessons = (subscription) => {
  const template = subscription.subscription?.template
  if (!template) return 0
  return (template.total_lessons || 0) + (template.free_lessons || 0)
}

const getRemainingLessons = (subscription) => {
  const total = getTotalLessons(subscription)
  const used = subscription.lessons_used || 0
  return Math.max(0, total - used)
}

const getUsagePercentage = (subscription) => {
  const total = getTotalLessons(subscription)
  if (total === 0) return 0
  const used = subscription.lessons_used || 0
  return Math.round((used / total) * 100)
}

// Vérifier si on revient d'un paiement Stripe réussi
const checkStripeReturn = () => {
  const urlParams = new URLSearchParams(window.location.search)
  const sessionId = urlParams.get('session_id')
  
  if (sessionId) {
    // Recharger les abonnements pour voir le nouveau
    loadSubscriptions()
    // Nettoyer l'URL
    window.history.replaceState({}, document.title, window.location.pathname)
  }
}

// Charger au montage
onMounted(() => {
  loadSubscriptions()
  checkStripeReturn()
})

useHead({
  title: 'Mes Abonnements | BookYourCoach'
})
</script>
