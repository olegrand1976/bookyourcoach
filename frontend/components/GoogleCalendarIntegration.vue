<template>
  <div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Intégration Google Calendar</h3>
        <p class="text-sm text-gray-600">Synchronisez vos cours avec Google Calendar</p>
      </div>
      <div class="flex items-center space-x-3">
        <button v-if="!isConnected" @click="connectGoogleCalendar" 
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
          <span class="mr-2">🔗</span>
          Connecter Google Calendar
        </button>
        <button v-else @click="disconnectGoogleCalendar" 
          class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
          <span class="mr-2">🔌</span>
          Déconnecter
        </button>
      </div>
    </div>

    <!-- État de connexion -->
    <div v-if="isConnected" class="mb-6">
      <div class="flex items-center space-x-3 p-4 bg-green-50 rounded-lg">
        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
        <div>
          <p class="text-sm font-medium text-green-800">Connecté à Google Calendar</p>
          <p class="text-xs text-green-600">{{ userInfo?.email }}</p>
        </div>
      </div>
    </div>

    <!-- Sélection de calendrier -->
    <div v-if="isConnected && calendars.length > 0" class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Calendrier Google à synchroniser
      </label>
      <select v-model="selectedGoogleCalendar" @change="updateSyncSettings"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">Sélectionner un calendrier</option>
        <option v-for="calendar in calendars" :key="calendar.id" :value="calendar.id">
          {{ calendar.name }} {{ calendar.primary ? '(Principal)' : '' }}
        </option>
      </select>
    </div>

    <!-- Paramètres de synchronisation -->
    <div v-if="isConnected && selectedGoogleCalendar" class="mb-6">
      <h4 class="text-md font-medium text-gray-900 mb-3">Paramètres de synchronisation</h4>
      <div class="space-y-3">
        <div class="flex items-center">
          <input v-model="syncSettings.syncToGoogle" type="checkbox" id="sync-to-google"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
          <label for="sync-to-google" class="ml-2 text-sm text-gray-700">
            Synchroniser les cours vers Google Calendar
          </label>
        </div>
        <div class="flex items-center">
          <input v-model="syncSettings.syncFromGoogle" type="checkbox" id="sync-from-google"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
          <label for="sync-from-google" class="ml-2 text-sm text-gray-700">
            Synchroniser les événements depuis Google Calendar
          </label>
        </div>
        <div class="flex items-center">
          <input v-model="syncSettings.autoSync" type="checkbox" id="auto-sync"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
          <label for="auto-sync" class="ml-2 text-sm text-gray-700">
            Synchronisation automatique
          </label>
        </div>
      </div>
    </div>

    <!-- Actions de synchronisation -->
    <div v-if="isConnected && selectedGoogleCalendar" class="mb-6">
      <div class="flex items-center space-x-3">
        <button @click="syncNow" :disabled="isSyncing"
          class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors disabled:opacity-50">
          <span class="mr-2">🔄</span>
          {{ isSyncing ? 'Synchronisation...' : 'Synchroniser maintenant' }}
        </button>
        <button @click="viewGoogleCalendar" 
          class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
          <span class="mr-2">👁️</span>
          Voir dans Google Calendar
        </button>
      </div>
    </div>

    <!-- Historique de synchronisation -->
    <div v-if="isConnected && syncHistory.length > 0" class="mb-6">
      <h4 class="text-md font-medium text-gray-900 mb-3">Historique de synchronisation</h4>
      <div class="space-y-2">
        <div v-for="sync in syncHistory" :key="sync.id" 
          class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div>
            <p class="text-sm font-medium text-gray-900">{{ sync.action }}</p>
            <p class="text-xs text-gray-600">{{ formatDate(sync.created_at) }}</p>
          </div>
          <div class="flex items-center space-x-2">
            <span :class="['px-2 py-1 rounded-full text-xs', 
              sync.status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
              {{ sync.status === 'success' ? 'Succès' : 'Erreur' }}
            </span>
            <span class="text-xs text-gray-500">{{ sync.events_count }} événements</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Instructions -->
    <div v-if="!isConnected" class="bg-blue-50 rounded-lg p-4">
      <h4 class="text-md font-medium text-blue-900 mb-2">Comment ça marche ?</h4>
      <ul class="text-sm text-blue-800 space-y-1">
        <li>• Connectez votre compte Google Calendar</li>
        <li>• Sélectionnez le calendrier à synchroniser</li>
        <li>• Vos cours seront automatiquement ajoutés à Google Calendar</li>
        <li>• Les modifications dans Google Calendar seront synchronisées</li>
      </ul>
    </div>

    <!-- Modal de connexion -->
    <div v-if="showConnectionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Connexion Google Calendar</h3>
        <p class="text-gray-600 mb-4">
          Vous allez être redirigé vers Google pour autoriser l'accès à votre calendrier.
        </p>
        <div class="flex justify-end space-x-3">
          <button @click="showConnectionModal = false"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors">
            Annuler
          </button>
          <button @click="proceedWithConnection"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
            Continuer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

