<template>
  <div class="min-h-screen bg-gray-50 p-8">
    <div class="max-w-7xl mx-auto">
    <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Planning</h1>
        <p class="mt-2 text-gray-600">Gestion des cours et créneaux horaires</p>
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
        <!-- Bloc 1: Liste des cours disponibles (disciplines actives) -->
        <DisciplinesList :disciplines="activeDisciplines" />
        
        <!-- Bloc 2: Gestion des créneaux horaires -->
        <SlotsList 
          :slots="openSlots"
          @create-slot="openSlotModal()"
          @edit-slot="openSlotModal"
          @delete-slot="(slot) => deleteSlot(slot.id)"
        />
          
        <!-- Bloc 3: Créneaux disponibles OU Calendrier journalier -->
        <DayCalendarView 
          v-if="showDayCalendar && selectedSlotForCalendar"
          :selected-slot="selectedSlotForCalendar"
          :lessons="lessons"
          @close="closeDayCalendar"
          @create-lesson="openCreateLessonFromCalendar"
          @select-lesson="openLessonModal"
        />
        
        <AvailableSlotsGrid 
          v-else
          :slots="openSlots"
          :lessons="lessons"
          @select-slot="openDayCalendar"
          @create-lesson="openCreateLessonModal"
        />
        
        <!-- Bloc 4: Cours programmés -->
        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-xl font-semibold text-gray-900">Cours programmés</h2>
              <p class="text-sm text-gray-500 mt-1">
                <span class="font-bold" :class="lessons.length > 0 ? 'text-green-600' : 'text-orange-600'">
                  {{ lessons.length }} cours programmé{{ lessons.length > 1 ? 's' : '' }}
                </span>
              </p>
            </div>
          </div>

          <!-- Liste des cours -->
          <div v-if="lessons.length > 0" class="space-y-3">
            <div 
              v-for="lesson in lessons" 
              :key="lesson.id"
              @click="openLessonModal(lesson)"
              class="border-2 rounded-lg p-4 cursor-pointer transition-all hover:shadow-md"
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
                    <span>👤 {{ lesson.student?.user?.name || 'Aucun étudiant' }}</span>
                    <span>🎓 {{ lesson.teacher?.user?.name || 'Coach' }}</span>
                    <span v-if="lesson.price">💰 {{ formatPrice(lesson.price) }} €</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- État vide -->
          <div v-else class="text-center py-12 text-gray-500">
            <div class="text-4xl mb-4">📚</div>
            <p class="text-lg mb-2">Aucun cours programmé</p>
            <p class="text-sm">Cliquez sur un créneau disponible pour créer votre premier cours</p>
          </div>
        </div> <!-- Fermeture du v-else class="space-y-6" -->
          
        <!-- Modale Créneau -->
        <div v-if="showSlotModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
              <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">
                  {{ editingSlot ? 'Modifier le créneau' : 'Nouveau créneau' }}
                </h3>
                <button @click="closeSlotModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

              <form @submit.prevent="saveSlot" class="space-y-4">
                <!-- Jour de la semaine -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Jour de la semaine *</label>
                  <select v-model.number="slotForm.day_of_week" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option :value="0">Dimanche</option>
                    <option :value="1">Lundi</option>
                    <option :value="2">Mardi</option>
                    <option :value="3">Mercredi</option>
                    <option :value="4">Jeudi</option>
                    <option :value="5">Vendredi</option>
                    <option :value="6">Samedi</option>
                  </select>
        </div>

                <!-- Horaires -->
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heure de début *</label>
                    <input v-model="slotForm.start_time" type="time" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
          <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heure de fin *</label>
                    <input v-model="slotForm.end_time" type="time" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                  </div>
            </div>
            
                <!-- Discipline -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Discipline *</label>
                  <select v-model.number="slotForm.discipline_id" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Sélectionnez une discipline</option>
                    <option v-for="discipline in activeDisciplines" :key="discipline.id" :value="discipline.id">
                      {{ discipline.name }}
              </option>
            </select>
          </div>

                <!-- Durée et Prix -->
                <div class="grid grid-cols-2 gap-4">
            <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Durée (min) *</label>
                    <input v-model.number="slotForm.duration" type="number" min="15" step="5" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix (€) *</label>
                    <input v-model.number="slotForm.price" type="number" min="0" step="0.01" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>

                <!-- Capacité et Plages -->
          <div class="grid grid-cols-2 gap-4">
            <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Participants par créneau *</label>
                    <input v-model.number="slotForm.max_capacity" type="number" min="1" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                    <p class="mt-1 text-xs text-gray-500">Nombre de participants pour UN créneau</p>
            </div>
            <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de plages simultanées *</label>
                    <input v-model.number="slotForm.max_slots" type="number" min="1" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                    <p class="mt-1 text-xs text-gray-500">Ex: 5 couloirs = 5 cours en même temps</p>
            </div>
          </div>

                <!-- Actif -->
                <div class="flex items-center">
                  <input v-model="slotForm.is_active" type="checkbox" id="is_active"
                         class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
                  <label for="is_active" class="ml-2 block text-sm text-gray-700">
                    Créneau actif
            </label>
          </div>

                <!-- Boutons -->
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                  <button type="button" @click="closeSlotModal"
                          class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
              Annuler
            </button>
                  <button type="submit" :disabled="saving"
                          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                    {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </form>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Modale Détails du Cours -->
      <div v-if="showLessonModal && selectedLesson" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <div class="p-6">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-2xl font-bold text-gray-900">
                Détails du cours
              </h3>
              <button @click="closeLessonModal" class="text-gray-400 hover:text-gray-600">
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
                <p class="text-lg font-semibold text-gray-900">
                  {{ selectedLesson.course_type?.name || 'Non défini' }}
                </p>
              </div>

              <!-- Horaires -->
              <div class="grid grid-cols-2 gap-4">
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

              <!-- Participants -->
              <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                  <label class="block text-sm font-medium text-gray-500 mb-1">Étudiant</label>
                  <p class="text-base font-semibold text-gray-900">
                    {{ selectedLesson.student?.user?.name || 'Non assigné' }}
                  </p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                  <label class="block text-sm font-medium text-gray-500 mb-1">Coach</label>
                  <p class="text-base font-semibold text-gray-900">
                    {{ selectedLesson.teacher?.user?.name || 'Non assigné' }}
                  </p>
                </div>
              </div>

              <!-- Prix -->
              <div class="bg-gray-50 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-500 mb-1">Prix</label>
                <p class="text-lg font-semibold text-gray-900">
                  {{ formatPrice(selectedLesson.price) }} €
                </p>
              </div>

              <!-- Statut -->
              <div class="bg-gray-50 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-500 mb-2">Statut</label>
                <div class="flex flex-wrap gap-2">
                  <button 
                    @click="updateLessonStatus(selectedLesson.id, 'confirmed')"
                    :class="selectedLesson.status === 'confirmed' ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-4 py-2 rounded-lg font-medium transition-colors hover:opacity-80"
                    :disabled="saving">
                    ✓ Confirmé
                  </button>
                  <button 
                    @click="updateLessonStatus(selectedLesson.id, 'pending')"
                    :class="selectedLesson.status === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-4 py-2 rounded-lg font-medium transition-colors hover:opacity-80"
                    :disabled="saving">
                    ⏳ En attente
                  </button>
                  <button 
                    @click="updateLessonStatus(selectedLesson.id, 'completed')"
                    :class="selectedLesson.status === 'completed' ? 'bg-gray-500 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-4 py-2 rounded-lg font-medium transition-colors hover:opacity-80"
                    :disabled="saving">
                    ✓ Terminé
                  </button>
                  <button 
                    @click="updateLessonStatus(selectedLesson.id, 'cancelled')"
                    :class="selectedLesson.status === 'cancelled' ? 'bg-red-500 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-4 py-2 rounded-lg font-medium transition-colors hover:opacity-80"
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
            <div class="flex justify-between gap-3 mt-6 pt-4 border-t">
              <button 
                @click="deleteLesson(selectedLesson.id)"
                :disabled="saving"
                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
                <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Supprimer
              </button>
              <button 
                @click="closeLessonModal"
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                Fermer
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Modale Création de Cours -->
      <CreateLessonModal
        :show="showCreateLessonModal"
        :form="lessonForm"
        :selected-slot="selectedSlotForLesson"
        :teachers="teachers"
        :students="students"
        :course-types="filteredCourseTypes"
        :available-days="availableDaysOfWeek"
        :saving="saving"
        @close="closeCreateLessonModal"
        @submit="createLesson"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import SlotsList from '~/components/planning/SlotsList.vue'
import DisciplinesList from '~/components/planning/DisciplinesList.vue'
import CreateLessonModal from '~/components/planning/CreateLessonModal.vue'
import AvailableSlotsGrid from '~/components/planning/AvailableSlotsGrid.vue'
import DayCalendarView from '~/components/planning/DayCalendarView.vue'

definePageMeta({
  middleware: ['auth']
})

// Types
interface Discipline {
  id: number
  activity_type_id: number
  name: string
  description: string | null
  slug: string
  is_active: boolean
}

interface DisciplineSettings {
  duration: number
  price: number
  min_participants: number
  max_participants: number
  notes: string
}

interface ClubDiscipline extends Discipline {
  settings: DisciplineSettings
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

interface OpenSlot {
  id: number
  club_id: number
  day_of_week: number
  start_time: string
  end_time: string
  discipline_id: number | null
  discipline?: Discipline
  max_capacity: number | null
  max_slots: number | null
  duration: number | null
  price: number | null
  is_active: boolean
  course_types?: CourseType[]
}

interface Lesson {
  id: number
  start_time: string // DateTime ISO string
  end_time: string   // DateTime ISO string
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
  course_type?: CourseType
  location?: any
  notes?: string
}

// State
const loading = ref(true)
const error = ref<string | null>(null)
const clubDisciplines = ref<ClubDiscipline[]>([])
const openSlots = ref<OpenSlot[]>([])
const lessons = ref<Lesson[]>([])
const showSlotModal = ref(false)
const editingSlot = ref<OpenSlot | null>(null)
const saving = ref(false)
const showLessonModal = ref(false)
const selectedLesson = ref<Lesson | null>(null)
const showCreateLessonModal = ref(false)
const selectedSlotForLesson = ref<OpenSlot | null>(null)
const showDayCalendar = ref(false)
const selectedSlotForCalendar = ref<OpenSlot | null>(null)
const teachers = ref<any[]>([])
const students = ref<any[]>([])
const courseTypes = ref<any[]>([])
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
const availableDaysOfWeek = ref<number[]>([]) // Jours de la semaine où il y a des créneaux

const slotForm = ref({
  day_of_week: 1,
      start_time: '09:00',
      end_time: '10:00',
  discipline_id: null as number | null,
      duration: 60,
  price: 0,
  max_capacity: 1,
  max_slots: 1,
  is_active: true
})

// Computed
const activeDisciplines = computed(() => {
  return clubDisciplines.value.filter(d => d.is_active)
})

// Types de cours filtrés - Utilise les courseTypes du créneau sélectionné
// au lieu de filtrer la liste globale (relation directe créneau → types)
const filteredCourseTypes = computed(() => {
  console.log('🔄 [filteredCourseTypes] Computed appelé', {
    hasSlot: !!selectedSlotForLesson.value,
    slotId: selectedSlotForLesson.value?.id,
    slotDisciplineId: selectedSlotForLesson.value?.discipline_id,
    slotHasCourseTypes: !!selectedSlotForLesson.value?.course_types,
    modalOpen: showCreateLessonModal.value
  })
  
  // Si la modale n'est pas ouverte, retourner un tableau vide
  if (!showCreateLessonModal.value) {
    console.log('⚠️ [filteredCourseTypes] Modale fermée → tableau vide')
    return []
  }
  
  // Si pas de créneau sélectionné, retourner tableau vide
  if (!selectedSlotForLesson.value) {
    console.log('⚠️ [filteredCourseTypes] Pas de créneau → tableau vide')
    return []
  }
  
  // ✅ SOLUTION : Utiliser directement les courseTypes du créneau
  // (relation créneau → types de cours via table pivot)
  const slotCourseTypes = selectedSlotForLesson.value.course_types || []
  
  console.log('🎯 [filteredCourseTypes] Types de cours du créneau', selectedSlotForLesson.value.id, ':', {
    slotDisciplineId: selectedSlotForLesson.value.discipline_id,
    slotDisciplineName: selectedSlotForLesson.value.discipline?.name,
    courseTypesCount: slotCourseTypes.length,
    courseTypes: slotCourseTypes.map(ct => ({ 
      id: ct.id, 
      name: ct.name,
      discipline_id: ct.discipline_id,
      duration: ct.duration,
      price: ct.price
    }))
  })
  
  return slotCourseTypes
})

// Watcher pour initialiser les valeurs quand on sélectionne une discipline
watch(() => slotForm.value.discipline_id, (newDisciplineId) => {
  if (newDisciplineId && !editingSlot.value) {
    // Trouver la discipline sélectionnée
    const selectedDiscipline = clubDisciplines.value.find(d => d.id === newDisciplineId)
    
    if (selectedDiscipline && selectedDiscipline.settings) {
      // Pré-remplir avec les valeurs configurées
      slotForm.value.duration = selectedDiscipline.settings.duration || 60
      slotForm.value.price = selectedDiscipline.settings.price || 0
      slotForm.value.max_capacity = selectedDiscipline.settings.max_participants || 1
      
      console.log('✨ Valeurs initialisées depuis la discipline:', {
        duration: slotForm.value.duration,
        price: slotForm.value.price,
        max_capacity: slotForm.value.max_capacity
      })
    }
  }
})

// Watcher pour pré-remplir durée et prix quand on sélectionne un type de cours
watch(() => lessonForm.value.course_type_id, (newCourseTypeId) => {
  if (newCourseTypeId) {
    const courseType = courseTypes.value.find(ct => ct.id === newCourseTypeId)
    if (courseType) {
      // Utiliser duration_minutes en priorité, puis duration
      lessonForm.value.duration = courseType.duration_minutes || courseType.duration || 60
      lessonForm.value.price = courseType.price || 0
      console.log('✨ Durée et prix initialisés depuis type de cours:', {
        name: courseType.name,
        duration: lessonForm.value.duration,
        price: lessonForm.value.price
      })
    }
  }
})

// Watcher pour réinitialiser le type de cours quand le créneau change
watch(() => selectedSlotForLesson.value, (newSlot, oldSlot) => {
  // Si on change de créneau et que la discipline change
  if (newSlot && oldSlot && newSlot.discipline_id !== oldSlot.discipline_id) {
    // Réinitialiser le type de cours car les options disponibles ont changé
    lessonForm.value.course_type_id = null
    console.log('🔄 Type de cours réinitialisé suite au changement de créneau')
  }
})

// Watcher pour mettre à jour les jours disponibles quand les créneaux changent
watch(openSlots, () => {
  updateAvailableDays()
}, { deep: true })

// Watcher pour combiner date et heure
watch(() => [lessonForm.value.date, lessonForm.value.time], ([date, time]) => {
  if (date && time) {
    lessonForm.value.start_time = `${date}T${time}`
  }
})

// Fonctions
async function loadClubDisciplines() {
  try {
    loading.value = true
    error.value = null
    
    const { $api } = useNuxtApp()
    const config = useRuntimeConfig()
    
    console.log('🔍 Début du chargement des disciplines...')
    
    // 1. Récupérer le profil du club avec les disciplines configurées
    const profileResponse = await $api.get('/club/profile')
    
    console.log('📥 Réponse profil brute:', profileResponse.data)
    
    if (!profileResponse.data.success || !profileResponse.data.data) {
      throw new Error('Impossible de récupérer le profil du club')
    }
    
    const clubData = profileResponse.data.data
    
    console.log('🏢 Données du club:', {
      id: clubData.id,
      name: clubData.name,
      disciplines_raw: clubData.disciplines,
      disciplines_type: typeof clubData.disciplines,
      discipline_settings_raw: clubData.discipline_settings,
      discipline_settings_type: typeof clubData.discipline_settings
    })
    
    // 2. Récupérer la liste complète des disciplines pour avoir les noms
    const disciplinesResponse = await $fetch(`${config.public.apiBase}/disciplines`)
    const allDisciplines = disciplinesResponse.data || []
    
    console.log('📚 Disciplines disponibles:', allDisciplines.map((d: any) => ({ id: d.id, name: d.name })))
    
    // 3. Parser les données du club
    let clubDisciplineIds = []
    
    if (clubData.disciplines) {
      if (Array.isArray(clubData.disciplines)) {
        clubDisciplineIds = clubData.disciplines
      } else if (typeof clubData.disciplines === 'string') {
        try {
          clubDisciplineIds = JSON.parse(clubData.disciplines)
  } catch (e) {
          console.error('Erreur parsing disciplines:', e)
          clubDisciplineIds = []
        }
      }
    }
    
    let disciplineSettings = {}
    
    if (clubData.discipline_settings) {
      if (typeof clubData.discipline_settings === 'string') {
        try {
          disciplineSettings = JSON.parse(clubData.discipline_settings)
  } catch (e) {
          console.error('Erreur parsing discipline_settings:', e)
          disciplineSettings = {}
        }
      } else if (typeof clubData.discipline_settings === 'object') {
        disciplineSettings = clubData.discipline_settings
      }
    }
    
    console.log('✅ Données parsées:', {
      clubDisciplineIds,
      disciplineSettings
    })
    
    // 4. Construire la liste des disciplines avec leurs settings
    clubDisciplines.value = clubDisciplineIds
      .map((disciplineId: number) => {
        console.log(`🔍 Recherche discipline ID ${disciplineId}...`)
        const discipline = allDisciplines.find((d: Discipline) => d.id === disciplineId)
        
        if (!discipline) {
          console.warn(`❌ Discipline ${disciplineId} non trouvée dans le référentiel`)
          console.log('   IDs disponibles:', allDisciplines.map((d: any) => d.id))
          return null
        }
        
        console.log(`✅ Discipline ${disciplineId} trouvée:`, discipline.name)
        
        const settings = disciplineSettings[disciplineId] || {
          duration: 45,
          price: 25.00,
          min_participants: 1,
          max_participants: 8,
  notes: ''
        }
        
        console.log(`   Settings pour ${discipline.name}:`, settings)
        
      return {
          ...discipline,
          settings
        }
      })
      .filter((d): d is ClubDiscipline => d !== null)
    
    console.log('🎯 RÉSULTAT FINAL:', clubDisciplines.value)
    console.log('📊 Nombre de disciplines actives:', activeDisciplines.value.length)
  } catch (err: any) {
    console.error('❌ ERREUR:', err)
    error.value = err.message || 'Erreur lors du chargement des cours disponibles'
  } finally {
    loading.value = false
  }
}

// Charger les créneaux horaires
async function loadOpenSlots() {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/open-slots')
    
    if (response.data.success) {
      openSlots.value = response.data.data
      console.log('✅ Créneaux chargés:', openSlots.value)
      
      // 🔍 DEBUG: Vérifier les course_types dans chaque slot
      openSlots.value.forEach((slot, index) => {
        console.log(`🔍 [Slot ${index + 1}] ID: ${slot.id}`, {
          discipline_id: slot.discipline_id,
          discipline_name: slot.discipline?.name,
          has_course_types: !!slot.course_types,
          course_types_count: slot.course_types?.length || 0,
          course_types: slot.course_types?.map(ct => ({
            id: ct.id,
            name: ct.name,
            duration_minutes: ct.duration_minutes,
            price: ct.price
          })) || []
        })
      })
  } else {
      console.error('Erreur chargement créneaux:', response.data.message)
    }
  } catch (err: any) {
    console.error('Erreur chargement créneaux:', err)
  }
}

// Charger les cours réels
async function loadLessons() {
  try {
    const { $api } = useNuxtApp()
    // Charger les cours de la semaine en cours et prochaines semaines
  const today = new Date()
    const nextWeek = new Date(today)
    nextWeek.setDate(today.getDate() + 14) // 2 semaines
    
    const response = await $api.get('/lessons', {
      params: {
        date_from: today.toISOString().split('T')[0],
        date_to: nextWeek.toISOString().split('T')[0]
      }
    })
    
    if (response.data.success) {
      lessons.value = response.data.data
      console.log('✅ Cours chargés:', lessons.value)
      // Debug: Afficher le statut de chaque cours
      lessons.value.forEach((lesson, index) => {
        console.log(`  Cours ${index + 1}:`, {
          id: lesson.id,
          status: lesson.status,
          course_type: lesson.course_type?.name,
          start_time: lesson.start_time
        })
      })
    } else {
      console.error('Erreur chargement cours:', response.data.message)
    }
  } catch (err: any) {
    console.error('Erreur chargement cours:', err)
  }
}

// Charger les enseignants du club
async function loadTeachers() {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/teachers')
    console.log('🔍 [Planning] Réponse enseignants:', response.data)
    if (response.data.success) {
      // La clé est 'teachers' et non 'data' (voir ClubController::getTeachers)
      teachers.value = response.data.teachers || response.data.data || []
      console.log('✅ Enseignants chargés:', teachers.value.length)
    }
  } catch (err) {
    console.error('Erreur chargement enseignants:', err)
  }
}

