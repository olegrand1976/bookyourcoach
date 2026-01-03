<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-6 md:mb-8">
        <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
              Mon Profil
            </h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">
              Gérez vos informations personnelles et vos affiliations aux clubs
            </p>
          </div>
          
          <NuxtLink 
            to="/student/dashboard"
            class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm md:text-base"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Retour au dashboard
          </NuxtLink>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-gray-600">Chargement du profil...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
        <div class="flex items-center">
          <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-red-800">{{ error }}</p>
        </div>
      </div>

      <!-- Form -->
      <div v-else class="space-y-6">
        <!-- Informations personnelles -->
        <div class="bg-white rounded-xl shadow-lg p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Informations personnelles</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                Prénom *
              </label>
              <input
                id="first_name"
                v-model="form.first_name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Prénom"
              />
            </div>
            
            <div>
              <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                Nom *
              </label>
              <input
                id="last_name"
                v-model="form.last_name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Nom"
              />
            </div>
            
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                Email *
              </label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Email"
              />
            </div>
            
            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                Téléphone
              </label>
              <input
                id="phone"
                v-model="form.phone"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Téléphone"
              />
            </div>
            
            <div>
              <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">
                Date de naissance
              </label>
              <input
                id="birth_date"
                v-model="form.birth_date"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>
        </div>

        <!-- Affiliations aux clubs -->
        <div class="bg-white rounded-xl shadow-lg p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Mes clubs</h2>
          
          <div v-if="loadingClubs" class="text-sm text-gray-500">
            Chargement des clubs...
          </div>
          
          <div v-else>
            <!-- Clubs actuels -->
            <div v-if="currentClubs.length > 0" class="mb-6">
              <h3 class="text-sm font-medium text-gray-700 mb-3">Clubs auxquels je suis affilié</h3>
              <div class="space-y-2">
                <div 
                  v-for="club in currentClubs" 
                  :key="club.id"
                  class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                >
                  <div>
                    <span class="font-medium text-gray-900">{{ club.name }}</span>
                    <span v-if="club.city" class="ml-2 text-sm text-gray-500">{{ club.city }}</span>
                  </div>
                  <button
                    @click="removeClub(club.id)"
                    :disabled="saving"
                    class="text-red-600 hover:text-red-800 text-sm font-medium disabled:opacity-50"
                  >
                    Retirer
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Ajouter un club -->
            <div>
              <h3 class="text-sm font-medium text-gray-700 mb-3">Ajouter un club</h3>
              <div v-if="availableClubsToAdd.length === 0" class="text-sm text-gray-500">
                Tous les clubs disponibles sont déjà ajoutés.
              </div>
              <div v-else class="space-y-2 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-3">
                <label 
                  v-for="club in availableClubsToAdd" 
                  :key="club.id"
                  class="flex items-center hover:bg-gray-50 p-2 rounded cursor-pointer"
                >
                  <input
                    v-model="selectedClubsToAdd"
                    :value="club.id"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <div class="ml-3 flex-1">
                    <span class="text-sm font-medium text-gray-900">{{ club.name }}</span>
                    <span v-if="club.city" class="ml-2 text-xs text-gray-500">{{ club.city }}</span>
                    <span v-if="club.postal_code" class="text-xs text-gray-500">({{ club.postal_code }})</span>
                  </div>
                </label>
              </div>
              
              <button
                v-if="selectedClubsToAdd.length > 0"
                @click="addClubs"
                :disabled="saving"
                class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium disabled:opacity-50"
              >
                Ajouter {{ selectedClubsToAdd.length }} club(s)
              </button>
            </div>
          </div>
        </div>

        <!-- Bouton de sauvegarde -->
        <div class="flex justify-end">
          <button
            @click="saveProfile"
            :disabled="saving"
            class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all shadow-lg hover:shadow-xl text-sm md:text-base font-medium disabled:opacity-50"
          >
            <span v-if="!saving">Enregistrer les modifications</span>
            <span v-else class="flex items-center">
              <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Enregistrement...
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from '~/composables/useToast'

definePageMeta({
  middleware: ['auth', 'student'],
  layout: 'student'
})

