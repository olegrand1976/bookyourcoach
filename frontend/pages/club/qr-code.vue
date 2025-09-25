<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">QR Code du Club</h1>
        <p class="text-gray-600">
          Partagez ce QR code pour permettre aux enseignants et étudiants de rejoindre votre club
        </p>
      </div>

      <!-- Club Info -->
      <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="flex items-center space-x-4">
          <div class="bg-blue-100 p-3 rounded-lg">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-semibold text-gray-900">{{ club.name }}</h2>
            <p class="text-gray-600">{{ club.email }}</p>
            <p class="text-sm text-gray-500">{{ club.address }}, {{ club.city }}</p>
          </div>
        </div>
      </div>

      <!-- QR Code Display -->
      <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <div class="text-center">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Code QR du Club</h3>
          
          <!-- QR Code Image -->
          <div class="flex justify-center mb-6">
            <div v-if="qrCodeData" class="bg-white p-4 rounded-lg border-2 border-gray-200">
              <div v-html="qrCodeData.qr_svg" class="w-64 h-64"></div>
            </div>
            <div v-else class="w-64 h-64 bg-gray-100 rounded-lg flex items-center justify-center">
              <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                <p class="text-gray-600">Génération du QR code...</p>
              </div>
            </div>
          </div>

          <!-- QR Code Info -->
          <div v-if="qrCodeData" class="space-y-2">
            <p class="text-sm text-gray-600">
              <span class="font-medium">Code:</span> {{ qrCodeData.qr_code }}
            </p>
            <p class="text-sm text-gray-600">
              <span class="font-medium">Généré le:</span> {{ formatDate(qrCodeData.generated_at) }}
            </p>
          </div>

          <!-- Actions -->
          <div class="mt-6 flex flex-col sm:flex-row gap-4 justify-center">
            <button
              @click="downloadQRCode"
              :disabled="!qrCodeData"
              class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center space-x-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              <span>Télécharger</span>
            </button>
            
            <button
              @click="regenerateQRCode"
              :disabled="isRegenerating"
              class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center space-x-2"
            >
              <svg v-if="isRegenerating" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              <span>{{ isRegenerating ? 'Régénération...' : 'Régénérer' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Instructions -->
      <div class="bg-blue-50 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-4">Comment utiliser ce QR code ?</h3>
        <div class="space-y-3 text-blue-800">
          <div class="flex items-start space-x-3">
            <div class="bg-blue-200 rounded-full p-1 mt-0.5">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <p>Les enseignants et étudiants peuvent scanner ce QR code pour rejoindre automatiquement votre club</p>
          </div>
          <div class="flex items-start space-x-3">
            <div class="bg-blue-200 rounded-full p-1 mt-0.5">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <p>Vous pouvez imprimer ce QR code et l'afficher dans votre club</p>
          </div>
          <div class="flex items-start space-x-3">
            <div class="bg-blue-200 rounded-full p-1 mt-0.5">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <p>Le QR code peut être partagé par email ou sur les réseaux sociaux</p>
          </div>
        </div>
      </div>

      <!-- Back Button -->
      <div class="mt-8 text-center">
        <button
          @click="goBack"
          class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors flex items-center space-x-2 mx-auto"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
          <span>Retour au tableau de bord</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from '@/composables/useToast'

definePageMeta({
  middleware: ['auth']
})

const { showToast } = useToast()

// State
const club = ref({})
const qrCodeData = ref(null)
const isRegenerating = ref(false)

// Methods
const loadClubData = async () => {
  try {
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/club/dashboard`)
    
    if (response.success && response.data && response.data.club) {
      club.value = response.data.club
      await loadQRCode()
    }
  } catch (error) {
    console.error('❌ Erreur lors du chargement des données du club:', error)
    showToast('Erreur lors du chargement des données du club', 'error')
  }
}

const loadQRCode = async () => {
  try {
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/qr-code/club/${club.value.id}`)
    
    if (response.success) {
      qrCodeData.value = response.data
    }
  } catch (error) {
    console.error('❌ Erreur lors du chargement du QR code:', error)
    showToast('Erreur lors du chargement du QR code', 'error')
  }
}

const regenerateQRCode = async () => {
  isRegenerating.value = true
  
  try {
    // Force regeneration by clearing existing QR code
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/qr-code/club/${club.value.id}`)
    
    if (response.success) {
      qrCodeData.value = response.data
      showToast('QR code régénéré avec succès', 'success')
    }
  } catch (error) {
    console.error('❌ Erreur lors de la régénération du QR code:', error)
    showToast('Erreur lors de la régénération du QR code', 'error')
  } finally {
    isRegenerating.value = false
  }
}

const downloadQRCode = () => {
  if (!qrCodeData.value) return
  
  try {
    // Create a temporary link to download the QR code
    const link = document.createElement('a')
    link.href = `data:image/png;base64,${qrCodeData.value.qr_image}`
    link.download = `qr-code-club-${club.value.name.replace(/\s+/g, '-').toLowerCase()}.png`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    showToast('QR code téléchargé avec succès', 'success')
  } catch (error) {
    console.error('❌ Erreur lors du téléchargement:', error)
    showToast('Erreur lors du téléchargement', 'error')
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const goBack = () => {
  navigateTo('/club/dashboard')
}

onMounted(() => {
  loadClubData()
})
</script>