// Charger les élèves du club
async function loadStudents() {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/students')
    console.log('🔍 [Planning] Réponse élèves:', response.data)
    if (response.data.success) {
      students.value = response.data.data || []
      console.log('✅ Élèves chargés:', students.value.length)
    }
  } catch (err) {
    console.error('Erreur chargement élèves:', err)
  }
}

async function loadCourseTypes() {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/course-types')
    
    if (response.data.success) {
      courseTypes.value = response.data.data
      console.log('✅ Types de cours chargés:', courseTypes.value.length)
      console.log('📋 Détail des types de cours:', courseTypes.value.map(ct => ({
        id: ct.id,
        name: ct.name,
        discipline_id: ct.discipline_id,
        duration_minutes: ct.duration_minutes,
        price: ct.price
      })))
    }
  } catch (err) {
    console.error('Erreur chargement types de cours:', err)
  }
}

// Calculer les jours de la semaine disponibles basés sur les créneaux
function updateAvailableDays() {
  const days = new Set<number>()
  openSlots.value.forEach(slot => {
    if (slot.is_active) {
      days.add(slot.day_of_week)
    }
  })
  availableDaysOfWeek.value = Array.from(days).sort()
  console.log('📅 Jours disponibles:', availableDaysOfWeek.value)
}

// Vérifier si une date correspond à un jour disponible
function isDateAvailable(dateStr: string): boolean {
  if (!dateStr) return false
  const date = new Date(dateStr)
  const dayOfWeek = date.getDay()
  return availableDaysOfWeek.value.includes(dayOfWeek)
}

