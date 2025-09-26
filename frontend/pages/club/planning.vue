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
              class="bg-cyan-600 text-white px-4 py-2 rounded-lg hover:bg-cyan-700 transition-colors flex items-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <span>Ouvrir créneaux</span>
            </button>
            <button 
              @click="showCreateLessonModal = true"
              class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition-colors flex items-center space-x-2"
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
      <!-- Légende des créneaux -->
      <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
        <h3 class="text-sm font-medium text-gray-900 mb-3">Légende des créneaux</h3>
        <div class="flex flex-wrap gap-4 text-xs">
          <div class="flex items-center">
            <div class="w-4 h-4 bg-gray-100 border-l-4 border-gray-400 rounded mr-2"></div>
            <span class="text-gray-600">🔒 Club fermé</span>
          </div>
          <div class="flex items-center">
            <div class="w-4 h-4 bg-yellow-50 border-l-4 border-yellow-400 rounded mr-2"></div>
            <span class="text-gray-600">⏰ Disponible (à ouvrir)</span>
          </div>
          <div class="flex items-center">
            <div class="w-4 h-4 bg-green-50 border-l-4 border-green-500 rounded mr-2"></div>
            <span class="text-gray-600">✅ Ouvert pour cours</span>
          </div>
          <div class="flex items-center">
            <div class="w-4 h-4 bg-blue-100 border-l-4 border-blue-500 rounded mr-2"></div>
            <span class="text-gray-600">📚 Cours programmé</span>
          </div>
        </div>
      </div>

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

            <!-- Créneaux fermés (hors périodes d'ouverture) -->
            <div v-if="!isInClubSchedule(day.date, hour)"
                 class="absolute inset-1 bg-gray-100 border-l-4 border-gray-400 rounded p-2 text-xs text-gray-500"
            >
              <div class="font-medium">🔒 Fermé</div>
              <div class="text-xs">Club fermé</div>
            </div>

            <!-- Créneaux ouverts mais pas disponibles pour cours -->
            <div v-else-if="isInClubSchedule(day.date, hour) && !isSlotOpen(day.date, hour)"
                 class="absolute inset-1 bg-yellow-50 border-l-4 border-yellow-400 rounded p-2 text-xs text-yellow-700"
            >
              <div class="font-medium">⏰ Disponible</div>
              <div class="text-xs">Ouvrir créneaux</div>
            </div>

            <!-- Créneaux ouverts et disponibles -->
            <div v-else-if="isSlotOpen(day.date, hour) && getLessonsForSlot(day.date, hour).length === 0"
                 class="absolute inset-1 bg-green-50 border-l-4 border-green-500 rounded p-2 text-xs text-green-700"
            >
              <div class="font-medium">✅ Disponible</div>
              <div class="text-xs">Réserver cours</div>
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
                  <option v-for="hour in availableHours" :key="hour" :value="hour">{{ hour }}h</option>
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
                  <option v-for="hour in availableHours" :key="hour" :value="hour">{{ hour }}h</option>
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
            <label class="block text-sm font-medium text-gray-700 mb-1">Sport</label>
            <select 
              v-model="openForm.activityTypeId"
              @change="openForm.disciplineId = ''"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              required
            >
              <option value="">Sélectionner un sport</option>
              <option v-for="activity in clubActivities" :key="activity.id" :value="activity.id">
                {{ activity.name }}
              </option>
            </select>
          </div>

          <div v-if="openForm.activityTypeId">
            <label class="block text-sm font-medium text-gray-700 mb-1">Type de cours</label>
            <select 
              v-model="openForm.disciplineId"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              required
            >
              <option value="">Sélectionner un type de cours</option>
              <option v-for="discipline in availableDisciplinesForActivity" :key="discipline.id" :value="discipline.id">
                {{ discipline.name }}
              </option>
            </select>
          </div>

          <!-- Informations sur le cours sélectionné -->
          <div v-if="selectedDisciplineSettings" class="bg-blue-50 p-4 rounded-lg">
            <h4 class="font-medium text-blue-900 mb-2">Paramètres du cours</h4>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-blue-700 font-medium">Durée :</span>
                <span class="ml-1">{{ lessonDuration }} minutes</span>
              </div>
              <div>
                <span class="text-blue-700 font-medium">Prix :</span>
                <span class="ml-1">{{ selectedDisciplineSettings.price }}€</span>
              </div>
              <div>
                <span class="text-blue-700 font-medium">Participants :</span>
                <span class="ml-1">{{ selectedDisciplineSettings.min_participants || 1 }} - {{ selectedDisciplineSettings.max_participants || 10 }}</span>
              </div>
              <div v-if="selectedDisciplineSettings.notes">
                <span class="text-blue-700 font-medium">Notes :</span>
                <span class="ml-1">{{ selectedDisciplineSettings.notes }}</span>
              </div>
            </div>
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
            :disabled="openForm.selectedDays.length === 0 || !computedStartTime || !computedEndTime || !openForm.activityTypeId || !openForm.disciplineId"
            class="bg-cyan-600 text-white px-4 py-2 rounded-lg hover:bg-cyan-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
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
            class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
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
const clubProfile = ref(null) // Profil du club avec horaires et disciplines
const availableDisciplines = ref([]) // Disciplines disponibles du club

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
  activityTypeId: '', // Sport sélectionné
  disciplineId: '', // Spécialité sélectionnée pour ce sport
  description: ''
})

