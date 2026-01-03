<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-semibold text-gray-900">Souscrire à un abonnement</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
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
      <div v-else-if="availableSubscriptions.length > 0" class="space-y-4">
        <div 
          v-for="subscription in availableSubscriptions" 
          :key="subscription.id"
          class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors cursor-pointer"
          :class="{ 'border-blue-500 bg-blue-50': selectedSubscription?.id === subscription.id }"
          @click="selectedSubscription = subscription"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <h4 class="font-semibold text-gray-900 mb-1">{{ subscription.model_number || 'Abonnement' }}</h4>
              <p v-if="subscription.club" class="text-sm text-gray-600 mb-2">
                Club: {{ subscription.club.name }}
              </p>
              <div class="space-y-1 text-sm text-gray-600">
                <p><strong>{{ subscription.total_lessons }}</strong> cours inclus</p>
                <p v-if="subscription.free_lessons > 0">
                  + <strong>{{ subscription.free_lessons }}</strong> cours gratuits
                </p>
                <p>Validité: <strong>{{ subscription.validity_months }}</strong> mois</p>
              </div>
            </div>
            <div class="text-right ml-4">
              <p class="text-2xl font-bold text-gray-900">{{ formatPrice(subscription.price) }}</p>
              <p class="text-xs text-gray-500">TTC</p>
            </div>
          </div>
        </div>

        <!-- Bouton de paiement Stripe -->
        <div v-if="selectedSubscription" class="mt-6 pt-6 border-t border-gray-200">
          <div class="bg-blue-50 rounded-lg p-4 mb-4">
            <h4 class="font-semibold text-gray-900 mb-2">Récapitulatif</h4>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600">Abonnement:</span>
                <span class="font-medium text-gray-900">{{ selectedSubscription.model_number }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Cours inclus:</span>
                <span class="font-medium text-gray-900">{{ selectedSubscription.total_lessons + (selectedSubscription.free_lessons || 0) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Validité:</span>
                <span class="font-medium text-gray-900">{{ selectedSubscription.validity_months }} mois</span>
              </div>
              <div class="flex justify-between pt-2 border-t border-gray-200">
                <span class="text-gray-900 font-semibold">Total:</span>
                <span class="text-gray-900 font-bold text-lg">{{ formatPrice(selectedSubscription.price) }}</span>
              </div>
            </div>
          </div>

          <button
            @click="processStripePayment"
            :disabled="processing"
            class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-semibold flex items-center justify-center space-x-2"
          >
            <svg v-if="processing" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-else>💳</span>
            <span>{{ processing ? 'Redirection vers Stripe...' : 'Payer avec Stripe' }}</span>
          </button>

          <p class="text-xs text-gray-500 text-center mt-3">
            Le paiement sera sécurisé via Stripe
          </p>
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
      </div>
    </div>
  </div>
</template>

<script setup>
const emit = defineEmits(['close', 'success'])

const { $api } = useNuxtApp()
const availableSubscriptions = ref([])
const selectedSubscription = ref(null)
const loading = ref(true)
const error = ref(null)
const processing = ref(false)

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

// Traiter le paiement Stripe
const processStripePayment = async () => {
  if (!selectedSubscription.value) {
    alert('Veuillez sélectionner un abonnement')
    return
  }

  try {
    processing.value = true
    
    // Créer la session Stripe Checkout
    const response = await $api.post('/student/subscriptions/create-checkout-session', {
      subscription_template_id: selectedSubscription.value.id
    })
    
    if (response.data.success && response.data.checkout_url) {
      // Rediriger vers Stripe Checkout
      window.location.href = response.data.checkout_url
    } else {
      alert(response.data.message || 'Erreur lors de la création de la session de paiement')
      processing.value = false
    }
  } catch (err) {
    console.error('Erreur lors de la souscription:', err)
    alert(err.response?.data?.message || 'Erreur lors de la création de la session de paiement')
    processing.value = false
  }
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(price)
}

// Charger au montage
onMounted(() => {
  loadAvailableSubscriptions()
})
</script>
