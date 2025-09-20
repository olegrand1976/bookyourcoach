<template>
  <div class="bg-white rounded-lg shadow-lg">
    <!-- En-tête du calendrier -->
    <div class="flex items-center justify-between p-6 border-b border-gray-200">
      <div class="flex items-center space-x-4">
        <h2 class="text-xl font-semibold text-gray-900">Mon Calendrier</h2>
        <div class="flex items-center space-x-2">
          <button @click="toggleView('month')" 
            :class="['px-3 py-1 rounded-md text-sm font-medium transition-colors', 
              currentView === 'month' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']">
            Mois
          </button>
          <button @click="toggleView('week')" 
            :class="['px-3 py-1 rounded-md text-sm font-medium transition-colors', 
              currentView === 'week' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']">
            Semaine
          </button>
          <button @click="toggleView('day')" 
            :class="['px-3 py-1 rounded-md text-sm font-medium transition-colors', 
              currentView === 'day' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']">
            Jour
          </button>
        </div>
      </div>
      
      <div class="flex items-center space-x-3">
        <!-- Sélecteur de calendrier -->
        <select v-model="selectedCalendar" @change="loadCalendarEvents" 
          class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="personal">Mon Calendrier Personnel</option>
          <option v-for="club in teacherClubs" :key="club.id" :value="club.id">
            {{ club.name }}
          </option>
        </select>
        
        <!-- Bouton d'ajout de cours -->
        <button @click="showAddLessonModal = true"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
          <span class="mr-2">+</span>
          Ajouter un cours
        </button>
        
        <!-- Bouton de synchronisation Google -->
        <button @click="syncWithGoogle" :disabled="isSyncing"
          class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors disabled:opacity-50">
          <span class="mr-2">🔄</span>
          {{ isSyncing ? 'Synchronisation...' : 'Sync Google' }}
        </button>
      </div>
    </div>

    <!-- Navigation du calendrier -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200">
      <div class="flex items-center space-x-4">
        <button @click="previousPeriod" 
          class="p-2 rounded-md hover:bg-gray-100 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        
        <h3 class="text-lg font-medium text-gray-900">
          {{ currentPeriodTitle }}
        </h3>
        
        <button @click="nextPeriod" 
          class="p-2 rounded-md hover:bg-gray-100 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
        
        <button @click="goToToday" 
          class="px-3 py-1 text-sm text-blue-600 hover:text-blue-800 transition-colors">
          Aujourd'hui
        </button>
      </div>
      
      <div class="text-sm text-gray-500">
        {{ events.length }} cours programmés
      </div>
    </div>

    <!-- Calendrier -->
    <div class="p-6">
      <!-- Vue Mois -->
      <div v-if="currentView === 'month'" class="calendar-month">
        <div class="grid grid-cols-7 gap-1 mb-2">
          <div v-for="day in weekDays" :key="day" 
            class="p-3 text-center text-sm font-medium text-gray-500 bg-gray-50">
            {{ day }}
          </div>
        </div>
        <div class="grid grid-cols-7 gap-1">
          <div v-for="day in calendarDays" :key="day.date" 
            :class="['min-h-[120px] p-2 border border-gray-200', 
              day.isCurrentMonth ? 'bg-white' : 'bg-gray-50',
              day.isToday ? 'bg-blue-50 border-blue-300' : '']">
            <div class="flex items-center justify-between mb-1">
              <span :class="['text-sm font-medium', 
                day.isCurrentMonth ? 'text-gray-900' : 'text-gray-400',
                day.isToday ? 'text-blue-600' : '']">
                {{ day.day }}
              </span>
              <button v-if="day.isCurrentMonth" @click="addEventToDay(day.date)"
                class="w-5 h-5 rounded-full bg-blue-600 text-white text-xs hover:bg-blue-700 transition-colors">
                +
              </button>
            </div>
            <div class="space-y-1">
              <div v-for="event in getEventsForDay(day.date)" :key="event.id"
                @click="selectEvent(event)"
                :class="['p-1 rounded text-xs cursor-pointer transition-colors', 
                  getEventColor(event.type)]">
                <div class="font-medium truncate">{{ event.title }}</div>
                <div class="text-xs opacity-75">{{ formatTime(event.start_time) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vue Semaine -->
      <div v-else-if="currentView === 'week'" class="calendar-week">
        <div class="grid grid-cols-8 gap-1">
          <div class="p-3 text-center text-sm font-medium text-gray-500 bg-gray-50">
            Heure
          </div>
          <div v-for="day in weekDays" :key="day" 
            :class="['p-3 text-center text-sm font-medium bg-gray-50', 
              isToday(day) ? 'text-blue-600 bg-blue-50' : 'text-gray-500']">
            {{ day }}
          </div>
        </div>
        <div class="grid grid-cols-8 gap-1">
          <div class="p-2 text-xs text-gray-500 bg-gray-50">
            <!-- Heures -->
            <div v-for="hour in hours" :key="hour" class="h-12 flex items-center">
              {{ hour }}:00
            </div>
          </div>
          <div v-for="day in weekDays" :key="day" class="border border-gray-200">
            <div v-for="hour in hours" :key="hour" 
              class="h-12 border-b border-gray-100 p-1 relative">
              <div v-for="event in getEventsForHour(day, hour)" :key="event.id"
                @click="selectEvent(event)"
                :class="['absolute inset-1 rounded text-xs p-1 cursor-pointer', 
                  getEventColor(event.type)]">
                {{ event.title }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vue Jour -->
      <div v-else-if="currentView === 'day'" class="calendar-day">
        <div class="grid grid-cols-2 gap-1">
          <div class="p-3 text-center text-sm font-medium text-gray-500 bg-gray-50">
            Heure
          </div>
          <div class="p-3 text-center text-sm font-medium text-gray-500 bg-gray-50">
            Événements
          </div>
        </div>
        <div class="grid grid-cols-2 gap-1">
          <div class="p-2 text-xs text-gray-500 bg-gray-50">
            <div v-for="hour in hours" :key="hour" class="h-12 flex items-center">
              {{ hour }}:00
            </div>
          </div>
          <div class="border border-gray-200">
            <div v-for="hour in hours" :key="hour" 
              class="h-12 border-b border-gray-100 p-1 relative">
              <div v-for="event in getEventsForHour(currentDate, hour)" :key="event.id"
                @click="selectEvent(event)"
                :class="['absolute inset-1 rounded text-xs p-1 cursor-pointer', 
                  getEventColor(event.type)]">
                {{ event.title }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal d'ajout de cours -->
    <div v-if="showAddLessonModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Ajouter un cours</h3>
        <form @submit.prevent="addLesson">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Titre du cours</label>
              <input v-model="newLesson.title" type="text" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Élève</label>
              <select v-model="newLesson.student_id" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Sélectionner un élève</option>
                <option v-for="student in students" :key="student.id" :value="student.id">
                  {{ student.name }}
                </option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input v-model="newLesson.date" type="date" required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Heure</label>
                <input v-model="newLesson.time" type="time" required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Durée (minutes)</label>
              <input v-model="newLesson.duration" type="number" min="30" max="180" step="30" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Type de cours</label>
              <select v-model="newLesson.type" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="lesson">Cours particulier</option>
                <option value="group">Cours de groupe</option>
                <option value="training">Entraînement</option>
                <option value="competition">Compétition</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea v-model="newLesson.description" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
          </div>
          <div class="flex justify-end space-x-3 mt-6">
            <button type="button" @click="showAddLessonModal = false"
              class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors">
              Annuler
            </button>
            <button type="submit" :disabled="isAddingLesson"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50">
              {{ isAddingLesson ? 'Ajout...' : 'Ajouter' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de détails d'événement -->
    <div v-if="selectedEvent" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">{{ selectedEvent.title }}</h3>
        <div class="space-y-2 text-sm text-gray-600">
          <div><strong>Date:</strong> {{ formatDate(selectedEvent.start_time) }}</div>
          <div><strong>Heure:</strong> {{ formatTime(selectedEvent.start_time) }}</div>
          <div><strong>Durée:</strong> {{ selectedEvent.duration }} minutes</div>
          <div><strong>Type:</strong> {{ selectedEvent.type }}</div>
          <div v-if="selectedEvent.student_name"><strong>Élève:</strong> {{ selectedEvent.student_name }}</div>
          <div v-if="selectedEvent.description"><strong>Description:</strong> {{ selectedEvent.description }}</div>
        </div>
        <div class="flex justify-end space-x-3 mt-6">
          <button @click="editEvent(selectedEvent)"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
            Modifier
          </button>
          <button @click="deleteEvent(selectedEvent)"
            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
            Supprimer
          </button>
          <button @click="selectedEvent = null"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors">
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'

// Props
const props = defineProps({
  teacherId: {
    type: Number,
    required: true
  }
})

// État réactif
const currentView = ref('month')
const currentDate = ref(new Date())
const selectedCalendar = ref('personal')
const showAddLessonModal = ref(false)
const selectedEvent = ref(null)
const isSyncing = ref(false)
const isAddingLesson = ref(false)

// Données
const events = ref([])
const students = ref([])
const teacherClubs = ref([])

// Nouveau cours
const newLesson = ref({
  title: '',
  student_id: '',
  date: '',
  time: '',
  duration: 60,
  type: 'lesson',
  description: ''
})

// Constantes
const weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
const hours = Array.from({ length: 24 }, (_, i) => i)

// Computed
const currentPeriodTitle = computed(() => {
  if (currentView.value === 'month') {
    return currentDate.value.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
  } else if (currentView.value === 'week') {
    const startOfWeek = new Date(currentDate.value)
    startOfWeek.setDate(currentDate.value.getDate() - currentDate.value.getDay() + 1)
    const endOfWeek = new Date(startOfWeek)
    endOfWeek.setDate(startOfWeek.getDate() + 6)
    return `${startOfWeek.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })} - ${endOfWeek.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })}`
  } else {
    return currentDate.value.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
  }
})

const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()
  
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  const startDate = new Date(firstDay)
  startDate.setDate(firstDay.getDate() - firstDay.getDay() + 1)
  
  const days = []
  const today = new Date()
  
  for (let i = 0; i < 42; i++) {
    const date = new Date(startDate)
    date.setDate(startDate.getDate() + i)
    
    days.push({
      date: date.toISOString().split('T')[0],
      day: date.getDate(),
      isCurrentMonth: date.getMonth() === month,
      isToday: date.toDateString() === today.toDateString()
    })
  }
  
  return days
})

// Méthodes
const toggleView = (view) => {
  currentView.value = view
}

const previousPeriod = () => {
  if (currentView.value === 'month') {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1)
  } else if (currentView.value === 'week') {
    currentDate.value = new Date(currentDate.value.getTime() - 7 * 24 * 60 * 60 * 1000)
  } else {
    currentDate.value = new Date(currentDate.value.getTime() - 24 * 60 * 60 * 1000)
  }
}

