<template>
  <div class="bg-white rounded-lg shadow-lg">
    <!-- En-tête du calendrier -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between p-4 sm:p-6 border-b border-gray-200">
      <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Mon Calendrier</h2>
      <div class="flex flex-wrap items-center gap-2">
        <button @click="toggleView('month')" 
          :class="['min-h-[44px] min-w-[44px] px-3 py-2 rounded-md text-sm font-medium transition-colors', 
            currentView === 'month' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']">
          Mois
        </button>
        <button
          v-if="!isMobile"
          :class="['min-h-[44px] min-w-[44px] px-3 py-2 rounded-md text-sm font-medium transition-colors', 
            currentView === 'week' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']"
          @click="toggleView('week')"
        >
          Semaine
        </button>
        <button @click="toggleView('day')" 
          :class="['min-h-[44px] min-w-[44px] px-3 py-2 rounded-md text-sm font-medium transition-colors', 
            currentView === 'day' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']">
          Jour
        </button>
        <button @click="goToToday" 
          class="min-h-[44px] px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md transition-colors">
          Aujourd'hui
        </button>
      </div>
    </div>

    <!-- Navigation du calendrier -->
    <div class="flex flex-wrap items-center justify-between gap-2 p-3 sm:p-4 border-b border-gray-200">
      <div class="flex items-center gap-2 sm:gap-4">
        <button @click="previousPeriod" 
          class="min-h-[44px] min-w-[44px] p-2 rounded-md hover:bg-gray-100 transition-colors flex items-center justify-center"
          aria-label="Période précédente">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <h3 class="text-base sm:text-lg font-medium text-gray-900 min-w-0">
          {{ currentPeriodTitle }}
        </h3>
        <button @click="nextPeriod" 
          class="min-h-[44px] min-w-[44px] p-2 rounded-md hover:bg-gray-100 transition-colors flex items-center justify-center"
          aria-label="Période suivante">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
      <div class="text-xs sm:text-sm text-gray-500">
        {{ events.length }} cours programmés
      </div>
    </div>

    <!-- Calendrier -->
    <div class="p-4 sm:p-6">
      <!-- Vue Mois -->
      <div v-if="currentView === 'month'" class="calendar-month">
        <div class="grid grid-cols-7 gap-0.5 sm:gap-1 mb-1 sm:mb-2">
          <div v-for="day in weekDays" :key="day" 
            class="p-1.5 sm:p-3 text-center text-xs sm:text-sm font-medium text-gray-500 bg-gray-50">
            {{ day }}
          </div>
        </div>
        <div class="grid grid-cols-7 gap-0.5 sm:gap-1">
          <div v-for="day in calendarDays" :key="day.date" 
            :class="['min-h-[80px] sm:min-h-[120px] p-1 sm:p-2 border border-gray-200', 
              day.isCurrentMonth ? 'bg-white' : 'bg-gray-50',
              day.isToday ? 'bg-blue-50 border-blue-300' : '']">
            <div class="flex items-center justify-between mb-0.5 sm:mb-1">
              <span :class="['text-xs sm:text-sm font-medium', 
                day.isCurrentMonth ? 'text-gray-900' : 'text-gray-400',
                day.isToday ? 'text-blue-600' : '']">
                {{ day.day }}
              </span>
            </div>
            <div class="space-y-0.5 sm:space-y-1">
              <div v-for="event in day.events" :key="event.id" 
                @click="selectEvent(event)"
                :class="['text-xs p-1.5 sm:p-2 rounded cursor-pointer hover:opacity-90 transition-opacity min-h-[32px] flex flex-col justify-center leading-tight', 
                  event.status === 'confirmed' ? 'bg-blue-100 text-blue-800' : 
                  event.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                  event.status === 'cancelled' ? 'bg-red-100 text-red-800' :
                  'bg-gray-100 text-gray-800']"
                :title="`${event.title} – ${formatTime(event.start)}`">
                <span class="font-medium whitespace-nowrap">{{ formatTime(event.start) }}</span>
                <span class="block truncate mt-0.5" :class="event.status === 'cancelled' ? 'line-through' : ''">{{ event.title }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vue Semaine : liste par jour sur mobile, grille sur md+ -->
      <div v-else-if="currentView === 'week'" class="calendar-week">
        <!-- Mobile : un bloc par jour avec les cours du jour -->
        <div class="md:hidden space-y-4">
          <div
            v-for="(dayInfo, index) in weekDaysWithEvents"
            :key="dayInfo.dateKey"
            class="border border-gray-200 rounded-lg overflow-hidden"
          >
            <div class="px-3 py-2 bg-gray-50 border-b border-gray-200 text-sm font-medium text-gray-700">
              {{ dayInfo.dayLabel }} {{ dayInfo.dateShort }}
            </div>
            <div class="p-2 space-y-2">
              <div
                v-for="event in dayInfo.events"
                :key="event.id"
                @click="selectEvent(event)"
                :class="['p-3 rounded-lg cursor-pointer hover:opacity-90 transition-opacity',
                  event.status === 'confirmed' ? 'bg-blue-100 text-blue-800' :
                  event.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                  event.status === 'cancelled' ? 'bg-red-100 text-red-800' :
                  'bg-gray-100 text-gray-800']"
              >
                <p class="font-medium text-sm" :class="event.status === 'cancelled' ? 'line-through' : ''">{{ event.title }}</p>
                <p class="text-xs mt-0.5 text-gray-600">{{ formatTime(event.start) }} – {{ formatTime(event.end) }}</p>
              </div>
              <p v-if="dayInfo.events.length === 0" class="text-xs text-gray-400 py-2">Aucun cours</p>
            </div>
          </div>
        </div>
        <!-- Desktop : grille heure × jours -->
        <div class="hidden md:block overflow-x-auto">
          <div class="grid grid-cols-8 gap-1 min-w-[600px]">
            <div class="p-3 text-center text-sm font-medium text-gray-500 bg-gray-50">
              Heure
            </div>
            <div v-for="day in weekDays" :key="day"
              class="p-3 text-center text-sm font-medium text-gray-500 bg-gray-50">
              {{ day }}
            </div>
          </div>
          <div class="grid grid-cols-8 gap-1 min-w-[600px]">
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
                  :class="['absolute left-1 right-1 p-2 rounded text-xs cursor-pointer hover:opacity-90 transition-opacity overflow-hidden flex flex-col justify-center',
                    event.status === 'confirmed' ? 'bg-blue-100 text-blue-800' :
                    event.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                    event.status === 'cancelled' ? 'bg-red-100 text-red-800' :
                    'bg-gray-100 text-gray-800']"
                  :style="{ top: `${getEventPosition(event, hour)}px`, height: `${getEventHeight(event)}px` }"
                  :title="event.title"
                >
                  <span class="font-medium">{{ formatTime(event.start) }}</span>
                  <span class="truncate block" :class="event.status === 'cancelled' ? 'line-through' : ''">{{ event.title }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vue Jour -->
      <div v-else-if="currentView === 'day'" class="calendar-day">
        <div class="space-y-3 sm:space-y-4">
          <div v-for="hour in dayHours" :key="hour" 
            class="flex items-start space-x-3 sm:space-x-4 p-3 sm:p-4 border border-gray-200 rounded-lg">
            <div class="w-14 sm:w-16 text-xs sm:text-sm text-gray-500 pt-1 shrink-0">
              {{ formatTime(hour) }}
            </div>
            <div class="flex-1 space-y-2 min-w-0">
              <div 
                v-for="event in getEventsForHour(hour)" 
                :key="event.id"
                @click="selectEvent(event)"
                class="min-h-[48px] flex flex-col justify-center"
                :class="['p-3 sm:p-4 rounded-lg cursor-pointer hover:opacity-90 transition-opacity',
                  event.status === 'confirmed' ? 'bg-blue-100 text-blue-800 border border-blue-200' : 
                  event.status === 'pending' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' :
                  event.status === 'cancelled' ? 'bg-red-100 text-red-800 border border-red-200' :
                  'bg-gray-100 text-gray-800 border border-gray-200']"
              >
                <p class="font-medium" :class="event.status === 'cancelled' ? 'line-through' : ''">{{ event.title }}</p>
                <p class="text-xs mt-1 text-gray-600">{{ formatTime(event.start) }} – {{ formatTime(event.end) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de détails d'événement -->
    <div v-if="selectedEvent" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="closeModal">
      <div class="bg-white rounded-lg p-4 sm:p-6 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between gap-2 mb-4">
          <h3 class="text-lg font-semibold text-gray-900 min-w-0">{{ selectedEvent.title }}</h3>
          <button @click="closeModal" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-gray-400 hover:text-gray-600 shrink-0" aria-label="Fermer">
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
          <template v-if="selectedEvent.status === 'cancelled'">
            <div v-if="selectedEvent.cancellation_count_in_subscription !== undefined || selectedEvent.cancellation_reason === 'medical'">
              <span class="text-sm font-medium text-gray-500">Impact abonnement:</span>
              <span
                :class="getCancellationSubscriptionImpactClass(selectedEvent)"
                class="ml-2 inline-block px-2 py-1 text-xs font-medium rounded-full"
              >
                {{ getCancellationSubscriptionImpact(selectedEvent) }}
              </span>
            </div>
            <div v-if="shouldShowCertificateStatusBadge(selectedEvent)">
              <span class="text-sm font-medium text-gray-500">Certificat:</span>
              <span
                :class="getCertificateStatusClass(selectedEvent.cancellation_certificate_status)"
                class="ml-2 inline-block px-2 py-1 text-xs font-medium rounded-full"
              >
                {{ getCertificateStatusLabel(selectedEvent.cancellation_certificate_status) }}
              </span>
            </div>
            <div v-if="selectedEvent.cancellation_certificate_status === 'rejected' || selectedEvent.cancellation_certificate_status === 'closed'" class="mt-2">
              <span class="text-sm font-medium text-gray-500">
                {{ selectedEvent.cancellation_certificate_status === 'rejected' ? 'Motif du refus :' : 'Raison de la clôture :' }}
              </span>
              <p class="mt-1 text-sm text-gray-700">{{ selectedEvent.cancellation_certificate_rejection_reason || '—' }}</p>
              <p v-if="selectedEvent.cancellation_certificate_status === 'rejected'" class="mt-1 text-xs text-blue-600">
                Vous pouvez renvoyer un certificat depuis votre dashboard.
              </p>
            </div>
          </template>
          <div v-if="selectedEvent.description || selectedEvent.notes">
            <span class="text-sm font-medium text-gray-500">Description:</span>
            <p class="mt-1 text-sm text-gray-900">{{ selectedEvent.description || selectedEvent.notes || 'N/A' }}</p>
          </div>
        </div>
        
        <div class="mt-6 flex justify-end">
          <button @click="closeModal" 
            class="min-h-[44px] px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors">
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  getCancellationSubscriptionImpact,
  getCancellationSubscriptionImpactClass,
  getCertificateStatusLabel,
  getCertificateStatusClass,
  shouldShowCertificateStatusBadge,
} from '~/composables/useCancellationLabels'

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

// Masquer le bouton Semaine sur mobile (largeur < 768px)
const isMobile = ref(typeof window !== 'undefined' ? window.innerWidth < 768 : false)
const updateIsMobile = () => {
  if (typeof window !== 'undefined') isMobile.value = window.innerWidth < 768
}
onMounted(() => {
  updateIsMobile()
  if (typeof window !== 'undefined') window.addEventListener('resize', updateIsMobile)
})
onUnmounted(() => {
  if (typeof window !== 'undefined') window.removeEventListener('resize', updateIsMobile)
})
watch(isMobile, (mobile) => {
  if (mobile && currentView.value === 'week') currentView.value = 'month'
})

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

// Pour la vue semaine mobile : 7 jours avec leurs événements
const weekDaysWithEvents = computed(() => {
  const date = currentDate.value
  const startOfWeek = new Date(date)
  startOfWeek.setDate(date.getDate() - date.getDay() + 1)
  startOfWeek.setHours(0, 0, 0, 0)
  const out = []
  for (let i = 0; i < 7; i++) {
    const d = new Date(startOfWeek)
    d.setDate(startOfWeek.getDate() + i)
    const dayEvents = events.value.filter(event => {
      const eventDate = new Date(event.start)
      return eventDate.toDateString() === d.toDateString()
    }).sort((a, b) => new Date(a.start).getTime() - new Date(b.start).getTime())
    out.push({
      dateKey: d.toISOString().split('T')[0],
      dayLabel: weekDays[i],
      dateShort: d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }),
      events: dayEvents
    })
  }
  return out
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

// Chargement des données (vue globale ou un élève selon le store)
const studentScopeStore = useStudentScopeStore()
const resolveParticipantNames = (lesson) => {
  const names = (lesson.participants || [])
    .map(p => p?.user?.name || p?.name)
    .filter(Boolean)
  return Array.from(new Set(names))
}

const loadCalendarEvents = async () => {
  try {
    isLoading.value = true
    const params = { active_student_id: studentScopeStore.apiScopeParam }
    const response = await $api.get('/student/bookings', { params })
    if (response.data.success) {
      const lessons = response.data.data || []
      const scopeLabel = studentScopeStore.apiScopeParam === 'all' ? (lesson) => {
        const participants = resolveParticipantNames(lesson)
        const fallbackName = lesson.student?.user?.name || lesson.student?.name
        const courseName = lesson.course_type?.name || lesson.courseType?.name || 'Cours'
        if (participants.length > 0) {
          return `${courseName} (${participants.join(', ')})`
        }
        return fallbackName ? `${courseName} (${fallbackName})` : courseName
      } : (lesson) => lesson.course_type?.name || lesson.courseType?.name || 'Cours'
      events.value = lessons
        .filter(lesson => lesson.start_time)
        .map(lesson => ({
          id: lesson.id,
          title: scopeLabel(lesson),
          start: lesson.start_time,
          end: lesson.end_time,
          teacher: lesson.teacher,
          location: lesson.location,
          price: lesson.price,
          status: lesson.status,
          description: lesson.notes,
          notes: lesson.notes,
          course_type: lesson.course_type || lesson.courseType,
          type: 'lesson',
          cancellation_certificate_status: lesson.cancellation_certificate_status,
          cancellation_count_in_subscription: lesson.cancellation_count_in_subscription,
          cancellation_reason: lesson.cancellation_reason,
          cancellation_certificate_rejection_reason: lesson.cancellation_certificate_rejection_reason
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
