<template>
  <div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-indigo-50 flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
      <div class="bg-white rounded-2xl shadow-2xl p-8 md:p-10 border border-gray-100">
        <!-- En-tête -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
          </div>
          <h2 class="text-3xl font-bold text-gray-900 mb-2">Inscription Club</h2>
          <p class="text-gray-600">Rejoignez activibe en tant qu'établissement sportif</p>
        </div>

        <!-- Messages -->
        <div v-if="error" class="mb-6 bg-red-50 border-l-4 border-red-400 rounded-lg p-4 animate-shake">
          <div class="flex items-start">
            <svg class="h-5 w-5 text-red-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
              <p class="text-sm text-red-700">{{ error }}</p>
            </div>
          </div>
        </div>

        <div v-if="success" class="mb-6 bg-green-50 border-l-4 border-green-400 rounded-lg p-4">
          <div class="flex items-start">
            <svg class="h-5 w-5 text-green-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
              <p class="text-sm text-green-700 font-medium">{{ success }}</p>
            </div>
          </div>
        </div>

        <!-- Formulaire -->
        <form @submit.prevent="registerClub" class="space-y-6">
          <!-- Informations du club -->
          <div class="bg-purple-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-purple-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
              </div>
              <h3 class="text-lg font-semibold text-gray-900">Informations du club</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Nom du club <span class="text-red-500">*</span>
                </label>
                <input 
                  v-model="form.name" 
                  type="text" 
                  required
                  placeholder="Ex: Centre Sportif Activibe"
                  class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Email <span class="text-red-500">*</span>
                </label>
                <input 
                  v-model="form.email" 
                  type="email" 
                  required
                  placeholder="contact@club.com"
                  class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Mot de passe <span class="text-red-500">*</span>
                </label>
                <input 
                  v-model="form.password" 
                  type="password" 
                  required
                  minlength="8"
                  placeholder="Minimum 8 caractères"
                  class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Confirmer le mot de passe <span class="text-red-500">*</span>
                </label>
                <input 
                  v-model="form.password_confirmation" 
                  type="password" 
                  required
                  minlength="8"
                  placeholder="Répétez le mot de passe"
                  class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Téléphone
                </label>
                <input 
                  v-model="form.phone" 
                  type="tel"
                  placeholder="06 12 34 56 78"
                  class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Site web
                </label>
                <input 
                  v-model="form.website" 
                  type="url"
                  placeholder="https://www.monclub.fr"
                  class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                />
              </div>
            </div>

            <div class="mt-4">
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Description
              </label>
              <textarea 
                v-model="form.description" 
                rows="3"
                placeholder="Décrivez brièvement votre club et vos activités..."
                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
              ></textarea>
            </div>
          </div>

          <!-- Adresse -->
          <div class="bg-gray-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-blue-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
              </div>
              <h3 class="text-lg font-semibold text-gray-900">Adresse</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Adresse
                </label>
                <input 
                  v-model="form.address" 
                  type="text"
                  placeholder="12 rue des Sports"
                  class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Code postal
                </label>
                <input 
                  v-model="form.postal_code" 
                  type="text"
                  placeholder="75001"
                  class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Ville
                </label>
                <input 
                  v-model="form.city" 
                  type="text"
                  placeholder="Paris"
                  class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                />
              </div>
            </div>
          </div>

          <!-- Boutons -->
          <div class="flex flex-col sm:flex-row gap-3 pt-6">
            <button 
              type="button" 
              @click="goBack"
              class="flex-1 px-6 py-3 border-2 border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 hover:border-gray-400 transition-all duration-200"
            >
              Annuler
            </button>
            <button 
              type="submit" 
              :disabled="loading"
              class="flex-1 flex items-center justify-center px-6 py-3 border border-transparent rounded-lg text-white font-semibold bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none shadow-lg hover:shadow-xl"
            >
              <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span v-if="loading">Inscription en cours...</span>
              <span v-else>Créer mon compte club</span>
            </button>
          </div>

          <!-- Lien de connexion -->
          <div class="text-center pt-4">
            <p class="text-sm text-gray-600">
              Vous avez déjà un compte ?
              <NuxtLink to="/login" class="font-medium text-purple-600 hover:text-purple-700 transition-colors">
                Connexion
              </NuxtLink>
            </p>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
const router = useRouter()
const config = useRuntimeConfig()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
  website: '',
  description: '',
  address: '',
  city: '',
  postal_code: '',
  role: 'club'
})

const loading = ref(false)
const error = ref('')
const success = ref('')

const registerClub = async () => {
  error.value = ''
  success.value = ''
  
  // Validation côté client
  if (form.password !== form.password_confirmation) {
    error.value = 'Les mots de passe ne correspondent pas'
    return
  }
  
  if (form.password.length < 8) {
    error.value = 'Le mot de passe doit contenir au moins 8 caractères'
    return
  }
  
  loading.value = true
  
  try {
    const response = await $fetch(`${config.public.apiBase}/auth/register`, {
      method: 'POST',
      body: form
    })
    
    success.value = 'Inscription réussie ! Redirection...'
    
    // Rediriger vers la page de connexion après 2 secondes
    setTimeout(() => {
      navigateTo('/login')
    }, 2000)
  } catch (err) {
    console.error('Erreur lors de l\'inscription:', err)
    
    if (err.response?.data?.message) {
      error.value = err.response.data.message
    } else if (err.response?.data?.errors) {
      const errors = Object.values(err.response.data.errors).flat()
      error.value = errors.join(', ')
    } else if (err.response?.status === 422) {
      error.value = 'Les données fournies sont invalides'
    } else {
      error.value = 'Une erreur est survenue lors de l\'inscription'
    }
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.back()
}

// SEO
useHead({
  title: 'Inscription Club | activibe',
  meta: [
    { name: 'description', content: 'Inscrivez votre club sportif sur activibe et gérez vos cours et adhérents.' }
  ]
})
</script>

<style scoped>
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
  20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.animate-shake {
  animation: shake 0.5s;
}
</style>

