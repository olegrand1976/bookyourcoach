<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">
        Bienvenue, {{ authStore.userName }}
      </h1>
      <p class="mt-2 text-gray-600">
        Voici votre tableau de bord personnel
      </p>
    </div>

    <!-- Stats cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="card">
        <div class="flex items-center">
          <div class="p-2 bg-primary-100 rounded-lg">
            <CalendarIcon class="w-6 h-6 text-primary-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Prochains cours</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.upcoming_lessons }}</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center">
          <div class="p-2 bg-green-100 rounded-lg">
            <CheckCircleIcon class="w-6 h-6 text-green-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Cours terminés</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.completed_lessons }}</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center">
          <div class="p-2 bg-orange-100 rounded-lg">
            <ClockIcon class="w-6 h-6 text-orange-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Heures totales</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total_hours }}h</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions rapides -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Prochains cours -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Prochains cours</h2>
          <NuxtLink to="/lessons" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
            Voir tous
          </NuxtLink>
        </div>
        
        <div v-if="upcomingLessons.length === 0" class="text-center py-8">
          <CalendarIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
          <p class="text-gray-500">Aucun cours planifié</p>
          <NuxtLink to="/lessons/book" class="btn-primary mt-4 inline-block">
            Réserver un cours
          </NuxtLink>
        </div>

        <div v-else class="space-y-4">
          <div 
            v-for="lesson in upcomingLessons" 
            :key="lesson.id"
            class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
          >
            <div>
              <h3 class="font-medium text-gray-900">{{ lesson.course_type }}</h3>
              <p class="text-sm text-gray-600">{{ formatDate(lesson.scheduled_at) }}</p>
              <p class="text-sm text-gray-600">avec {{ lesson.teacher_name }}</p>
            </div>
            <div class="text-right">
              <p class="font-medium text-gray-900">{{ lesson.price }}€</p>
              <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                Confirmé
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions rapides -->
      <div class="card">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Actions rapides</h2>
        
        <div class="space-y-3">
          <NuxtLink to="/lessons/book" class="block w-full btn-primary text-center">
            Réserver un nouveau cours
          </NuxtLink>
          
          <NuxtLink to="/teachers" class="block w-full btn-secondary text-center">
            Parcourir les enseignants
          </NuxtLink>
          
          <NuxtLink to="/profile" class="block w-full btn-secondary text-center">
            Modifier mon profil
          </NuxtLink>
          
          <NuxtLink to="/lessons" class="block w-full btn-secondary text-center">
            Historique des cours
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { 
  CalendarIcon, 
  CheckCircleIcon, 
  ClockIcon 
} from '@heroicons/vue/24/outline'

definePageMeta({
  middleware: 'auth'
})

const authStore = useAuthStore()
const { $api } = useNuxtApp()

// État des données
const stats = ref({
  upcoming_lessons: 0,
  completed_lessons: 0,
  total_hours: 0
})

const upcomingLessons = ref([])
const loading = ref(true)

// Charger les données du dashboard
const loadDashboardData = async () => {
  try {
    // Charger les statistiques
    const statsResponse = await $api.get('/students/dashboard-stats')
    stats.value = statsResponse.data

    // Charger les prochains cours
    const lessonsResponse = await $api.get('/lessons?status=confirmed&limit=5')
    upcomingLessons.value = lessonsResponse.data.data
  } catch (error) {
    console.error('Erreur lors du chargement des données:', error)
  } finally {
    loading.value = false
  }
}

// Formatage des dates
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Charger les données au montage
onMounted(() => {
  loadDashboardData()
})
</script>
