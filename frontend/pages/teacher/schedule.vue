<template>
  <div class="min-h-screen bg-gray-50 p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6 sm:mb-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Mon Planning</h1>
            <p class="mt-1 sm:mt-2 text-sm sm:text-base text-gray-600">Gestion de vos cours et créneaux horaires</p>
          </div>
          <NuxtLink to="/teacher/dashboard" 
            class="inline-flex items-center justify-center min-h-[44px] px-4 sm:px-6 py-2.5 sm:py-3 bg-gradient-to-r from-gray-600 to-gray-700 text-white rounded-lg hover:from-gray-700 hover:to-gray-800 transition-all duration-200 font-medium w-full sm:w-auto"
            aria-label="Retour au dashboard">
            <span aria-hidden="true">←</span>
            <span class="ml-2 hidden sm:inline">Retour au dashboard</span>
          </NuxtLink>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="text-center">
          <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-gray-600">Chargement des données...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <p class="text-red-800">{{ error }}</p>
      </div>

      <!-- Content -->
      <div v-else class="space-y-6">
        <!-- Sélecteur de calendrier - Boutons -->
        <div class="bg-white rounded-lg shadow p-4">
          <label class="block text-sm font-medium text-gray-700 mb-3">Calendrier</label>
          <div class="flex flex-wrap gap-2">
            <!-- Boutons pour les clubs (en premier) -->
            <button
              v-for="club in teacherClubs"
              :key="club.id"
              @click="selectCalendar(String(club.id))"
              :class="[
                'min-h-[44px] px-3 sm:px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200',
                selectedCalendar === String(club.id)
                  ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-md'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              {{ club.name }}
            </button>
            <!-- Bouton calendrier personnel (en dernier) -->
            <button
              @click="selectCalendar('personal')"
              :class="[
                'min-h-[44px] px-3 sm:px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200',
                selectedCalendar === 'personal'
                  ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-md'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              <span class="sm:hidden">Personnel</span>
              <span class="hidden sm:inline">Mon Calendrier Personnel</span>
            </button>
          </div>
          <p v-if="selectedCalendar !== 'personal'" class="mt-3 text-sm text-gray-500">
            ℹ️ Vous pouvez uniquement consulter les cours du club. L'ajout de cours est réservé au calendrier personnel.
          </p>
        </div>

        <!-- Calendrier avec cours -->
        <div class="bg-white shadow rounded-lg p-4 sm:p-6 mb-6">
          <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">Calendrier mensuel</h3>
          <div class="calendar-month-view">
            <!-- En-tête des jours de la semaine -->
            <div class="grid grid-cols-7 gap-0.5 sm:gap-1 mb-1 sm:mb-2">
              <div v-for="day in weekDays" :key="day" 
                class="p-1 sm:p-2 text-center text-xs font-medium text-gray-500 bg-gray-50">
                {{ day }}
              </div>
            </div>
            <!-- Grille du calendrier -->
            <div class="grid grid-cols-7 gap-0.5 sm:gap-1">
              <div v-for="day in calendarDays" :key="day.date" 
                :class="[
                  'min-h-[80px] sm:min-h-[100px] p-1 sm:p-2 border border-gray-200 rounded',
                  day.isCurrentMonth ? 'bg-white' : 'bg-gray-50',
                  day.isToday ? 'bg-blue-50 border-blue-300' : ''
                ]">
                <div class="flex items-center justify-between mb-0.5 sm:mb-1">
                  <span :class="[
                    'text-xs sm:text-sm font-medium',
                    day.isCurrentMonth ? 'text-gray-900' : 'text-gray-400',
                    day.isToday ? 'text-blue-600 font-bold' : ''
                  ]">
                    {{ day.day }}
                  </span>
                </div>
                <!-- Cours du jour -->
                <div class="space-y-0.5 sm:space-y-1">
                  <div 
                    v-for="lesson in getLessonsForDay(day.date)" 
                    :key="lesson.id"
                    @click="openLessonModal(lesson)"
                    class="min-h-[36px] flex flex-col justify-center p-1 rounded text-xs cursor-pointer transition-colors hover:shadow-sm"
                    :class="getLessonBorderClass(lesson)"
                  >
                    <div class="font-medium truncate">{{ formatLessonTime(lesson.start_time) }}</div>
                    <div class="truncate text-gray-600">{{ lesson.course_type?.name || 'Cours' }}</div>
                    <div v-if="lesson.student?.user?.name" class="truncate text-gray-500">
                      {{ lesson.student.user.name }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Bloc : Cours programmés -->
        <div class="bg-white shadow rounded-lg p-4 sm:p-6">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="min-w-0">
              <h2 class="text-lg sm:text-xl font-semibold text-gray-900">
                Cours programmés
                <span v-if="selectedCalendar !== 'personal'" class="text-sm sm:text-base font-normal text-gray-600">
                  • {{ getClubName(selectedCalendar) }}
                </span>
                <span v-else class="text-sm sm:text-base font-normal text-gray-600">
                  • Cours directs uniquement
                </span>
              </h2>
              <p class="text-xs sm:text-sm text-gray-500 mt-1">
                {{ lessons.length }} cours programmé{{ lessons.length > 1 ? 's' : '' }}
                <span v-if="selectedCalendar === 'personal'" class="text-xs text-gray-400">
                  (cours directs avec vos élèves, sans club)
                </span>
              </p>
            </div>
            <!-- Bouton d'ajout de cours - uniquement pour calendrier personnel -->
            <button 
              v-if="selectedCalendar === 'personal'"
              @click="openCreateLessonModal"
              class="min-h-[44px] w-full sm:w-auto px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 flex items-center justify-center gap-2 shadow-lg font-medium">
              <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Créer un cours
            </button>
          </div>

          <!-- Liste des cours -->
          <div v-if="lessons.length > 0" class="space-y-3">
            <div 
              v-for="lesson in lessons" 
              :key="lesson.id"
              @click="openLessonModal(lesson)"
              class="min-h-[44px] border-2 rounded-lg p-4 cursor-pointer transition-all hover:shadow-md active:bg-gray-50"
              :class="getLessonBorderClass(lesson)">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <!-- Type et horaire -->
                  <div class="flex items-center gap-3 mb-2">
                    <h3 class="font-semibold text-gray-900">
                      {{ lesson.course_type?.name || 'Cours' }}
                    </h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="getStatusBadgeClass(lesson.status)">
                      {{ getStatusLabel(lesson.status) }}
                    </span>
                  </div>
                  
                  <!-- Date et heure -->
                  <div class="text-sm text-gray-600 mb-2">
                    📅 {{ formatLessonDate(lesson.start_time) }} • 
                    🕐 {{ formatLessonTime(lesson.start_time) }} - {{ formatLessonTime(lesson.end_time) }}
                  </div>
                  
                  <!-- Participants -->
                  <div class="flex items-center gap-4 text-sm text-gray-600">
                    <span v-if="lesson.student?.user?.name">👤 {{ lesson.student.user.name }}</span>
                    <span v-if="lesson.club?.name">🏢 {{ lesson.club.name }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- État vide -->
          <div v-else class="text-center py-12 text-gray-500">
            <div class="text-4xl mb-4">📚</div>
            <p class="text-lg mb-2">Aucun cours programmé</p>
            <p v-if="selectedCalendar === 'personal'" class="text-sm">
              Cliquez sur "Créer un cours" pour ajouter votre premier cours
            </p>
            <p v-else class="text-sm">
              Aucun cours trouvé pour ce club
            </p>
          </div>
        </div>
      </div>
      
      <!-- Modale Détails du Cours -->
      <div v-if="showLessonModal && selectedLesson" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <div class="p-4 sm:p-6">
            <div class="flex items-center justify-between gap-2 mb-4 sm:mb-6">
              <h3 class="text-xl sm:text-2xl font-bold text-gray-900 min-w-0">
                Détails du cours
              </h3>
              <button @click="closeLessonModal" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-gray-400 hover:text-gray-600 shrink-0" aria-label="Fermer">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Informations du cours -->
            <div class="space-y-4">
              <!-- Type de cours -->
              <div class="bg-gray-50 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-500 mb-1">Type de cours</label>
                <p class="text-base sm:text-lg font-semibold text-gray-900">
                  {{ selectedLesson.course_type?.name || 'Non défini' }}
                </p>
              </div>

              <!-- Horaires -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                  <label class="block text-sm font-medium text-gray-500 mb-1">Début</label>
                  <p class="text-base font-semibold text-gray-900">
                    {{ new Date(selectedLesson.start_time).toLocaleString('fr-FR', { 
                      dateStyle: 'short', 
                      timeStyle: 'short' 
                    }) }}
                  </p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                  <label class="block text-sm font-medium text-gray-500 mb-1">Fin</label>
                  <p class="text-base font-semibold text-gray-900">
                    {{ new Date(selectedLesson.end_time).toLocaleString('fr-FR', { 
                      dateStyle: 'short', 
                      timeStyle: 'short' 
                    }) }}
                  </p>
                </div>
              </div>

              <!-- Élève -->
              <div class="bg-gray-50 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-500 mb-1">Élève</label>
                <p v-if="getLessonStudentNames(selectedLesson) && getLessonStudentNames(selectedLesson) !== 'Sans élève'" class="text-base font-semibold text-gray-900">
                  {{ getLessonStudentNames(selectedLesson) }}
                </p>
                <p v-else class="text-base font-semibold text-gray-400 italic">
                  Sans élève
                </p>
              </div>

              <!-- Club -->
              <div v-if="selectedLesson.club?.name" class="bg-gray-50 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-500 mb-1">Club</label>
                <p class="text-base font-semibold text-gray-900">
                  {{ selectedLesson.club.name }}
                </p>
              </div>

              <!-- Statut -->
              <div class="bg-gray-50 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-500 mb-2">Statut</label>
                <div class="flex flex-wrap gap-2">
                  <button 
                    @click="updateLessonStatus(selectedLesson.id, 'confirmed')"
                    :class="selectedLesson.status === 'confirmed' ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-700'"
                    class="min-h-[44px] px-4 py-2 rounded-lg font-medium transition-colors hover:opacity-80"
                    :disabled="saving">
                    ✓ Confirmé
                  </button>
                  <button 
                    @click="updateLessonStatus(selectedLesson.id, 'pending')"
                    :class="selectedLesson.status === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-700'"
                    class="min-h-[44px] px-4 py-2 rounded-lg font-medium transition-colors hover:opacity-80"
                    :disabled="saving">
                    ⏳ En attente
                  </button>
                  <button 
                    @click="updateLessonStatus(selectedLesson.id, 'completed')"
                    :class="selectedLesson.status === 'completed' ? 'bg-gray-500 text-white' : 'bg-gray-200 text-gray-700'"
                    class="min-h-[44px] px-4 py-2 rounded-lg font-medium transition-colors hover:opacity-80"
                    :disabled="saving">
                    ✓ Terminé
                  </button>
                  <button 
                    @click="updateLessonStatus(selectedLesson.id, 'cancelled')"
                    :class="selectedLesson.status === 'cancelled' ? 'bg-red-500 text-white' : 'bg-gray-200 text-gray-700'"
                    class="min-h-[44px] px-4 py-2 rounded-lg font-medium transition-colors hover:opacity-80"
                    :disabled="saving">
                    ✗ Annulé
                  </button>
                </div>
              </div>

              <!-- Notes -->
              <div v-if="selectedLesson.notes" class="bg-gray-50 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-500 mb-1">Notes</label>
                <p class="text-sm text-gray-700">{{ selectedLesson.notes }}</p>
              </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex flex-col-reverse sm:flex-row justify-between gap-3 mt-6 pt-4 border-t">
              <button 
                @click="deleteLesson(selectedLesson.id)"
                :disabled="saving"
                class="min-h-[44px] px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 font-medium">
                <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Supprimer
              </button>
              <button 
                @click="closeLessonModal"
                class="min-h-[44px] px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                Fermer
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Modale Création de Cours -->
      <CreateLessonModal
        v-if="showCreateLessonModal"
        :show="showCreateLessonModal"
        :form="lessonForm"
        :teachers="[]"
        :students="students"
        :course-types="courseTypes"
        :available-days="[]"
        :saving="saving"
        @close="closeCreateLessonModal"
        @submit="createLesson"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import CreateLessonModal from '~/components/planning/CreateLessonModal.vue'

definePageMeta({
  middleware: ['auth']
})

// Types
interface Lesson {
  id: number
  start_time: string
  end_time: string
  status: string
  price: number
  teacher?: {
    id: number
    user: {
      name: string
    }
  }
  student?: {
    id: number
    user: {
      name: string
    }
  }
  students?: Array<{
    id: number
    user?: {
      name: string
    }
    name?: string
  }>
  course_type?: {
    id: number
    name: string
  }
  club?: {
    id: number
    name: string
  }
  location?: any
  notes?: string
}

interface CourseType {
  id: number
  name: string
  description: string | null
  discipline_id: number | null
  is_individual: boolean
  max_participants: number | null
  is_active: boolean
  duration?: number
  duration_minutes?: number
  price?: number
}

// State
const loading = ref(true)
const error = ref<string | null>(null)
const lessons = ref<Lesson[]>([])
const showLessonModal = ref(false)
const selectedLesson = ref<Lesson | null>(null)
const showCreateLessonModal = ref(false)
const saving = ref(false)
const selectedCalendar = ref<string>('personal')
const teacherClubs = ref<any[]>([])
const students = ref<any[]>([])
const courseTypes = ref<CourseType[]>([])
const lessonForm = ref({
  teacher_id: null as number | null,
  student_id: null as number | null,
  course_type_id: null as number | null,
  date: '',
  time: '',
  start_time: '',
  duration: 60,
  price: 0,
  notes: ''
})

// Computed
const getClubName = (clubId: string | number) => {
  if (clubId === 'personal') return 'Calendrier Personnel'
  const club = teacherClubs.value.find(c => c.id === Number(clubId))
  return club?.name || 'Club'
}

// Fonction pour sélectionner un calendrier
function selectCalendar(calendarId: string) {
  selectedCalendar.value = calendarId
  loadLessons()
}

// Jours de la semaine
const weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

// Calculer les jours du calendrier mensuel
const calendarDays = computed(() => {
  const today = new Date()
  const year = today.getFullYear()
  const month = today.getMonth()
  
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  const startDate = new Date(firstDay)
  // Ajuster pour commencer le lundi (day 1)
  startDate.setDate(firstDay.getDate() - (firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1))
  
  const days = []
  const todayStr = today.toISOString().split('T')[0]
  
  for (let i = 0; i < 42; i++) {
    const date = new Date(startDate)
    date.setDate(startDate.getDate() + i)
    
    const dateStr = date.toISOString().split('T')[0]
    
    days.push({
      date: dateStr,
      day: date.getDate(),
      isCurrentMonth: date.getMonth() === month,
      isToday: dateStr === todayStr
    })
  }
  
  return days
})

// Obtenir les cours pour un jour donné
function getLessonsForDay(dateStr: string): Lesson[] {
  return lessons.value.filter(lesson => {
    const lessonDate = new Date(lesson.start_time).toISOString().split('T')[0]
    return lessonDate === dateStr
  }).sort((a, b) => {
    const timeA = new Date(a.start_time).getTime()
    const timeB = new Date(b.start_time).getTime()
    return timeA - timeB
  })
}

// Fonctions
async function loadLessons() {
  try {
    loading.value = true
    error.value = null
    
    const { $api } = useNuxtApp()
    
    // Construire les paramètres selon le calendrier sélectionné
    const params: any = {
      period: 'current_month' // Charger le mois en cours par défaut
    }
    
    const response = await $api.get('/teacher/lessons', { params })
    
    if (response.data.success) {
      let allLessons = response.data.data || []
      
      // Filtrer selon le calendrier sélectionné
      if (selectedCalendar.value === 'personal') {
        // Pour le calendrier personnel : uniquement les cours directs (sans club_id)
        // Ce sont les cours créés directement par l'enseignant pour ses élèves
        allLessons = allLessons.filter((lesson: Lesson) => {
          // Un cours est "personnel" si club est null, undefined, ou si club.id n'existe pas
          return !lesson.club || 
                 lesson.club === null || 
                 lesson.club === undefined ||
                 !lesson.club.id || 
                 lesson.club.id === null
        })
        console.log('📅 Calendrier Personnel: cours directs uniquement (sans club)', {
          total: response.data.data?.length || 0,
          filtres: allLessons.length
        })
      } else {
        // Pour un calendrier de club : uniquement les cours de ce club
        const clubId = Number(selectedCalendar.value)
        allLessons = allLessons.filter((lesson: Lesson) => lesson.club?.id === clubId)
        console.log(`📅 Calendrier Club ${clubId}: cours du club uniquement`, {
          total: response.data.data?.length || 0,
          filtres: allLessons.length
        })
      }
      
      lessons.value = allLessons.sort((a: Lesson, b: Lesson) => 
        new Date(a.start_time).getTime() - new Date(b.start_time).getTime()
      )
      console.log('✅ Cours chargés:', lessons.value.length)
    } else {
      error.value = response.data.message || 'Erreur lors du chargement des cours'
    }
  } catch (err: any) {
    console.error('Erreur chargement cours:', err)
    error.value = err.message || 'Erreur lors du chargement des cours'
  } finally {
    loading.value = false
  }
}

async function loadTeacherClubs() {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/teacher/clubs')
    if (response.data.success) {
      teacherClubs.value = response.data.clubs || []
      console.log('✅ Clubs chargés:', teacherClubs.value.length)
    }
  } catch (err: any) {
    console.error('Erreur chargement clubs:', err)
  }
}

async function loadStudents() {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/teacher/students')
    if (response.data.success) {
      students.value = response.data.students || []
      console.log('✅ Élèves chargés:', students.value.length)
    }
  } catch (err: any) {
    console.error('Erreur chargement élèves:', err)
  }
}

