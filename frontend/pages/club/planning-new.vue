<template>
  <div class="min-h-screen bg-gray-50 flex">
    <!-- Panneau latéral - Gestion des créneaux disponibles -->
    <div class="w-80 bg-white border-r border-gray-200 overflow-y-auto">
      <div class="p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Créneaux disponibles</h2>
        <p class="text-sm text-gray-600 mb-4">
          Définissez les horaires où les cours peuvent avoir lieu
        </p>

        <!-- Bouton ajouter un créneau -->
        <button
          @click="showAddSlotModal = true"
          class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center space-x-2 mb-6">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          <span>Ajouter un créneau</span>
        </button>

        <!-- Liste des créneaux -->
        <div class="space-y-3">
          <div
            v-for="slot in availableSlots"
            :key="slot.id"
            class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
            <div class="flex items-start justify-between mb-2">
              <div class="flex-1">
                <div class="font-semibold text-gray-900">{{ getDayName(slot.day_of_week) }}</div>
                <div class="text-sm text-gray-600">
                  {{ slot.start_time }} - {{ slot.end_time }}
                </div>
                <div class="text-xs text-blue-600 font-medium mt-1">
                  {{ slot.discipline_name }}
                </div>
              </div>
              <button
                @click="deleteSlot(slot.id)"
                class="text-red-500 hover:text-red-700 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
              </button>
            </div>

            <!-- Jauge de capacité -->
            <div class="mt-3">
              <div class="flex items-center justify-between text-xs mb-1">
                <span class="text-gray-600">Capacité</span>
                <span class="font-medium"
                      :class="getUsedSlots(slot) >= slot.max_capacity ? 'text-red-600' : 'text-gray-900'">
                  {{ getUsedSlots(slot) }} / {{ slot.max_capacity }} cours
                </span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                  class="h-2 rounded-full transition-all"
                  :class="getUsedSlots(slot) >= slot.max_capacity ? 'bg-red-500' : 'bg-blue-600'"
                  :style="{ width: `${(getUsedSlots(slot) / slot.max_capacity) * 100}%` }">
                </div>
              </div>
            </div>

            <!-- Badge durée -->
            <div class="mt-2 flex items-center gap-2">
              <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">
                Durée: {{ slot.duration }}min
              </span>
              <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">
                Prix: {{ slot.price }}€
              </span>
            </div>
          </div>

          <!-- Message si pas de créneaux -->
          <div v-if="availableSlots.length === 0" class="text-center py-8">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-sm text-gray-500">Aucun créneau disponible</p>
            <p class="text-xs text-gray-400 mt-1">Ajoutez un créneau pour commencer</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Zone principale - Calendrier -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Header -->
      <div class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Planning des Cours</h1>
            <p class="text-sm text-gray-600 mt-1">Cliquez sur le calendrier pour ajouter un cours</p>
          </div>
          <button
            @click="showCreateLessonModal = true"
            class="bg-emerald-600 text-white px-6 py-2 rounded-lg hover:bg-emerald-700 transition-colors flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span>Nouveau cours</span>
          </button>
        </div>
      </div>

      <!-- Navigation semaine -->
      <div class="bg-white border-b border-gray-200 px-6 py-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <button @click="previousWeek" class="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg hover:bg-gray-100">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
            <h2 class="text-lg font-semibold text-gray-900">
              {{ formatWeekRange(currentWeek) }}
            </h2>
            <button @click="nextWeek" class="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg hover:bg-gray-100">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </button>
          </div>
          <button
            @click="goToToday"
            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
            Aujourd'hui
          </button>
        </div>
      </div>

      <!-- Calendrier -->
      <div class="flex-1 overflow-auto p-6">
        <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
          <!-- En-têtes des jours -->
          <div class="grid grid-cols-8 bg-gradient-to-b from-gray-50 to-gray-100 border-b-2 border-gray-300 sticky top-0 z-10">
            <div class="p-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">
              Horaires
            </div>
            <div v-for="day in weekDays" :key="day.date"
                 class="p-4 text-center border-l border-gray-300"
                 :class="isToday(day.date) ? 'bg-blue-50' : ''">
              <div class="text-sm font-semibold text-gray-900 uppercase">{{ day.name }}</div>
              <div class="text-lg font-bold mt-1" :class="isToday(day.date) ? 'text-blue-600' : 'text-gray-700'">
                {{ formatDate(day.date) }}
              </div>
              <div v-if="isToday(day.date)" class="text-xs text-blue-600 font-medium mt-1">
                Aujourd'hui
              </div>
            </div>
          </div>

          <!-- Grille horaire -->
          <div class="relative" :style="{ height: `${hourRanges.length * 60}px` }">
            <!-- Lignes horaires -->
            <div v-for="(hour, index) in hourRanges" :key="`hour-${hour}`"
                 class="absolute left-0 right-0 border-b border-gray-100"
                 :style="{ top: `${index * 60}px`, height: '60px' }">

              <div class="grid grid-cols-8 h-full">
                <!-- Colonne horaire -->
                <div class="relative bg-gray-50/50 border-r border-gray-200">
                  <span class="absolute -top-2 right-2 text-xs font-medium text-gray-500 bg-white px-1">
                    {{ hour }}:00
                  </span>
                </div>

                <!-- Colonnes des jours -->
                <div v-for="(day, dayIndex) in weekDays" :key="`grid-${day.date}-${hour}`"
                     class="relative border-l border-gray-100 hover:bg-blue-50/10 transition-colors cursor-pointer group"
                     @click="selectTimeSlot(day.date, hour, 0)"
                     :class="{ 'bg-blue-50/5': isToday(day.date) }">

                  <!-- Ligne de 30 minutes -->
                  <div class="absolute top-1/2 left-0 right-0 border-t border-gray-50"></div>

                  <!-- Indicateur "+" au hover -->
                  <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                    <div class="w-8 h-8 bg-emerald-500/90 rounded-full flex items-center justify-center shadow-lg">
                      <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                      </svg>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Cours - positionnés en absolu -->
            <div v-for="(day, dayIndex) in weekDays" :key="`lessons-${day.date}`"
                 class="absolute top-0 pointer-events-none"
                 :style="{
                   left: `${((dayIndex + 1) / 8) * 100}%`,
                   width: `${(1 / 8) * 100}%`,
                   height: '100%'
                 }">

              <div v-for="lesson in getLessonsForDayWithColumns(day.date)"
                   :key="lesson.id"
                   class="absolute rounded-xl border-l-4 shadow-lg hover:shadow-2xl transition-all cursor-pointer z-20 overflow-hidden pointer-events-auto"
                   :class="[
                     getLessonClass(lesson),
                     lesson.totalColumns > 2 ? 'p-1.5 text-[10px]' : 'p-3 text-xs'
                   ]"
                   :style="getLessonPositionWithColumns(lesson)"
                   @click.stop="viewLesson(lesson)"
                   :title="`${lesson.title} - ${getLessonTime(lesson)}`">

                <div class="font-bold truncate mb-1 flex items-center gap-1">
                  <span class="flex-1 truncate">{{ lesson.title }}</span>
                </div>

                <div v-if="lesson.totalColumns <= 2" class="text-[11px] opacity-80 font-medium truncate mb-1">
                  ⏰ {{ getLessonTime(lesson) }}
                </div>

                <div v-if="lesson.teacher_name && lesson.totalColumns <= 2" class="text-[11px] opacity-75 truncate">
                  👨‍🏫 {{ lesson.teacher_name }}
                </div>

                <div v-if="lesson.student_name && lesson.totalColumns === 1" class="text-[11px] opacity-75 truncate mt-0.5">
                  👤 {{ lesson.student_name }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal : Ajouter un créneau disponible -->
    <div v-if="showAddSlotModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Ajouter un créneau disponible</h3>

        <div class="space-y-4">
          <!-- Jour -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Jour de la semaine</label>
            <select v-model="slotForm.day_of_week" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <option value="1">Lundi</option>
              <option value="2">Mardi</option>
              <option value="3">Mercredi</option>
              <option value="4">Jeudi</option>
              <option value="5">Vendredi</option>
              <option value="6">Samedi</option>
              <option value="0">Dimanche</option>
            </select>
          </div>

          <!-- Heures -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heure début</label>
              <input v-model="slotForm.start_time" type="time" required
                     class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heure fin</label>
              <input v-model="slotForm.end_time" type="time" required
                     class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
          </div>

          <!-- Discipline -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type de cours</label>
            <select v-model="slotForm.discipline_id" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <option value="">Sélectionner...</option>
              <option v-for="discipline in availableDisciplines" :key="discipline.id" :value="discipline.id">
                {{ discipline.name }}
              </option>
            </select>
          </div>

          <!-- Capacité max -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Nombre maximum de cours simultanés
            </label>
            <input v-model.number="slotForm.max_capacity" type="number" min="1" max="10" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">
              Nombre de cours pouvant avoir lieu en même temps sur ce créneau
            </p>
          </div>

          <!-- Durée -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Durée du cours (minutes)</label>
            <input v-model.number="slotForm.duration" type="number" min="15" step="5" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
          </div>

          <!-- Prix -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Prix (€)</label>
            <input v-model.number="slotForm.price" type="number" min="0" step="0.01" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
          </div>
        </div>

        <div class="flex items-center justify-end space-x-3 mt-6">
          <button @click="showAddSlotModal = false"
                  class="px-4 py-2 text-gray-600 hover:text-gray-800">
            Annuler
          </button>
          <button @click="saveSlot"
                  class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            Enregistrer
          </button>
        </div>
      </div>
    </div>

    <!-- Modal : Créer un cours (existant simplifié) -->
    <div v-if="showCreateLessonModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl my-8">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Nouveau cours</h3>

        <div class="grid grid-cols-2 gap-4">
          <!-- Date et heure -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input v-model="lessonForm.date" type="date" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Heure</label>
            <input v-model="lessonForm.time" type="time" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
          </div>

          <!-- Durée -->
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Durée (minutes)</label>
            <div class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-700">
              {{ lessonForm.duration }} minutes
            </div>
            <p class="text-xs text-gray-500 mt-1">La durée est définie par le créneau sélectionné</p>
          </div>

          <!-- Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type de cours</label>
            <div class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-700">
              {{ lessonForm.disciplineName || 'Défini par le créneau' }}
            </div>
            <p class="text-xs text-gray-500 mt-1">Le type de cours est défini par le créneau sélectionné</p>
          </div>
          
          <!-- Titre -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Titre du cours</label>
            <input v-model="lessonForm.title" type="text" required
                   placeholder="Ex: Natation débutant"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
          </div>

          <!-- Prix -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Prix (€)</label>
            <div class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 font-semibold">
              {{ lessonForm.price }}€
            </div>
            <p class="text-xs text-gray-500 mt-1">Le prix est fixé automatiquement selon le créneau</p>
          </div>

          <!-- Enseignant -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Enseignant</label>
            <select v-model="lessonForm.teacherId" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <option value="">Sélectionner...</option>
              <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                {{ teacher.name }}
              </option>
            </select>
          </div>

          <!-- Élève -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Élève (optionnel)</label>
            <select v-model="lessonForm.studentId"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <option value="">Aucun</option>
              <option v-for="student in students" :key="student.id" :value="student.id">
                {{ student.name }}
              </option>
            </select>
          </div>

          <!-- Notes -->
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optionnel)</label>
            <textarea v-model="lessonForm.notes" rows="2"
                      placeholder="Notes complémentaires..."
                      class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
          </div>

          <!-- Avertissement si créneau plein -->
          <div v-if="isSlotFull(lessonForm.date, lessonForm.time)" class="col-span-2 bg-red-50 border border-red-200 rounded-lg p-3">
            <div class="flex items-center text-red-800">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>
              <span class="text-sm font-medium">
                Attention : Ce créneau est complet ({{ getSlotCapacityInfo(lessonForm.date, lessonForm.time) }})
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end space-x-3 mt-6">
          <button @click="showCreateLessonModal = false"
                  class="px-4 py-2 text-gray-600 hover:text-gray-800">
            Annuler
          </button>
          <button @click="createLesson"
                  :disabled="!lessonForm.date || !lessonForm.time || !lessonForm.teacherId || isSlotFull(lessonForm.date, lessonForm.time)"
                  class="bg-emerald-600 text-white px-6 py-2 rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed">
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

