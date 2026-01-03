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
        <button @click="goToToday" 
          class="px-3 py-1 text-sm text-blue-600 hover:text-blue-800 transition-colors">
          Aujourd'hui
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
                :class="['text-xs p-1 rounded cursor-pointer truncate hover:opacity-80 transition-opacity', 
                  event.status === 'confirmed' ? 'bg-blue-100 text-blue-800' : 
                  event.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                  event.status === 'cancelled' ? 'bg-red-100 text-red-800' :
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
          <div v-for="hour in dayHours" :key="hour" class="relative">
            <div class="p-2 text-xs text-gray-500 bg-gray-50 border-r border-gray-200">
              {{ formatTime(hour) }}
            </div>
            <div v-for="day in weekDays" :key="day" 
              class="min-h-[60px] p-2 border border-gray-200 bg-white relative">
              <div 
                v-for="event in getEventsForHourAndDay(hour, day)" 
                :key="event.id"
                @click="selectEvent(event)"
                :class="['absolute left-1 right-1 p-1 rounded text-xs cursor-pointer hover:opacity-80 transition-opacity',
                  event.status === 'confirmed' ? 'bg-blue-100 text-blue-800' : 
                  event.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                  event.status === 'cancelled' ? 'bg-red-100 text-red-800' :
                  'bg-gray-100 text-gray-800']"
                :style="{ top: `${getEventPosition(event, hour)}px`, height: `${getEventHeight(event)}px` }"
              >
                {{ event.title }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vue Jour -->
      <div v-else-if="currentView === 'day'" class="calendar-day">
        <div class="space-y-4">
          <div v-for="hour in dayHours" :key="hour" 
            class="flex items-start space-x-4 p-3 border border-gray-200 rounded-lg">
            <div class="w-16 text-sm text-gray-500 pt-1">
              {{ formatTime(hour) }}
            </div>
            <div class="flex-1 space-y-2">
              <div 
                v-for="event in getEventsForHour(hour)" 
                :key="event.id"
                @click="selectEvent(event)"
                :class="['p-3 rounded-lg cursor-pointer hover:opacity-80 transition-opacity',
                  event.status === 'confirmed' ? 'bg-blue-100 text-blue-800 border border-blue-200' : 
                  event.status === 'pending' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' :
                  event.status === 'cancelled' ? 'bg-red-100 text-red-800 border border-red-200' :
                  'bg-gray-100 text-gray-800 border border-gray-200']"
              >
                <p class="font-medium">{{ event.title }}</p>
                <p class="text-xs mt-1">{{ formatTime(event.start) }} - {{ formatTime(event.end) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de détails d'événement -->
    <div v-if="selectedEvent" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="closeModal">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">{{ selectedEvent.title }}</h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
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
            <span class="ml-2 text-sm text-gray-900">{{ selectedEvent.teacher?.user?.name || selectedEvent.teacher?.name || 'N/A' }}</span>
          </div>
          <div v-if="selectedEvent.location">
            <span class="text-sm font-medium text-gray-500">Lieu:</span>
            <span class="ml-2 text-sm text-gray-900">{{ selectedEvent.location?.name || selectedEvent.location || 'N/A' }}</span>
          </div>
          <div v-if="selectedEvent.price">
            <span class="text-sm font-medium text-gray-500">Prix:</span>
            <span class="ml-2 text-sm text-gray-900">{{ formatPrice(selectedEvent.price) }}</span>
          </div>
          <div v-if="selectedEvent.status">
            <span class="text-sm font-medium text-gray-500">Statut:</span>
            <span 
              :class="{
                'bg-green-100 text-green-800': selectedEvent.status === 'confirmed',
                'bg-yellow-100 text-yellow-800': selectedEvent.status === 'pending',
                'bg-gray-100 text-gray-800': selectedEvent.status === 'completed',
                'bg-red-100 text-red-800': selectedEvent.status === 'cancelled'
              }"
              class="ml-2 inline-block px-2 py-1 text-xs font-medium rounded-full"
            >
              {{ getStatusLabel(selectedEvent.status) }}
            </span>
          </div>
          <div v-if="selectedEvent.description || selectedEvent.notes">
            <span class="text-sm font-medium text-gray-500">Description:</span>
            <p class="mt-1 text-sm text-gray-900">{{ selectedEvent.description || selectedEvent.notes || 'N/A' }}</p>
          </div>
        </div>
        
        <div class="mt-6 flex justify-end">
          <button @click="closeModal" 
            class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors">
            Fermer
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

// État du calendrier
const currentView = ref('month')
const currentDate = ref(new Date())
const events = ref([])
const selectedEvent = ref(null)
const isLoading = ref(false)

// Jours de la semaine
const weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

// Heures de la journée
const dayHours = Array.from({ length: 14 }, (_, i) => i + 8)

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
  loadCalendarEvents()
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

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(price)
}

