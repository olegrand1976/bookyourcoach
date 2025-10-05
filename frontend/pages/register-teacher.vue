<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="flex justify-center">
        <img src="/images/logo-activibe.svg" alt="Acti'Vibe" class="h-16 w-auto" />
      </div>
      <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">
        Inscription Enseignant
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        Rejoignez notre plateforme en tant qu'enseignant
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-2xl">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <form @submit.prevent="registerTeacher" class="space-y-6">
          
          <!-- Section Informations personnelles -->
          <div class="bg-gray-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-blue-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <h3 class="text-lg font-semibold text-gray-900">Informations personnelles</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Nom complet <span class="text-red-500">*</span>
                </label>
                <input 
                  v-model="form.name" 
                  type="text" 
                  required 
                  placeholder="Ex: Marie Dubois"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Email <span class="text-red-500">*</span>
                </label>
                <input 
                  v-model="form.email" 
                  type="email" 
                  required 
                  placeholder="Ex: marie.dubois@email.com"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Mot de passe <span class="text-red-500">*</span>
                </label>
                <input 
                  v-model="form.password" 
                  type="password" 
                  required 
                  placeholder="Minimum 8 caractères"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Confirmer le mot de passe <span class="text-red-500">*</span>
                </label>
                <input 
                  v-model="form.password_confirmation" 
                  type="password" 
                  required 
                  placeholder="Répétez le mot de passe"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                <input 
                  v-model="form.phone" 
                  type="tel" 
                  placeholder="Ex: 06 12 34 56 78"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Années d'expérience</label>
                <input 
                  v-model.number="form.experience_years" 
                  type="number" 
                  min="0" 
                  placeholder="Ex: 5"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>
            </div>
          </div>

          <!-- Section Spécialisations -->
          <div class="bg-purple-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-purple-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900">Spécialisations</h3>
                <p class="text-sm text-gray-600">
                  Sélectionnez vos domaines d'expertise (optionnel)
                </p>
              </div>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
              <div 
                v-for="specialization in availableSpecializations" 
                :key="specialization.value" 
                class="flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 hover:shadow-md"
                :class="form.specializations.includes(specialization.value) 
                  ? 'border-blue-500 bg-blue-50 shadow-md' 
                  : 'border-gray-200 bg-white hover:border-gray-300'"
                @click="toggleSpecialization(specialization.value)"
              >
                <input 
                  :id="'specialization-' + specialization.value" 
                  v-model="form.specializations" 
                  :value="specialization.value" 
                  type="checkbox" 
                  class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                >
                <label :for="'specialization-' + specialization.value" class="ml-4 flex items-center cursor-pointer flex-1">
                  <span class="text-2xl mr-3">{{ specialization.icon }}</span>
                  <div>
                    <div class="font-medium text-gray-900">{{ specialization.label }}</div>
                    <div class="text-sm text-gray-500">{{ specialization.description }}</div>
                  </div>
                </label>
              </div>
            </div>
            
            <!-- Message si aucune spécialisation sélectionnée -->
            <div v-if="form.specializations.length === 0" class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <div class="flex items-center">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm text-blue-800">
                  Aucune spécialisation sélectionnée. Vous pourrez les ajouter plus tard dans votre profil.
                </p>
              </div>
            </div>
          </div>

          <!-- Section Tarifs et Bio -->
          <div class="bg-emerald-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-emerald-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <h3 class="text-lg font-semibold text-gray-900">Tarifs et présentation</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Tarif horaire (€)</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">€</span>
                  </div>
                  <input 
                    v-model.number="form.hourly_rate" 
                    type="number" 
                    min="0" 
                    step="0.01" 
                    placeholder="50.00"
                    class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  >
                </div>
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Bio / Présentation</label>
                <textarea 
                  v-model="form.bio" 
                  rows="4" 
                  placeholder="Décrivez votre expérience et votre approche pédagogique..."
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
                ></textarea>
              </div>
            </div>
          </div>
          
          <!-- Boutons d'action -->
          <div class="flex justify-between items-center pt-6 border-t border-gray-200">
            <NuxtLink 
              to="/login"
              class="text-gray-600 hover:text-gray-800 font-medium"
            >
              ← Retour à la connexion
            </NuxtLink>
            
            <button 
              type="submit" 
              :disabled="loading || form.password !== form.password_confirmation" 
              class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg hover:from-blue-600 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-medium flex items-center space-x-2"
            >
              <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ loading ? 'Inscription en cours...' : 'S\'inscrire' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

definePageMeta({
  layout: 'minimal'
})

const loading = ref(false)

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
  specializations: [], // Aucune spécialisation par défaut pour l'auto-inscription
  experience_years: 0,
  hourly_rate: 50,
  bio: ''
})

// Spécialisations disponibles avec icônes
const availableSpecializations = ref([
  { value: 'dressage', label: 'Dressage', description: 'Équitation classique', icon: '🏇' },
  { value: 'obstacle', label: 'Obstacle', description: 'Saut d\'obstacles', icon: '🏆' },
  { value: 'cross', label: 'Cross', description: 'Cross-country', icon: '🌲' },
  { value: 'complet', label: 'Complet', description: 'Concours complet', icon: '🎯' },
  { value: 'voltige', label: 'Voltige', description: 'Voltige équestre', icon: '🤸' },
  { value: 'pony', label: 'Poney', description: 'Cours poney', icon: '🐴' }
])

// Toggle spécialisation
const toggleSpecialization = (specializationValue) => {
  const index = form.value.specializations.indexOf(specializationValue)
  if (index > -1) {
    form.value.specializations.splice(index, 1)
  } else {
    form.value.specializations.push(specializationValue)
  }
}

const registerTeacher = async () => {
  loading.value = true
  try {
    const config = useRuntimeConfig()
    
    // Validation côté client
    if (form.value.password !== form.value.password_confirmation) {
      throw new Error('Les mots de passe ne correspondent pas')
    }
    
    if (form.value.password.length < 8) {
      throw new Error('Le mot de passe doit contenir au moins 8 caractères')
    }
    
    const response = await $fetch(`${config.public.apiBase}/auth/register-teacher`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: {
        name: form.value.name,
        email: form.value.email,
        password: form.value.password,
        password_confirmation: form.value.password_confirmation,
        phone: form.value.phone,
        specializations: form.value.specializations,
        experience_years: form.value.experience_years,
        hourly_rate: form.value.hourly_rate,
        bio: form.value.bio
      }
    })
    
    console.log('✅ Enseignant inscrit avec succès:', response)
    
    // Afficher le toast de succès
    const { showToast } = useToast()
    showToast('Inscription réussie ! Vous pouvez maintenant vous connecter.', 'success')
    
    // Rediriger vers la page de connexion
    await navigateTo('/login')
    
  } catch (error) {
    console.error('❌ Erreur lors de l\'inscription:', error)
    
    // Afficher le toast d'erreur
    const { showToast } = useToast()
    showToast(error.message || 'Erreur lors de l\'inscription', 'error')
    
  } finally {
    loading.value = false
  }
}
</script>