const nextPeriod = () => {
  if (currentView.value === 'month') {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1)
  } else if (currentView.value === 'week') {
    currentDate.value = new Date(currentDate.value.getTime() + 7 * 24 * 60 * 60 * 1000)
  } else {
    currentDate.value = new Date(currentDate.value.getTime() + 24 * 60 * 60 * 1000)
  }
}

const goToToday = () => {
  currentDate.value = new Date()
}

const getEventsForDay = (date) => {
  return events.value.filter(event => {
    const eventDate = new Date(event.start_time).toISOString().split('T')[0]
    return eventDate === date
  })
}

const getEventsForHour = (date, hour) => {
  return events.value.filter(event => {
    const eventDate = new Date(event.start_time).toISOString().split('T')[0]
    const eventHour = new Date(event.start_time).getHours()
    return eventDate === date && eventHour === hour
  })
}

const getEventColor = (type) => {
  const colors = {
    lesson: 'bg-blue-100 text-blue-800 hover:bg-blue-200',
    group: 'bg-green-100 text-green-800 hover:bg-green-200',
    training: 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200',
    competition: 'bg-red-100 text-red-800 hover:bg-red-200'
  }
  return colors[type] || 'bg-gray-100 text-gray-800 hover:bg-gray-200'
}

const formatTime = (dateTime) => {
  return new Date(dateTime).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

const formatDate = (dateTime) => {
  return new Date(dateTime).toLocaleDateString('fr-FR', { 
    weekday: 'long', 
    day: 'numeric', 
    month: 'long', 
    year: 'numeric' 
  })
}

const isToday = (day) => {
  const today = new Date()
  const dayIndex = weekDays.indexOf(day)
  const currentDayIndex = today.getDay() === 0 ? 6 : today.getDay() - 1
  return dayIndex === currentDayIndex
}

const addEventToDay = (date) => {
  newLesson.value.date = date
  showAddLessonModal.value = true
}

const selectEvent = (event) => {
  selectedEvent.value = event
}

const loadCalendarEvents = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get(`/teacher/calendar?calendar_id=${selectedCalendar.value}`)
    events.value = response.data.events || []
  } catch (error) {
    console.error('Erreur lors du chargement des événements:', error)
  }
}

