<template>
  <div class="min-h-screen bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-3xl font-bold mb-8 text-center">🧪 Test d'Authentification en Temps Réel</h1>
      
      <!-- État actuel -->
      <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">📊 État Actuel du Store</h2>
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-gray-50 p-4 rounded">
            <strong>Authentifié:</strong> 
            <span :class="authStore.isAuthenticated ? 'text-green-600' : 'text-red-600'">
              {{ authStore.isAuthenticated ? '✅ OUI' : '❌ NON' }}
            </span>
          </div>
          <div class="bg-gray-50 p-4 rounded">
            <strong>Token:</strong> 
            <span :class="authStore.token ? 'text-green-600' : 'text-red-600'">
              {{ authStore.token ? '✅ Présent' : '❌ Absent' }}
            </span>
          </div>
          <div class="bg-gray-50 p-4 rounded">
            <strong>Utilisateur:</strong> 
            <span :class="authStore.user ? 'text-green-600' : 'text-red-600'">
              {{ authStore.user ? '✅ Présent' : '❌ Absent' }}
            </span>
          </div>
          <div class="bg-gray-50 p-4 rounded">
            <strong>Rôle:</strong> 
            <span class="text-blue-600">{{ authStore.user?.role || 'N/A' }}</span>
          </div>
        </div>
        
        <div v-if="authStore.user" class="mt-4 bg-blue-50 p-4 rounded">
          <strong>Détails utilisateur:</strong>
          <pre class="text-sm mt-2">{{ JSON.stringify(authStore.user, null, 2) }}</pre>
        </div>
      </div>

      <!-- Test des cookies -->
      <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">🍪 État des Cookies</h2>
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-gray-50 p-4 rounded">
            <strong>Cookie auth-token:</strong>
            <div class="text-sm mt-2 break-all">
              <span :class="tokenCookie ? 'text-green-600' : 'text-red-600'">
                {{ tokenCookie ? tokenCookie.substring(0, 50) + '...' : '❌ Absent' }}
              </span>
            </div>
            <div class="text-xs text-gray-500 mt-1">Type: {{ typeof tokenCookie }}</div>
          </div>
          <div class="bg-gray-50 p-4 rounded">
            <strong>Cookie auth-user:</strong>
            <div class="text-sm mt-2 break-all">
              <span :class="userCookie ? 'text-green-600' : 'text-red-600'">
                {{ userCookie ? (typeof userCookie === 'string' ? userCookie.substring(0, 50) + '...' : 'OBJET CORROMPU') : '❌ Absent' }}
              </span>
            </div>
            <div class="text-xs text-gray-500 mt-1">Type: {{ typeof userCookie }}</div>
          </div>
        </div>
      </div>

      <!-- Actions de test -->
      <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">🎮 Actions de Test</h2>
        <div class="grid grid-cols-2 gap-4">
          <button 
            @click="testLogin" 
            :disabled="loading"
            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
          >
            {{ loading ? '⏳ Connexion...' : '🔐 Test Connexion' }}
          </button>
          
          <button 
            @click="testInitialize" 
            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
          >
            🔄 Test Initialisation
          </button>
          
          <button 
            @click="clearCookies" 
            class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
          >
            🧹 Nettoyer Cookies
          </button>
          
          <button 
            @click="reloadPage" 
            class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700"
          >
            🔄 Recharger Page
          </button>
        </div>
      </div>

      <!-- Logs en temps réel -->
      <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-xl font-semibold mb-4">📝 Logs en Temps Réel</h2>
        <div class="bg-black text-green-400 p-4 rounded font-mono text-sm h-64 overflow-y-auto">
          <div v-for="(log, index) in logs" :key="index" class="mb-1">
            {{ log }}
          </div>
        </div>
        <button 
          @click="clearLogs" 
          class="mt-2 bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700"
        >
          Effacer logs
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

// Désactiver le middleware pour cette page de test
definePageMeta({
  middleware: []
})

const authStore = useAuthStore()
const loading = ref(false)
const logs = ref([])

// Fonction pour ajouter des logs
const addLog = (message) => {
  const timestamp = new Date().toLocaleTimeString()
  logs.value.push(`[${timestamp}] ${message}`)
  console.log(`🧪 [TEST PAGE] ${message}`)
}

// Cookies réactifs
const tokenCookie = computed(() => {
  if (process.client) {
    const cookie = useCookie('auth-token')
    return cookie.value
  }
  return null
})

const userCookie = computed(() => {
  if (process.client) {
    const cookie = useCookie('auth-user')
    return cookie.value
  }
  return null
})

// Actions de test
const testLogin = async () => {
  loading.value = true
  addLog('🔐 Début test de connexion...')
  
  try {
    const result = await authStore.login({
      email: 'test@club.com',
      password: 'password123'
    })
    addLog('✅ Connexion réussie!')
    addLog(`👤 Utilisateur: ${authStore.user?.email} (${authStore.user?.role})`)
    addLog(`🔑 Token reçu: ${result.access_token?.substring(0, 20)}...`)
  } catch (error) {
    addLog(`❌ Erreur de connexion: ${error.message}`)
    if (error.response?.data?.message) {
      addLog(`📝 Message serveur: ${error.response.data.message}`)
    }
  } finally {
    loading.value = false
  }
}

const testInitialize = async () => {
  addLog('🔄 Test d\'initialisation...')
  
  try {
    // Réinitialiser le store
    authStore.isInitialized = false
    await authStore.initializeAuth()
    addLog('✅ Initialisation terminée')
    addLog(`📊 État: Auth=${authStore.isAuthenticated}, Token=${!!authStore.token}, User=${!!authStore.user}`)
  } catch (error) {
    addLog(`❌ Erreur d'initialisation: ${error.message}`)
  }
}

const clearCookies = () => {
  addLog('🧹 Nettoyage des cookies...')
  
  if (process.client) {
    // Nettoyer via le store
    authStore.clearAuth()
    
    // Nettoyer manuellement aussi
    document.cookie = 'auth-token=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/;'
    document.cookie = 'auth-user=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/;'
    
    addLog('✅ Cookies nettoyés')
  }
}

const reloadPage = () => {
  addLog('🔄 Rechargement de la page...')
  if (process.client) {
    window.location.reload()
  }
}

const clearLogs = () => {
  logs.value = []
}

// Initialisation
onMounted(() => {
  addLog('🚀 Page de test chargée')
  addLog(`📊 État initial: Auth=${authStore.isAuthenticated}, Token=${!!authStore.token}, User=${!!authStore.user}`)
  
  // Surveiller les changements du store
  watch(() => authStore.isAuthenticated, (newVal) => {
    addLog(`🔄 Changement d'authentification: ${newVal}`)
  })
  
  watch(() => authStore.token, (newVal) => {
    addLog(`🔄 Changement de token: ${newVal ? 'Présent' : 'Absent'}`)
  })
})
</script>