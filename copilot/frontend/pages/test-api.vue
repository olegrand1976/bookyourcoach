<template>
    <div class="min-h-screen bg-gray-100 flex items-center justify-center">
        <div class="max-w-md w-full bg-white p-8 rounded-lg shadow-md">
            <h1 class="text-2xl font-bold mb-6 text-center">Test de Configuration API</h1>

            <div class="space-y-4">
                <div class="p-4 bg-blue-50 rounded-lg">
                    <h3 class="font-semibold text-blue-800">Configuration Runtime</h3>
                    <p class="text-sm text-blue-600">API Base: {{ config.public.apiBase }}</p>
                    <p class="text-sm text-blue-600">App Name: {{ config.public.appName }}</p>
                </div>

                <div class="p-4 bg-green-50 rounded-lg">
                    <h3 class="font-semibold text-green-800">État API</h3>
                    <p class="text-sm text-green-600">Status: {{ apiStatus }}</p>
                    <button @click="testApi" class="mt-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                        :disabled="testing">
                        {{ testing ? 'Test en cours...' : 'Tester API' }}
                    </button>
                </div>

                <div class="p-4 bg-yellow-50 rounded-lg">
                    <h3 class="font-semibold text-yellow-800">Test de Connexion</h3>
                    <form @submit.prevent="testLogin" class="space-y-3">
                        <input v-model="email" type="email" placeholder="Email" class="w-full p-2 border rounded">
                        <input v-model="password" type="password" placeholder="Mot de passe"
                            class="w-full p-2 border rounded">
                        <button type="submit" class="w-full py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700"
                            :disabled="loginTesting">
                            {{ loginTesting ? 'Connexion...' : 'Se connecter' }}
                        </button>
                    </form>
                </div>

                <div v-if="result" class="p-4 rounded-lg" :class="result.success ? 'bg-green-50' : 'bg-red-50'">
                    <h3 class="font-semibold" :class="result.success ? 'text-green-800' : 'text-red-800'">
                        Résultat du Test
                    </h3>
                    <pre class="text-xs mt-2 overflow-auto"
                        :class="result.success ? 'text-green-600' : 'text-red-600'">{{ JSON.stringify(result.data, null, 2) }}</pre>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const config = useRuntimeConfig()
const { $api } = useNuxtApp()

const apiStatus = ref('Non testé')
const testing = ref(false)
const loginTesting = ref(false)
const result = ref(null)
const email = ref('admin@bookyourcoach.com')
const password = ref('admin123')

const testApi = async () => {
    testing.value = true
    apiStatus.value = 'Test en cours...'

    try {
        const response = await $api.get('/health')
        apiStatus.value = 'Connecté ✅'
        result.value = {
            success: true,
            data: { message: 'API accessible', response: response.data }
        }
    } catch (error) {
        apiStatus.value = 'Erreur ❌'
        result.value = {
            success: false,
            data: {
                message: 'Erreur API',
                error: error.message,
                config: error.config,
                response: error.response?.data
            }
        }
    } finally {
        testing.value = false
    }
}

const testLogin = async () => {
    loginTesting.value = true
    result.value = null

    try {
        console.log('Configuration API utilisée:', config.public.apiBase)
        console.log('Tentative de connexion avec:', { email: email.value, password: '***' })

        const response = await $api.post('/auth/login', {
            email: email.value,
            password: password.value
        })

        result.value = {
            success: true,
            data: {
                message: 'Connexion réussie !',
                user: response.data.user,
                token: response.data.token?.substring(0, 20) + '...'
            }
        }
    } catch (error) {
        console.error('Erreur de connexion:', error)
        result.value = {
            success: false,
            data: {
                message: 'Erreur de connexion',
                error: error.message,
                status: error.response?.status,
                statusText: error.response?.statusText,
                url: error.config?.url,
                baseURL: error.config?.baseURL,
                response: error.response?.data
            }
        }
    } finally {
        loginTesting.value = false
    }
}

// Test automatique au chargement
onMounted(() => {
    console.log('Configuration runtime chargée:', config.public)
    testApi()
})
</script>
