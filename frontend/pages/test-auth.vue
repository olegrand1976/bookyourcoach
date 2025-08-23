<template>
    <div class="p-8">
        <h1 class="text-2xl font-bold mb-4">🔍 Test Authentification Simple</h1>

        <div class="bg-gray-100 p-4 rounded mb-4">
            <h2 class="text-lg font-semibold mb-2">État actuel</h2>
            <p><strong>Utilisateur:</strong> {{ user?.name || 'Non connecté' }}</p>
            <p><strong>Email:</strong> {{ user?.email || 'N/A' }}</p>
            <p><strong>Rôle:</strong> <span class="font-bold"
                    :class="user?.role === 'admin' ? 'text-green-600' : 'text-red-600'">{{ user?.role || 'N/A' }}</span>
            </p>
            <p><strong>Authentifié:</strong> {{ isAuthenticated ? '✅ Oui' : '❌ Non' }}</p>
        </div>

        <div class="space-y-2 mb-4">
            <button @click="login" class="bg-blue-500 text-white px-4 py-2 rounded mr-2">Se connecter</button>
            <button @click="refresh" class="bg-green-500 text-white px-4 py-2 rounded mr-2">Rafraîchir</button>
            <button @click="logout" class="bg-red-500 text-white px-4 py-2 rounded">Se déconnecter</button>
        </div>

        <div class="bg-black text-green-400 p-4 rounded font-mono text-sm">
            <div v-for="log in logs" :key="log.id">{{ log.message }}</div>
        </div>
    </div>
</template>

<script setup>
const authStore = useAuthStore()
const { $api } = useNuxtApp()

const user = computed(() => authStore.user)
const isAuthenticated = computed(() => authStore.isAuthenticated)

const logs = ref([])
let logId = 0

function addLog(message) {
    logs.value.push({
        id: logId++,
        message: `[${new Date().toLocaleTimeString()}] ${message}`
    })
}

async function login() {
    addLog('🔄 Tentative de connexion...')
    try {
        await authStore.login({
            email: 'admin.secours@bookyourcoach.com',
            password: 'secours123'
        })
        addLog(`✅ Connexion réussie - Role: ${authStore.user?.role}`)
    } catch (error) {
        addLog(`❌ Erreur: ${error.message}`)
    }
}

async function refresh() {
    addLog('🔄 Rafraîchissement...')
    try {
        await authStore.fetchUser()
        addLog(`✅ Rafraîchi - Role: ${authStore.user?.role}`)
    } catch (error) {
        addLog(`❌ Erreur: ${error.message}`)
    }
}

async function logout() {
    addLog('🔄 Déconnexion...')
    await authStore.logout()
    addLog('✅ Déconnecté')
}

onMounted(() => {
    addLog('🚀 Page chargée')
})
</script>
