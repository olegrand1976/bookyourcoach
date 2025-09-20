<template>
  <div class="min-h-screen bg-gray-50">
    <div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-md w-full space-y-8">
        <div>
          <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Réinitialiser votre mot de passe
          </h2>
          <p class="mt-2 text-center text-sm text-gray-700">
            Entrez votre nouveau mot de passe
          </p>
        </div>

        <form class="mt-8 space-y-6" @submit.prevent="handleResetPassword">
          <div class="rounded-md shadow-sm -space-y-px">
            <div>
              <label for="email" class="sr-only">Email</label>
              <input 
                id="email" 
                v-model="form.email" 
                name="email" 
                type="email" 
                autocomplete="email" 
                required
                class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                placeholder="Adresse email" 
              />
            </div>
            <div>
              <label for="token" class="sr-only">Token</label>
              <input 
                id="token" 
                v-model="form.token" 
                name="token" 
                type="text" 
                required
                class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                placeholder="Token de réinitialisation" 
              />
            </div>
            <div>
              <label for="password" class="sr-only">Nouveau mot de passe</label>
              <input 
                id="password" 
                v-model="form.password" 
                name="password" 
                type="password" 
                autocomplete="new-password"
                required
                class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                placeholder="Nouveau mot de passe" 
              />
            </div>
            <div>
              <label for="password_confirmation" class="sr-only">Confirmer le mot de passe</label>
              <input 
                id="password_confirmation" 
                v-model="form.password_confirmation" 
                name="password_confirmation" 
                type="password" 
                autocomplete="new-password"
                required
                class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                placeholder="Confirmer le nouveau mot de passe" 
              />
            </div>
          </div>

          <div>
            <button 
              type="submit" 
              :disabled="loading"
              class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-500 bg-blue-600:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
            >
              <span v-if="loading">Réinitialisation en cours...</span>
              <span v-else>Réinitialiser le mot de passe</span>
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

          <!-- Message de succès -->
          <div v-if="success" class="bg-green-50 border border-green-200 rounded-md p-4">
            <div class="text-sm text-green-600">
              {{ success }}
            </div>
          </div>

          <div class="text-center">
            <NuxtLink to="/login" class="font-medium text-blue-400 bg-blue-600:text-yellow-600 hover:text-blue-500">
              Retour à la connexion
            </NuxtLink>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

// Fonction toast simple
const showToast = (message, type = 'info') => {
  console.log(`[${type.toUpperCase()}] ${message}`)
  // TODO: Implémenter un vrai système de toast
}

const form = reactive({
  email: '',
  token: '',
  password: '',
  password_confirmation: ''
})

const loading = ref(false)
const error = ref('')
const success = ref('')
const validationErrors = ref([])

// Récupérer les paramètres de l'URL si présents
onMounted(() => {
  if (route.query.email) {
    form.email = String(route.query.email)
  }
  if (route.query.token) {
    form.token = String(route.query.token)
  }
})

// Fonction de validation
const validateForm = () => {
  const errors = []

  if (!form.email) {
    errors.push('Email requis')
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.push('Format email invalide')
  }

  if (!form.token) {
    errors.push('Token requis')
  }

  if (!form.password) {
    errors.push('Mot de passe requis')
  } else if (form.password.length < 8) {
    errors.push('Mot de passe trop court (minimum 8 caractères)')
  }

  if (!form.password_confirmation) {
    errors.push('Confirmation du mot de passe requise')
  } else if (form.password !== form.password_confirmation) {
    errors.push('Les mots de passe ne correspondent pas')
  }

  return errors
}

const handleResetPassword = async () => {
  loading.value = true
  error.value = ''
  success.value = ''
  validationErrors.value = []

  // Validation côté client
  const clientErrors = validateForm()
  if (clientErrors.length > 0) {
    validationErrors.value = clientErrors
    loading.value = false
    return
  }

  try {
    await authStore.resetPassword({
      email: form.email,
      token: form.token,
      password: form.password,
      password_confirmation: form.password_confirmation
    })

    success.value = 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.'
    showToast('Mot de passe réinitialisé avec succès', 'success')

    // Rediriger vers la page de connexion après 3 secondes
    setTimeout(() => {
      navigateTo('/login')
    }, 3000)
    } catch (err) {
    console.error('Erreur lors de la réinitialisation:', err)

    let errorMessage = 'Une erreur est survenue'

    if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.response?.status === 400) {
      errorMessage = 'Token invalide ou expiré'
    } else if (err.response?.status === 422) {
      errorMessage = 'Données invalides'
    } else {
      errorMessage = `Erreur: ${err.message || 'Erreur inconnue'}`
    }

    error.value = errorMessage
    showToast('Erreur lors de la réinitialisation', 'error')
  } finally {
    loading.value = false
  }
}

// Rediriger si déjà connecté
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
