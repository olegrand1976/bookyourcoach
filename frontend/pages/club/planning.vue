<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Planning des Cours</h1>
            <p class="text-gray-600">Gérez les créneaux, bloquez des plages et affectez les enseignants/élèves</p>
          </div>
          <div class="flex items-center space-x-3">
            <button 
              @click="showOpenSlotModal = true"
              class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors flex items-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <span>Ouvrir créneaux</span>
            </button>
            <button 
              @click="showCreateLessonModal = true"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <span>Nouveau cours</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation semaine -->
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <button 
              @click="previousWeek"
              class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
            <h2 class="text-lg font-semibold text-gray-900">
              {{ formatWeekRange(currentWeek) }}
            </h2>
            <button 
              @click="nextWeek"
              class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </button>
          </div>
          <button 
            @click="goToToday"
            class="bg-gray-100 text-gray-700 px-3 py-1 rounded-lg hover:bg-gray-200 transition-colors text-sm"
          >
            Aujourd'hui
          </button>
        </div>
      </div>
    </div>

    <!-- Planning Calendrier -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- En-têtes des jours -->
        <div class="grid grid-cols-8 bg-gray-50 border-b border-gray-200">
          <div class="p-4 text-center text-sm font-medium text-gray-500">Horaires</div>
          <div v-for="day in weekDays" :key="day.date" class="p-4 text-center border-l border-gray-200">
            <div class="text-sm font-medium text-gray-900">{{ day.name }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ formatDate(day.date) }}</div>
          </div>
        </div>

        <!-- Grille des créneaux -->
        <div class="relative">
          <div v-for="hour in timeSlots" :key="hour" class="grid grid-cols-8 border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <!-- Colonne horaire -->
            <div class="p-4 text-center text-sm text-gray-600 bg-gray-50 border-r border-gray-200">
              {{ hour }}
            </div>
            
            <!-- Créneaux par jour -->
            <div v-for="day in weekDays" :key="`${day.date}-${hour}`" 
                 class="relative border-l border-gray-100 min-h-[60px] group cursor-pointer hover:bg-blue-50 transition-colors"
                 @click="selectSlot(day.date, hour)"
            >
              <!-- Cours existants -->
              <div v-for="lesson in getLessonsForSlot(day.date, hour)" 
                   :key="lesson.id"
                   class="absolute inset-1 rounded p-2 text-xs border-l-4"
                   :class="getLessonClass(lesson)"
              >
                <div class="font-medium truncate">{{ lesson.title }}</div>
                <div class="text-xs opacity-75">{{ lesson.teacher_name }}</div>
                <div class="text-xs opacity-75">{{ lesson.student_name }}</div>
              </div>

            <!-- Créneaux fermés (non ouverts) -->
            <div v-if="!isSlotOpen(day.date, hour)"
                 class="absolute inset-1 bg-gray-100 border-l-4 border-gray-400 rounded p-2 text-xs text-gray-600"
            >
              <div class="font-medium">⏰ Fermé</div>
              <div class="text-xs">Pas ouvert</div>
            </div>

            <!-- Créneaux ouverts (disponibles) -->
            <div v-if="isSlotOpen(day.date, hour) && getLessonsForSlot(day.date, hour).length === 0"
                 class="absolute inset-1 bg-green-50 border-l-4 border-green-400 rounded p-2 text-xs text-green-700"
            >
              <div class="font-medium">✅ Ouvert</div>
              <div class="text-xs">Disponible</div>
            </div>

              <!-- Indicateur de sélection -->
              <div v-if="isSlotSelected(day.date, hour)"
                   class="absolute inset-0 bg-blue-200 bg-opacity-50 border-2 border-blue-400 rounded"
              ></div>

              <!-- Indicateur hover -->
              <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Ouvrir créneaux récurrents -->
    <div v-if="showOpenSlotModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-lg">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ouvrir des créneaux récurrents</h3>
        <p class="text-sm text-gray-600 mb-6">Définissez les créneaux horaires où les cours peuvent avoir lieu de manière récurrente.</p>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Jour(s) de la semaine</label>
            <div class="grid grid-cols-4 gap-2">
              <label v-for="day in weekDaysRecurrence" :key="day.value" class="flex items-center space-x-2 cursor-pointer">
                <input 
                  v-model="openForm.selectedDays"
                  :value="day.value"
                  type="checkbox"
                  class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                >
                <span class="text-sm">{{ day.label }}</span>
              </label>
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Heure début</label>
              <div class="grid grid-cols-2 gap-2">
                <select 
                  v-model="openForm.startHour"
                  class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                >
                  <option v-for="hour in hours" :key="hour" :value="hour">{{ hour }}h</option>
                </select>
                <select 
                  v-model="openForm.startMinute"
                  class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                >
                  <option v-for="minute in minutes" :key="minute" :value="minute">{{ minute }}min</option>
                </select>
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Heure fin</label>
              <div class="grid grid-cols-2 gap-2">
                <select 
                  v-model="openForm.endHour"
                  class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                >
                  <option v-for="hour in hours" :key="hour" :value="hour">{{ hour }}h</option>
                </select>
                <select 
                  v-model="openForm.endMinute"
                  class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                >
                  <option v-for="minute in minutes" :key="minute" :value="minute">{{ minute }}min</option>
                </select>
              </div>
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Durée par cours (minutes)</label>
            <select 
              v-model="openForm.lessonDuration"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
            >
              <option value="30">30 minutes</option>
              <option value="60">60 minutes</option>
              <option value="90">90 minutes</option>
              <option value="120">120 minutes</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description (optionnel)</label>
            <input 
              v-model="openForm.description"
              type="text" 
              placeholder="Ex: Cours de dressage, Cours tous niveaux..."
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
            >
          </div>

          <!-- Aperçu des créneaux -->
          <div v-if="openForm.selectedDays.length > 0 && computedStartTime && computedEndTime" class="bg-green-50 border border-green-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-green-800 mb-2">Aperçu des créneaux :</h4>
            <div class="text-sm text-green-700">
              <div v-for="day in getSelectedDayLabels()" :key="day">
                <strong>{{ day }} :</strong> {{ computedStartTime }} - {{ computedEndTime }} 
                ({{ calculateTimeSlots() }} créneaux de {{ openForm.lessonDuration }}min)
              </div>
            </div>
          </div>
        </div>
        
        <div class="flex items-center justify-end space-x-3 mt-6">
          <button 
            @click="showOpenSlotModal = false"
            class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
          >
            Annuler
          </button>
          <button 
            @click="openRecurrentSlots"
            :disabled="openForm.selectedDays.length === 0 || !computedStartTime || !computedEndTime"
            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Ouvrir les créneaux
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Créer un cours -->
    <div v-if="showCreateLessonModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl my-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Nouveau cours</h3>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input 
              v-model="lessonForm.date"
              type="date" 
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Heure</label>
            <select 
              v-model="lessonForm.time"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option v-for="time in timeSlots" :key="time" :value="time">{{ time }}</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Durée</label>
            <select 
              v-model="lessonForm.duration"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="30">30 minutes</option>
              <option value="60">1 heure</option>
              <option value="90">1h30</option>
              <option value="120">2 heures</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type de cours</label>
            <input 
              v-model="lessonForm.title"
              type="text" 
              placeholder="Ex: Dressage, Saut d'obstacles..."
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Enseignant</label>
            <select 
              v-model="lessonForm.teacherId"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Sélectionner un enseignant</option>
              <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                {{ teacher.name }}
              </option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Élève</label>
            <select 
              v-model="lessonForm.studentId"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Sélectionner un élève</option>
              <option v-for="student in students" :key="student.id" :value="student.id">
                {{ student.name }}
              </option>
            </select>
          </div>
          
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Prix (€)</label>
            <input 
              v-model="lessonForm.price"
              type="number" 
              step="0.01"
              placeholder="50.00"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
          </div>
          
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optionnel)</label>
            <textarea 
              v-model="lessonForm.notes"
              rows="3"
              placeholder="Notes complémentaires..."
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
          </div>
        </div>
        
        <div class="flex items-center justify-end space-x-3 mt-6">
          <button 
            @click="showCreateLessonModal = false"
            class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
          >
            Annuler
          </button>
          <button 
            @click="createLesson"
            :disabled="!lessonForm.date || !lessonForm.time || !lessonForm.teacherId"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Créer le cours
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

