<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">
              Test des données du club
            </h1>
            <p class="mt-2 text-gray-600">
              Vérification de la présence des données dans la base
            </p>
          </div>
          <div class="flex space-x-4">
            <button 
              @click="refreshData"
              :disabled="loading"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
            >
              <span v-if="loading">Chargement...</span>
              <span v-else>Actualiser</span>
            </button>
            <button 
              @click="navigateTo('/club/dashboard')"
              class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors"
            >
              Retour au dashboard
            </button>
          </div>
        </div>
      </div>

      <!-- Statistiques générales -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
          <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Clubs</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.clubs }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
          <div class="flex items-center">
            <div class="p-3 bg-green-100 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Utilisateurs</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.users }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
          <div class="flex items-center">
            <div class="p-3 bg-purple-100 rounded-lg">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Cours</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.lessons }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
          <div class="flex items-center">
            <div class="p-3 bg-yellow-100 rounded-lg">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Paiements</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.payments }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Liste des clubs -->
      <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
          <h2 class="text-xl font-semibold text-gray-900">Clubs dans la base de données</h2>
        </div>
        <div class="p-6">
          <div v-if="clubs.length === 0" class="text-center text-gray-500 py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <p>Aucun club trouvé dans la base de données</p>
          </div>
          <div v-else class="space-y-4">
            <div 
              v-for="club in clubs" 
              :key="club.id" 
              class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <h3 class="text-lg font-semibold text-gray-900">{{ club.name }}</h3>
                  <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600">
                    <div>
                      <span class="font-medium">Email:</span> {{ club.email || 'Non renseigné' }}
                    </div>
                    <div>
                      <span class="font-medium">Téléphone:</span> {{ club.phone || 'Non renseigné' }}
                    </div>
                    <div>
                      <span class="font-medium">Ville:</span> {{ club.city || 'Non renseigné' }}
                    </div>
                    <div>
                      <span class="font-medium">Statut:</span> 
                      <span :class="club.is_active ? 'text-green-600' : 'text-red-600'">
                        {{ club.is_active ? 'Actif' : 'Inactif' }}
                      </span>
                    </div>
                  </div>
                  <div v-if="club.description" class="mt-2 text-sm text-gray-600">
                    <span class="font-medium">Description:</span> {{ club.description }}
                  </div>
                </div>
                <div class="ml-4">
                  <button 
                    @click="viewClubDetails(club.id)"
                    class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition-colors"
                  >
                    Détails
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Détails d'un club sélectionné -->
      <div v-if="selectedClub" class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50">
          <h2 class="text-xl font-semibold text-gray-900">Détails du club: {{ selectedClub.name }}</h2>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Informations du club -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations générales</h3>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="font-medium text-gray-600">ID:</span>
                  <span class="text-gray-900">{{ selectedClub.id }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium text-gray-600">Nom:</span>
                  <span class="text-gray-900">{{ selectedClub.name }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium text-gray-600">Email:</span>
                  <span class="text-gray-900">{{ selectedClub.email || 'Non renseigné' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium text-gray-600">Téléphone:</span>
                  <span class="text-gray-900">{{ selectedClub.phone || 'Non renseigné' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium text-gray-600">Adresse:</span>
                  <span class="text-gray-900">{{ selectedClub.address || 'Non renseigné' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium text-gray-600">Ville:</span>
                  <span class="text-gray-900">{{ selectedClub.city || 'Non renseigné' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium text-gray-600">Code postal:</span>
                  <span class="text-gray-900">{{ selectedClub.postal_code || 'Non renseigné' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium text-gray-600">Pays:</span>
                  <span class="text-gray-900">{{ selectedClub.country || 'Non renseigné' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium text-gray-600">Site web:</span>
                  <span class="text-gray-900">{{ selectedClub.website || 'Non renseigné' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium text-gray-600">Statut:</span>
                  <span :class="selectedClub.is_active ? 'text-green-600' : 'text-red-600'">
                    {{ selectedClub.is_active ? 'Actif' : 'Inactif' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Statistiques du club -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistiques</h3>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="font-medium text-gray-600">Enseignants:</span>
                  <span class="text-gray-900">{{ clubStats.teachers }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium text-gray-600">Élèves:</span>
                  <span class="text-gray-900">{{ clubStats.students }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium text-gray-600">Cours:</span>
                  <span class="text-gray-900">{{ clubStats.lessons }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium text-gray-600">Cours terminés:</span>
                  <span class="text-gray-900">{{ clubStats.completed_lessons }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium text-gray-600">Revenus totaux:</span>
                  <span class="text-gray-900">{{ clubStats.total_revenue }}€</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium text-gray-600">Revenus ce mois:</span>
                  <span class="text-gray-900">{{ clubStats.monthly_revenue }}€</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Description -->
          <div v-if="selectedClub.description" class="mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Description</h3>
            <p class="text-gray-600">{{ selectedClub.description }}</p>
          </div>

          <!-- Équipements -->
          <div v-if="selectedClub.facilities" class="mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Équipements</h3>
            <div class="flex flex-wrap gap-2">
              <span 
                v-for="facility in selectedClub.facilities" 
                :key="facility"
                class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm"
              >
                {{ facility }}
              </span>
            </div>
          </div>

          <!-- Disciplines -->
          <div v-if="selectedClub.disciplines" class="mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Disciplines</h3>
            <div class="flex flex-wrap gap-2">
              <span 
                v-for="discipline in selectedClub.disciplines" 
                :key="discipline"
                class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm"
              >
                {{ discipline }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

definePageMeta({
  middleware: ['auth']
})

const loading = ref(false)
const clubs = ref([])
const selectedClub = ref(null)
const clubStats = ref({})
const stats = ref({
  clubs: 0,
  users: 0,
  lessons: 0,
  payments: 0
})

// Charger toutes les données
const loadAllData = async () => {
  loading.value = true
  try {
    const config = useRuntimeConfig()
    
    // Charger les statistiques générales
    const statsResponse = await $fetch(`${config.public.apiBase}/club/test-stats`)
    stats.value = statsResponse.data
    
    // Charger la liste des clubs
    const clubsResponse = await $fetch(`${config.public.apiBase}/club/test-clubs`)
    clubs.value = clubsResponse.data
    
    console.log('✅ Données chargées:', { stats: stats.value, clubs: clubs.value })
  } catch (error) {
    console.error('❌ Erreur lors du chargement des données:', error)
  } finally {
    loading.value = false
  }
}

// Actualiser les données
const refreshData = () => {
  loadAllData()
}

// Voir les détails d'un club
const viewClubDetails = async (clubId) => {
  try {
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/club/test-club-details/${clubId}`)
    
    selectedClub.value = response.data.club
    clubStats.value = response.data.stats
    
    console.log('✅ Détails du club chargés:', response.data)
  } catch (error) {
    console.error('❌ Erreur lors du chargement des détails:', error)
  }
}

onMounted(() => {
  loadAllData()
})
</script>

