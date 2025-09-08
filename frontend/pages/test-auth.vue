<template>
  <div class="p-8">
    <h1 class="text-2xl font-bold mb-4">Test d'authentification</h1>
    
    <div class="space-y-4">
      <div class="p-4 border rounded">
        <h3 class="font-semibold">État du store d'authentification :</h3>
        <p><strong>isAuthenticated:</strong> {{ authStore.isAuthenticated }}</p>
        <p><strong>Token présent:</strong> {{ !!authStore.token }}</p>
        <p><strong>User:</strong> {{ authStore.user ? authStore.user.email : 'Aucun' }}</p>
        <p><strong>canActAsTeacher:</strong> {{ authStore.canActAsTeacher }}</p>
      </div>
      
      <div class="p-4 border rounded">
        <h3 class="font-semibold">Cookies :</h3>
        <p><strong>auth-token:</strong> {{ authToken ? 'Présent' : 'Absent' }}</p>
        <p><strong>Valeur:</strong> {{ authToken ? authToken.substring(0, 20) + '...' : 'N/A' }}</p>
      </div>
      
      <div class="p-4 border rounded">
        <h3 class="font-semibold">Test API :</h3>
        <button @click="testAPI" class="px-4 py-2 bg-blue-500 text-white rounded">
          Tester l'API
        </button>
        <div v-if="apiResult" class="mt-2 p-2 bg-gray-100 rounded">
          <pre>{{ JSON.stringify(apiResult, null, 2) }}</pre>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const authStore = useAuthStore()
const authToken = useCookie('auth-token')
const apiResult = ref(null)

const testAPI = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/auth/user')
    apiResult.value = response.data
  } catch (error) {
    apiResult.value = { error: error.message }
  }
}
</script>