const loadStudents = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/teacher/students')
    students.value = response.data.students || []
  } catch (error) {
    console.error('Erreur lors du chargement des élèves:', error)
  }
}

const loadTeacherClubs = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/teacher/clubs')
    teacherClubs.value = response.data.clubs || []
  } catch (error) {
    console.error('Erreur lors du chargement des clubs:', error)
  }
}

const addLesson = async () => {
  try {
    isAddingLesson.value = true
    const { $api } = useNuxtApp()
    
    const lessonData = {
      ...newLesson.value,
      start_time: new Date(`${newLesson.value.date}T${newLesson.value.time}`).toISOString(),
      end_time: new Date(new Date(`${newLesson.value.date}T${newLesson.value.time}`).getTime() + newLesson.value.duration * 60000).toISOString(),
      calendar_id: selectedCalendar.value
    }
    
    await $api.post('/teacher/lessons', lessonData)
    
    // Recharger les événements
    await loadCalendarEvents()
    
    // Réinitialiser le formulaire
    newLesson.value = {
      title: '',
      student_id: '',
      date: '',
      time: '',
      duration: 60,
      type: 'lesson',
      description: ''
    }
    
    showAddLessonModal.value = false
  } catch (error) {
    console.error('Erreur lors de l\'ajout du cours:', error)
  } finally {
    isAddingLesson.value = false
  }
}

const editEvent = (event) => {
  // TODO: Implémenter l'édition d'événement
  console.log('Édition de l\'événement:', event)
  selectedEvent.value = null
}

const deleteEvent = async (event) => {
  try {
    const { $api } = useNuxtApp()
    await $api.delete(`/teacher/lessons/${event.id}`)
    await loadCalendarEvents()
    selectedEvent.value = null
  } catch (error) {
    console.error('Erreur lors de la suppression du cours:', error)
  }
}

const syncWithGoogle = async () => {
  try {
    isSyncing.value = true
    const { $api } = useNuxtApp()
    await $api.post('/teacher/calendar/sync-google', {
      calendar_id: selectedCalendar.value
    })
    await loadCalendarEvents()
  } catch (error) {
    console.error('Erreur lors de la synchronisation Google:', error)
  } finally {
    isSyncing.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadCalendarEvents()
  loadStudents()
  loadTeacherClubs()
})

// Watchers
watch(currentDate, () => {
  loadCalendarEvents()
})
</script>
