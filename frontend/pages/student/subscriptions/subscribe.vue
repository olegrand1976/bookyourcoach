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
      <div v-else-if="availableSubscriptions.length > 0 || isEligibleForTrial" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Trial Session Card (Special UI) -->
        <div 
          v-if="isEligibleForTrial"
          class="bg-gradient-to-br from-blue-50 to-white border-2 border-blue-200 rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden"
        >
          <div class="p-6 border-b border-blue-100">
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <h3 class="text-xl font-bold text-blue-900 mb-1">Séance d'essai</h3>
                <span class="bg-blue-600 text-white text-[10px] uppercase px-2 py-0.5 rounded font-bold">Offre de bienvenue</span>
              </div>
            </div>
            
            <div class="space-y-3">
              <p class="text-sm text-gray-600">Idéal pour découvrir nos cours avant de vous engager.</p>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Prix unique</span>
                <span class="text-2xl font-black text-blue-600">{{ formatPrice(18) }}</span>
              </div>
              <p class="text-[10px] text-gray-400 italic">* Limité à une seule séance par compte utilisateur.</p>
            </div>
          </div>
          <div class="p-4">
            <button
              @click="handleTrialSession"
              class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-bold text-sm flex items-center justify-center space-x-2 shadow-sm"
            >
              <span>Réserver ma séance d'essai</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Filter out the 18€ template from the list to avoid duplication if trial card is shown -->
        <template v-for="subscription in availableSubscriptions" :key="subscription.id">
          <div 
            v-if="!(isEligibleForTrial && subscription.price == 18)"
            class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col h-full"
            :class="{'border-2 border-emerald-500 ring-2 ring-emerald-100': subscription.price === 180}"
          >
            <!-- Header -->
            <div class="p-6 border-b border-gray-200 flex-grow">
              <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <h3 class="text-lg font-bold text-gray-900">
                      {{ subscription.price === 180 ? 'Pack 10 cours' : (subscription.model_number || 'Abonnement') }}
                    </h3>
                    <span v-if="subscription.price === 180" class="bg-emerald-100 text-emerald-700 text-[10px] uppercase px-2 py-0.5 rounded font-bold">Le plus populaire</span>
                  </div>
                  <div class="flex flex-col gap-1">
                    <p v-if="subscription.club" class="text-sm text-gray-600">
                      {{ subscription.club.name }}
                    </p>
                    <span v-if="subscription.is_recurring" class="self-start bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full font-medium">
                      Abonnement Récurrent
                    </span>
                  </div>
                </div>
              </div>

              <!-- Détails -->
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">Nombre de séances</span>
                  <span class="text-sm font-semibold text-gray-900">
                    {{ subscription.total_lessons }} 
                    {{ subscription.total_lessons > 1 ? 'cours' : 'cours' }}
                    <span v-if="subscription.free_lessons > 0" class="text-green-600">
                      + {{ subscription.free_lessons }} offert{{ subscription.free_lessons > 1 ? 's' : '' }}
                    </span>
                  </span>
                </div>
                
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">Prix total</span>
                  <span class="text-xl font-black text-gray-900" :class="{'text-emerald-600': subscription.price === 180}">
                    {{ formatPrice(subscription.price) }}
                  </span>
                </div>
                
                <div v-if="subscription.total_lessons > 1" class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">Soit par cours</span>
                  <span class="text-sm font-medium text-gray-700">
                    {{ formatPricePerLesson(subscription.price, subscription.total_lessons) }}
                  </span>
                </div>

                <div v-if="subscription.validity_months" class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">Durée de validité</span>
                  <span class="text-sm font-medium text-gray-700" :class="{'text-emerald-600 font-bold': subscription.validity_months === 24}">
                    {{ subscription.validity_months }} mois
                  </span>
                </div>
              </div>
            </div>

            <!-- Action -->
            <div class="p-4 bg-gray-50 border-t border-gray-100 mt-auto">
              <button
                @click="subscribeToSubscription(subscription.id)"
                :disabled="subscribing"
                class="w-full px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-black transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-sm font-bold flex items-center justify-center space-x-2"
                :class="{'bg-emerald-600 hover:bg-emerald-700': subscription.price === 180}"
              >
                <span v-if="!subscribing">💳 Choisir cette offre</span>
                <span v-else class="flex items-center justify-center">
                  <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Redirection...
                </span>
              </button>
            </div>
          </div>
        </template>
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
import { useStudentData } from '~/composables/useStudentData'

definePageMeta({
  middleware: ['auth', 'student'],
  layout: 'student'
})

const { $api } = useNuxtApp()
const router = useRouter()
const { loadStats } = useStudentData()
const availableSubscriptions = ref([])
const loading = ref(true)
const eligibilityLoading = ref(true)
const isEligibleForTrial = ref(false)
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

const checkTrialEligibility = async () => {
  try {
    eligibilityLoading.value = true
    const stats = await loadStats()
    isEligibleForTrial.value = stats.student?.is_eligible_for_trial ?? false
  } catch (err) {
    console.error('Error checking trial eligibility:', err)
  } finally {
    eligibilityLoading.value = false
  }
}

// Souscrire à un abonnement via Stripe Checkout
const subscribeToSubscription = async (subscriptionTemplateId) => {
  try {
    subscribing.value = true
    
    // Créer la session Stripe Checkout
    const response = await $api.post('/student/subscriptions/create-checkout-session', {
      subscription_template_id: subscriptionTemplateId
    })
    
    if (response.data.success && response.data.checkout_url) {
      // Rediriger vers Stripe Checkout
      window.location.href = response.data.checkout_url
    } else {
      alert(response.data.message || 'Erreur lors de la création de la session de paiement')
    }
  } catch (err) {
    console.error('Erreur lors de la souscription:', err)
    alert(err.response?.data?.message || 'Erreur lors de la création de la session de paiement')
  } finally {
    subscribing.value = false
  }
}

const handleTrialSession = async () => {
  try {
    subscribing.value = true
    // Pour la séance d'essai dans la page d'abonnement, on a besoin d'une leçon?
    // En fait non, le user veut peut-être juste "acheter" une séance d'essai d'avance?
    // Mais normalement la séance d'essai est liée à une réservation.
    // Si on est ici, on redirige plutôt vers le planning pour choisir une séance.
    router.push('/student/lessons')
  } catch (err) {
    console.error('Erreur séance d\'essai:', err)
  } finally {
    subscribing.value = false
  }
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(price)
}

const formatPricePerLesson = (price, totalLessons) => {
  if (!totalLessons || totalLessons === 0) return formatPrice(0)
  return formatPrice(price / totalLessons)
}

// Vérifier si on revient d'un paiement réussi
onMounted(() => {
  loadAvailableSubscriptions()
  checkTrialEligibility()
  
  // Vérifier si on revient d'un paiement Stripe réussi
  const urlParams = new URLSearchParams(window.location.search)
  const sessionId = urlParams.get('session_id')
  
  if (sessionId) {
    // Recharger les abonnements pour voir le nouveau
    setTimeout(() => {
      router.push('/student/subscriptions')
    }, 2000)
  }
})

useHead({
  title: 'Souscrire à un abonnement | BookYourCoach'
})
</script>