// Props
const props = defineProps({
  teacherId: {
    type: Number,
    required: false
  },
  studentId: {
    type: Number,
    required: false
  }
})

// État réactif
const isConnected = ref(false)
const isSyncing = ref(false)
const showConnectionModal = ref(false)
const userInfo = ref(null)
const calendars = ref([])
const selectedGoogleCalendar = ref('')
const syncHistory = ref([])

// Paramètres de synchronisation
const syncSettings = ref({
  syncToGoogle: true,
  syncFromGoogle: false,
  autoSync: true
})

// Computed
const hasValidConnection = computed(() => {
  return isConnected.value && selectedGoogleCalendar.value
})

// Méthodes
const connectGoogleCalendar = () => {
  showConnectionModal.value = true
}

const proceedWithConnection = async () => {
  try {
    const { $api } = useNuxtApp()
    const endpoint = props.teacherId ? '/teacher/google-calendar/auth-url' : '/student/google-calendar/auth-url'
    const response = await $api.get(endpoint)
    
    if (response.data.success) {
      // Rediriger vers Google
      window.location.href = response.data.auth_url
    }
  } catch (error) {
    console.error('Erreur lors de la connexion Google Calendar:', error)
  }
  
  showConnectionModal.value = false
}

const disconnectGoogleCalendar = async () => {
  try {
    // TODO: Implémenter la déconnexion
    isConnected.value = false
    userInfo.value = null
    calendars.value = []
    selectedGoogleCalendar.value = ''
  } catch (error) {
    console.error('Erreur lors de la déconnexion:', error)
  }
}

const loadConnectionStatus = async () => {
  try {
    const { $api } = useNuxtApp()
    const endpoint = props.teacherId ? '/teacher/google-calendar/calendars' : '/student/google-calendar/calendars'
    const response = await $api.get(endpoint)
    
    if (response.data.success) {
      isConnected.value = true
      calendars.value = response.data.calendars
      
      // Sélectionner le calendrier principal par défaut
      const primaryCalendar = calendars.value.find(cal => cal.primary)
      if (primaryCalendar) {
        selectedGoogleCalendar.value = primaryCalendar.id
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement du statut de connexion:', error)
    isConnected.value = false
  }
}

const updateSyncSettings = () => {
  // TODO: Sauvegarder les paramètres de synchronisation
  console.log('Mise à jour des paramètres de synchronisation:', syncSettings.value)
}

const syncNow = async () => {
  try {
    isSyncing.value = true
    const { $api } = useNuxtApp()
    
    const endpoint = props.teacherId ? '/teacher/google-calendar/sync-events' : '/student/google-calendar/sync-events'
    const response = await $api.post(endpoint, {
      calendar_id: selectedGoogleCalendar.value
    })
    
    if (response.data.success) {
      // Ajouter à l'historique
      syncHistory.value.unshift({
        id: Date.now(),
        action: 'Synchronisation manuelle',
        status: 'success',
        events_count: response.data.synced_events?.length || 0,
        created_at: new Date().toISOString()
      })
      
      // Limiter l'historique à 10 entrées
      if (syncHistory.value.length > 10) {
        syncHistory.value = syncHistory.value.slice(0, 10)
      }
    }
  } catch (error) {
    console.error('Erreur lors de la synchronisation:', error)
    
    // Ajouter l'erreur à l'historique
    syncHistory.value.unshift({
      id: Date.now(),
      action: 'Synchronisation manuelle',
      status: 'error',
      events_count: 0,
      created_at: new Date().toISOString()
    })
  } finally {
    isSyncing.value = false
  }
}

const viewGoogleCalendar = () => {
  if (selectedGoogleCalendar.value) {
    window.open(`https://calendar.google.com/calendar/u/0/r`, '_blank')
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(() => {
  loadConnectionStatus()
})

// Gestion du callback Google
if (process.client) {
  const urlParams = new URLSearchParams(window.location.search)
  const code = urlParams.get('code')
  const state = urlParams.get('state')
  
  if (code && state === 'google-calendar') {
    // Traiter le callback Google
    handleGoogleCallback(code)
  }
}

const handleGoogleCallback = async (code) => {
  try {
    const { $api } = useNuxtApp()
    const endpoint = props.teacherId ? '/teacher/google-calendar/callback' : '/student/google-calendar/callback'
    const response = await $api.post(endpoint, { code })
    
    if (response.data.success) {
      isConnected.value = true
      userInfo.value = response.data.user_info
      calendars.value = response.data.calendars
      
      // Sélectionner le calendrier principal par défaut
      const primaryCalendar = calendars.value.find(cal => cal.primary)
      if (primaryCalendar) {
        selectedGoogleCalendar.value = primaryCalendar.id
      }
      
      // Nettoyer l'URL
      window.history.replaceState({}, document.title, window.location.pathname)
    }
  } catch (error) {
    console.error('Erreur lors du traitement du callback Google:', error)
  }
}
</script>
