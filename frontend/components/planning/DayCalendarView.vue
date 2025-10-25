<template>
  <div v-if="selectedSlot" class="bg-white rounded-lg shadow-lg p-6">
    <!-- Header avec navigation -->
    <div class="flex items-center justify-between mb-6">
      <button 
        @click="previousDay"
        class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      
      <div class="text-center">
        <h2 class="text-2xl font-bold text-gray-900">
          {{ formatDate(currentDate) }}
        </h2>
        <p class="text-sm text-gray-600 mt-1">
          {{ selectedSlot.discipline?.name }} - {{ formatTimeRange(selectedSlot.start_time, selectedSlot.end_time) }}
        </p>
      </div>
      
      <button 
        @click="nextDay"
        class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>

    <!-- Bouton retour -->
    <button 
      @click="$emit('close')"
      class="mb-4 text-blue-600 hover:text-blue-800 flex items-center gap-2">
      ← Retour aux créneaux
    </button>

    <!-- Grille des plages horaires -->
    <div class="grid gap-4" :style="gridStyle">
      <div 
        v-for="timeSlot in timeSlots" 
        :key="timeSlot.start"
        class="border border-gray-200 rounded-lg p-4 bg-gray-50">
        
        <!-- En-tête de la plage horaire -->
        <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-300">
          <h3 class="font-semibold text-gray-700">
            {{ timeSlot.start }} - {{ timeSlot.end }}
          </h3>
          <button 
            @click="createLesson(timeSlot)"
            class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            + Créer un cours
          </button>
        </div>

        <!-- Liste des cours dans cette plage -->
        <div class="space-y-2">
          <div 
            v-for="lesson in getLessonsForTimeSlot(timeSlot)" 
            :key="lesson.id"
            @click="$emit('select-lesson', lesson)"
            class="bg-white p-3 rounded border border-gray-200 hover:border-blue-300 cursor-pointer transition-colors">
            
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                  <span class="text-sm font-medium text-gray-900">
                    {{ formatLessonTime(lesson.start_time) }}
                  </span>
                  <span :class="getStatusBadgeClass(lesson.status)" class="text-xs px-2 py-0.5 rounded">
                    {{ getStatusLabel(lesson.status) }}
                  </span>
                </div>
                
                <p class="text-sm text-gray-600">
                  👤 {{ lesson.student?.user?.name || 'Sans élève' }}
                  <span v-if="lesson.student?.age" class="text-xs text-gray-500 ml-1">({{ lesson.student.age }} ans)</span>
                </p>
                <p class="text-xs text-gray-500">
                  🎓 {{ lesson.teacher?.user?.name }}
                </p>
                <p class="text-xs text-gray-500">
                  📚 {{ lesson.course_type?.name }} ({{ lesson.duration }}min)
                </p>
              </div>
              
              <div class="text-right">
                <p class="text-sm font-semibold text-gray-900">
                  {{ lesson.price }}€
                </p>
              </div>
            </div>
          </div>

          <!-- Message si aucun cours -->
          <p v-if="getLessonsForTimeSlot(timeSlot).length === 0" class="text-sm text-gray-400 text-center py-4">
            Aucun cours prévu
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

interface Slot {
  id: number
  day_of_week: number
  start_time: string
  end_time: string
  discipline?: {
    id: number
    name: string
  }
  duration?: number
}

interface Lesson {
  id: number
  start_time: string
  end_time: string
  duration: number
  price: number
  status: string
  student?: {
    user?: {
      name: string
    }
  }
  teacher?: {
    user?: {
      name: string
    }
  }
  course_type?: {
    name: string
  }
}

interface TimeSlot {
  start: string
  end: string
  startMinutes: number
  endMinutes: number
}

const props = defineProps<{
  selectedSlot: Slot | null
  lessons: Lesson[]
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'create-lesson', timeSlot: TimeSlot, date: string): void
  (e: 'select-lesson', lesson: Lesson): void
}>()

const currentDate = ref<Date>(new Date())