async function loadCourseTypes() {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/course-types')
    if (response.data.success) {
      courseTypes.value = response.data.data || []
      console.log('✅ Types de cours chargés:', courseTypes.value.length)
    }
  } catch (err: any) {
    console.error('Erreur chargement types de cours:', err)
  }
}

function openCreateLessonModal() {
  // Pré-remplir avec la date d'aujourd'hui
  const today = new Date()
  const dateStr = today.toISOString().split('T')[0]
  const timeStr = '09:00'
  
  lessonForm.value = {
    teacher_id: null,
    student_id: null,
    course_type_id: null,
    date: dateStr,
    time: timeStr,
    start_time: `${dateStr}T${timeStr}`,
    duration: 60,
    price: 0,
    notes: ''
  }
  
  showCreateLessonModal.value = true
}

function closeCreateLessonModal() {
  showCreateLessonModal.value = false
}

async function createLesson() {
  try {
    saving.value = true
    const { $api } = useNuxtApp()
    
    const payload = {
      student_id: lessonForm.value.student_id,
      course_type_id: lessonForm.value.course_type_id,
      start_time: lessonForm.value.start_time,
      duration: lessonForm.value.duration,
      price: lessonForm.value.price,
      notes: lessonForm.value.notes
    }
    
    console.log('📤 Création du cours avec payload:', payload)
    
    const response = await $api.post('/teacher/lessons', payload)
    
    if (response.data.success) {
      console.log('✅ Cours créé:', response.data.data)
      await loadLessons()
      closeCreateLessonModal()
    } else {
      alert('❌ ' + (response.data.message || 'Erreur lors de la création du cours'))
    }
  } catch (err: any) {
    console.error('Erreur création cours:', err)
    const errorMessage = err.response?.data?.message || err.response?.data?.errors || 'Erreur lors de la création du cours'
    
    if (typeof errorMessage === 'object') {
      const errors = Object.entries(errorMessage).map(([field, msgs]) => `${field}: ${Array.isArray(msgs) ? msgs.join(', ') : msgs}`).join('\n')
      alert('❌ Erreurs de validation:\n\n' + errors)
    } else {
      alert('❌ ' + errorMessage)
    }
  } finally {
    saving.value = false
  }
}