const getStatusLabel = (status) => {
  const labels = {
    'confirmed': 'Confirmé',
    'pending': 'En attente',
    'completed': 'Terminé',
    'cancelled': 'Annulé',
    'available': 'Disponible'
  }
  return labels[status] || status
}

// Fonctions de gestion des événements
const selectEvent = (event) => {
  selectedEvent.value = event
}

const closeModal = () => {
  selectedEvent.value = null
}

// Fonctions pour la vue semaine
const getEventsForHourAndDay = (hour, dayName) => {
  const dayIndex = weekDays.indexOf(dayName)
  const weekStart = new Date(currentDate.value)
  weekStart.setDate(currentDate.value.getDate() - currentDate.value.getDay() + 1)
  const targetDate = new Date(weekStart)
  targetDate.setDate(weekStart.getDate() + dayIndex)
  
  return events.value.filter(event => {
    const eventDate = new Date(event.start)
    const eventHour = eventDate.getHours()
    return eventDate.toDateString() === targetDate.toDateString() && eventHour === hour
  })
}

const getEventsForHour = (hour) => {
  const targetDate = new Date(currentDate.value)
  return events.value.filter(event => {
    const eventDate = new Date(event.start)
    const eventHour = eventDate.getHours()
    return eventDate.toDateString() === targetDate.toDateString() && eventHour === hour
  })
}

const getEventPosition = (event, hour) => {
  const eventDate = new Date(event.start)
  const minutes = eventDate.getMinutes()
  return (minutes / 60) * 60 // Position en pixels dans la case d'heure
}

const getEventHeight = (event) => {
  const start = new Date(event.start)
  const end = new Date(event.end)
  const durationMinutes = (end - start) / (1000 * 60)
  return Math.max((durationMinutes / 60) * 60, 20) // Hauteur minimale de 20px
}

// Chargement des données
const loadCalendarEvents = async () => {
  try {
    isLoading.value = true
    // Charger tous les cours de l'étudiant depuis l'API bookings (incluant les annulés et passés)
    const response = await $api.get('/student/bookings')
    if (response.data.success) {
      const lessons = response.data.data || []
      
      // Transformer les cours en événements pour le calendrier (inclure tous les statuts y compris cancelled)
      events.value = lessons
        .filter(lesson => lesson.start_time) // Filtrer seulement ceux qui ont une date
        .map(lesson => ({
          id: lesson.id,
          title: lesson.course_type?.name || lesson.courseType?.name || 'Cours',
          start: lesson.start_time,
          end: lesson.end_time,
          teacher: lesson.teacher,
          location: lesson.location,
          price: lesson.price,
          status: lesson.status,
          description: lesson.notes,
          notes: lesson.notes,
          course_type: lesson.course_type || lesson.courseType,
          type: 'lesson'
        }))
        .sort((a, b) => new Date(a.start).getTime() - new Date(b.start).getTime())
    }
  } catch (error) {
    console.error('Erreur lors du chargement des événements:', error)
    events.value = []
  } finally {
    isLoading.value = false
  }
}

// Initialisation
onMounted(() => {
  loadCalendarEvents()
})
</script>