// Calculer la prochaine occurrence du jour de la semaine du créneau
watch(() => props.selectedSlot, (slot) => {
  if (slot) {
    const today = new Date()
    const targetDay = slot.day_of_week
    const currentDay = today.getDay()
    
    let daysToAdd = targetDay - currentDay
    if (daysToAdd < 0) {
      daysToAdd += 7
    }
    
    const nextOccurrence = new Date(today)
    nextOccurrence.setDate(today.getDate() + daysToAdd)
    currentDate.value = nextOccurrence
  }
}, { immediate: true })

// Créer les plages horaires (par exemple toutes les 20-30-60 minutes selon la durée du créneau)
const timeSlots = computed<TimeSlot[]>(() => {
  if (!props.selectedSlot) return []
  
  const slots: TimeSlot[] = []
  const [startHour, startMin] = props.selectedSlot.start_time.split(':').map(Number)
  const [endHour, endMin] = props.selectedSlot.end_time.split(':').map(Number)
  
  const startMinutes = startHour * 60 + startMin
  const endMinutes = endHour * 60 + endMin
  
  // Utiliser la durée du créneau ou par défaut 30 minutes
  const slotDuration = props.selectedSlot.duration || 30
  
  let currentMinutes = startMinutes
  while (currentMinutes < endMinutes) {
    const nextMinutes = Math.min(currentMinutes + slotDuration, endMinutes)
    
    const startH = Math.floor(currentMinutes / 60)
    const startM = currentMinutes % 60
    const endH = Math.floor(nextMinutes / 60)
    const endM = nextMinutes % 60
    
    slots.push({
      start: `${String(startH).padStart(2, '0')}:${String(startM).padStart(2, '0')}`,
      end: `${String(endH).padStart(2, '0')}:${String(endM).padStart(2, '0')}`,
      startMinutes: currentMinutes,
      endMinutes: nextMinutes
    })
    
    currentMinutes = nextMinutes
  }
  
  return slots
})

// Style de grille dynamique selon le nombre de plages
const gridStyle = computed(() => {
  const count = timeSlots.value.length
  if (count <= 2) {
    return { gridTemplateColumns: `repeat(${count}, 1fr)` }
  } else if (count <= 4) {
    return { gridTemplateColumns: 'repeat(2, 1fr)' }
  } else {
    return { gridTemplateColumns: 'repeat(3, 1fr)' }
  }
})

function previousDay() {
  const newDate = new Date(currentDate.value)
  newDate.setDate(newDate.getDate() - 7) // Semaine précédente (même jour)
  currentDate.value = newDate
}

function nextDay() {
  const newDate = new Date(currentDate.value)
  newDate.setDate(newDate.getDate() + 7) // Semaine suivante (même jour)
  currentDate.value = newDate
}

function formatDate(date: Date): string {
  const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
  const months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 
                  'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre']
  
  return `${days[date.getDay()]} ${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`
}

function formatTimeRange(start: string, end: string): string {
  return `${start.substring(0, 5)} - ${end.substring(0, 5)}`
}

function formatLessonTime(datetime: string): string {
  const date = new Date(datetime)
  return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`
}

function getLessonsForTimeSlot(timeSlot: TimeSlot): Lesson[] {
  if (!props.lessons) return []
  
  const dateStr = currentDate.value.toISOString().split('T')[0]
  
  return props.lessons.filter(lesson => {
    const lessonDate = lesson.start_time.split('T')[0]
    if (lessonDate !== dateStr) return false
    
    const lessonTime = new Date(lesson.start_time)
    const lessonMinutes = lessonTime.getHours() * 60 + lessonTime.getMinutes()
    
    return lessonMinutes >= timeSlot.startMinutes && lessonMinutes < timeSlot.endMinutes
  })
}

function createLesson(timeSlot: TimeSlot) {
  const dateStr = currentDate.value.toISOString().split('T')[0]
  emit('create-lesson', timeSlot, dateStr)
}

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    'confirmed': '✓ Confirmé',
    'pending': '⏳ Attente',
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
    'completed': 'bg-blue-100 text-blue-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>

