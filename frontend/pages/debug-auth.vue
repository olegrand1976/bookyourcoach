<template>
    <div class="container mx-auto p-8">
        <h1 class="text-3xl font-bold mb-6">🔍 Debug Authentification</h1>

        <!-- État actuel -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h2 class="text-xl font-semibold mb-3">📊 État actuel</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <strong>Utilisateur:</strong> {{ authStore.user?.name || 'Non connecté' }}
                </div>
                <div>
                    <strong>Email:</strong> {{ authStore.user?.email || 'N/A' }}
                </div>
                <div>
                    <strong>Rôle:</strong>
                    <span
                        :class="authStore.user?.role === 'admin' ? 'text-green-600 font-bold' : 'text-red-600 font-bold'">
                        {{ authStore.user?.role || 'N/A' }}
                    </span>
                </div>
                <div>
                    <strong>Authentifié:</strong> {{ authStore.isAuthenticated ? '✅ Oui' : '❌ Non' }}
                </div>
                <div>
                    <strong>Token présent:</strong> {{ authStore.token ? '✅ Oui' : '❌ Non' }}
                </div>
                <div>
                    <strong>Admin:</strong> {{ authStore.isAdmin ? '✅ Oui' : '❌ Non' }}
                </div>
            </div>
        </div>

        <!-- Actions de test -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <h2 class="text-xl font-semibold mb-3">🧪 Tests</h2>
            <div class="space-y-2">
                <button @click="doLogin" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2">
                    🔑 Se connecter
                </button>
                <button @click="refreshUser" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 mr-2">
                    🔄 Rafraîchir utilisateur
                </button>
                <button @click="checkLocalStorage"
                    class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 mr-2">
                    📦 Vérifier localStorage
                </button>
                <button @click="testDirectAPI"
                    class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600 mr-2">
                    🎯 Test API direct
                </button>
                <button @click="simulateRefresh" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                    🔄 Simuler rafraîchissement
                </button>
            </div>
        </div>

        <!-- Logs -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h2 class="text-xl font-semibold mb-3">📝 Logs de debug</h2>
            <div class="bg-black text-green-400 p-4 rounded font-mono text-sm max-h-96 overflow-y-auto">
                <div v-for="(log, index) in logs" :key="index" class="mb-1">
                    [{{ log.time }}] {{ log.message }}
                </div>
            </div>
            <button @click="clearLogs" class="mt-2 bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600 text-sm">
                🗑️ Effacer logs
            </button>
        </div>
    </div>
</template>

<script setup>
const authStore = useAuthStore()
const { $api } = useNuxtApp()

const logs = ref([])

function addLog(message) {
    const now = new Date()
    logs.value.push({
        time: now.toLocaleTimeString(),
        message
    })
}

function clearLogs() {
    logs.value = []
}

async function doLogin() {
    addLog('🔄 Tentative de connexion...')

    try {
        await authStore.login({
            email: 'admin.secours@activibe.com',
            password: 'secours123'
        })

        addLog(`✅ Connexion réussie - Role: ${authStore.user?.role}`)
    } catch (error) {
        addLog(`❌ Erreur de connexion: ${error.message}`)
    }
}

async function refreshUser() {
    addLog('🔄 Rafraîchissement utilisateur...')

    try {
        await authStore.fetchUser()
        addLog(`✅ Utilisateur rafraîchi - Role: ${authStore.user?.role}`)
    } catch (error) {
        addLog(`❌ Erreur rafraîchissement: ${error.message}`)
    }
}

function checkLocalStorage() {
    addLog('📦 Vérification localStorage...')

    if (process.client) {
        const token = localStorage.getItem('auth-token')
        const userData = localStorage.getItem('user-data')

        addLog(`🔑 Token: ${token ? token.substring(0, 30) + '...' : 'ABSENT'}`)

        if (userData) {
            try {
                const user = JSON.parse(userData)
                addLog(`👤 User data: ${user.name} - Role: ${user.role}`)
            } catch (e) {
                addLog(`❌ Erreur parsing user data: ${e.message}`)
            }
        } else {
            addLog('📦 Pas de données utilisateur dans localStorage')
        }
    }
}

async function testDirectAPI() {
    addLog('🎯 Test API direct...')

    try {
        const response = await $api.get('/auth/user')
        const user = response.data.user || response.data
        addLog(`📥 API Response - Role: ${user.role}, Name: ${user.name}`)
    } catch (error) {
        addLog(`❌ Erreur API: ${error.message}`)
    }
}

async function simulateRefresh() {
    addLog('🔄 Simulation rafraîchissement de page...')
    addLog('📤 État avant: ' + JSON.stringify({
        role: authStore.user?.role,
        isAuthenticated: authStore.isAuthenticated,
        isAdmin: authStore.isAdmin
    }))

    // Simuler l'initialisation comme au rafraîchissement
    await authStore.initializeAuth()

    addLog('📥 État après: ' + JSON.stringify({
        role: authStore.user?.role,
        isAuthenticated: authStore.isAuthenticated,
        isAdmin: authStore.isAdmin
    }))
}

// Initialisation au chargement de la page
onMounted(() => {
    addLog('🚀 Page de debug chargée')
    addLog('📊 État initial: ' + JSON.stringify({
        user: authStore.user?.email || 'Non connecté',
        role: authStore.user?.role || 'N/A',
        isAuthenticated: authStore.isAuthenticated,
        isAdmin: authStore.isAdmin
    }))
})
</script>
