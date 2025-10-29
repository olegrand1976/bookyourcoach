<template>
  <div class="lessons-page">
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-6 md:mb-8">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
          Leçons Disponibles
        </h1>
        <p class="text-gray-600">
          Découvrez et réservez les cours qui vous intéressent
        </p>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-base md:text-lg font-semibold text-gray-900 mb-4">Filtres</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Discipline</label>
            <select 
              v-model="filters.discipline" 
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Toutes les disciplines</option>
              <option v-for="discipline in disciplines" :key="discipline.id" :value="discipline.id">
                {{ discipline.name }}
              </option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type de cours</label>
            <select 
              v-model="filters.courseType" 
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Tous les types</option>
              <option v-for="courseType in filteredCourseTypes" :key="courseType.id" :value="courseType.id">
                {{ courseType.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Format</label>
            <select 
              v-model="filters.format" 
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Tous les formats</option>
              <option value="individual">Individuel</option>
              <option value="group">Collectif</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
            <input 
              v-model="filters.date" 
              type="date" 
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
        
        <div class="mt-4 flex justify-end">
          <button 
            @click="applyFilters"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
          >
            Appliquer les filtres
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Erreur</h3>
            <div class="mt-2 text-sm text-red-700">{{ error }}</div>
          </div>
        </div>
      </div>

      <!-- Lessons Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-4 md:gap-6">
        <div 
          v-for="lesson in filteredLessons" 
          :key="lesson.id"
          class="bg-white rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition-shadow"
        >
          <div class="p-6">
            <!-- Lesson Header -->
            <div class="flex items-start justify-between mb-4">
              <div>
                <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-1">
                  {{ lesson.title || 'Leçon' }}
                </h3>
                <p class="text-xs md:text-sm text-gray-600">
                  {{ lesson.course_type?.name || 'Type non spécifié' }}
                </p>
              </div>
              <span 
                :class="[
                  'px-2 py-1 text-xs font-medium rounded-full',
                  getStatusClass(lesson.status)
                ]"
              >
                {{ getStatusText(lesson.status) }}
              </span>
            </div>

            <!-- Teacher Info -->
            <div class="flex items-center mb-4">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">
                  {{ lesson.teacher?.user?.name || 'Enseignant' }}
                </p>
                <p class="text-xs text-gray-500">Enseignant</p>
              </div>
            </div>

            <!-- Lesson Details -->
            <div class="space-y-2 mb-4">
              <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ formatDateTime(lesson.start_time) }}
              </div>
              
              <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ lesson.location?.name || 'Lieu non spécifié' }}
              </div>

              <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                </svg>
                {{ lesson.price ? `${lesson.price}€` : 'Prix non spécifié' }}
              </div>
            </div>

            <!-- Description -->
            <p v-if="lesson.description" class="text-xs md:text-sm text-gray-600 mb-4 line-clamp-2">
              {{ lesson.description }}
            </p>

            <!-- Actions -->
            <div class="flex space-x-2">
              <button 
                v-if="lesson.status === 'available'"
                @click="bookLesson(lesson.id)"
                class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-sm font-medium"
              >
                Réserver
              </button>
              <button 
                @click="viewLessonDetails(lesson.id)"
                class="flex-1 bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm font-medium"
              >
                Détails
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && !error && filteredLessons.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune leçon trouvée</h3>
        <p class="mt-1 text-sm text-gray-500">Essayez de modifier vos filtres de recherche.</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

// Meta
definePageMeta({
  middleware: ['auth', 'student'],
  layout: 'student'
})

// State
const lessons = ref<any[]>([])
const disciplines = ref<any[]>([])
const loading = ref(false)
const error = ref<string | null>(null)

const filters = ref({
  discipline: '',
  courseType: '',
  format: '',
  date: ''
})

// Computed
const filteredCourseTypes = computed(() => {
  if (!filters.value.discipline) return []
  const discipline = disciplines.value.find(d => d.id === parseInt(filters.value.discipline))
  return discipline?.course_types || []
})

const filteredLessons = computed(() => {
  let filtered = lessons.value

  if (filters.value.discipline) {
    filtered = filtered.filter(lesson => 
      lesson.course_type?.discipline_id === parseInt(filters.value.discipline)
    )
  }

  if (filters.value.courseType) {
    filtered = filtered.filter(lesson => 
      lesson.course_type?.id === parseInt(filters.value.courseType)
    )
  }

  if (filters.value.format) {
    filtered = filtered.filter(lesson => {
      if (filters.value.format === 'individual') {
        return lesson.course_type?.is_individual === true
      } else if (filters.value.format === 'group') {
        return lesson.course_type?.is_individual === false
      }
      return true
    })
  }

  if (filters.value.date) {
    const filterDate = new Date(filters.value.date)
    filtered = filtered.filter(lesson => {
      const lessonDate = new Date(lesson.start_time)
      return lessonDate.toDateString() === filterDate.toDateString()
    })
  }

  return filtered
})

// Methods
const loadLessons = async () => {
  try {
    loading.value = true
    error.value = null
    
    const { $api } = useNuxtApp()
    const response = await $api.get('/student/available-lessons')
    
    if (response.data.success) {
      lessons.value = response.data.data
    } else {
      throw new Error('Erreur lors du chargement des leçons')
    }
  } catch (err: any) {
    error.value = err.message || 'Erreur lors du chargement des leçons'
    console.error('Error loading lessons:', err)
  } finally {
    loading.value = false
  }
}

const loadDisciplines = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/student/disciplines')
    
    if (response.data.success) {
      disciplines.value = response.data.data
    }
  } catch (err) {
    console.error('Error loading disciplines:', err)
  }
}

const applyFilters = () => {
  // Les filtres sont appliqués automatiquement via computed
  console.log('Filters applied:', filters.value)
}

const bookLesson = async (lessonId: number) => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.post('/student/bookings', {
      lesson_id: lessonId
    })
    
    if (response.data.success) {
      // Recharger les leçons pour mettre à jour le statut
      await loadLessons()
      
      // Afficher un message de succès
      const { $toast } = useNuxtApp()
      $toast.success('Leçon réservée avec succès!')
    } else {
      throw new Error(response.data.message || 'Erreur lors de la réservation')
    }
  } catch (err: any) {
    const { $toast } = useNuxtApp()
    $toast.error(err.message || 'Erreur lors de la réservation')
    console.error('Error booking lesson:', err)
  }
}

const viewLessonDetails = (lessonId: number) => {
  // Navigation vers la page de détails (à implémenter)
  console.log('View lesson details:', lessonId)
}

const getStatusClass = (status: string) => {
  switch (status) {
    case 'available':
      return 'bg-green-100 text-green-800'
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'confirmed':
      return 'bg-blue-100 text-blue-800'
    case 'completed':
      return 'bg-gray-100 text-gray-800'
    case 'cancelled':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getStatusText = (status: string) => {
  switch (status) {
    case 'available':
      return 'Disponible'
    case 'pending':
      return 'En attente'
    case 'confirmed':
      return 'Confirmé'
    case 'completed':
      return 'Terminé'
    case 'cancelled':
      return 'Annulé'
    default:
      return status
  }
}

const formatDateTime = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(() => {
  loadLessons()
  loadDisciplines()
})
</script>

<style scoped>
.lessons-page {
  min-height: 100vh;
  background-color: #f9fafb;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
