<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Connexion
        </h2>
        <p class="mt-2 text-center text-sm text-gray-700">
          Pas encore de compte ?
          <NuxtLink to="/register" class="font-medium text-blue-400 bg-blue-600:text-yellow-600">
            Créer un compte
          </NuxtLink>
        </p>
        <p class="mt-1 text-center text-sm text-gray-600">
          Vous êtes enseignant ?
          <NuxtLink to="/register-teacher" class="font-medium text-emerald-600 hover:text-emerald-500">
            S'inscrire comme enseignant
          </NuxtLink>
        </p>
      </div>

      <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="email" class="sr-only">Email</label>
            <input id="email" v-model="form.email" name="email" type="email" autocomplete="email" required
              class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
              placeholder="Adresse email" />
          </div>
          <div>
            <label for="password" class="sr-only">Mot de passe</label>
            <input id="password" v-model="form.password" name="password" type="password" autocomplete="current-password"
              required
              class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
              placeholder="Mot de passe" />
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input id="remember-me" v-model="form.remember" name="remember-me" type="checkbox"
              class="h-4 w-4 text-blue-400 focus:ring-blue-500 border-gray-300 rounded" />
            <label for="remember-me" class="ml-2 block text-sm text-gray-900">
              Se souvenir de moi
            </label>
          </div>

          <div class="text-sm">
            <a href="#" class="font-medium text-blue-400 bg-blue-600:text-yellow-600">
              Mot de passe oublié ?
            </a>
          </div>
        </div>

        <div>
          <button type="submit" :disabled="loading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-500 bg-blue-600:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
            <span v-if="loading">Connexion en cours...</span>
            <span v-else>Connexion</span>
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
const router = useRouter()

// Fonction toast simple
const showToast = (message, type = 'info') => {
  console.log(`[${type.toUpperCase()}] ${message}`)
  // TODO: Implémenter un vrai système de toast
}

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

    showToast('Connexion réussie', 'success')

    // Rediriger selon le rôle (admin en priorité)
    if (authStore.isAdmin) {
      await navigateTo('/admin')
    } else if (authStore.isTeacher) {
      await navigateTo('/teacher/dashboard')
    } else if (authStore.isClub) {
      await navigateTo('/club/dashboard')
    } else if (authStore.isStudent) {
      await navigateTo('/student/dashboard')
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
    showToast('Erreur de connexion', 'error')
  } finally {
    loading.value = false
  }
}

// Rediriger si déjà connecté (admin en priorité)
watchEffect(() => {
  if (authStore.isAuthenticated) {
    if (authStore.isAdmin) {
      navigateTo('/admin')
    } else if (authStore.isTeacher) {
      navigateTo('/teacher/dashboard')
    } else if (authStore.isClub) {
      navigateTo('/club/dashboard')
    } else if (authStore.isStudent) {
      navigateTo('/student/dashboard')
    } else {
      navigateTo('/dashboard')
    }
  }
})
</script>
