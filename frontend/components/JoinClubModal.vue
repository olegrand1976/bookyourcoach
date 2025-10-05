<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-900">
          Rejoindre un club
        </h3>
        <button
          @click="closeModal"
          class="text-gray-400 hover:text-gray-600 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="p-6">
        <!-- Instructions -->
        <div class="text-center mb-6">
          <div class="bg-blue-100 p-3 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
            </svg>
          </div>
          <h4 class="text-lg font-medium text-gray-900 mb-2">Scanner le QR Code du club</h4>
          <p class="text-gray-600 text-sm">
            Scannez le QR code affiché par le club pour rejoindre automatiquement
          </p>
        </div>

        <!-- QR Code Scanner Area -->
        <div class="bg-gray-50 rounded-lg p-6 text-center mb-6">
          <div class="mb-4">
            <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
            </svg>
          </div>
          <p class="text-gray-600 mb-4">Fonctionnalité de scan QR Code à implémenter</p>
          <button
            @click="simulateQrScan"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
          >
            Simuler le scan (Test)
          </button>
        </div>

        <!-- Manual QR Code Input -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Ou saisissez le code QR manuellement
          </label>
          <input
            v-model="manualQrCode"
            type="text"
            placeholder="ACTIVIBE_CLUB_..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <!-- Club Info Display -->
        <div v-if="scannedClub" class="mb-6 p-4 bg-green-50 rounded-lg">
          <h4 class="font-medium text-green-900 mb-2">Club trouvé :</h4>
          <div class="text-sm text-green-800">
            <p class="font-medium">{{ scannedClub.name }}</p>
            <p>{{ scannedClub.email }}</p>
            <p v-if="scannedClub.address">{{ scannedClub.address }}, {{ scannedClub.city }}</p>
            <p v-if="scannedClub.description" class="mt-2">{{ scannedClub.description }}</p>
          </div>
        </div>

        <!-- User Role Selection -->
        <div v-if="scannedClub" class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Votre rôle dans ce club
          </label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input
                v-model="selectedRole"
                type="radio"
                value="teacher"
                class="mr-3 text-blue-600 focus:ring-blue-500"
              />
              <span class="text-sm text-gray-700">Enseignant</span>
            </label>
            <label class="flex items-center">
              <input
                v-model="selectedRole"
                type="radio"
                value="student"
                class="mr-3 text-blue-600 focus:ring-blue-500"
              />
              <span class="text-sm text-gray-700">Étudiant</span>
            </label>
          </div>
        </div>

        <!-- Additional Info for Students -->
        <div v-if="scannedClub && selectedRole === 'student'" class="mb-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Niveau</label>
            <select
              v-model="studentInfo.level"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Sélectionnez votre niveau</option>
              <option value="debutant">🌱 Débutant</option>
              <option value="intermediaire">📈 Intermédiaire</option>
              <option value="avance">⭐ Avancé</option>
              <option value="expert">🏆 Expert</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Objectifs (optionnel)</label>
            <textarea
              v-model="studentInfo.goals"
              placeholder="Décrivez vos objectifs..."
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
          </div>
        </div>

        <!-- Additional Info for Teachers -->
        <div v-if="scannedClub && selectedRole === 'teacher'" class="mb-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tarif horaire (€)</label>
            <input
              v-model="teacherInfo.hourly_rate"
              type="number"
              step="0.01"
              placeholder="25.00"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
        <button
          @click="closeModal"
          class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
        >
          Annuler
        </button>
        <button
          @click="joinClub"
          :disabled="!scannedClub || !selectedRole || isJoining"
          :class="[
            'px-6 py-2 rounded-lg font-medium transition-colors',
            scannedClub && selectedRole && !isJoining
              ? 'bg-blue-600 text-white hover:bg-blue-700'
              : 'bg-gray-300 text-gray-500 cursor-not-allowed'
          ]"
        >
          <span v-if="isJoining" class="flex items-center space-x-2">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
            <span>Rejoindre...</span>
          </span>
          <span v-else>Rejoindre le club</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  userId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'success'])

const { showToast } = useToast()

// State
const manualQrCode = ref('')
const scannedClub = ref(null)
const selectedRole = ref('')
const isJoining = ref(false)
const studentInfo = ref({
  level: '',
  goals: '',
  medical_info: ''
})
const teacherInfo = ref({
  hourly_rate: null,
  allowed_disciplines: [],
  restricted_disciplines: []
})

// Methods
const closeModal = () => {
  emit('close')
  resetForm()
}

const resetForm = () => {
  manualQrCode.value = ''
  scannedClub.value = null
  selectedRole.value = ''
  isJoining.value = false
  studentInfo.value = {
    level: '',
    goals: '',
    medical_info: ''
  }
  teacherInfo.value = {
    hourly_rate: null,
    allowed_disciplines: [],
    restricted_disciplines: []
  }
}

const simulateQrScan = async () => {
  // Simulation d'un scan QR Code pour les tests
  try {
    const testQrCode = 'ACTIVIBE_CLUB_TEST123'
    await scanQrCode(testQrCode)
    showToast('QR Code scanné avec succès', 'success')
  } catch (error) {
    showToast('Erreur lors du scan du QR Code', 'error')
  }
}

const scanQrCode = async (qrCode) => {
  try {
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/qr-code/scan`, {
      method: 'POST',
      body: {
        qr_code: qrCode
      }
    })

    if (response.success && response.type === 'club') {
      scannedClub.value = response.data
    } else {
      showToast('QR Code invalide ou ne correspond pas à un club', 'error')
    }
  } catch (error) {
    console.error('❌ Erreur lors du scan:', error)
    showToast('Erreur lors du scan du QR Code', 'error')
  }
}

const joinClub = async () => {
  if (!scannedClub.value || !selectedRole.value) return

  isJoining.value = true

  try {
    const config = useRuntimeConfig()
    const requestData = {
      qr_code: manualQrCode.value || 'ACTIVIBE_CLUB_TEST123', // Use manual input or test code
      user_id: props.userId,
      role: selectedRole.value
    }

    // Add role-specific data
    if (selectedRole.value === 'student') {
      requestData.level = studentInfo.value.level
      requestData.goals = studentInfo.value.goals
      requestData.medical_info = studentInfo.value.medical_info
    } else if (selectedRole.value === 'teacher') {
      requestData.hourly_rate = teacherInfo.value.hourly_rate
      requestData.allowed_disciplines = teacherInfo.value.allowed_disciplines
      requestData.restricted_disciplines = teacherInfo.value.restricted_disciplines
    }

    const response = await $fetch(`${config.public.apiBase}/join-club`, {
      method: 'POST',
      body: requestData
    })

    if (response.success) {
      showToast('Vous avez rejoint le club avec succès !', 'success')
      emit('success')
      closeModal()
    } else {
      showToast(response.message || 'Erreur lors de l\'ajout au club', 'error')
    }
  } catch (error) {
    console.error('❌ Erreur lors de l\'ajout au club:', error)
    showToast('Erreur lors de l\'ajout au club', 'error')
  } finally {
    isJoining.value = false
  }
}

// Watch for manual QR code input
watch(manualQrCode, (newValue) => {
  if (newValue && newValue.startsWith('ACTIVIBE_CLUB_')) {
    scanQrCode(newValue)
  }
})
</script>