definePageMeta({
  middleware: ['auth']
})

// État réactif
const currentWeek = ref(new Date())
const selectedSlot = ref(null)
const lessons = ref([])
const openSlots = ref([]) // Créneaux ouverts récurrents
const teachers = ref([])
const students = ref([])

// Modals
const showOpenSlotModal = ref(false)
const showCreateLessonModal = ref(false)

// Jours de la semaine pour récurrence
const weekDaysRecurrence = [
  { value: 1, label: 'Lun' },
  { value: 2, label: 'Mar' },
  { value: 3, label: 'Mer' },
  { value: 4, label: 'Jeu' },
  { value: 5, label: 'Ven' },
  { value: 6, label: 'Sam' },
  { value: 0, label: 'Dim' }
]

// Formulaires
const openForm = ref({
  selectedDays: [],
  startHour: '08',
  startMinute: '00',
  endHour: '18',
  endMinute: '00',
  lessonDuration: '60',
  description: ''
})

// Computed pour les heures complètes
const computedStartTime = computed(() => `${openForm.value.startHour}:${openForm.value.startMinute}`)
const computedEndTime = computed(() => `${openForm.value.endHour}:${openForm.value.endMinute}`)

const lessonForm = ref({
  date: '',
  time: '',
  duration: '60',
  title: '',
  teacherId: '',
  studentId: '',
  price: '',
  notes: ''
})

