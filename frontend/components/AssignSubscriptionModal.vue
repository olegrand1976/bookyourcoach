<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" @click.self="$emit('close')">
    <div class="flex items-center justify-center min-h-screen px-4 py-12">
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="$emit('close')"></div>
      
      <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">Assigner un abonnement</h2>
            <button 
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6">
          <!-- Élève(s) sélectionné(s) -->
          <div v-if="student" class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <p class="text-sm font-medium text-gray-700 mb-2">Élève sélectionné:</p>
            <p class="font-semibold text-gray-900">{{ student.name }}</p>
          </div>

          <!-- Sélection de l'abonnement -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Abonnement *
            </label>
            <select 
              v-model="form.subscription_id"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
              :disabled="loadingSubscriptions"
            >
              <option value="">Sélectionner un abonnement</option>
              <option 
                v-for="subscription in availableSubscriptions" 
                :key="subscription.id"
                :value="subscription.id"
              >
                {{ subscription.name }} - {{ subscription.price }}€ ({{ subscription.total_lessons }} cours, validité: {{ subscription.validity_months || 12 }} mois)
              </option>
            </select>
            <p v-if="!loadingSubscriptions && availableSubscriptions.length === 0" class="mt-2 text-sm text-amber-600">
              Aucun abonnement disponible. Veuillez d'abord créer un abonnement dans la section "Abonnements".
            </p>
          </div>

          <!-- Date de début -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Date de début *
            </label>
            <input 
              v-model="form.started_at"
              type="date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <!-- Date d'expiration (optionnelle, sera calculée automatiquement si non fournie) -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Date d'expiration (optionnelle)
            </label>
            <input 
              v-model="form.expires_at"
              type="date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
            />
            <p class="mt-1 text-xs text-gray-500">
              Laissé vide, la date d'expiration sera calculée automatiquement selon la durée de validité de l'abonnement.
            </p>
          </div>

          <!-- Élèves additionnels (pour abonnement familial) -->
          <div v-if="showFamilyOption" class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Ajouter d'autres élèves (abonnement familial)
            </label>
            <div class="border border-gray-300 rounded-lg p-3 max-h-48 overflow-y-auto">
              <label 
                v-for="s in otherStudents" 
                :key="s.id"
                class="flex items-center space-x-2 hover:bg-gray-50 p-2 rounded cursor-pointer"
              >
                <input 
                  type="checkbox" 
                  :value="s.id"
                  v-model="form.additional_student_ids"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-sm text-gray-700">{{ s.name }}</span>
              </label>
              <p v-if="otherStudents.length === 0" class="text-sm text-gray-500 italic">
                Aucun autre élève disponible
              </p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-3">
          <button
            @click="$emit('close')"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
          >
            Annuler
          </button>
          <button
            @click="assignSubscription"
            :disabled="!form.subscription_id || !form.started_at || submitting"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="!submitting">Assigner</span>
            <span v-else class="flex items-center">
              <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Attribution...
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  student: {
    type: Object,
    required: true
  },
  showFamilyOption: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['close', 'success'])

const form = ref({
  subscription_id: '',
  started_at: new Date().toISOString().split('T')[0],
  expires_at: '',
  additional_student_ids: []
})

const availableSubscriptions = ref([])
const otherStudents = ref([])
const loadingSubscriptions = ref(false)
const submitting = ref(false)

// Charger les abonnements disponibles
const loadSubscriptions = async () => {
  try {
    loadingSubscriptions.value = true
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/subscriptions')
    
    if (response.data.success) {
      availableSubscriptions.value = response.data.data.filter(s => s.is_active)
    }
  } catch (error) {
    console.error('Erreur lors du chargement des abonnements:', error)
  } finally {
    loadingSubscriptions.value = false
  }
}

// Charger les autres élèves (pour abonnement familial)
const loadOtherStudents = async () => {
  if (!props.showFamilyOption) return
  
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/students')
    
    if (response.data.success) {
      // Exclure l'élève actuel
      otherStudents.value = response.data.data.filter(s => s.id !== props.student.id)
    }
  } catch (error) {
    console.error('Erreur lors du chargement des élèves:', error)
  }
}

// Assigner l'abonnement
const assignSubscription = async () => {
  try {
    submitting.value = true
    const { $api } = useNuxtApp()
    
    // Préparer les IDs des élèves (l'élève principal + éventuellement d'autres)
    const studentIds = [props.student.id, ...form.value.additional_student_ids]
    
    const response = await $api.post('/club/subscriptions/assign', {
      subscription_id: form.value.subscription_id,
      student_ids: studentIds,
      started_at: form.value.started_at,
      expires_at: form.value.expires_at || null
    })
    
    if (response.data.success) {
      alert(response.data.message || 'Abonnement assigné avec succès')
      emit('success')
      emit('close')
    } else {
      alert(response.data.message || 'Erreur lors de l\'assignation')
    }
  } catch (error) {
    console.error('Erreur lors de l\'assignation:', error)
    alert(error.response?.data?.message || 'Erreur lors de l\'assignation de l\'abonnement')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadSubscriptions()
  loadOtherStudents()
})
</script>