// Computed pour les heures complètes
const computedStartTime = computed(() => `${openForm.value.startHour}:${openForm.value.startMinute}`)
const computedEndTime = computed(() => `${openForm.value.endHour}:${openForm.value.endMinute}`)

// Computed properties pour les données du profil
const availableHours = computed(() => {
  if (!clubProfile.value?.schedule_config) return hours
  
  // Extraire les heures min/max des horaires d'ouverture du club
  let minHour = 24, maxHour = 0
  
  clubProfile.value.schedule_config.forEach(day => {
    if (day.periods && day.periods.length > 0) {
      day.periods.forEach(period => {
        const startHour = parseInt(period.startHour)
        const endHour = parseInt(period.endHour)
        if (startHour < minHour) minHour = startHour
        if (endHour > maxHour) maxHour = endHour
      })
    }
  })
  
  // Si aucun horaire défini, utiliser les heures par défaut
  if (minHour === 24) return hours
  
  // Générer les heures dans la plage définie
  const result = []
  for (let i = minHour; i <= maxHour; i++) {
    result.push(i.toString().padStart(2, '0'))
  }
  return result
})

const selectedDisciplineSettings = computed(() => {
  if (!openForm.value.disciplineId || !clubProfile.value?.discipline_settings) {
    return null
  }
  return clubProfile.value.discipline_settings[openForm.value.disciplineId] || null
})

const lessonDuration = computed(() => {
  return selectedDisciplineSettings.value?.duration || 60
})

// Computed properties pour les activités et disciplines
const clubActivities = computed(() => {
  if (!clubProfile.value?.disciplines) return []
  
  try {
    // Récupérer les disciplines sélectionnées du club
    const disciplineIds = typeof clubProfile.value.disciplines === 'string' 
      ? JSON.parse(clubProfile.value.disciplines) 
      : clubProfile.value.disciplines
    
    if (!Array.isArray(disciplineIds)) return []
    
    // Extraire les activity_type_id uniques des disciplines sélectionnées
    const activityTypeIds = new Set()
    
    disciplineIds.forEach(disciplineId => {
      const id = typeof disciplineId === 'object' ? disciplineId.id : disciplineId
      const discipline = availableDisciplines.value.find(d => d.id === parseInt(id))
      if (discipline && discipline.activity_type_id) {
        activityTypeIds.add(discipline.activity_type_id)
      }
    })
    
    // Retourner les activités uniques
    return Array.from(activityTypeIds).map(activityTypeId => ({
      id: activityTypeId,
      name: getActivityName(activityTypeId),
      icon: getActivityIcon(activityTypeId)
    }))
  } catch (e) {
    console.warn('Erreur parsing disciplines du club:', e)
    return []
  }
})