// Gestion de la modale
function openSlotModal(slot?: OpenSlot) {
    if (slot) {
    editingSlot.value = slot
    slotForm.value = {
      day_of_week: slot.day_of_week,
      start_time: formatTime(slot.start_time),
      end_time: formatTime(slot.end_time),
      discipline_id: slot.discipline_id,
      duration: slot.duration || 60,
      price: slot.price || 0,
      max_capacity: slot.max_capacity || 1,
      max_slots: slot.max_slots || 1,
      is_active: slot.is_active
    }
  } else {
    editingSlot.value = null
    slotForm.value = {
      day_of_week: 1,
      start_time: '09:00',
      end_time: '10:00',
      discipline_id: null,
      duration: 60,
      price: 0,
      max_capacity: 1,
      max_slots: 1,
      is_active: true
    }
  }
  showSlotModal.value = true
}

function closeSlotModal() {
  showSlotModal.value = false
  editingSlot.value = null
}

async function saveSlot() {
  try {
    saving.value = true
    const { $api } = useNuxtApp()
    
    const payload = {
      day_of_week: slotForm.value.day_of_week,
      start_time: slotForm.value.start_time,
      end_time: slotForm.value.end_time,
      discipline_id: slotForm.value.discipline_id,
      duration: slotForm.value.duration,
      price: slotForm.value.price,
      max_capacity: slotForm.value.max_capacity,
      max_slots: slotForm.value.max_slots,
      is_active: slotForm.value.is_active
    }
    
    if (editingSlot.value) {
      // Mise à jour
      await $api.put(`/club/open-slots/${editingSlot.value.id}`, payload)
      console.log('✅ Créneau mis à jour')
    } else {
      // Création
      await $api.post('/club/open-slots', payload)
      console.log('✅ Créneau créé')
    }
    
    // Recharger la liste
      await loadOpenSlots()
    closeSlotModal()
  } catch (err: any) {
    console.error('Erreur sauvegarde créneau:', err)
    alert('Erreur lors de la sauvegarde du créneau')
  } finally {
    saving.value = false
  }
}

