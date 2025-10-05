<template>
  <div class="min-h-screen bg-gray-50 p-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
      <h1 class="text-2xl font-bold mb-6">Test de Connexion</h1>
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Email</label>
          <input 
            v-model="email" 
            type="email" 
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md"
            placeholder="sophie.martin@activibe.com"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Mot de passe</label>
          <input 
            v-model="password" 
            type="password" 
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md"
            placeholder="password"
          />
        </div>
        
        <button 
          @click="testLogin" 
          :disabled="loading"
          class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 disabled:opacity-50"
        >
          {{ loading ? 'Test en cours...' : 'Tester la connexion' }}
        </button>
      </div>
      
      <div v-if="result" class="mt-6 p-4 bg-gray-100 rounded-md">
        <h3 class="font-semibold mb-2">Résultat :</h3>
        <pre class="text-sm">{{ JSON.stringify(result, null, 2) }}</pre>
      </div>
      
      <div v-if="error" class="mt-6 p-4 bg-red-100 border border-red-300 rounded-md">
        <h3 class="font-semibold text-red-800 mb-2">Erreur :</h3>
        <p class="text-red-700">{{ error }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
const email = ref('sophie.martin@activibe.com')
const password = ref('password')
const loading = ref(false)
const result = ref(null)
const error = ref('')

const testLogin = async () => {
  loading.value = true
  result.value = null
  error.value = ''
  
  try {
    const { $api } = useNuxtApp()
    console.log('🔑 Test de connexion avec:', { email: email.value, password: '***' })
    console.log('🔑 URL de base API:', useRuntimeConfig().public.apiBase)
    
    const response = await $api.post('/auth/login', {
      email: email.value,
      password: password.value
    })
    
    result.value = {
      success: true,
      message: 'Connexion réussie !',
      user: response.data.user,
      token: response.data.token?.substring(0, 20) + '...'
    }
    
    console.log('✅ Connexion réussie:', response.data)
  } catch (err) {
    console.error('❌ Erreur de connexion:', err)
    
    error.value = `Erreur: ${err.message}`
    if (err.response?.data?.message) {
      error.value = err.response.data.message
    }
    if (err.response?.status) {
      error.value += ` (Status: ${err.response.status})`
    }
    
    result.value = {
      success: false,
      error: error.value,
      details: err.response?.data
    }
  } finally {
    loading.value = false
  }
}
</script>