const availableDisciplinesForActivity = computed(() => {
  if (!openForm.value.activityTypeId || !clubProfile.value?.disciplines) return []
  
  try {
    // Récupérer les disciplines sélectionnées du club
    const clubDisciplineIds = typeof clubProfile.value.disciplines === 'string' 
      ? JSON.parse(clubProfile.value.disciplines) 
      : clubProfile.value.disciplines
    
    if (!Array.isArray(clubDisciplineIds)) return []
    
    // Convertir en nombres si nécessaire
    const clubDisciplineIdNumbers = clubDisciplineIds.map(id => 
      typeof id === 'object' ? id.id : parseInt(id)
    )
    
    // Filtrer les disciplines pour cette activité ET que le club propose
    return availableDisciplines.value.filter(discipline => 
      discipline.activity_type_id === parseInt(openForm.value.activityTypeId) &&
      clubDisciplineIdNumbers.includes(discipline.id)
    )
  } catch (e) {
    console.warn('Erreur parsing disciplines du club pour filtrage:', e)
    return []
  }
})

// Fonctions utilitaires pour récupérer les noms et icônes des activités
const getActivityName = (activityTypeId) => {
  const activityNames = {
    1: 'Équitation',
    2: 'Natation', 
    3: 'Fitness',
    4: 'Sports collectifs',
    5: 'Arts martiaux',
    6: 'Danse',
    7: 'Tennis',
    8: 'Gymnastique'
  }
  return activityNames[activityTypeId] || `Activité ${activityTypeId}`
}

const getActivityIcon = (activityTypeId) => {
  const activityIcons = {
    1: 'horse',
    2: 'swimmer', 
    3: 'dumbbell',
    4: 'futbol',
    5: 'fist-raised',
    6: 'music',
    7: 'table-tennis',
    8: 'child'
  }
  return activityIcons[activityTypeId] || 'star'
}

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

