<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">
        Administration
      </h1>
      <p class="mt-2 text-gray-600">
        Tableau de bord administrateur
      </p>
    </div>

    <!-- Stats cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="card">
        <div class="flex items-center">
          <div class="p-2 bg-blue-100 rounded-lg">
            <UsersIcon class="w-6 h-6 text-blue-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Total utilisateurs</p>
            <p class="text-2xl font-bold text-gray-900">{{ dashboardData.stats?.total_users || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center">
          <div class="p-2 bg-green-100 rounded-lg">
            <AcademicCapIcon class="w-6 h-6 text-green-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Enseignants</p>
            <p class="text-2xl font-bold text-gray-900">{{ dashboardData.stats?.total_teachers || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center">
          <div class="p-2 bg-purple-100 rounded-lg">
            <CalendarIcon class="w-6 h-6 text-purple-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Cours totaux</p>
            <p class="text-2xl font-bold text-gray-900">{{ dashboardData.stats?.total_lessons || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center">
          <div class="p-2 bg-yellow-100 rounded-lg">
            <CurrencyEuroIcon class="w-6 h-6 text-yellow-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Revenus totaux</p>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(dashboardData.stats?.total_revenue || 0) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Gestion des utilisateurs -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Gestion des utilisateurs</h2>
          <NuxtLink to="/admin/users" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
            Voir tous
          </NuxtLink>
        </div>
        
        <div class="space-y-3">
          <NuxtLink to="/admin/users" class="block w-full btn-secondary text-center">
            Gérer les utilisateurs
          </NuxtLink>
          <NuxtLink to="/admin/teachers" class="block w-full btn-secondary text-center">
            Gérer les enseignants
          </NuxtLink>
          <NuxtLink to="/admin/students" class="block w-full btn-secondary text-center">
            Gérer les élèves
          </NuxtLink>
        </div>
      </div>

      <!-- Gestion du contenu -->
      <div class="card">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Gestion du contenu</h2>
        
        <div class="space-y-3">
          <NuxtLink to="/admin/course-types" class="block w-full btn-secondary text-center">
            Types de cours
          </NuxtLink>
          <NuxtLink to="/admin/locations" class="block w-full btn-secondary text-center">
            Lieux de cours
          </NuxtLink>
          <NuxtLink to="/admin/lessons" class="block w-full btn-secondary text-center">
            Cours et réservations
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- Cours récents -->
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Cours récents</h2>
        <NuxtLink to="/admin/lessons" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
          Voir tous
        </NuxtLink>
      </div>
      
      <div v-if="loading" class="text-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
      </div>

      <div v-else-if="recentLessons.length === 0" class="text-center py-8">
        <CalendarIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
        <p class="text-gray-500">Aucun cours récent</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Cours
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Date
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Statut
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Prix
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="lesson in recentLessons" :key="lesson.id">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ lesson.course_type }}</div>
                <div class="text-sm text-gray-500">{{ lesson.location }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDate(lesson.scheduled_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusClass(lesson.status)" class="inline-flex px-2 py-1 text-xs font-medium rounded-full">
                  {{ getStatusText(lesson.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatCurrency(lesson.price) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { 
  UsersIcon, 
  AcademicCapIcon, 
  CalendarIcon,
  CurrencyEuroIcon
} from '@heroicons/vue/24/outline'

definePageMeta({
  middleware: 'admin'
})

const { $api } = useNuxtApp()

// État des données
const dashboardData = ref({
  stats: {},
  recent_lessons: []
})
const recentLessons = ref([])
const loading = ref(true)

// Charger les données du dashboard admin
const loadDashboardData = async () => {
  try {
    const response = await $api.get('/admin/dashboard')
    if (response.data.success) {
      dashboardData.value = response.data.data
      recentLessons.value = response.data.data.recent_lessons || []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des données:', error)
  } finally {
    loading.value = false
  }
}

// Utilitaires de formatage
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusClass = (status) => {
  const classes = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'confirmed': 'bg-green-100 text-green-800',
    'cancelled': 'bg-red-100 text-red-800',
    'completed': 'bg-blue-100 text-blue-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status) => {
  const texts = {
    'pending': 'En attente',
    'confirmed': 'Confirmé',
    'cancelled': 'Annulé',
    'completed': 'Terminé'
  }
  return texts[status] || status
}

// Charger les données au montage
onMounted(() => {
  loadDashboardData()
})

// SEO
useHead({
  title: 'Administration - BookYourCoach'
})
</script>
