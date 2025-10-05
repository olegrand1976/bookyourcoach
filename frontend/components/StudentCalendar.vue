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
          <option v-for="club in studentClubs" :key="club.id" :value="club.id">
            {{ club.name }}
          </option>
        </select>
        
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
            </div>
            <div class="space-y-1">
              <div v-for="event in day.events" :key="event.id" 
                @click="selectEvent(event)"
                :class="['text-xs p-1 rounded cursor-pointer truncate', 
                  event.type === 'lesson' ? 'bg-blue-100 text-blue-800' : 
                  event.type === 'booking' ? 'bg-green-100 text-green-800' :
                  'bg-gray-100 text-gray-800']">
                {{ event.title }}
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
            class="p-3 text-center text-sm font-medium text-gray-500 bg-gray-50">
            {{ day }}
          </div>
        </div>
        <div class="grid grid-cols-8 gap-1">
          <div class="p-2 text-xs text-gray-500 bg-gray-50">
            {{ formatTime(8) }}
          </div>
          <div v-for="day in weekDays" :key="day" 
            class="min-h-[60px] p-2 border border-gray-200 bg-white">
            <!-- Événements pour cette heure et ce jour -->
          </div>
        </div>
      </div>

      <!-- Vue Jour -->
      <div v-else-if="currentView === 'day'" class="calendar-day">
        <div class="space-y-4">
          <div v-for="hour in dayHours" :key="hour" 
            class="flex items-center space-x-4 p-3 border border-gray-200 rounded-lg">
            <div class="w-16 text-sm text-gray-500">
              {{ formatTime(hour) }}
            </div>
            <div class="flex-1">
              <!-- Événements pour cette heure -->
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de détails d'événement -->
    <div v-if="selectedEvent" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">{{ selectedEvent.title }}</h3>
          <button @click="selectedEvent = null" 
            class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <div class="space-y-3">
          <div>
            <span class="text-sm font-medium text-gray-500">Date:</span>
            <span class="ml-2 text-sm text-gray-900">{{ formatDate(selectedEvent.start) }}</span>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-500">Heure:</span>
            <span class="ml-2 text-sm text-gray-900">
              {{ formatTime(selectedEvent.start) }} - {{ formatTime(selectedEvent.end) }}
            </span>
          </div>
          <div v-if="selectedEvent.teacher">
            <span class="text-sm font-medium text-gray-500">Enseignant:</span>
            <span class="ml-2 text-sm text-gray-900">{{ selectedEvent.teacher.name }}</span>
          </div>
          <div v-if="selectedEvent.location">
            <span class="text-sm font-medium text-gray-500">Lieu:</span>
            <span class="ml-2 text-sm text-gray-900">{{ selectedEvent.location }}</span>
          </div>
          <div v-if="selectedEvent.description">
            <span class="text-sm font-medium text-gray-500">Description:</span>
            <p class="mt-1 text-sm text-gray-900">{{ selectedEvent.description }}</p>
          </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-3">
          <button @click="selectedEvent = null" 
            class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors">
            Fermer
          </button>
          <button v-if="selectedEvent.type === 'lesson'" @click="bookLesson(selectedEvent)"
            class="px-4 py-2 text-sm text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors">
            Réserver
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  studentId: {
    type: [String, Number],
    required: true
  }
})

const { $api } = useNuxtApp()
const authStore = useAuthStore()

// État du calendrier
const currentView = ref('month')
const currentDate = ref(new Date())
const selectedCalendar = ref('personal')
const events = ref([])
const studentClubs = ref([])
const selectedEvent = ref(null)
const isSyncing = ref(false)

// Jours de la semaine
const weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

// Heures de la journée
const dayHours = Array.from({ length: 12 }, (_, i) => i + 8)