// Configuration des créneaux horaires basée sur le profil du club
const timeSlots = computed(() => {
  if (!clubProfile.value?.schedule_config) {
    // Fallback: générer les créneaux par défaut (6h-22h)
    const slots = []
    for (let hour = 6; hour <= 22; hour++) {
      for (let minute = 0; minute < 60; minute += 5) {
        const timeStr = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`
        slots.push(timeStr)
      }
    }
    return slots
  }
  
  // Extraire les heures min/max de toutes les périodes configurées
  let minHour = 24, maxHour = 0
  
  clubProfile.value.schedule_config.forEach(day => {
    if (day.periods && day.periods.length > 0) {
      day.periods.forEach(period => {
        const startHour = parseInt(period.startHour)
        const endHour = parseInt(period.endHour)
        if (startHour < minHour) minHour = startHour
        if (endHour > maxHour) maxHour = endHour
      })
    }
  })
  
  // Si aucune période définie, utiliser les heures par défaut
  if (minHour === 24) {
    minHour = 6
    maxHour = 22
  }
  
  // Générer les créneaux de 5 minutes dans la plage configurée
  const slots = []
  for (let hour = minHour; hour <= maxHour; hour++) {
    for (let minute = 0; minute < 60; minute += 5) {
      const timeStr = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`
      slots.push(timeStr)
    }
  }
  
  return slots
})

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
  // Vérifier le type de créneau pour donner un message approprié
  if (!isInClubSchedule(date, hour)) {
    alert('Ce créneau est en dehors des heures d\'ouverture du club. Configurez d\'abord les horaires dans le profil.')
    return
  }
  
  if (!isSlotOpen(date, hour)) {
    alert('Ce créneau n\'est pas ouvert pour les cours. Utilisez "Ouvrir créneaux" pour le rendre disponible.')
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

// Vérifier si un créneau est dans les périodes d'ouverture du club
const isInClubSchedule = (date, hour) => {
  if (!clubProfile.value?.schedule_config) return true // Si pas de config, tout est ouvert par défaut
  
  const dayOfWeek = new Date(date).getDay()
  const scheduleConfig = clubProfile.value.schedule_config
  const dayConfig = scheduleConfig[dayOfWeek === 0 ? 6 : dayOfWeek - 1] // Convertir dimanche=0 vers index 6
  
  if (!dayConfig || !dayConfig.periods || dayConfig.periods.length === 0) {
    return false // Pas de périodes configurées pour ce jour
  }
  
  // Vérifier si l'heure est dans une des périodes d'ouverture du jour
  return dayConfig.periods.some(period => {
    const startTime = `${period.startHour}:${period.startMinute}`
    const endTime = `${period.endHour}:${period.endMinute}`
    return hour >= startTime && hour < endTime
  })
}

const isSlotOpen = (date, hour) => {
  // D'abord vérifier si c'est dans les horaires du club
  if (!isInClubSchedule(date, hour)) {
    return false
  }
  
  // Ensuite vérifier les créneaux spécifiquement ouverts pour des cours
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
    if (!lesson.start_time) return false
    
    // Parse both ISO format (2025-09-23T09:00:00.000000Z) and traditional format (2025-09-23 09:00:00)
    let lessonDate, lessonHour
    
    if (lesson.start_time.includes('T')) {
      // ISO format: 2025-09-23T09:00:00.000000Z
      const [datePart, timePart] = lesson.start_time.split('T')
      lessonDate = datePart
      lessonHour = timePart.substring(0, 5) // Get HH:MM
    } else if (lesson.start_time.includes(' ')) {
      // Traditional format: 2025-09-23 09:00:00
      const [datePart, timePart] = lesson.start_time.split(' ')
      lessonDate = datePart
      lessonHour = timePart.substring(0, 5) // Get HH:MM
    } else {
      // Fallback: try to parse as date object
      const lessonDateTime = new Date(lesson.start_time)
      if (isNaN(lessonDateTime.getTime())) return false
      
      lessonDate = lessonDateTime.toISOString().split('T')[0]
      lessonHour = lessonDateTime.toISOString().split('T')[1].substring(0, 5)
    }
    
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
    
    const selectedDiscipline = availableDisciplines.value.find(d => d.id === parseInt(openForm.value.disciplineId))
    
    // Ajouter localement (en attendant l'API backend)
    const newOpenSlot = {
      id: Date.now(), // ID temporaire
      days: [...openForm.value.selectedDays],
      startTime: computedStartTime.value,
      endTime: computedEndTime.value,
      disciplineId: openForm.value.disciplineId,
      disciplineName: selectedDiscipline?.name || '',
      lessonDuration: lessonDuration.value,
      price: selectedDisciplineSettings.value?.price || 0,
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
      activityTypeId: '',
      disciplineId: '',
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

const loadClubProfile = async () => {
  try {
    const { $api } = useNuxtApp()
    
    // Charger le profil du club
    const profileResponse = await $api.get('/club/profile')
    if (profileResponse.data.success) {
      clubProfile.value = profileResponse.data.data
      
      // Parser les données JSON si elles sont stockées sous forme de chaînes
      if (typeof clubProfile.value.schedule_config === 'string') {
        try {
          clubProfile.value.schedule_config = JSON.parse(clubProfile.value.schedule_config)
        } catch (e) {
          clubProfile.value.schedule_config = []
        }
      }
      
      if (typeof clubProfile.value.discipline_settings === 'string') {
        try {
          clubProfile.value.discipline_settings = JSON.parse(clubProfile.value.discipline_settings)
        } catch (e) {
          clubProfile.value.discipline_settings = {}
        }
      }
      
      console.log('✅ Profil club chargé:', clubProfile.value)
    }
    
    // Charger les disciplines disponibles
    const disciplinesResponse = await $api.get('/disciplines')
    if (disciplinesResponse.data.success) {
      availableDisciplines.value = disciplinesResponse.data.data
      console.log('✅ Disciplines chargées:', availableDisciplines.value)
    }
    
  } catch (error) {
    console.error('Erreur lors du chargement du profil du club:', error)
  }
}

// Initialisation
onMounted(async () => {
  console.log('🚀 Initialisation du planning club')
  await Promise.all([
    loadPlanningData(),
    loadTeachersAndStudents(),
    loadClubProfile()
  ])
})
</script>