// Configuration des créneaux horaires (par 5 minutes)
const generateTimeSlots = () => {
  const slots = []
  for (let hour = 6; hour <= 22; hour++) {
    for (let minute = 0; minute < 60; minute += 5) {
      const timeStr = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`
      slots.push(timeStr)
    }
  }
  return slots
}

const timeSlots = generateTimeSlots()

// Heures et minutes pour les selects
const hours = Array.from({ length: 17 }, (_, i) => (i + 6).toString().padStart(2, '0')) // 06-22
const minutes = Array.from({ length: 12 }, (_, i) => (i * 5).toString().padStart(2, '0')) // 00, 05, 10, ..., 55

// Jours de la semaine courante
const weekDays = computed(() => {
  const start = new Date(currentWeek.value)
  start.setDate(start.getDate() - start.getDay() + 1) // Lundi
  
  const days = []
  for (let i = 0; i < 7; i++) {
    const day = new Date(start)
    day.setDate(start.getDate() + i)
    
    days.push({
      date: day.toISOString().split('T')[0],
      name: day.toLocaleDateString('fr-FR', { weekday: 'short' }),
      dayNumber: day.getDate()
    })
  }
  
  return days
})

// Navigation semaine
const previousWeek = () => {
  const newWeek = new Date(currentWeek.value)
  newWeek.setDate(newWeek.getDate() - 7)
  currentWeek.value = newWeek
  loadPlanningData()
}

const nextWeek = () => {
  const newWeek = new Date(currentWeek.value)
  newWeek.setDate(newWeek.getDate() + 7)
  currentWeek.value = newWeek
  loadPlanningData()
}

const goToToday = () => {
  currentWeek.value = new Date()
  loadPlanningData()
}

// Utilitaires dates
const formatWeekRange = (date) => {
  const start = new Date(date)
  start.setDate(start.getDate() - start.getDay() + 1)
  const end = new Date(start)
  end.setDate(start.getDate() + 6)
  
  return `${start.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })} - ${end.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })}`
}

const formatDate = (dateStr) => {
  const date = new Date(dateStr)
  return date.getDate()
}

// Gestion des créneaux
const selectSlot = (date, hour) => {
  // Vérifier si le créneau est ouvert avant de permettre la création
  if (!isSlotOpen(date, hour)) {
    alert('Ce créneau n\'est pas ouvert. Vous devez d\'abord ouvrir des créneaux récurrents.')
    return
  }
  
  selectedSlot.value = { date, hour }
  lessonForm.value.date = date
  lessonForm.value.time = hour
  showCreateLessonModal.value = true
}

const isSlotSelected = (date, hour) => {
  return selectedSlot.value?.date === date && selectedSlot.value?.hour === hour
}

const isSlotOpen = (date, hour) => {
  const dayOfWeek = new Date(date).getDay()
  
  return openSlots.value.some(slot => {
    // Vérifier si le jour correspond
    if (!slot.days.includes(dayOfWeek)) return false
    
    // Vérifier si l'heure est dans la plage
    return hour >= slot.startTime && hour < slot.endTime
  })
}

// Helpers pour la modal d'ouverture
const getSelectedDayLabels = () => {
  return openForm.value.selectedDays.map(dayValue => 
    weekDaysRecurrence.find(day => day.value === dayValue)?.label
  ).filter(Boolean)
}

const calculateTimeSlots = () => {
  if (!computedStartTime.value || !computedEndTime.value) return 0
  
  const start = timeToMinutes(computedStartTime.value)
  const end = timeToMinutes(computedEndTime.value)
  const duration = parseInt(openForm.value.lessonDuration)
  
  return Math.floor((end - start) / duration)
}

const timeToMinutes = (time) => {
  const [hours, minutes] = time.split(':').map(Number)
  return hours * 60 + minutes
}

const getLessonsForSlot = (date, hour) => {
  return lessons.value.filter(lesson => {
    const lessonDate = lesson.start_time.split(' ')[0]
    const lessonHour = lesson.start_time.split(' ')[1].substring(0, 5)
    return lessonDate === date && lessonHour === hour
  })
}

const getLessonClass = (lesson) => {
  const statusClasses = {
    'confirmed': 'bg-green-100 border-green-500 text-green-800',
    'pending': 'bg-yellow-100 border-yellow-500 text-yellow-800',
    'completed': 'bg-blue-100 border-blue-500 text-blue-800',
    'cancelled': 'bg-gray-100 border-gray-500 text-gray-800'
  }
  return statusClasses[lesson.status] || 'bg-gray-100 border-gray-500 text-gray-800'
}

// Actions
const openRecurrentSlots = async () => {
  try {
    console.log('✅ Ouverture des créneaux récurrents:', openForm.value)
    
    // Ajouter localement (en attendant l'API backend)
    const newOpenSlot = {
      id: Date.now(), // ID temporaire
      days: [...openForm.value.selectedDays],
      startTime: computedStartTime.value,
      endTime: computedEndTime.value,
      lessonDuration: parseInt(openForm.value.lessonDuration),
      description: openForm.value.description,
      isActive: true
    }
    
    openSlots.value.push(newOpenSlot)
    
    // TODO: Appeler l'API backend pour persister
    
    showOpenSlotModal.value = false
    openForm.value = {
      selectedDays: [],
      startHour: '08',
      startMinute: '00',
      endHour: '18',
      endMinute: '00',
      lessonDuration: '60',
      description: ''
    }
    
    console.log('✅ Créneaux ouverts avec succès')
    
  } catch (error) {
    console.error('Erreur lors de l\'ouverture des créneaux:', error)
  }
}

const createLesson = async () => {
  try {
    console.log('📝 Création du cours:', lessonForm.value)
    
    // Vérifier à nouveau que le créneau est ouvert
    if (!isSlotOpen(lessonForm.value.date, lessonForm.value.time)) {
      alert('Ce créneau n\'est plus ouvert pour les cours.')
      return
    }
    
    const { $api } = useNuxtApp()
    
    // Construire les données du cours
    const lessonData = {
      title: lessonForm.value.title,
      start_time: `${lessonForm.value.date} ${lessonForm.value.time}:00`,
      duration: parseInt(lessonForm.value.duration),
      teacher_id: lessonForm.value.teacherId,
      student_id: lessonForm.value.studentId || null,
      price: parseFloat(lessonForm.value.price),
      notes: lessonForm.value.notes,
      status: 'confirmed'
    }
    
    const response = await $api.post('/lessons', lessonData)
    
    if (response.data.success) {
      console.log('✅ Cours créé avec succès')
      await loadPlanningData() // Recharger les données
      showCreateLessonModal.value = false
      lessonForm.value = { date: '', time: '', duration: '60', title: '', teacherId: '', studentId: '', price: '', notes: '' }
    }
    
  } catch (error) {
    console.error('Erreur lors de la création du cours:', error)
  }
}

// Chargement des données
const loadPlanningData = async () => {
  try {
    const { $api } = useNuxtApp()
    
    // Charger les cours de la semaine
    const startDate = weekDays.value[0].date
    const endDate = weekDays.value[6].date
    
    const lessonsResponse = await $api.get(`/lessons?date_from=${startDate}&date_to=${endDate}`)
    if (lessonsResponse.data.success) {
      lessons.value = lessonsResponse.data.data
    }
    
  } catch (error) {
    console.error('Erreur lors du chargement du planning:', error)
  }
}

const loadTeachersAndStudents = async () => {
  try {
    const { $api } = useNuxtApp()
    
    // Charger les enseignants
    const teachersResponse = await $api.get('/club/teachers')
    if (teachersResponse.data.success) {
      teachers.value = teachersResponse.data.data
    }
    
    // Charger les élèves
    const studentsResponse = await $api.get('/club/students')
    if (studentsResponse.data.success) {
      students.value = studentsResponse.data.data
    }
    
  } catch (error) {
    console.error('Erreur lors du chargement des enseignants/élèves:', error)
  }
}

// Initialisation
onMounted(async () => {
  console.log('🚀 Initialisation du planning club')
  await Promise.all([
    loadPlanningData(),
    loadTeachersAndStudents()
  ])
})
</script>