async function deleteSlot(id: number) {
  if (!confirm('Êtes-vous sûr de vouloir supprimer ce créneau ?')) {
    return
  }
  
  try {
    const { $api } = useNuxtApp()
    await $api.delete(`/club/open-slots/${id}`)
    console.log('✅ Créneau supprimé')
    
    // Recharger la liste
    await loadOpenSlots()
  } catch (err: any) {
    console.error('Erreur suppression créneau:', err)
    alert('Erreur lors de la suppression du créneau')
  }
}

function openCreateLessonModal(slot?: OpenSlot) {
  console.log('📝 [openCreateLessonModal] DÉBUT - Avant mise à jour selectedSlotForLesson', {
    hasSlot: !!slot,
    slotId: slot?.id,
    slotDisciplineId: slot?.discipline_id,
    slotDisciplineName: slot?.discipline?.name,
    slotHasCourseTypes: !!slot?.course_types,
    slotCourseTypesCount: slot?.course_types?.length || 0,
    slotCourseTypes: slot?.course_types?.map(ct => ct.name) || [],
    totalCourseTypes: courseTypes.value.length,
    currentSelectedSlot: selectedSlotForLesson.value?.id
  })
  
  selectedSlotForLesson.value = slot || null
  
  console.log('📝 [openCreateLessonModal] APRÈS mise à jour selectedSlotForLesson', {
    newSelectedSlotId: selectedSlotForLesson.value?.id,
    newSelectedSlotDisciplineId: selectedSlotForLesson.value?.discipline_id,
    newSelectedSlotHasCourseTypes: !!selectedSlotForLesson.value?.course_types,
    newSelectedSlotCourseTypesCount: selectedSlotForLesson.value?.course_types?.length || 0
  })
  
  if (slot) {
    // Calculer la prochaine date correspondant au jour du créneau
    const today = new Date()
    const targetDay = slot.day_of_week
    const daysUntilTarget = (targetDay - today.getDay() + 7) % 7
    const nextDate = new Date(today)
    nextDate.setDate(today.getDate() + (daysUntilTarget === 0 ? 7 : daysUntilTarget))
    
    const dateStr = nextDate.toISOString().split('T')[0]
    const timeStr = slot.start_time.substring(0, 5)
    
    // Trouver le course_type correspondant à la discipline si possible
    let courseTypeId = null
    if (slot.discipline_id) {
      const matchingCourseType = courseTypes.value.find(ct => ct.discipline_id === slot.discipline_id)
      if (matchingCourseType) {
        courseTypeId = matchingCourseType.id
      }
      console.log('🔍 Recherche type de cours pour discipline', slot.discipline_id, ':', {
        found: !!matchingCourseType,
        selectedId: courseTypeId,
        allTypes: courseTypes.value.map(ct => ({ id: ct.id, name: ct.name, discipline_id: ct.discipline_id }))
      })
    }
    
    lessonForm.value = {
      teacher_id: null,
      student_id: null,
      course_type_id: courseTypeId,
      date: dateStr,
      time: timeStr,
      start_time: `${dateStr}T${timeStr}`,
      duration: slot.duration || 60,
      price: slot.price || 0,
      notes: ''
    }
  } else {
    // Réinitialiser le formulaire
    lessonForm.value = {
      teacher_id: null,
      student_id: null,
      course_type_id: null,
      date: '',
      time: '',
      start_time: '',
      duration: 60,
      price: 0,
      notes: ''
    }
  }
  
  showCreateLessonModal.value = true
}

