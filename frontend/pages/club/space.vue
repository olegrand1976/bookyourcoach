<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
          Espace Club
        </h1>
        <p class="mt-2 text-gray-600">
          Gérez votre club équestre : enseignants, élèves, cours et installations
        </p>
      </div>

      <!-- Navigation Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Gestion des Enseignants -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer">
          <div class="flex items-center mb-4">
            <div class="p-3 bg-blue-100 rounded-lg">
              <EquestrianIcon icon="helmet" :size="24" class="text-blue-600" />
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-gray-900">Enseignants</h3>
              <p class="text-sm text-gray-600">Gérer les moniteurs</p>
            </div>
          </div>
          <div class="text-sm text-gray-500">
            {{ stats.total_teachers }} enseignant(s) actif(s)
          </div>
        </div>

        <!-- Gestion des Élèves -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer">
          <div class="flex items-center mb-4">
            <div class="p-3 bg-green-100 rounded-lg">
              <EquestrianIcon icon="horse" :size="24" class="text-green-600" />
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-gray-900">Élèves</h3>
              <p class="text-sm text-gray-600">Gérer les cavaliers</p>
            </div>
          </div>
          <div class="text-sm text-gray-500">
            {{ stats.total_students }} élève(s) inscrit(s)
          </div>
        </div>

        <!-- Planning des Cours -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer">
          <div class="flex items-center mb-4">
            <div class="p-3 bg-purple-100 rounded-lg">
              <EquestrianIcon icon="calendar" :size="24" class="text-purple-600" />
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-gray-900">Planning</h3>
              <p class="text-sm text-gray-600">Organiser les cours</p>
            </div>
          </div>
          <div class="text-sm text-gray-500">
            {{ stats.total_lessons }} cours programmés
          </div>
        </div>

        <!-- Gestion Financière -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer">
          <div class="flex items-center mb-4">
            <div class="p-3 bg-yellow-100 rounded-lg">
              <EquestrianIcon icon="chart" :size="24" class="text-yellow-600" />
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-gray-900">Finances</h3>
              <p class="text-sm text-gray-600">Suivre les revenus</p>
            </div>
          </div>
          <div class="text-sm text-gray-500">
            {{ stats.total_revenue }}€ de CA total
          </div>
        </div>

        <!-- Installations -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer">
          <div class="flex items-center mb-4">
            <div class="p-3 bg-indigo-100 rounded-lg">
              <EquestrianIcon icon="saddle" :size="24" class="text-indigo-600" />
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-gray-900">Installations</h3>
              <p class="text-sm text-gray-600">Gérer les équipements</p>
            </div>
          </div>
          <div class="text-sm text-gray-500">
            Manèges et carrières
          </div>
        </div>

        <!-- Paramètres -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer">
          <div class="flex items-center mb-4">
            <div class="p-3 bg-gray-100 rounded-lg">
              <EquestrianIcon icon="settings" :size="24" class="text-gray-600" />
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-gray-900">Paramètres</h3>
              <p class="text-sm text-gray-600">Configuration du club</p>
            </div>
          </div>
          <div class="text-sm text-gray-500">
            Personnaliser les fonctionnalités
          </div>
        </div>
      </div>

      <!-- Statistiques Rapides -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Statistiques Rapides</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="text-center">
            <div class="text-2xl font-bold text-blue-600">{{ stats.total_teachers }}</div>
            <div class="text-sm text-gray-600">Enseignants</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-green-600">{{ stats.total_students }}</div>
            <div class="text-sm text-gray-600">Élèves</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-purple-600">{{ stats.total_lessons }}</div>
            <div class="text-sm text-gray-600">Cours</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ stats.total_revenue }}€</div>
            <div class="text-sm text-gray-600">CA Total</div>
          </div>
        </div>
      </div>

      <!-- Actions Rapides -->
      <div class="mt-8 bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Actions Rapides</h2>
        <div class="flex flex-wrap gap-4">
          <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            Ajouter un Enseignant
          </button>
          <button class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
            Inscrire un Élève
          </button>
          <button class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
            Programmer un Cours
          </button>
          <button class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
            Voir les Finances
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import EquestrianIcon from '~/components/EquestrianIcon.vue'

// Données réactives
const club = ref(null)
const stats = ref({
  total_teachers: 0,
  total_students: 0,
  total_lessons: 0,
  total_revenue: 0
})

// Charger les données du club
const loadClubData = async () => {
  try {
    console.log('🔄 Chargement des données de l\'espace club...')
    
    const response = await $fetch('http://localhost:8081/api/club/dashboard-test')
    
    console.log('✅ Données reçues:', response)
    
    if (response.success && response.data) {
      club.value = response.data.club
      stats.value = response.data.stats
    }
  } catch (error) {
    console.error('❌ Erreur lors du chargement des données:', error)
  }
}

onMounted(() => {
  loadClubData()
})
</script>
