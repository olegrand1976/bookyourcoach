<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-semibold text-gray-900">Payer la séance</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div v-if="lesson" class="mb-6 bg-gray-50 p-4 rounded-lg">
        <h4 class="font-medium text-gray-900 mb-2">{{ lesson.course_type?.name || 'Cours' }}</h4>
        <div class="space-y-1 text-sm text-gray-600">
          <p class="flex justify-between">
             <span>Date:</span>
             <span class="font-medium text-gray-900">{{ formatDate(lesson.start_time) }}</span>
          </p>
          <p class="flex justify-between">
            <span>Enseignant:</span>
            <span class="font-medium text-gray-900">{{ lesson.teacher?.user?.name }}</span>
          </p>
          <div class="flex justify-between pt-2 mt-2 border-t border-gray-200">
             <span class="font-semibold text-gray-900">Montant à payer:</span>
             <span class="font-bold text-lg text-blue-600">{{ formatPrice(lesson.price) }}</span>
          </div>
        </div>
      </div>

      <!-- Stripe Elements Container -->
      <form @submit.prevent="handleSubmit" class="mb-4">
        <div id="payment-element" class="mb-4 min-h-[50px]">
           <!-- Stripe Elements will be inserted here -->
        </div>

        <div v-if="errorMessage" class="mb-4 text-red-600 text-sm">
          {{ errorMessage }}
        </div>

        <button
          type="submit"
          :disabled="processing || !stripe || !elements"
          class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-semibold flex items-center justify-center space-x-2"
        >
          <svg v-if="processing" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span v-else>💳 Payer {{ lesson ? formatPrice(lesson.price) : '' }}</span>
        </button>
      </form>
      
      <p class="text-xs text-center text-gray-500 flex items-center justify-center gap-1">
        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 2.18l7 3.12v4.86c0 4.54-3.09 8.78-7 9.87-3.91-1.09-7-5.33-7-9.87V6.3l7-3.12z"/></svg>
        Paiement sécurisé par Stripe
      </p>
    </div>
  </div>
</template>

<script setup>
import { loadStripe } from '@stripe/stripe-js'

const props = defineProps({
  lesson: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'success'])
const { $api } = useNuxtApp()
const config = useRuntimeConfig()

// State
const stripe = ref(null)
const elements = ref(null)
const processing = ref(false)
const errorMessage = ref(null)
const clientSecret = ref(null)
const stripeKey = config.public.stripeKey || 'pk_test_TYooMQauvdEDq54NiTphI7jx' // Fallback for dev

// Initialize Stripe
onMounted(async () => {
  try {
    stripe.value = await loadStripe(stripeKey)
    
    // Create PaymentIntent via API
    const response = await $api.post('/student/payments/create-intent', {
      lesson_id: props.lesson.id
    })
    
    if (response.data.success) {
      clientSecret.value = response.data.client_secret
      
      const appearance = { theme: 'stripe' }
      elements.value = stripe.value.elements({ 
        appearance, 
        clientSecret: clientSecret.value 
      })
      
      const paymentElement = elements.value.create('payment')
      paymentElement.mount('#payment-element')
    } else {
      errorMessage.value = response.data.message || 'Erreur lors de l\'initialisation du paiement'
    }
  } catch (err) {
    console.error('Error initializing Stripe:', err)
    errorMessage.value = 'Erreur lors de l\'initialisation du paiement'
  }
})

const handleSubmit = async () => {
  if (!stripe.value || !elements.value || !clientSecret.value) return
  
  processing.value = true
  errorMessage.value = null
  
  try {
    const { error } = await stripe.value.confirmPayment({
      elements: elements.value,
      redirect: 'if_required' 
    })

    if (error) {
       errorMessage.value = error.message
    } else {
       // Payment succeeded!
       emit('success')
       emit('close')
    }
  } catch (err) {
    errorMessage.value = 'Une erreur est survenue lors du paiement.'
    console.error(err)
  } finally {
    processing.value = false
  }
}

// Helpers
const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(price || 0)
}
</script>