// Titre de la période actuelle
const currentPeriodTitle = computed(() => {
  const date = currentDate.value
  if (currentView.value === 'month') {
    return date.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
  } else if (currentView.value === 'week') {
    const startOfWeek = new Date(date)
    startOfWeek.setDate(date.getDate() - date.getDay() + 1)
    const endOfWeek = new Date(startOfWeek)
    endOfWeek.setDate(startOfWeek.getDate() + 6)
    return `${startOfWeek.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })} - ${endOfWeek.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })}`
  } else {
    return date.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
  }
})

// Jours du calendrier pour la vue mois
const calendarDays = computed(() => {
  const date = currentDate.value
  const year = date.getFullYear()
  const month = date.getMonth()
  
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  const startDate = new Date(firstDay)
  startDate.setDate(firstDay.getDate() - (firstDay.getDay() + 6) % 7)
  
  const days = []
  const today = new Date()
  
  for (let i = 0; i < 42; i++) {
    const dayDate = new Date(startDate)
    dayDate.setDate(startDate.getDate() + i)
    
    const dayEvents = events.value.filter(event => {
      const eventDate = new Date(event.start)
      return eventDate.toDateString() === dayDate.toDateString()
    })
    
    days.push({
      date: dayDate.toISOString().split('T')[0],
      day: dayDate.getDate(),
      isCurrentMonth: dayDate.getMonth() === month,
      isToday: dayDate.toDateString() === today.toDateString(),
      events: dayEvents
    })
  }
  
  return days
})

// Fonctions de navigation
const toggleView = (view) => {
  currentView.value = view
}

const previousPeriod = () => {
  const date = new Date(currentDate.value)
  if (currentView.value === 'month') {
    date.setMonth(date.getMonth() - 1)
  } else if (currentView.value === 'week') {
    date.setDate(date.getDate() - 7)
  } else {
    date.setDate(date.getDate() - 1)
  }
  currentDate.value = date
  loadCalendarEvents()
}

const nextPeriod = () => {
  const date = new Date(currentDate.value)
  if (currentView.value === 'month') {
    date.setMonth(date.getMonth() + 1)
  } else if (currentView.value === 'week') {
    date.setDate(date.getDate() + 7)
  } else {
    date.setDate(date.getDate() + 1)
  }
  currentDate.value = date
  loadCalendarEvents()
}

const goToToday = () => {
  currentDate.value = new Date()
  loadCalendarEvents()
}

// Fonctions utilitaires
const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

const formatTime = (date) => {
  if (typeof date === 'number') {
    return `${date.toString().padStart(2, '0')}:00`
  }
  return new Date(date).toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Fonctions de gestion des événements
const selectEvent = (event) => {
  selectedEvent.value = event
}

const bookLesson = async (lesson) => {
  try {
    await $api.post('/student/bookings', {
      lesson_id: lesson.id,
      student_id: props.studentId
    })
    
    // Recharger les événements
    await loadCalendarEvents()
    
    // Fermer le modal
    selectedEvent.value = null
    
    // Afficher un message de succès
    // Vous pouvez utiliser une notification toast ici
    console.log('Leçon réservée avec succès')
  } catch (error) {
    console.error('Erreur lors de la réservation:', error)
  }
}

const syncWithGoogle = async () => {
  isSyncing.value = true
  try {
    await $api.post('/student/calendar/sync-google', {
      calendar_id: selectedCalendar.value
    })
    await loadCalendarEvents()
  } catch (error) {
    console.error('Erreur lors de la synchronisation:', error)
  } finally {
    isSyncing.value = false
  }
}

// Chargement des données
const loadCalendarEvents = async () => {
  try {
    const response = await $api.get(`/student/calendar?calendar_id=${selectedCalendar.value}`)
    events.value = response.data.events || []
  } catch (error) {
    console.error('Erreur lors du chargement des événements:', error)
  }
}

const loadStudentClubs = async () => {
  try {
    const response = await $api.get('/student/clubs')
    studentClubs.value = response.data.clubs || []
  } catch (error) {
    console.error('Erreur lors du chargement des clubs:', error)
  }
}

// Initialisation
onMounted(() => {
  loadCalendarEvents()
  loadStudentClubs()
})
</script>