const toast = useToast()
const { $api } = useNuxtApp()
const config = useRuntimeConfig()

// State
const loading = ref(true)
const saving = ref(false)
const loadingClubs = ref(false)
const error = ref<string | null>(null)
const availableClubs = ref<any[]>([])
const currentClubs = ref<any[]>([])
const selectedClubsToAdd = ref<number[]>([])

// Form
const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  birth_date: ''
})

// Computed
const availableClubsToAdd = computed(() => {
  const currentClubIds = currentClubs.value.map(c => c.id)
  return availableClubs.value.filter(club => !currentClubIds.includes(club.id))
})

// Methods
const loadProfile = async () => {
  try {
    loading.value = true
    error.value = null
    
    const authStore = useAuthStore()
    const user = authStore.user
    
    if (user) {
      form.first_name = user.first_name || ''
      form.last_name = user.last_name || ''
      form.email = user.email || ''
      form.phone = user.phone || ''
      form.birth_date = user.birth_date || ''
    }
    
    // Charger les clubs de l'élève
    await loadStudentClubs()
    
    // Charger la liste des clubs disponibles
    await loadAvailableClubs()
  } catch (err: any) {
    console.error('Erreur lors du chargement du profil:', err)
    error.value = err.response?.data?.message || 'Erreur lors du chargement du profil'
    toast.error(error.value)
  } finally {
    loading.value = false
  }
}

const loadStudentClubs = async () => {
  try {
    const response = await $api.get('/student/clubs')
    if (response.data.success) {
      currentClubs.value = response.data.data || []
    }
  } catch (err: any) {
    console.error('Erreur lors du chargement des clubs de l\'élève:', err)
  }
}

const loadAvailableClubs = async () => {
  try {
    loadingClubs.value = true
    const response = await $fetch('/clubs/public', {
      baseURL: config.public.apiBase
    })
    
    if (response.success && response.data) {
      availableClubs.value = response.data
    }
  } catch (err: any) {
    console.error('Erreur lors du chargement des clubs disponibles:', err)
  } finally {
    loadingClubs.value = false
  }
}

const saveProfile = async () => {
  try {
    saving.value = true
    error.value = null
    
    const authStore = useAuthStore()
    
    // Mettre à jour les informations utilisateur
    await $api.put('/auth/profile', {
      first_name: form.first_name,
      last_name: form.last_name,
      email: form.email,
      phone: form.phone,
      birth_date: form.birth_date
    })
    
    // Mettre à jour le store
    if (authStore.user) {
      authStore.user.first_name = form.first_name
      authStore.user.last_name = form.last_name
      authStore.user.email = form.email
      authStore.user.phone = form.phone
      authStore.user.birth_date = form.birth_date
    }
    
    toast.success('Profil mis à jour avec succès')
  } catch (err: any) {
    console.error('Erreur lors de la sauvegarde du profil:', err)
    error.value = err.response?.data?.message || 'Erreur lors de la sauvegarde'
    toast.error(error.value)
  } finally {
    saving.value = false
  }
}

const addClubs = async () => {
  if (selectedClubsToAdd.value.length === 0) return
  
  try {
    saving.value = true
    
    await $api.post('/student/clubs', {
      club_ids: selectedClubsToAdd.value
    })
    
    // Recharger les clubs
    await loadStudentClubs()
    selectedClubsToAdd.value = []
    
    toast.success('Clubs ajoutés avec succès')
  } catch (err: any) {
    console.error('Erreur lors de l\'ajout des clubs:', err)
    toast.error(err.response?.data?.message || 'Erreur lors de l\'ajout des clubs')
  } finally {
    saving.value = false
  }
}

const removeClub = async (clubId: number) => {
  if (!confirm('Êtes-vous sûr de vouloir retirer ce club ?')) return
  
  try {
    saving.value = true
    
    await $api.delete(`/student/clubs/${clubId}`)
    
    // Recharger les clubs
    await loadStudentClubs()
    
    toast.success('Club retiré avec succès')
  } catch (err: any) {
    console.error('Erreur lors de la suppression du club:', err)
    toast.error(err.response?.data?.message || 'Erreur lors de la suppression du club')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadProfile()
})
</script>