function openLessonModal(lesson: Lesson) {
  selectedLesson.value = lesson
  showLessonModal.value = true
}

function closeLessonModal() {
  showLessonModal.value = false
  selectedLesson.value = null
}

async function updateLessonStatus(lessonId: number, newStatus: string) {
  try {
    saving.value = true
    const { $api } = useNuxtApp()
    
    const response = await $api.put(`/teacher/lessons/${lessonId}`, {
      status: newStatus
    })
    
    if (response.data.success) {
      await loadLessons()
      closeLessonModal()
    } else {
      alert('Erreur lors de la mise à jour du statut')
    }
  } catch (err: any) {
    console.error('Erreur mise à jour cours:', err)
    alert('Erreur lors de la mise à jour du statut')
  } finally {
    saving.value = false
  }
}

async function deleteLesson(lessonId: number) {
  if (!confirm('Êtes-vous sûr de vouloir supprimer ce cours ?')) return
  
  try {
    saving.value = true
    const { $api } = useNuxtApp()
    
    const response = await $api.delete(`/teacher/lessons/${lessonId}`)
    
    if (response.data.success) {
      await loadLessons()
      closeLessonModal()
    } else {
      alert('Erreur lors de la suppression')
    }
  } catch (err: any) {
    console.error('Erreur suppression cours:', err)
    alert('Erreur lors de la suppression')
  } finally {
    saving.value = false
  }
}

