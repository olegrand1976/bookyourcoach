<template>
  <div class="min-h-screen bg-gray-50 p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header avec gradient -->
      <div class="mb-6 md:mb-8 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-lg p-4 md:p-6 border-2 border-blue-100">
        <div class="flex items-center justify-between">
          <div class="flex-1 min-w-0">
            <h1 class="text-xl md:text-3xl font-bold text-gray-900">Modifier mon profil</h1>
            <p class="mt-1 md:mt-2 text-xs md:text-base text-gray-600">Mettez à jour vos informations personnelles et professionnelles</p>
          </div>
          <div class="flex items-center space-x-4">
            <NuxtLink to="/teacher/profile"
              class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
              <span>←</span>
              <span class="ml-2 hidden md:inline">Retour au profil</span>
            </NuxtLink>
            <div class="p-2 md:p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-md flex-shrink-0">
              <svg class="w-6 h-6 md:w-8 md:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoadingProfile" class="flex items-center justify-center py-20">
        <div class="text-center">
          <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-gray-600">Chargement des données...</p>
        </div>
      </div>

      <!-- Formulaire -->
      <form v-else @submit.prevent="updateProfile" class="bg-white shadow-lg rounded-lg p-4 md:p-6 space-y-6 md:space-y-8">
          <!-- Informations personnelles -->
          <section class="border-b pb-4 md:pb-6">
            <h2 class="text-lg md:text-xl font-semibold text-gray-900 mb-3 md:mb-4">Informations personnelles</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet *</label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': errors.birth_date }"
                />
                <p v-if="errors.birth_date" class="mt-1 text-sm text-red-600">{{ errors.birth_date }}</p>
              </div>
            </div>
          </section>

          <!-- Informations professionnelles -->
          <section class="border-b pb-4 md:pb-6">
            <h2 class="text-lg md:text-xl font-semibold text-gray-900 mb-3 md:mb-4">Informations professionnelles</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="specialties" class="block text-sm font-medium text-gray-700 mb-1">Spécialités</label>
                <textarea
                  id="specialties"
                  v-model="form.specialties"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
                  disabled
                  readonly
                  class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600 cursor-not-allowed"
                />
                <p class="mt-1 text-xs text-gray-500">Calculé automatiquement à partir de la date de début d'expérience</p>
              </div>
              <div>
                <label for="certifications" class="block text-sm font-medium text-gray-700 mb-1">Certifications</label>
                <textarea
                  id="certifications"
                  v-model="form.certifications"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
                  disabled
                  readonly
                  class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600 cursor-not-allowed"
                />
                <p class="mt-1 text-xs text-gray-500">Le tarif horaire est géré par le club</p>
              </div>
            </div>
          </div>

          <!-- Description -->
          <section>
            <h2 class="text-lg md:text-xl font-semibold text-gray-900 mb-3 md:mb-4">Description</h2>
            <div>
              <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Biographie</label>
              <textarea
                id="bio"
                v-model="form.bio"
                rows="4"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.bio }"
                placeholder="Décrivez votre parcours, votre approche pédagogique..."
              ></textarea>
              <p v-if="errors.bio" class="mt-1 text-sm text-red-600">{{ errors.bio }}</p>
            </div>
          </section>

          <!-- Actions -->
          <div class="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 pt-4 md:pt-6 border-t border-gray-200">
            <button
              type="button"
              @click="goBack"
              class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
              Annuler
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <div v-if="loading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
              <span v-if="loading">Enregistrement...</span>
              <span v-else>Enregistrer les modifications</span>
            </button>
          </div>
        </form>
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
const isLoadingProfile = ref(true)
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
    isLoadingProfile.value = true
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
    const toast = useToast()
    toast.error('Impossible de charger les données du profil')
  } finally {
    isLoadingProfile.value = false
  }
}

// Mettre à jour le profil
const updateProfile = async () => {
  try {
    loading.value = true
    errors.value = {}
    
    // Exclure hourly_rate et experience_years du formulaire car ils ne doivent pas être modifiés
    const { hourly_rate, experience_years, ...updateData } = form.value
    
    const { $api } = useNuxtApp()
    const response = await $api.put('/teacher/profile', updateData)
    
    if (response.data) {
      // Afficher un message de succès
      const toast = useToast()
      toast.success('Profil mis à jour avec succès')
      
      // Rediriger vers la page du profil
      await navigateTo('/teacher/profile')
    }
  } catch (err) {
    console.error('Erreur lors de la mise à jour du profil:', err)
    
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      const toast = useToast()
      toast.error('Erreur lors de la mise à jour du profil')
    }
  } finally {
    loading.value = false
  }
}
</script>