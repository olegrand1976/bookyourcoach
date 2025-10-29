<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- En-tête -->
      <div class="mb-6 md:mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Modifier mon profil</h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">Mettez à jour vos informations personnelles et professionnelles</p>
          </div>
          <NuxtLink to="/teacher/profile"
            class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
            <span>←</span>
            <span class="ml-2">Retour au profil</span>
          </NuxtLink>
        </div>
      </div>

      <!-- Formulaire -->
      <div class="bg-white rounded-xl shadow-lg p-6">
        <form @submit.prevent="updateProfile" class="space-y-6">
          <!-- Informations personnelles -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Informations personnelles</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet *</label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': errors.name }"
                />
                <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
              </div>
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': errors.email }"
                />
                <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
              </div>
              <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                <input
                  id="phone"
                  v-model="form.phone"
                  type="tel"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': errors.phone }"
                />
                <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone }}</p>
              </div>
              <div>
                <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">Date de naissance</label>
                <input
                  id="birth_date"
                  v-model="form.birth_date"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': errors.birth_date }"
                />
                <p v-if="errors.birth_date" class="mt-1 text-sm text-red-600">{{ errors.birth_date }}</p>
              </div>
            </div>
          </div>

          <!-- Informations professionnelles -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Informations professionnelles</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="specialties" class="block text-sm font-medium text-gray-700 mb-1">Spécialités</label>
                <textarea
                  id="specialties"
                  v-model="form.specialties"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': errors.specialties }"
                  placeholder="Décrivez vos spécialités et domaines d'expertise..."
                ></textarea>
                <p v-if="errors.specialties" class="mt-1 text-sm text-red-600">{{ errors.specialties }}</p>
              </div>
              <div>
                <label for="experience_years" class="block text-sm font-medium text-gray-700 mb-1">Années d'expérience</label>
                <input
                  id="experience_years"
                  v-model.number="form.experience_years"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': errors.experience_years }"
                />
                <p v-if="errors.experience_years" class="mt-1 text-sm text-red-600">{{ errors.experience_years }}</p>
              </div>
              <div>
                <label for="certifications" class="block text-sm font-medium text-gray-700 mb-1">Certifications</label>
                <textarea
                  id="certifications"
                  v-model="form.certifications"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': errors.certifications }"
                  placeholder="Listez vos certifications et diplômes..."
                ></textarea>
                <p v-if="errors.certifications" class="mt-1 text-sm text-red-600">{{ errors.certifications }}</p>
              </div>
              <div>
                <label for="hourly_rate" class="block text-sm font-medium text-gray-700 mb-1">Tarif horaire (€)</label>
                <input
                  id="hourly_rate"
                  v-model.number="form.hourly_rate"
                  type="number"
                  min="0"
                  step="0.01"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': errors.hourly_rate }"
                />
                <p v-if="errors.hourly_rate" class="mt-1 text-sm text-red-600">{{ errors.hourly_rate }}</p>
              </div>
            </div>
          </div>

          <!-- Description -->
          <div>
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Description</h2>
            <div>
              <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Biographie</label>
              <textarea
                id="bio"
                v-model="form.bio"
                rows="4"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.bio }"
                placeholder="Décrivez votre parcours, votre approche pédagogique..."
              ></textarea>
              <p v-if="errors.bio" class="mt-1 text-sm text-red-600">{{ errors.bio }}</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
            <button
              type="button"
              @click="goBack"
              class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
              Annuler
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <div v-if="loading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
              <span v-if="loading">Enregistrement...</span>
              <span v-else>Enregistrer les modifications</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: ['auth']
})

// Utiliser le store d'authentification
const authStore = useAuthStore()

// État réactif
const loading = ref(false)
const errors = ref({})

// Formulaire
const form = ref({
  name: '',
  email: '',
  phone: '',
  birth_date: '',
  specialties: '',
  experience_years: null,
  certifications: '',
  hourly_rate: null,
  bio: ''
})

// Retour à la page précédente
const goBack = () => {
  // Utiliser l'historique du navigateur pour revenir en arrière
  if (process.client && window.history.length > 1) {
    window.history.back()
  } else {
    // Fallback vers la page du profil si pas d'historique
    navigateTo('/teacher/profile')
  }
}

// Vérifier que l'utilisateur est un enseignant
onMounted(() => {
  if (!authStore.canActAsTeacher) {
    throw createError({
      statusCode: 403,
      statusMessage: 'Accès non autorisé'
    })
  }
  
  loadProfileData()
})

// Charger les données du profil
const loadProfileData = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/teacher/profile')
    
    if (response.data) {
      const profile = response.data.profile
      const teacher = response.data.teacher
      
      // Remplir le formulaire avec les données existantes
      form.value = {
        name: profile?.name || authStore.user?.name || '',
        email: profile?.email || authStore.user?.email || '',
        phone: profile?.phone || '',
        birth_date: profile?.birth_date || '',
        specialties: teacher?.specialties ? (Array.isArray(teacher.specialties) ? teacher.specialties.join(', ') : teacher.specialties) : '',
        experience_years: teacher?.experience_years || null,
        certifications: teacher?.certifications ? (Array.isArray(teacher.certifications) ? teacher.certifications.join(', ') : teacher.certifications) : '',
        hourly_rate: teacher?.hourly_rate || null,
        bio: teacher?.bio || ''
      }
    }
  } catch (err) {
    console.error('Erreur lors du chargement du profil:', err)
    // Afficher une notification d'erreur
    const { showToast } = useToast()
    showToast('Impossible de charger les données du profil', { type: 'error' })
  }
}

// Mettre à jour le profil
const updateProfile = async () => {
  try {
    loading.value = true
    errors.value = {}
    
    const { $api } = useNuxtApp()
    const response = await $api.put('/teacher/profile', form.value)
    
    if (response.data) {
      // Afficher un message de succès
      const { showToast } = useToast()
      showToast('Profil mis à jour avec succès', { type: 'success' })
      
      // Rediriger vers la page du profil
      await navigateTo('/teacher/profile')
    }
  } catch (err) {
    console.error('Erreur lors de la mise à jour du profil:', err)
    
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      const { showToast } = useToast()
      showToast('Erreur lors de la mise à jour du profil', { type: 'error' })
    }
  } finally {
    loading.value = false
  }
}
</script>