// État
const currentWeek = ref(new Date())
const lessons = ref([])
const availableSlots = ref([]) // Créneaux disponibles configurés
const teachers = ref([])
const students = ref([])
const availableDisciplines = ref([])
const clubProfile = ref(null)

// Modals
const showAddSlotModal = ref(false)
const showCreateLessonModal = ref(false)

// Formulaires
const slotForm = ref({
  day_of_week: '1',
  start_time: '09:00',
  end_time: '10:00',
  discipline_id: '',
  max_capacity: 3,
  duration: 60,
  price: 25
})

const lessonForm = ref({
  date: '',
  time: '',
  duration: '60',
  title: '',
  teacherId: '',
  studentId: '',
  price: '',
  notes: '',
  disciplineId: '',
  disciplineName: ''
})

// Heures affichées
const hourRanges = computed(() => {
  return Array.from({ length: 17 }, (_, i) => i + 6) // 6h-22h
})

// Jours de la semaine
const weekDays = computed(() => {
  const start = new Date(currentWeek.value)
  start.setDate(start.getDate() - start.getDay() + 1)

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

// Fonctions utilitaires
const getDayName = (dayOfWeek) => {
  const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
  return days[dayOfWeek]
}

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

const isToday = (dateStr) => {
  const today = new Date()
  const date = new Date(dateStr)
  return today.toISOString().split('T')[0] === date.toISOString().split('T')[0]
}

// Navigation
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

// Gestion des créneaux disponibles
const getUsedSlots = (slot) => {
  // Compter combien de cours utilisent ce créneau
  return lessons.value.filter(lesson => {
    const lessonDate = new Date(lesson.start_time)
    const lessonDay = lessonDate.getDay()
    const lessonTime = lessonDate.toTimeString().substring(0, 5)

    return lessonDay === slot.day_of_week &&
           lessonTime >= slot.start_time &&
           lessonTime < slot.end_time
  }).length
}

const saveSlot = async () => {
  try {
    // Trouver la discipline
    const discipline = availableDisciplines.value.find(d => d.id === parseInt(slotForm.value.discipline_id))

    const newSlot = {
      id: Date.now(),
      ...slotForm.value,
      discipline_name: discipline?.name || ''
    }

    availableSlots.value.push(newSlot)

    // TODO: Sauvegarder dans la base de données

    showAddSlotModal.value = false
    slotForm.value = {
      day_of_week: '1',
      start_time: '09:00',
      end_time: '10:00',
      discipline_id: '',
      max_capacity: 3,
      duration: 60,
      price: 25
    }
  } catch (error) {
    console.error('Erreur lors de la sauvegarde du créneau:', error)
  }
}

const deleteSlot = (slotId) => {
  if (confirm('Voulez-vous vraiment supprimer ce créneau ?')) {
    availableSlots.value = availableSlots.value.filter(s => s.id !== slotId)
    // TODO: Supprimer de la base de données
  }
}

// Gestion des cours
const selectTimeSlot = (date, hour, minute) => {
  const timeStr = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`

  // Trouver le créneau correspondant à cette date/heure
  const dayOfWeek = new Date(date).getDay()
  const slot = availableSlots.value.find(s =>
    s.day_of_week === dayOfWeek &&
    timeStr >= s.start_time &&
    timeStr < s.end_time
  )

  if (!slot) {
    alert('Aucun créneau n\'est configuré pour cet horaire. Veuillez d\'abord ajouter un créneau disponible.')
    return
  }

  // Vérifier si le créneau n'est pas plein
  if (getUsedSlots(slot) >= slot.max_capacity) {
    alert('Ce créneau est complet. Impossible d\'ajouter un nouveau cours.')
    return
  }

  lessonForm.value.date = date
  lessonForm.value.time = timeStr
  lessonForm.value.duration = slot.duration.toString()
  lessonForm.value.price = slot.price.toString()
  lessonForm.value.disciplineId = slot.discipline_id || ''
  lessonForm.value.disciplineName = slot.discipline_name || ''
  
  showCreateLessonModal.value = true
}

const isSlotFull = (date, time) => {
  if (!date || !time) return false

  const lessonDate = new Date(`${date}T${time}`)
  const dayOfWeek = lessonDate.getDay()

  // Trouver le créneau correspondant
  const slot = availableSlots.value.find(s =>
    s.day_of_week === dayOfWeek &&
    time >= s.start_time &&
    time < s.end_time
  )

  if (!slot) return false

  return getUsedSlots(slot) >= slot.max_capacity
}

const getSlotCapacityInfo = (date, time) => {
  if (!date || !time) return ''

  const lessonDate = new Date(`${date}T${time}`)
  const dayOfWeek = lessonDate.getDay()

  const slot = availableSlots.value.find(s =>
    s.day_of_week === dayOfWeek &&
    time >= s.start_time &&
    time < s.end_time
  )

  if (!slot) return 'Pas de créneau configuré'

  return `${getUsedSlots(slot)}/${slot.max_capacity} cours`
}

const createLesson = async () => {
  try {
    const { $api } = useNuxtApp()

    const lessonData = {
      title: lessonForm.value.title,
      start_time: `${lessonForm.value.date} ${lessonForm.value.time}:00`,
      duration: parseInt(lessonForm.value.duration),
      teacher_id: parseInt(lessonForm.value.teacherId),
      student_id: lessonForm.value.studentId ? parseInt(lessonForm.value.studentId) : null,
      discipline_id: lessonForm.value.disciplineId ? parseInt(lessonForm.value.disciplineId) : null,
      location_id: 1,
      price: parseFloat(lessonForm.value.price),
      notes: lessonForm.value.notes,
      status: 'confirmed'
    }

    const response = await $api.post('/lessons', lessonData)

    if (response.data.success) {
      await loadPlanningData()
      showCreateLessonModal.value = false
      lessonForm.value = {
        date: '',
        time: '',
        duration: '60',
        title: '',
        teacherId: '',
        studentId: '',
        price: '',
        notes: '',
        disciplineId: '',
        disciplineName: ''
      }
    }
  } catch (error) {
    console.error('Erreur lors de la création du cours:', error)
    alert('Erreur lors de la création du cours. Veuillez réessayer.')
  }
}

const viewLesson = (lesson) => {
  console.log('Voir le cours:', lesson)
  alert(`Cours: ${lesson.title}\nEnseignant: ${lesson.teacher_name}\nÉlève: ${lesson.student_name || 'Non assigné'}\nStatut: ${lesson.status}`)
}

// Positionnement des cours (réutilisé)
const getLessonsForDay = (date) => {
  return lessons.value.filter(lesson => {
    if (!lesson.start_time) return false

    let lessonDate

    if (lesson.start_time.includes('T')) {
      lessonDate = lesson.start_time.split('T')[0]
    } else if (lesson.start_time.includes(' ')) {
      lessonDate = lesson.start_time.split(' ')[0]
    } else {
      const lessonDateTime = new Date(lesson.start_time)
      if (isNaN(lessonDateTime.getTime())) return false
      lessonDate = lessonDateTime.toISOString().split('T')[0]
    }

    return lessonDate === date
  })
}

const getLessonStartMinutes = (lesson) => {
  let startHour, startMinute

  if (lesson.start_time.includes('T')) {
    const timePart = lesson.start_time.split('T')[1]
    const [h, m] = timePart.substring(0, 5).split(':')
    startHour = parseInt(h)
    startMinute = parseInt(m)
  } else if (lesson.start_time.includes(' ')) {
    const timePart = lesson.start_time.split(' ')[1]
    const [h, m] = timePart.substring(0, 5).split(':')
    startHour = parseInt(h)
    startMinute = parseInt(m)
  } else {
    const lessonDateTime = new Date(lesson.start_time)
    startHour = lessonDateTime.getHours()
    startMinute = lessonDateTime.getMinutes()
  }

  return startHour * 60 + startMinute
}

const lessonsOverlap = (lesson1, lesson2) => {
  const start1 = getLessonStartMinutes(lesson1)
  const end1 = start1 + lesson1.duration
  const start2 = getLessonStartMinutes(lesson2)
  const end2 = start2 + lesson2.duration

  return start1 < end2 && start2 < end1
}

const getLessonsForDayWithColumns = (date) => {
  const dayLessons = getLessonsForDay(date)

  if (dayLessons.length === 0) return []

  const sortedLessons = [...dayLessons].sort((a, b) => {
    return getLessonStartMinutes(a) - getLessonStartMinutes(b)
  })

  const lessonsWithColumns = []

  sortedLessons.forEach(lesson => {
    const overlappingLessons = lessonsWithColumns.filter(l =>
      lessonsOverlap(lesson, l)
    )

    if (overlappingLessons.length === 0) {
      lessonsWithColumns.push({
        ...lesson,
        column: 0,
        totalColumns: 1
      })
    } else {
      const usedColumns = overlappingLessons.map(l => l.column)
      let column = 0
      while (usedColumns.includes(column)) {
        column++
      }

      const maxColumn = Math.max(...overlappingLessons.map(l => l.column), column)
      const totalColumns = maxColumn + 1

      overlappingLessons.forEach(l => {
        l.totalColumns = totalColumns
      })

      lessonsWithColumns.push({
        ...lesson,
        column,
        totalColumns
      })
    }
  })

  return lessonsWithColumns
}

const getLessonPositionWithColumns = (lesson) => {
  if (!lesson.start_time || !lesson.duration) return { top: '0px', height: '60px', left: '4px', width: 'calc(100% - 8px)' }

  let startHour, startMinute

  if (lesson.start_time.includes('T')) {
    const timePart = lesson.start_time.split('T')[1]
    const [h, m] = timePart.substring(0, 5).split(':')
    startHour = parseInt(h)
    startMinute = parseInt(m)
  } else if (lesson.start_time.includes(' ')) {
    const timePart = lesson.start_time.split(' ')[1]
    const [h, m] = timePart.substring(0, 5).split(':')
    startHour = parseInt(h)
    startMinute = parseInt(m)
  } else {
    const lessonDateTime = new Date(lesson.start_time)
    startHour = lessonDateTime.getHours()
    startMinute = lessonDateTime.getMinutes()
  }

  const calendarStartHour = hourRanges.value[0] || 6
  const offsetMinutes = (startHour - calendarStartHour) * 60 + startMinute
  const pixelsPerMinute = 60 / 60
  const top = offsetMinutes * pixelsPerMinute
  const height = lesson.duration * pixelsPerMinute

  const column = lesson.column || 0
  const totalColumns = lesson.totalColumns || 1

  const widthPercent = 100 / totalColumns
  const leftPercent = column * widthPercent
  const gapPx = 2

  return {
    top: `${top}px`,
    height: `${Math.max(height, 40)}px`,
    left: `calc(${leftPercent}% + ${gapPx}px)`,
    width: `calc(${widthPercent}% - ${gapPx * 2}px)`
  }
}

const getLessonTime = (lesson) => {
  if (!lesson.start_time || !lesson.duration) return ''

  let startHour, startMinute

  if (lesson.start_time.includes('T')) {
    const timePart = lesson.start_time.split('T')[1]
    const [h, m] = timePart.substring(0, 5).split(':')
    startHour = parseInt(h)
    startMinute = parseInt(m)
  } else if (lesson.start_time.includes(' ')) {
    const timePart = lesson.start_time.split(' ')[1]
    const [h, m] = timePart.substring(0, 5).split(':')
    startHour = parseInt(h)
    startMinute = parseInt(m)
  } else {
    const lessonDateTime = new Date(lesson.start_time)
    startHour = lessonDateTime.getHours()
    startMinute = lessonDateTime.getMinutes()
  }

  const endMinutes = startMinute + lesson.duration
  const endHour = startHour + Math.floor(endMinutes / 60)
  const endMinute = endMinutes % 60

  return `${startHour.toString().padStart(2, '0')}:${startMinute.toString().padStart(2, '0')} - ${endHour.toString().padStart(2, '0')}:${endMinute.toString().padStart(2, '0')}`
}

const getLessonClass = (lesson) => {
  const statusClasses = {
    'confirmed': 'bg-blue-100 border-blue-500 text-blue-900',
    'pending': 'bg-yellow-100 border-yellow-500 text-yellow-900',
    'completed': 'bg-green-100 border-green-500 text-green-900',
    'cancelled': 'bg-gray-100 border-gray-400 text-gray-700 line-through'
  }
  return statusClasses[lesson.status] || 'bg-blue-100 border-blue-500 text-blue-900'
}

// Chargement des données
const loadPlanningData = async () => {
  try {
    const { $api } = useNuxtApp()

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

    const teachersResponse = await $api.get('/club/teachers')
    if (teachersResponse.data.success) {
      teachers.value = teachersResponse.data.data
    }

    const studentsResponse = await $api.get('/club/students')
    if (studentsResponse.data.success) {
      students.value = studentsResponse.data.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement des enseignants/élèves:', error)
  }
}

const loadDisciplines = async () => {
  try {
    const { $api } = useNuxtApp()

    const response = await $api.get('/disciplines')
    if (response.data.success) {
      availableDisciplines.value = response.data.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement des disciplines:', error)
  }
}

// Initialisation
onMounted(async () => {
  console.log('🚀 Initialisation du nouveau planning')
  await Promise.all([
    loadPlanningData(),
    loadTeachersAndStudents(),
    loadDisciplines()
  ])
})
</script>

<style scoped>
.bg-today {
  background-color: rgba(219, 234, 254, 0.15) !important;
}

.bg-today:hover {
  background-color: rgba(219, 234, 254, 0.3) !important;
}
</style>

