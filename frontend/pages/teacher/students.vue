<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-6 md:mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Mes Élèves</h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">Gérez et suivez la progression de vos élèves</p>
          </div>
          <NuxtLink to="/teacher/dashboard" 
            class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
            <span>←</span>
            <span class="ml-2">Retour au dashboard</span>
          </NuxtLink>
        </div>
      </div>

      <!-- Filtres et statistiques -->
      <div class="mb-6 md:mb-8">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Filtres -->
            <div class="flex flex-col sm:flex-row gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Filtrer par club</label>
                <select v-model="selectedClub" @change="filterStudents" 
                  class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <option value="">Tous les élèves</option>
                  <option value="personal">Élèves personnels</option>
                  <option v-for="club in clubs" :key="club.id" :value="club.id">
                    {{ club.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                <input v-model="searchQuery" @input="filterStudents" type="text" 
                  placeholder="Nom de l'élève..." 
                  class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>
            </div>
            
            <!-- Statistiques -->
            <div class="flex gap-4 md:gap-4 md:gap-6 text-sm">
              <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ filteredStudents.length }}</div>
                <div class="text-gray-600">Élèves affichés</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ totalStudents }}</div>
                <div class="text-gray-600">Total élèves</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Liste des élèves -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Chargement des élèves...</p>
      </div>

      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <div class="text-red-500 text-6xl mb-4">⚠️</div>
        <h3 class="text-base md:text-lg font-semibold text-red-900 mb-2">Erreur de chargement</h3>
        <p class="text-red-700 mb-4">{{ error }}</p>
        <button @click="loadStudents" 
          class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
          Réessayer
        </button>
      </div>

      <div v-else-if="filteredStudents.length === 0" class="bg-white rounded-lg shadow p-8 text-center">
        <div class="text-6xl mb-4">👨‍🎓</div>
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Aucun élève trouvé</h2>
        <p class="text-gray-600 mb-6">
          {{ searchQuery || selectedClub ? 'Aucun élève ne correspond à vos critères de recherche.' : 'Vous n\'avez pas encore d\'élèves assignés.' }}
        </p>
        <NuxtLink to="/teacher/schedule" 
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
          <span>📅</span>
          <span class="ml-2">Créer un cours</span>
        </NuxtLink>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-4 md:gap-6">
        <div v-for="student in filteredStudents" :key="student.id" 
          class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
          <!-- En-tête de la carte -->
          <div class="p-4 md:p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                  <span class="text-xl">👤</span>
                </div>
                <div>
                  <h3 class="text-base md:text-lg font-semibold text-gray-900">{{ student.name }}</h3>
                  <p class="text-xs md:text-sm text-gray-600">{{ student.email }}</p>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <span v-if="student.club_name" 
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  🏢 {{ student.club_name }}
                </span>
                <span v-else 
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  👤 Personnel
                </span>
              </div>
            </div>
          </div>

          <!-- Informations de l'élève -->
          <div class="p-6">
            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-xs md:text-sm text-gray-600">Niveau :</span>
                <span class="text-sm font-medium text-gray-900">{{ getLevelLabel(student.level) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-xs md:text-sm text-gray-600">Cours suivis :</span>
                <span class="text-sm font-medium text-gray-900">{{ student.lessons_count || 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-xs md:text-sm text-gray-600">Dernier cours :</span>
                <span class="text-sm font-medium text-gray-900">{{ formatDate(student.last_lesson) }}</span>
              </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex space-x-2">
              <button @click="viewStudentDetails(student)" 
                class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <span>👁️</span>
                <span class="ml-1">Détails</span>
              </button>
              <button @click="viewStudentLessons(student)" 
                class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-blue-300 rounded-md text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors">
                <span>📅</span>
                <span class="ml-1">Cours</span>
              </button>
              <button @click="createLesson(student)" 
                class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition-colors">
                <span>➕</span>
                <span class="ml-1">Nouveau</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal de détails de l'élève -->
      <div v-if="selectedStudent" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-gray-900">Détails de l'élève</h3>
            <button @click="selectedStudent = null" 
              class="text-gray-400 hover:text-gray-600">
              <span class="text-2xl">×</span>
            </button>
          </div>

          <div class="space-y-6">
            <!-- Informations personnelles -->
            <div>
              <h4 class="text-lg font-medium text-gray-900 mb-3">Informations personnelles</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Nom complet</label>
                  <p class="text-gray-900">{{ selectedStudent.name }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Email</label>
                  <p class="text-gray-900">{{ selectedStudent.email }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Niveau</label>
                  <p class="text-gray-900">{{ getLevelLabel(selectedStudent.level) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Club</label>
                  <p class="text-gray-900">{{ selectedStudent.club_name || 'Élève personnel' }}</p>
                </div>
              </div>
            </div>

            <!-- Statistiques -->
            <div>
              <h4 class="text-lg font-medium text-gray-900 mb-3">Statistiques</h4>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                  <div class="text-2xl font-bold text-blue-600">{{ selectedStudent.lessons_count || 0 }}</div>
                  <div class="text-sm text-blue-700">Cours suivis</div>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg">
                  <div class="text-2xl font-bold text-green-600">{{ selectedStudent.completed_lessons || 0 }}</div>
                  <div class="text-sm text-green-700">Cours terminés</div>
                </div>
                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                  <div class="text-2xl font-bold text-yellow-600">{{ selectedStudent.upcoming_lessons || 0 }}</div>
                  <div class="text-sm text-yellow-700">Cours à venir</div>
                </div>
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                  <div class="text-2xl font-bold text-purple-600">{{ selectedStudent.total_hours || 0 }}h</div>
                  <div class="text-sm text-purple-700">Heures totales</div>
                </div>
              </div>
            </div>

            <!-- Derniers cours -->
            <div v-if="selectedStudent.recent_lessons && selectedStudent.recent_lessons.length > 0">
              <h4 class="text-lg font-medium text-gray-900 mb-3">Derniers cours</h4>
              <div class="space-y-2">
                <div v-for="lesson in selectedStudent.recent_lessons" :key="lesson.id" 
                  class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <div>
                    <p class="font-medium text-gray-900">{{ lesson.title || 'Cours' }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ formatDate(lesson.start_time) }}</p>
                  </div>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="getLessonStatusClass(lesson.status)">
                    {{ getLessonStatusLabel(lesson.status) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end space-x-3 mt-6 pt-6 border-t border-gray-200">
            <button @click="selectedStudent = null" 
              class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors">
              Fermer
            </button>
            <button @click="createLesson(selectedStudent)" 
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
              Créer un cours
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

const authStore = useAuthStore()
const { $api } = useNuxtApp()

// État réactif
const loading = ref(true)
const error = ref(null)
const students = ref([])
const clubs = ref([])
const selectedStudent = ref(null)

// Filtres
const selectedClub = ref('')
const searchQuery = ref('')

// Vérifier que l'utilisateur peut agir comme enseignant
if (!authStore.canActAsTeacher) {
  throw createError({
    statusCode: 403,
    statusMessage: 'Accès refusé - Droits enseignant requis'
  })
}

// Computed
const totalStudents = computed(() => students.value.length)

const filteredStudents = computed(() => {
  let filtered = students.value

  // Filtrer par club
  if (selectedClub.value) {
    if (selectedClub.value === 'personal') {
      filtered = filtered.filter(student => !student.club_name)
    } else {
      filtered = filtered.filter(student => student.club_id == selectedClub.value)
    }
  }

  // Filtrer par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(student => 
      student.name.toLowerCase().includes(query) ||
      student.email.toLowerCase().includes(query)
    )
  }

  return filtered
})

// Méthodes
const loadStudents = async () => {
  try {
    loading.value = true
    error.value = null

    // Charger les élèves
    const studentsResponse = await $api.get('/teacher/students')
    students.value = studentsResponse.data.students || []

    // Charger les clubs de l'enseignant
    const clubsResponse = await $api.get('/teacher/clubs')
    clubs.value = clubsResponse.data.clubs || []

  } catch (err) {
    console.error('Erreur lors du chargement des élèves:', err)
    error.value = 'Impossible de charger la liste des élèves'
  } finally {
    loading.value = false
  }
}

const filterStudents = () => {
  // Le filtrage est géré par le computed filteredStudents
}

const getLevelLabel = (level) => {
  const levels = {
    'debutant': 'Débutant',
    'intermediaire': 'Intermédiaire',
    'avance': 'Avancé',
    'expert': 'Expert'
  }
  return levels[level] || level || 'Non défini'
}

const formatDate = (dateString) => {
  if (!dateString) return 'Jamais'
  
  try {
    const date = new Date(dateString)
    if (isNaN(date.getTime())) return 'Date invalide'
    
    return date.toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
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

const viewStudentDetails = async (student) => {
  try {
    // Charger les détails complets de l'élève
    const response = await $api.get(`/teacher/students/${student.id}`)
    selectedStudent.value = response.data.student
  } catch (err) {
    console.error('Erreur lors du chargement des détails:', err)
    // Afficher les informations de base si l'API échoue
    selectedStudent.value = student
  }
}

const viewStudentLessons = (student) => {
  // Rediriger vers le planning avec filtre sur l'élève
  navigateTo(`/teacher/schedule?student=${student.id}`)
}

const createLesson = (student) => {
  // Rediriger vers le planning pour créer un cours avec cet élève
  navigateTo(`/teacher/schedule?student=${student.id}&action=create`)
}

// Charger les données au montage
onMounted(() => {
  loadStudents()
})
</script>