// Fonctions utilitaires
function formatPrice(price: any): string {
  const numPrice = typeof price === 'string' ? parseFloat(price) : price
  return isNaN(numPrice) ? '0.00' : numPrice.toFixed(2)
}

function formatLessonDate(datetime: string): string {
  const date = new Date(datetime)
  return date.toLocaleDateString('fr-FR', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

function formatLessonTime(datetime: string): string {
  const date = new Date(datetime)
  return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    'confirmed': '✓ Confirmé',
    'pending': '⏳ En attente',
    'cancelled': '✗ Annulé',
    'completed': '✓ Terminé'
  }
  return labels[status] || status
}

function getStatusBadgeClass(status: string): string {
  const classes: Record<string, string> = {
    'confirmed': 'bg-green-100 text-green-800',
    'pending': 'bg-yellow-100 text-yellow-800',
    'cancelled': 'bg-red-100 text-red-800',
    'completed': 'bg-gray-100 text-gray-600'
  }
  return classes[status] || 'bg-blue-100 text-blue-800'
}

function getLessonBorderClass(lesson: Lesson): string {
  const classes: Record<string, string> = {
    'confirmed': 'border-green-300 bg-green-50',
    'pending': 'border-yellow-300 bg-yellow-50',
    'cancelled': 'border-red-300 bg-red-50',
    'completed': 'border-gray-300 bg-gray-50'
  }
  return classes[lesson.status] || 'border-blue-300 bg-blue-50'
}

// Lifecycle
onMounted(async () => {
  // Vérifier si un paramètre club est présent dans l'URL
  const route = useRoute()
  if (route.query.club) {
    selectedCalendar.value = String(route.query.club)
  }
  
  await Promise.all([
    loadLessons(),
    loadTeacherClubs(),
    loadStudents(),
    loadCourseTypes()
  ])
})
</script>
