<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- En-tête -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Mon Profil Enseignant</h1>
            <p class="mt-2 text-gray-600">Gérez vos informations personnelles et professionnelles</p>
          </div>
          <NuxtLink to="/teacher/dashboard"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            <span>←</span>
            <span class="ml-2">Retour au tableau de bord</span>
          </NuxtLink>
        </div>
      </div>

      <!-- Contenu du profil -->
      <div class="bg-white rounded-xl shadow-lg p-6">
        <div v-if="loading" class="text-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-4 text-gray-600">Chargement du profil...</p>
        </div>

        <div v-else-if="error" class="text-center py-8">
          <div class="text-red-500 text-6xl mb-4">⚠️</div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Erreur de chargement</h3>
          <p class="text-gray-600 mb-4">{{ error }}</p>
          <button @click="loadProfileData"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            Réessayer
          </button>
        </div>

        <div v-else class="space-y-6">
          <!-- Informations personnelles -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Informations personnelles</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                <p class="text-gray-900">{{ profileData?.name || 'Non renseigné' }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <p class="text-gray-900">{{ profileData?.email || 'Non renseigné' }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                <p class="text-gray-900">{{ profileData?.phone || 'Non renseigné' }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date de naissance</label>
                <p class="text-gray-900">{{ profileData?.birth_date || 'Non renseigné' }}</p>
              </div>
            </div>
          </div>

          <!-- Informations professionnelles -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Informations professionnelles</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Spécialités</label>
                <div v-if="teacherData?.specialties" class="flex flex-wrap gap-2">
                  <span v-for="specialty in getSpecialtiesArray(teacherData.specialties)" :key="specialty"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ specialty }}
                  </span>
                </div>
                <p v-else class="text-gray-500">Non renseigné</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Années d'expérience</label>
                <p class="text-gray-900">{{ teacherData?.experience_years ? `${teacherData.experience_years} ans` : 'Non renseigné' }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tarif horaire</label>
                <p class="text-gray-900">{{ teacherData?.hourly_rate ? `${teacherData.hourly_rate}€/h` : 'Non renseigné' }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="profileData?.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                  {{ profileData?.status === 'active' ? 'Actif' : 'En attente' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Description -->
          <div>
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Description</h2>
            <div class="bg-gray-50 rounded-lg p-4">
              <p class="text-gray-700">{{ teacherData?.bio || 'Aucune description renseignée' }}</p>
            </div>
          </div>

          <!-- Certifications -->
          <div v-if="teacherData?.certifications">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Certifications</h2>
            <div class="flex flex-wrap gap-2">
              <span v-for="certification in getCertificationsArray(teacherData.certifications)" :key="certification"
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                🏆 {{ certification }}
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
            <button @click="editProfile"
              class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
              <span>✏️</span>
              <span class="ml-2">Modifier le profil</span>
            </button>
          </div>
        </div>
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
const loading = ref(true)
const error = ref(null)
const profileData = ref(null)
const teacherData = ref(null)

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
    loading.value = true
    error.value = null
    
    const { $api } = useNuxtApp()
    const response = await $api.get('/profile')
    
    if (response.data) {
      profileData.value = response.data.profile
      teacherData.value = response.data.teacher
    }
  } catch (err) {
    console.error('Erreur lors du chargement du profil:', err)
    error.value = 'Impossible de charger les données du profil'
  } finally {
    loading.value = false
  }
}

// Modifier le profil
const editProfile = () => {
  // Rediriger vers la page d'édition du profil
  navigateTo('/teacher/profile/edit')
}

// Fonctions utilitaires pour convertir les données JSON
const getSpecialtiesArray = (specialties) => {
  if (!specialties) return []
  
  try {
    // Si c'est déjà un tableau
    if (Array.isArray(specialties)) return specialties
    
    // Si c'est une chaîne JSON
    if (typeof specialties === 'string') {
      const parsed = JSON.parse(specialties)
      return Array.isArray(parsed) ? parsed : [specialties]
    }
    
    return []
  } catch (error) {
    console.error('Erreur lors du parsing des spécialités:', error)
    return [specialties]
  }
}

const getCertificationsArray = (certifications) => {
  if (!certifications) return []
  
  try {
    // Si c'est déjà un tableau
    if (Array.isArray(certifications)) return certifications
    
    // Si c'est une chaîne JSON
    if (typeof certifications === 'string') {
      const parsed = JSON.parse(certifications)
      return Array.isArray(parsed) ? parsed : [certifications]
    }
    
    return []
  } catch (error) {
    console.error('Erreur lors du parsing des certifications:', error)
    return [certifications]
  }
}

// Watcher pour recharger les données si l'utilisateur change
watch(() => authStore.user, (newUser) => {
  if (newUser) {
    loadProfileData()
  }
}, { immediate: false })
</script>
