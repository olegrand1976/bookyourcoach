<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-900">
          Ajouter un étudiant existant
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
        <!-- Mode Selection -->
        <div class="mb-6">
          <div class="flex space-x-4">
            <button
              @click="mode = 'qr'"
              :class="[
                'flex-1 py-3 px-4 rounded-lg border-2 transition-all',
                mode === 'qr' 
                  ? 'border-blue-500 bg-blue-50 text-blue-700' 
                  : 'border-gray-200 text-gray-600 hover:border-gray-300'
              ]"
            >
              <div class="flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
                <span>Scanner QR Code</span>
              </div>
            </button>
            <button
              @click="mode = 'search'"
              :class="[
                'flex-1 py-3 px-4 rounded-lg border-2 transition-all',
                mode === 'search' 
                  ? 'border-blue-500 bg-blue-50 text-blue-700' 
                  : 'border-gray-200 text-gray-600 hover:border-gray-300'
              ]"
            >
              <div class="flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span>Recherche manuelle</span>
              </div>
            </button>
          </div>
        </div>

        <!-- QR Code Scanner -->
        <div v-if="mode === 'qr'" class="space-y-4">
          <div class="text-center">
            <h4 class="text-lg font-medium text-gray-900 mb-2">Scanner le QR Code de l'étudiant</h4>
            <p class="text-gray-600 text-sm">Demandez à l'étudiant d'afficher son QR Code sur son écran</p>
          </div>
          
          <div class="bg-gray-50 rounded-lg p-6 text-center">
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
        </div>

        <!-- Manual Search -->
        <div v-if="mode === 'search'" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Rechercher un étudiant
            </label>
            <div class="relative">
              <input
                v-model="searchQuery"
                @input="searchUsers"
                type="text"
                placeholder="Nom, email ou téléphone..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <div v-if="isSearching" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
              </div>
            </div>
          </div>

          <!-- Search Results -->
          <div v-if="searchResults.length > 0" class="space-y-2">
            <h4 class="text-sm font-medium text-gray-700">Résultats de recherche :</h4>
            <div class="max-h-60 overflow-y-auto space-y-2">
              <div
                v-for="user in searchResults"
                :key="user.id"
                @click="selectUser(user)"
                class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <p class="font-medium text-gray-900">{{ user.name }}</p>
                    <p class="text-sm text-gray-600">{{ user.email }}</p>
                    <p v-if="user.phone" class="text-sm text-gray-500">{{ user.phone }}</p>
                  </div>
                  <div class="text-sm text-gray-500">
                    {{ user.role === 'student' ? 'Étudiant' : user.role }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="searchQuery && !isSearching && searchResults.length === 0" class="text-center py-4">
            <p class="text-gray-500">Aucun étudiant trouvé</p>
          </div>
        </div>

        <!-- Selected User Info -->
        <div v-if="selectedUser" class="mt-6 p-4 bg-blue-50 rounded-lg">
          <h4 class="font-medium text-blue-900 mb-2">Étudiant sélectionné :</h4>
          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-blue-900">{{ selectedUser.name }}</p>
              <p class="text-sm text-blue-700">{{ selectedUser.email }}</p>
            </div>
            <button
              @click="selectedUser = null"
              class="text-blue-600 hover:text-blue-800"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
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
          @click="addStudentToClub"
          :disabled="!selectedUser || isAdding"
          :class="[
            'px-6 py-2 rounded-lg font-medium transition-colors',
            selectedUser && !isAdding
              ? 'bg-blue-600 text-white hover:bg-blue-700'
              : 'bg-gray-300 text-gray-500 cursor-not-allowed'
          ]"
        >
          <span v-if="isAdding" class="flex items-center space-x-2">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
            <span>Ajout en cours...</span>
          </span>
          <span v-else>Ajouter au club</span>
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
  clubId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'success'])

const { showToast } = useToast()

// State
const mode = ref('qr')
const searchQuery = ref('')
const searchResults = ref([])
const selectedUser = ref(null)
const isSearching = ref(false)
const isAdding = ref(false)

// Methods
const closeModal = () => {
  emit('close')
  resetForm()
}

const resetForm = () => {
  mode.value = 'qr'
  searchQuery.value = ''
  searchResults.value = []
  selectedUser.value = null
  isSearching.value = false
  isAdding.value = false
}

const simulateQrScan = async () => {
  // Simulation d'un scan QR Code pour les tests
  try {
    const testUser = {
      id: 1,
      name: 'Étudiant Test',
      email: 'student@test.com',
      role: 'student',
      phone: '0123456789'
    }
    selectedUser.value = testUser
    showToast('QR Code scanné avec succès', 'success')
  } catch (error) {
    showToast('Erreur lors du scan du QR Code', 'error')
  }
}

const searchUsers = async () => {
  if (searchQuery.value.length < 2) {
    searchResults.value = []
    return
  }

  isSearching.value = true
  
  try {
    // Simulation de recherche pour les tests
    // En production, utiliser l'API réelle
    await new Promise(resolve => setTimeout(resolve, 500))
    
    const mockResults = [
      {
        id: 1,
        name: 'Étudiant Test 1',
        email: 'student1@test.com',
        role: 'student',
        phone: '0123456789'
      },
      {
        id: 2,
        name: 'Étudiant Test 2',
        email: 'student2@test.com',
        role: 'student',
        phone: '0987654321'
      }
    ].filter(user => 
      user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      user.email.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
    
    searchResults.value = mockResults
  } catch (error) {
    showToast('Erreur lors de la recherche', 'error')
    searchResults.value = []
  } finally {
    isSearching.value = false
  }
}

const selectUser = (user) => {
  selectedUser.value = user
  searchQuery.value = ''
  searchResults.value = []
}

const addStudentToClub = async () => {
  if (!selectedUser.value) return

  isAdding.value = true

  try {
    // Simulation de l'ajout pour les tests
    // En production, utiliser l'API réelle
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    showToast('Étudiant ajouté au club avec succès', 'success')
    emit('success')
    closeModal()
  } catch (error) {
    showToast('Erreur lors de l\'ajout de l\'étudiant', 'error')
  } finally {
    isAdding.value = false
  }
}

// Watch for search query changes
watch(searchQuery, () => {
  if (searchQuery.value.length >= 2) {
    searchUsers()
  } else {
    searchResults.value = []
  }
})
</script>