function closeCreateLessonModal() {
  console.log('🚪 [closeCreateLessonModal] Fermeture modale')
  showCreateLessonModal.value = false
  // Ne pas réinitialiser selectedSlotForLesson immédiatement pour éviter
  // que le computed retourne tous les types pendant la fermeture
  setTimeout(() => {
    selectedSlotForLesson.value = null
    console.log('🧹 [closeCreateLessonModal] selectedSlotForLesson réinitialisé après délai')
  }, 100)
}

// Gestion du calendrier journalier
function openDayCalendar(slot: OpenSlot) {
  console.log('📅 [openDayCalendar] Ouverture calendrier pour créneau', slot.id)
  selectedSlotForCalendar.value = slot
  showDayCalendar.value = true
}

function closeDayCalendar() {
  console.log('🚪 [closeDayCalendar] Fermeture calendrier')
  showDayCalendar.value = false
  selectedSlotForCalendar.value = null
}

function openCreateLessonFromCalendar(timeSlot: any, date: string) {
  console.log('📝 [openCreateLessonFromCalendar] Création cours depuis calendrier', { timeSlot, date })
  
  // Préparer le formulaire avec les infos du créneau
  selectedSlotForLesson.value = selectedSlotForCalendar.value
  
  // Pré-remplir le formulaire
  lessonForm.value.date = date
  lessonForm.value.time = timeSlot.start
  lessonForm.value.start_time = `${date}T${timeSlot.start}`
  
  // Si le créneau a une durée et un prix, les utiliser
  if (selectedSlotForCalendar.value?.duration) {
    lessonForm.value.duration = selectedSlotForCalendar.value.duration
  }
  if (selectedSlotForCalendar.value?.price) {
    lessonForm.value.price = selectedSlotForCalendar.value.price
  }
  
  showCreateLessonModal.value = true
}

