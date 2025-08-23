<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Créer un compte
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Ou
          <NuxtLink to="/login" class="font-medium text-primary-600 hover:text-primary-500">
            connectez-vous à votre compte existant
          </NuxtLink>
        </p>
      </div>
      
      <form class="mt-8 space-y-6" @submit.prevent="handleRegister">
        <div class="space-y-4">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700">
              Nom complet
            </label>
            <input
              id="name"
              v-model="form.name"
              name="name"
              type="text"
              required
              class="input-field"
              placeholder="Votre nom complet"
            />
          </div>
          
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700">
              Adresse email
            </label>
            <input
              id="email"
              v-model="form.email"
              name="email"
              type="email"
              autocomplete="email"
              required
              class="input-field"
              placeholder="votre@email.com"
            />
          </div>
          
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">
              Mot de passe
            </label>
            <input
              id="password"
              v-model="form.password"
              name="password"
              type="password"
              required
              class="input-field"
              placeholder="Au moins 8 caractères"
            />
          </div>
          
          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
              Confirmer le mot de passe
            </label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              name="password_confirmation"
              type="password"
              required
              class="input-field"
              placeholder="Confirmez votre mot de passe"
            />
          </div>
        </div>

        <div class="flex items-center">
          <input
            id="terms"
            v-model="form.terms"
            name="terms"
            type="checkbox"
            required
            class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
          />
          <label for="terms" class="ml-2 block text-sm text-gray-900">
            J'accepte les 
            <a href="#" class="text-primary-600 hover:text-primary-500">
              conditions d'utilisation
            </a>
          </label>
        </div>

        <div>
          <button
            type="submit"
            :disabled="loading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
          >
            <span v-if="loading">Création en cours...</span>
            <span v-else>Créer mon compte</span>
          </button>
        </div>

        <!-- Messages d'erreur -->
        <div v-if="errors.length > 0" class="bg-red-50 border border-red-200 rounded-md p-4">
          <div class="text-sm text-red-600">
            <ul class="list-disc list-inside space-y-1">
              <li v-for="error in errors" :key="error">{{ error }}</li>
            </ul>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: false
})

const authStore = useAuthStore()
const toast = useToast()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  terms: false
})

const loading = ref(false)
const errors = ref([])

const handleRegister = async () => {
  loading.value = true
  errors.value = []
  
  try {
    await authStore.register(form)
    toast.success('Inscription réussie')
    await navigateTo('/dashboard')
  } catch (err) {
    if (err.response?.data?.errors) {
      // Erreurs de validation Laravel
      const validationErrors = err.response.data.errors
      errors.value = Object.values(validationErrors).flat()
    } else {
      errors.value = [err.response?.data?.message || 'Une erreur est survenue']
    }
    toast.error('Erreur lors de l\'inscription')
  } finally {
    loading.value = false
  }
}

// Rediriger si déjà connecté
watchEffect(() => {
  if (authStore.isAuthenticated) {
    navigateTo('/dashboard')
  }
})
</script>
