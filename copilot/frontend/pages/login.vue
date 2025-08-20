<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Connexion à votre compte
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Ou
          <NuxtLink to="/register" class="font-medium text-primary-600 hover:text-primary-500">
            créez un nouveau compte
          </NuxtLink>
        </p>
      </div>

      <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="email" class="sr-only">Adresse email</label>
            <input id="email" v-model="form.email" name="email" type="email" autocomplete="email" required
              class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm"
              placeholder="Adresse email" />
          </div>
          <div>
            <label for="password" class="sr-only">Mot de passe</label>
            <input id="password" v-model="form.password" name="password" type="password" autocomplete="current-password"
              required
              class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm"
              placeholder="Mot de passe" />
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input id="remember-me" v-model="form.remember" name="remember-me" type="checkbox"
              class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" />
            <label for="remember-me" class="ml-2 block text-sm text-gray-900">
              Se souvenir de moi
            </label>
          </div>

          <div class="text-sm">
            <a href="#" class="font-medium text-primary-600 hover:text-primary-500">
              Mot de passe oublié ?
            </a>
          </div>
        </div>

        <div>
          <button type="submit" :disabled="loading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
            <span v-if="loading">Connexion en cours...</span>
            <span v-else>Se connecter</span>
          </button>
        </div>

        <!-- Messages d'erreur de validation -->
        <div v-if="validationErrors.length > 0" class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
          <div class="text-sm text-yellow-700">
            <ul class="list-disc list-inside">
              <li v-for="validationError in validationErrors" :key="validationError">
                {{ validationError }}
              </li>
            </ul>
          </div>
        </div>

        <!-- Messages d'erreur -->
        <div v-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
          <div class="text-sm text-red-600">
            {{ error }}
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
const router = useRouter()

const form = reactive({
  email: '',
  password: '',
  remember: false
})

const loading = ref(false)
const error = ref('')
const validationErrors = ref([])

// Fonction de validation renforcée
const validateForm = () => {
  const errors = []

  if (!form.email) {
    errors.push('Email requis')
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.push('Format email invalide')
  }

  if (!form.password) {
    errors.push('Mot de passe requis')
  } else if (form.password.length < 6) {
    errors.push('Mot de passe trop court (minimum 6 caractères)')
  }

  return errors
}

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  validationErrors.value = []

  // Validation côté client
  const clientErrors = validateForm()
  if (clientErrors.length > 0) {
    validationErrors.value = clientErrors
    loading.value = false
    return
  }

  try {
    await authStore.login({
      email: form.email,
      password: form.password
    })

    toast.success('Connexion réussie')

    // Rediriger selon le rôle
    if (authStore.isAdmin) {
      await navigateTo('/admin')
    } else if (authStore.isTeacher) {
      await navigateTo('/teacher')
    } else {
      await navigateTo('/dashboard')
    }
  } catch (err) {
    console.error('Erreur de connexion complète:', err)
    console.error('Response data:', err.response?.data)
    console.error('Response status:', err.response?.status)
    console.error('URL de base API:', useRuntimeConfig().public.apiBase)

    let errorMessage = 'Une erreur est survenue'

    if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.code === 'ECONNREFUSED' || err.message.includes('Network Error')) {
      errorMessage = 'Impossible de se connecter au serveur. Vérifiez que l\'API est accessible.'
    } else if (err.response?.status === 422) {
      errorMessage = 'Informations de connexion invalides'
    } else if (err.response?.status === 401) {
      errorMessage = 'Email ou mot de passe incorrect'
    } else {
      errorMessage = `Erreur: ${err.message || 'Erreur inconnue'}`
    }

    error.value = errorMessage
    toast.error('Erreur de connexion')
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
