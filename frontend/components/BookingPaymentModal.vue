<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
    <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4 transform transition-all">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-900">Paiement requis</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="mb-8">
        <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-6">
          <p class="text-blue-800 text-sm">
            Vous n'avez pas de crédit disponible pour réserver ce cours.
            Choisissez une option pour continuer.
          </p>
        </div>

        <div v-if="lesson" class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-100">
          <h4 class="font-semibold text-gray-900 mb-2">{{ lesson.course_type?.name || 'Cours' }}</h4>
          <div class="space-y-2 text-sm text-gray-600">
            <div class="flex justify-between">
               <span>Date</span>
               <span class="font-medium text-gray-900">{{ formatDate(lesson.start_time) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Horaire</span>
              <span class="font-medium text-gray-900">
                {{ formatTime(lesson.start_time) }} - {{ formatTime(lesson.end_time) }}
              </span>
            </div>
            <div class="flex justify-between">
              <span>Enseignant</span>
              <span class="font-medium text-gray-900">{{ lesson.teacher?.user?.name }}</span>
            </div>
            <div class="flex justify-between pt-2 mt-2 border-t border-gray-200">
               <span class="font-bold text-gray-900">Prix à l'unité</span>
               <span class="font-bold text-lg text-blue-600">{{ formatPrice(lesson.price) }}</span>
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <!-- Option 1: Pay per session -->
          <button
            @click="handlePayPerSession"
            :disabled="processing"
            class="w-full group relative flex items-center justify-between p-4 border-2 border-blue-100 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all"
          >
            <div class="flex items-center">
              <span class="flex h-10 w-10 bg-blue-100 text-blue-600 rounded-full items-center justify-center mr-4 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
              </span>
              <div class="text-left">
                <p class="font-bold text-gray-900">Payer la séance</p>
                <p class="text-sm text-gray-500">Paiement unique via Stripe</p>
              </div>
            </div>
            <span class="text-lg font-bold text-blue-600">{{ formatPrice(lesson?.price) }}</span>
            
            <div v-if="processing" class="absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center rounded-xl">
               <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
               </svg>
            </div>
          </button>

          <!-- Option 2: Buy Pack -->
          <button
            @click="handleSubscribe"
            class="w-full flex items-center justify-between p-4 border-2 border-emerald-100 rounded-xl hover:border-emerald-500 hover:bg-emerald-50 transition-all group"
          >
            <div class="flex items-center">
              <span class="flex h-10 w-10 bg-emerald-100 text-emerald-600 rounded-full items-center justify-center mr-4 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
              </span>
              <div class="text-left">
                <p class="font-bold text-gray-900">Prendre un abonnement</p>
                <p class="text-sm text-gray-500">Plus économique sur la durée</p>
              </div>
            </div>
            <svg class="w-5 h-5 text-gray-400 group-hover:text-emerald-600 transform group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>
      
      <p class="text-xs text-center text-gray-400">
        Paiement 100% sécurisé via Stripe
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps({
  isOpen: Boolean,
  lesson: Object
})

const emit = defineEmits(['close'])
const router = useRouter()
const { $api } = useNuxtApp()
const processing = ref(false)

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long'
  })
}

const formatTime = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleTimeString('fr-FR', {
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

const handlePayPerSession = async () => {
  if (!props.lesson) return
  
  try {
    processing.value = true
    const response = await $api.post('/payments/create-lesson-checkout', {
      lesson_id: props.lesson.id
    })

    if (response.data.success && response.data.url) {
      window.location.href = response.data.url
    } else {
      alert(response.data.message || 'Erreur lors de l\'initialisation du paiement')
    }
  } catch (err) {
    console.error('Erreur paiement:', err)
    alert(err.response?.data?.message || 'Erreur technique lors du paiement')
  } finally {
    processing.value = false
  }
}

const handleSubscribe = () => {
  emit('close')
  router.push('/student/subscriptions/subscribe')
}
</script>
