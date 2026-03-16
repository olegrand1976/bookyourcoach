<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-6 md:mb-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Mes Revenus</h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">Consultez vos revenus et statistiques financières</p>
          </div>
          <NuxtLink to="/teacher/dashboard" 
            class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors w-full sm:w-auto">
            <span aria-hidden="true">←</span>
            <span class="ml-2">Retour au dashboard</span>
          </NuxtLink>
        </div>
      </div>

      <!-- Filtres de période -->
      <div class="mb-6 md:mb-8">
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex flex-col sm:flex-row gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Période</label>
                <select v-model="selectedPeriod" @change="loadEarningsData" 
                  class="min-h-[44px] w-full sm:w-auto px-3 py-2.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <option value="current_month">Ce mois</option>
                  <option value="last_month">Mois dernier</option>
                  <option value="current_quarter">Ce trimestre</option>
                  <option value="current_year">Cette année</option>
                  <option value="custom">Période personnalisée</option>
                </select>
              </div>
              <div v-if="selectedPeriod === 'custom'">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                <input v-model="customStartDate" type="date" 
                  class="min-h-[44px] w-full sm:w-auto px-3 py-2.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>
              <div v-if="selectedPeriod === 'custom'">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                <input v-model="customEndDate" type="date" 
                  class="min-h-[44px] w-full sm:w-auto px-3 py-2.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>
            </div>
            
            <div class="flex gap-4 md:gap-4 md:gap-6 text-sm">
              <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ formatCurrency(totalEarnings) }}</div>
                <div class="text-gray-600">Total revenus</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ totalLessons }}</div>
                <div class="text-gray-600">Cours donnés</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Contenu principal -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Chargement des données de revenus...</p>
      </div>

      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <div class="text-red-500 text-6xl mb-4">⚠️</div>
        <h3 class="text-base md:text-lg font-semibold text-red-900 mb-2">Erreur de chargement</h3>
        <p class="text-red-700 mb-4">{{ error }}</p>
        <button @click="loadEarningsData" 
          class="min-h-[44px] inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium">
          Réessayer
        </button>
      </div>

      <div v-else class="space-y-8">
        <!-- Vue d'ensemble -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
          <div class="bg-white rounded-lg shadow p-4 sm:p-6">
            <div class="flex items-center">
              <div class="p-2 bg-green-100 rounded-lg">
                <span class="text-2xl text-green-600">💰</span>
              </div>
              <div class="ml-3 md:ml-4">
                <p class="text-sm font-medium text-gray-600">Revenus totaux</p>
                <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(totalEarnings) }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-4 sm:p-6">
            <div class="flex items-center">
              <div class="p-2 bg-blue-100 rounded-lg">
                <span class="text-2xl text-blue-600">👤</span>
              </div>
              <div class="ml-3 md:ml-4">
                <p class="text-sm font-medium text-gray-600">Revenus personnels</p>
                <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(personalEarnings) }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-4 sm:p-6">
            <div class="flex items-center">
              <div class="p-2 bg-purple-100 rounded-lg">
                <span class="text-2xl text-purple-600">🏢</span>
              </div>
              <div class="ml-3 md:ml-4">
                <p class="text-sm font-medium text-gray-600">Revenus clubs</p>
                <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(clubEarnings) }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-4 sm:p-6">
            <div class="flex items-center">
              <div class="p-2 bg-yellow-100 rounded-lg">
                <span class="text-2xl text-yellow-600">📊</span>
              </div>
              <div class="ml-3 md:ml-4">
                <p class="text-sm font-medium text-gray-600">Moyenne par cours</p>
                <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(averagePerLesson) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Onglets de navigation -->
        <div class="bg-white rounded-lg shadow">
          <div class="border-b border-gray-200 overflow-x-auto">
            <nav class="-mb-px flex space-x-4 sm:space-x-8 px-4 sm:px-6 min-w-0">
              <button v-for="tab in tabs" :key="tab.id" 
                @click="activeTab = tab.id"
                :class="[
                  activeTab === tab.id 
                    ? 'border-blue-500 text-blue-600' 
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                  'whitespace-nowrap min-h-[44px] py-4 px-2 sm:px-1 border-b-2 font-medium text-sm flex items-center'
                ]">
                {{ tab.name }}
              </button>
            </nav>
          </div>

          <div class="p-4 sm:p-6">
            <!-- Onglet: Par club -->
            <div v-if="activeTab === 'clubs'">
              <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-4">Revenus par club</h3>
              <div class="space-y-4">
                <div v-for="club in earningsByClub" :key="club.id" 
                  class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                      <span class="text-lg">🏢</span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">{{ club.name }}</p>
                      <p class="text-xs md:text-sm text-gray-600">{{ club.lessons_count }} cours</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-base md:text-lg font-semibold text-gray-900">{{ formatCurrency(club.total_earnings) }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ formatCurrency(club.average_per_lesson) }}/cours</p>
                  </div>
                </div>
                
                <!-- Revenus personnels -->
                <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                      <span class="text-lg">👤</span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">Cours personnels</p>
                      <p class="text-xs md:text-sm text-gray-600">{{ personalLessonsCount }} cours</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-base md:text-lg font-semibold text-gray-900">{{ formatCurrency(personalEarnings) }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ formatCurrency(personalAveragePerLesson) }}/cours</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Onglet: Par élève -->
            <div v-if="activeTab === 'students'">
              <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-4">Revenus par élève</h3>
              <div class="space-y-4">
                <div v-for="student in earningsByStudent" :key="student.id" 
                  class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                      <span class="text-lg">👤</span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">{{ student.name }}</p>
                      <p class="text-xs md:text-sm text-gray-600">{{ student.lessons_count }} cours</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-base md:text-lg font-semibold text-gray-900">{{ formatCurrency(student.total_earnings) }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ formatCurrency(student.average_per_lesson) }}/cours</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Onglet: Par type de cours -->
            <div v-if="activeTab === 'course_types'">
              <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-4">Revenus par type de cours</h3>
              <div class="space-y-4">
                <div v-for="courseType in earningsByCourseType" :key="courseType.id" 
                  class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                      <span class="text-lg">📚</span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">{{ courseType.name }}</p>
                      <p class="text-xs md:text-sm text-gray-600">{{ courseType.lessons_count }} cours</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-base md:text-lg font-semibold text-gray-900">{{ formatCurrency(courseType.total_earnings) }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ formatCurrency(courseType.average_per_lesson) }}/cours</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Onglet: Détail des cours -->
            <div v-if="activeTab === 'lessons'">
              <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-4">Détail des cours</h3>
              <div class="overflow-x-auto -mx-4 sm:mx-0">
                <table class="min-w-[600px] divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Élève</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Club</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durée</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenu</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="lesson in detailedLessons" :key="lesson.id">
                      <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ formatDate(lesson.start_time) }}
                      </td>
                      <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ lesson.student_name }}
                      </td>
                      <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ lesson.course_type_name }}
                      </td>
                      <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ lesson.club_name || 'Personnel' }}
                      </td>
                      <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ lesson.duration }}min
                      </td>
                      <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ formatCurrency(lesson.earnings) }}
                      </td>
                      <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="getLessonStatusClass(lesson.status)">
                          {{ getLessonStatusLabel(lesson.status) }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
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

const authStore = useAuthStore()
const { $api } = useNuxtApp()

// État réactif
const loading = ref(true)
const error = ref(null)
const activeTab = ref('clubs')

// Filtres de période
const selectedPeriod = ref('current_month')
const customStartDate = ref('')
const customEndDate = ref('')

// Données de revenus
const totalEarnings = ref(0)
const totalLessons = ref(0)
const personalEarnings = ref(0)
const clubEarnings = ref(0)
const averagePerLesson = ref(0)
const personalLessonsCount = ref(0)
const personalAveragePerLesson = ref(0)

// Données détaillées
const earningsByClub = ref([])
const earningsByStudent = ref([])
const earningsByCourseType = ref([])
const detailedLessons = ref([])

// Onglets
const tabs = [
  { id: 'clubs', name: 'Par club' },
  { id: 'students', name: 'Par élève' },
  { id: 'course_types', name: 'Par type de cours' },
  { id: 'lessons', name: 'Détail des cours' }
]

// Vérifier que l'utilisateur peut agir comme enseignant
if (!authStore.canActAsTeacher) {
  throw createError({
    statusCode: 403,
    statusMessage: 'Accès refusé - Droits enseignant requis'
  })
}

// Méthodes
const loadEarningsData = async () => {
  try {
    loading.value = true
    error.value = null

    const params = {
      period: selectedPeriod.value
    }

    if (selectedPeriod.value === 'custom') {
      if (customStartDate.value) params.start_date = customStartDate.value
      if (customEndDate.value) params.end_date = customEndDate.value
    }

    // Charger les données de revenus
    const response = await $api.get('/teacher/earnings', { params })
    
    if (response.data) {
      const data = response.data
      
      // Données générales
      totalEarnings.value = data.total_earnings || 0
      totalLessons.value = data.total_lessons || 0
      personalEarnings.value = data.personal_earnings || 0
      clubEarnings.value = data.club_earnings || 0
      averagePerLesson.value = data.average_per_lesson || 0
      personalLessonsCount.value = data.personal_lessons_count || 0
      personalAveragePerLesson.value = data.personal_average_per_lesson || 0
      
      // Données détaillées
      earningsByClub.value = data.earnings_by_club || []
      earningsByStudent.value = data.earnings_by_student || []
      earningsByCourseType.value = data.earnings_by_course_type || []
      detailedLessons.value = data.detailed_lessons || []
    }

  } catch (err) {
    console.error('Erreur lors du chargement des revenus:', err)
    error.value = 'Impossible de charger les données de revenus'
  } finally {
    loading.value = false
  }
}

const formatCurrency = (amount) => {
  if (!amount) return '0,00 €'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}

const formatDate = (dateString) => {
  if (!dateString) return 'Date inconnue'
  
  try {
    const date = new Date(dateString)
    if (isNaN(date.getTime())) return 'Date invalide'
    
    return date.toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (error) {
    return 'Date invalide'
  }
}

const getLessonStatusClass = (status) => {
  const classes = {
    'scheduled': 'bg-blue-100 text-blue-800',
    'completed': 'bg-green-100 text-green-800',
    'cancelled': 'bg-red-100 text-red-800',
    'in_progress': 'bg-yellow-100 text-yellow-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getLessonStatusLabel = (status) => {
  const labels = {
    'scheduled': 'Programmé',
    'completed': 'Terminé',
    'cancelled': 'Annulé',
    'in_progress': 'En cours'
  }
  return labels[status] || status
}

// Charger les données au montage
onMounted(() => {
  loadEarningsData()
})

// Watcher pour recharger les données quand la période change
watch([selectedPeriod, customStartDate, customEndDate], () => {
  if (selectedPeriod.value === 'custom' && customStartDate.value && customEndDate.value) {
    loadEarningsData()
  } else if (selectedPeriod.value !== 'custom') {
    loadEarningsData()
  }
})
</script>