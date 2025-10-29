<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-6 md:mb-8">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
          Dashboard Enseignant
        </h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">
          Bonjour {{ authStore.userName }}, gérez vos cours et votre planning
        </p>
      </div>

      <!-- Test simple -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Test d'accès</h2>
        <p><strong>Authentifié :</strong> {{ authStore.isAuthenticated }}</p>
        <p><strong>Peut agir comme enseignant :</strong> {{ authStore.canActAsTeacher }}</p>
        <p><strong>Utilisateur :</strong> {{ authStore.user?.email }}</p>
        
        <div class="mt-4">
          <button @click="testAPI" class="px-4 py-2 bg-blue-500 text-white rounded">
            Tester l'API
          </button>
          <div v-if="apiResult" class="mt-2 p-2 bg-gray-100 rounded">
            <pre>{{ JSON.stringify(apiResult, null, 2) }}</pre>
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

const authStore = useAuthStore()
const apiResult = ref(null)

const testAPI = async () => {
  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    
    const response = await $fetch(`${config.public.apiBase}/teacher/dashboard`, {
      headers: {
        'Authorization': `Bearer ${tokenCookie.value}`
      }
    })
    
    apiResult.value = response
  } catch (error) {
    apiResult.value = { error: error.message }
  }
}

onMounted(() => {
  console.log('Page teacher/dashboard montée')
})
</script>