async function createLesson() {
  try {
    saving.value = true
    const { $api } = useNuxtApp()
    
    // Validations
    const validationErrors = []
    
    if (!lessonForm.value.teacher_id) {
      validationErrors.push('Veuillez sélectionner un enseignant')
    }
    
    if (!lessonForm.value.course_type_id) {
      validationErrors.push('Veuillez sélectionner un type de cours')
    }
    
    if (!lessonForm.value.date || !lessonForm.value.time) {
      validationErrors.push('Veuillez sélectionner une date et une heure')
    }
    
    // Vérifier que la date correspond à un jour disponible
    if (lessonForm.value.date && !isDateAvailable(lessonForm.value.date)) {
      validationErrors.push('Cette date ne correspond à aucun créneau disponible pour ce jour de la semaine')
    }
    
    // Vérifier la durée
    if (!lessonForm.value.duration || lessonForm.value.duration < 15) {
      validationErrors.push('La durée du cours doit être d\'au moins 15 minutes')
    }
    
    // Vérifier le prix
    if (lessonForm.value.price === null || lessonForm.value.price === undefined || lessonForm.value.price < 0) {
      validationErrors.push('Le prix du cours doit être un nombre positif')
    }
    
    // Vérifier que le type de cours correspond à la discipline du créneau
    if (selectedSlotForLesson.value && lessonForm.value.course_type_id) {
      const selectedCourseType = courseTypes.value.find(ct => ct.id === lessonForm.value.course_type_id)
      if (selectedCourseType && selectedCourseType.discipline_id !== selectedSlotForLesson.value.discipline_id) {
        validationErrors.push('Le type de cours sélectionné ne correspond pas à la discipline du créneau')
      }
    }
    
    // Afficher les erreurs s'il y en a
    if (validationErrors.length > 0) {
      alert('⚠️ Erreurs de validation:\n\n' + validationErrors.map((e, i) => `${i + 1}. ${e}`).join('\n'))
      return
    }
    
    const payload = {
      teacher_id: lessonForm.value.teacher_id,
      student_id: lessonForm.value.student_id,
      course_type_id: lessonForm.value.course_type_id,
      start_time: lessonForm.value.start_time,
      duration: lessonForm.value.duration,
      price: lessonForm.value.price,
      notes: lessonForm.value.notes
    }
    
    console.log('📤 Création du cours avec payload:', payload)
    
    const response = await $api.post('/lessons', payload)
    
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
      // Formater les erreurs de validation Laravel
      const errors = Object.entries(errorMessage).map(([field, msgs]) => `${field}: ${Array.isArray(msgs) ? msgs.join(', ') : msgs}`).join('\n')
      alert('❌ Erreurs de validation:\n\n' + errors)
    } else {
      alert('❌ ' + errorMessage)
    }
  } finally {
    saving.value = false
  }
}

// Gestion de la modale de cours
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
    
    const response = await $api.put(`/lessons/${lessonId}`, {
      status: newStatus
    })
    
    if (response.data.success) {
      // Recharger les cours
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
    
    const response = await $api.delete(`/lessons/${lessonId}`)
    
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
function getDayName(dayNumber: number): string {
  const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
  return days[dayNumber] || 'Inconnu'
}

function formatTime(time: string): string {
  if (!time) return ''
  // Si le format est HH:MM:SS, on prend seulement HH:MM
  return time.substring(0, 5)
}

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
  await Promise.all([
    loadClubDisciplines(),
    loadOpenSlots(),
    loadLessons(),
    loadTeachers(),
    loadStudents(),
    loadCourseTypes()
  ])
  updateAvailableDays()
})
</script>
