<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-6 md:mb-8">
        <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
              Leçons Disponibles
            </h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">
              Découvrez et réservez les cours qui vous intéressent
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

      <!-- Filters -->
      <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-base md:text-lg font-semibold text-gray-900 mb-4">Filtres</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Discipline</label>
            <select 
              v-model="filters.discipline" 
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-gray-600">Chargement des cours...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3 flex-1">
            <h3 class="text-sm font-medium text-red-800">Erreur</h3>
            <div class="mt-2 text-sm text-red-700">{{ error }}</div>
            <button 
              @click="loadLessons"
              class="mt-4 px-4 py-2 bg-red-100 text-red-800 rounded-lg hover:bg-red-200 transition-colors text-sm font-medium"
            >
              Réessayer
            </button>
          </div>
        </div>
      </div>

      <!-- Lessons Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="lesson in filteredLessons" 
          :key="lesson.id"
          class="bg-white rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-all"
        >
          <div class="p-6">
            <!-- Lesson Header -->
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1 min-w-0">
                <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-1 truncate">
                  {{ lesson.course_type?.name || lesson.title || 'Leçon' }}
                </h3>
                <p class="text-xs md:text-sm text-gray-600 truncate">
                  {{ lesson.teacher?.user?.name || 'Enseignant' }}
                </p>
              </div>
              <span 
                :class="[
                  'px-2 py-1 text-xs font-medium rounded-full ml-2 flex-shrink-0',
                  getStatusClass(lesson.status)
                ]"
              >
                {{ getStatusText(lesson.status) }}
              </span>
            </div>

            <!-- Lesson Details -->
            <div class="space-y-3 mb-4">
              <div class="flex items-center text-sm text-gray-600">
                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                  <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <span class="font-medium">{{ formatDateTime(lesson.start_time) }}</span>
              </div>
              
              <div class="flex items-center text-sm text-gray-600">
                <div class="bg-emerald-100 p-2 rounded-lg mr-3">
                  <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </div>
                <span class="font-medium">{{ lesson.location?.name || 'Lieu non spécifié' }}</span>
              </div>

              <div class="flex items-center text-sm text-gray-600">
                <div class="bg-purple-100 p-2 rounded-lg mr-3">
                  <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                  </svg>
                </div>
                <span class="font-medium">{{ formatPrice(lesson.price) }}</span>
              </div>
            </div>

            <!-- Description -->
            <p v-if="lesson.description" class="text-xs md:text-sm text-gray-600 mb-4 line-clamp-2">
              {{ lesson.description }}
            </p>

            <!-- Actions -->
            <div class="flex gap-2">
              <button 
                v-if="lesson.status === 'available'"
                @click="handleBookLesson(lesson.id)"
                class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2 rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all shadow-sm hover:shadow-md text-sm font-medium"
              >
                Réserver
              </button>
              <button 
                @click="viewLessonDetails(lesson.id)"
                class="flex-1 bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium"
              >
                Détails
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && !error && filteredLessons.length === 0" class="text-center py-12 bg-white rounded-xl shadow-lg">
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        <h3 class="mt-2 text-base md:text-lg font-medium text-gray-900">Aucune leçon trouvée</h3>
        <p class="mt-1 text-sm md:text-base text-gray-500">Essayez de modifier vos filtres de recherche.</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useStudentData } from '~/composables/useStudentData'
import { useStudentFormatters } from '~/composables/useStudentFormatters'

definePageMeta({
  middleware: ['auth', 'student'],
  layout: 'default'
})

// Composables
const { loading, loadAvailableLessons, bookLesson } = useStudentData()
const { formatDateTime, formatPrice, getStatusClass, getStatusText } = useStudentFormatters()

// State
const lessons = ref<any[]>([])
const disciplines = ref<any[]>([])
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
    error.value = null
    lessons.value = await loadAvailableLessons()
  } catch (err: any) {
    error.value = err.message || 'Erreur lors du chargement des leçons'
    console.error('Error loading lessons:', err)
  }
}

const loadDisciplines = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/disciplines')
    
    if (response.data.success) {
      disciplines.value = response.data.data || []
    }
  } catch (err) {
    console.error('Error loading disciplines:', err)
  }
}

const handleBookLesson = async (lessonId: number) => {
  try {
    await bookLesson(lessonId)
    await loadLessons() // Recharger pour mettre à jour les statuts
  } catch (err) {
    console.error('Error booking lesson:', err)
  }
}

const viewLessonDetails = (lessonId: number) => {
  // TODO: Navigation vers la page de détails ou modal
  console.log('View lesson details:', lessonId)
}

// Lifecycle
onMounted(() => {
  loadLessons()
  loadDisciplines()
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
