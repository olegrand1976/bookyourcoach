<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Mon QR Code</h1>
        <p class="mt-2 text-gray-600">
          Présentez ce QR code aux clubs pour vous ajouter rapidement
        </p>
      </div>

      <!-- QR Code Card -->
      <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="bg-blue-100 p-2 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900">Code QR Personnel</h3>
                <p class="text-sm text-gray-600">{{ user?.name }} - {{ user?.email }}</p>
              </div>
            </div>
            <button 
              @click="generateNewQrCode"
              :disabled="loading"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors text-sm font-medium"
            >
              {{ loading ? 'Génération...' : 'Régénérer' }}
            </button>
          </div>
        </div>
        
        <div class="p-8">
          <div v-if="qrData" class="text-center">
            <!-- QR Code SVG -->
            <div class="inline-block p-4 bg-white border-2 border-gray-200 rounded-lg shadow-sm">
              <div v-html="qrData.qr_svg" class="mx-auto"></div>
            </div>
            
            <!-- Informations -->
            <div class="mt-6 space-y-2">
              <p class="text-sm text-gray-600">Code QR:</p>
              <p class="font-mono text-sm bg-gray-100 px-3 py-2 rounded-lg inline-block">{{ qrData.qr_code }}</p>
            </div>
            
            <div class="mt-4 text-xs text-gray-500">
              Généré le {{ formatDate(qrData.generated_at) }}
            </div>
          </div>
          
          <div v-else class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
            </svg>
            <p class="text-gray-500">Chargement du QR code...</p>
          </div>
        </div>
      </div>

      <!-- Instructions -->
      <div class="mt-8 bg-blue-50 rounded-xl p-6">
        <div class="flex items-start space-x-3">
          <div class="bg-blue-100 p-2 rounded-lg">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div>
            <h4 class="text-lg font-semibold text-gray-900 mb-2">Comment utiliser votre QR code</h4>
            <div class="space-y-2 text-sm text-gray-700">
              <p>1. <strong>Présentez votre QR code</strong> aux clubs qui souhaitent vous ajouter</p>
              <p>2. <strong>Ils peuvent le scanner</strong> avec leur application ou saisir le code manuellement</p>
              <p>3. <strong>Vous serez ajouté</strong> automatiquement à leur liste d'enseignants</p>
              <p>4. <strong>Vous pourrez enseigner</strong> dans plusieurs clubs simultanément</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Clubs actuels -->
      <div v-if="userClubs.length > 0" class="mt-8 bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-emerald-50 to-teal-50">
          <h3 class="text-lg font-semibold text-gray-900">Clubs où vous enseignez</h3>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div 
              v-for="club in userClubs" 
              :key="club.id" 
              class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium text-gray-900">{{ club.name }}</h4>
                  <p class="text-sm text-gray-600">{{ club.email }}</p>
                  <p class="text-xs text-emerald-600">Membre depuis {{ formatDate(club.pivot.joined_at) }}</p>
                </div>
                <span class="px-2 py-1 text-xs font-medium bg-emerald-100 text-emerald-800 rounded-full">
                  Actif
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

definePageMeta({
  middleware: ['auth']
})

const user = ref(null)
const qrData = ref(null)
const userClubs = ref([])
const loading = ref(false)

// Charger les données utilisateur et QR code
const loadUserData = async () => {
  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    
    // Charger les données utilisateur
    const userResponse = await $fetch(`${config.public.apiBase}/auth/user`)
    user.value = userResponse.user
    
    // Charger le QR code
    if (user.value) {
      const qrResponse = await $fetch(`${config.public.apiBase}/qr-code/${user.value.id}`)
      if (qrResponse.success) {
        qrData.value = qrResponse.data
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement des données:', error)
  }
}

// Charger les clubs de l'utilisateur
const loadUserClubs = async () => {
  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    
    // Pour les enseignants, charger les clubs où ils enseignent
    if (user.value?.role === 'teacher') {
      const response = await $fetch(`${config.public.apiBase}/teacher/clubs`)
      if (response.success) {
        userClubs.value = response.data
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement des clubs:', error)
  }
}

// Générer un nouveau QR code
const generateNewQrCode = async () => {
  loading.value = true
  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    
    const response = await $fetch(`${config.public.apiBase}/qr-code/${user.value.id}`)
    if (response.success) {
      qrData.value = response.data
      
      const { showToast } = useToast()
      showToast('Nouveau QR code généré !', 'success')
    }
  } catch (error) {
    console.error('Erreur lors de la génération du QR code:', error)
    
    const { showToast } = useToast()
    showToast('Erreur lors de la génération du QR code', 'error')
  } finally {
    loading.value = false
  }
}

// Formater une date
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

onMounted(() => {
  loadUserData()
  loadUserClubs()
})
</script>
