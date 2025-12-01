<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-5xl w-full max-h-[95vh] overflow-y-auto">
      <div class="p-8">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-2xl font-bold text-gray-900">
            {{ editingLesson ? 'Modifier le cours' : 'Créer un nouveau cours' }}
          </h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Informations du créneau sélectionné -->
        <div v-if="selectedSlot" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <h4 class="font-semibold text-blue-900 mb-2">Créneau sélectionné</h4>
          <div class="text-sm text-blue-800 space-y-1">
            <p><strong>Jour :</strong> {{ getDayName(selectedSlot.day_of_week) }}</p>
            <p><strong>Horaire :</strong> {{ selectedSlot.start_time?.substring(0, 5) }} - {{ selectedSlot.end_time?.substring(0, 5) }}</p>
            <p><strong>Discipline :</strong> {{ selectedSlot.discipline?.name || 'Non définie' }}</p>
            <p v-if="selectedSlot.discipline_id" class="text-xs text-blue-600 mt-2">
              🔍 Les types de cours affichés sont filtrés pour cette discipline
            </p>
          </div>
        </div>

        <!-- Formulaire -->
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Section 1: Informations du créneau et horaire -->
          <div class="bg-gray-50 rounded-lg p-6 space-y-4">
            <h4 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">📅 Créneau et horaire</h4>
            
            <!-- 2.5. Créneau (en mode édition uniquement) -->
            <div v-if="editingLesson" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Créneau *</label>
                <select 
                  v-model="selectedSlotId"
                  required
                  @change="onSlotChange"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 bg-white text-gray-900">
                  <option :value="null">Sélectionnez un créneau</option>
                  <option v-for="slot in (openSlots || [])" :key="slot.id" :value="slot.id">
                    {{ getDayName(slot.day_of_week) }} • {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
                    <template v-if="slot.discipline || (slot as any).discipline_name">
                      • {{ slot.discipline?.name || (slot as any).discipline_name || 'Non définie' }}
                    </template>
                  </option>
                </select>
                <p v-if="selectedSlotId && currentSelectedSlot" class="text-xs text-green-600 mt-1">
                  ✓ Créneau sélectionné : {{ getDayName(currentSelectedSlot.day_of_week) }} de {{ formatTime(currentSelectedSlot.start_time) }} à {{ formatTime(currentSelectedSlot.end_time) }}
                </p>
              </div>
            </div>

            <!-- 2. Type de cours (masqué en mode édition) -->
            <div v-if="!editingLesson" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type de cours *</label>
                <select v-model.number="form.course_type_id" required
                        :disabled="courseTypes.length === 0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                  <option :value="null">
                    {{ courseTypes.length === 0 ? 'Aucun type de cours pour cette discipline' : 'Sélectionnez un type de cours' }}
                  </option>
                  <option v-for="courseType in courseTypes" :key="courseType.id" :value="courseType.id">
                    {{ courseType.name }} 
                    ({{ courseType.duration_minutes || courseType.duration }}min - {{ courseType.price }}€)
                  </option>
                </select>
                <p v-if="selectedSlot && courseTypes.length === 0" class="text-xs text-red-600 mt-1">
                  ⚠️ Aucun type de cours disponible pour ce créneau
                  <br>
                  <span class="text-xs">
                    Vérifiez que :
                    <br>• Des types de cours sont associés à ce créneau
                    <br>• Ces types correspondent aux disciplines activées pour votre club
                  </span>
                </p>
                <p v-else-if="selectedSlot && courseTypes.length > 0" class="text-xs text-green-600 mt-1">
                  ✓ {{ courseTypes.length }} type(s) de cours disponible(s) pour ce créneau
                </p>
              </div>
            </div>

            <!-- 3. Date et Heure -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Date -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Date *
                  <span v-if="(editingLesson ? currentSelectedSlot : selectedSlot)" class="text-xs text-blue-600 ml-2 font-medium">
                    (Uniquement les {{ getDayName((editingLesson ? currentSelectedSlot : selectedSlot)?.day_of_week || 0) }}s)
                  </span>
                  <span v-else-if="availableDays.length > 0" class="text-xs text-gray-500 ml-2">
                    (Jours disponibles: {{ availableDays.map(d => getDayName(d)).join(', ') }})
                  </span>
                </label>
                <!-- Conteneur avec flèches de navigation -->
                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    @click="navigateDate(-1)"
                    :disabled="!canNavigateDate(-1)"
                    :class="[
                      'px-3 py-2 border rounded-md transition-colors',
                      canNavigateDate(-1)
                        ? 'border-gray-300 bg-white hover:bg-gray-50 text-gray-700'
                        : 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed'
                    ]"
                    title="Date précédente"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                  </button>
                  <input 
                    v-model="form.date" 
                    type="date" 
                    required
                    :min="minDate || undefined"
                    @input="validateDate"
                    :class="[
                      'flex-1 px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500',
                      form.date && !isDateAvailable(form.date) ? 'border-red-500 bg-red-50' : 'border-gray-300'
                    ]" />
                  <button
                    type="button"
                    @click="navigateDate(1)"
                    :disabled="!canNavigateDate(1)"
                    :class="[
                      'px-3 py-2 border rounded-md transition-colors',
                      canNavigateDate(1)
                        ? 'border-gray-300 bg-white hover:bg-gray-50 text-gray-700'
                        : 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed'
                    ]"
                    title="Date suivante"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                  </button>
                </div>
                <p v-if="form.date && !isDateAvailable(form.date)" class="text-xs text-red-600 mt-1">
                  ⚠️ Cette date doit être un {{ getDayName((editingLesson ? currentSelectedSlot : selectedSlot)?.day_of_week || 0) }}
                </p>
                <p v-else-if="form.date && (editingLesson ? currentSelectedSlot : selectedSlot)" class="text-xs text-green-600 mt-1">
                  ✓ Date valide pour ce créneau
                </p>
                <!-- Suggestions de dates -->
                <div v-if="(editingLesson ? currentSelectedSlot : selectedSlot) && suggestedDates.length > 0" class="mt-2">
                  <p class="text-xs text-gray-600 mb-1">Suggestions :</p>
                  <div class="flex flex-wrap gap-2">
                    <button
                      v-for="(suggestedDate, index) in suggestedDates.slice(0, 4)"
                      :key="index"
                      type="button"
                      @click="form.date = suggestedDate"
                      class="px-3 py-1 text-xs bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                      {{ formatSuggestedDate(suggestedDate) }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- Heure -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Heure *</label>
                <select 
                  v-model="form.time" 
                  required
                  :disabled="!availableTimes.length && !editingLesson"
                  :class="[
                    'w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500',
                    (!availableTimes.length && !editingLesson) 
                      ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-gray-300' 
                      : 'bg-white text-gray-900 border-gray-300'
                  ]">
                  <option :value="''">
                    {{ editingLesson ? 'Sélectionnez une heure' : (availableTimes.length === 0 ? 'Aucune heure disponible' : 'Sélectionnez une heure') }}
                  </option>
                  <option v-for="time in availableTimes" :key="time.value" :value="time.value">
                    {{ time.label }}
                  </option>
                </select>
                <p v-if="!editingLesson && selectedSlot && form.date && availableTimes.length === 0" class="text-xs text-red-600 mt-1">
                  ⚠️ Aucune plage horaire disponible pour cette date. Le créneau est complet (toutes les plages sont occupées).
                </p>
                <p v-else-if="!editingLesson && selectedSlot && form.date && availableTimes.length > 0" class="text-xs text-green-600 mt-1">
                  ✓ {{ availableTimes.length }} plage(s) horaire(s) disponible(s) (les plages complètes sont automatiquement masquées)
                </p>
                <p v-if="loadingLessons" class="text-xs text-gray-500 mt-1">
                  🔄 Chargement des cours existants...
                </p>
              </div>
            </div>
          </div>

          <!-- Section 2: Participants -->
          <div class="bg-blue-50 rounded-lg p-6 space-y-4">
            <h4 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">👥 Participants</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Enseignant -->
              <div>
                <Autocomplete
                  v-model="form.teacher_id"
                  :items="teachers"
                  label="Enseignant *"
                  placeholder="Rechercher un enseignant..."
                  :required="true"
                  :get-item-label="(teacher) => teacher.user?.name || teacher.name || 'Enseignant sans nom'"
                  :get-item-id="(teacher) => teacher.id"
                  :is-item-unavailable="(teacher) => !isTeacherAvailable(teacher.id)"
                >
                  <template #item="{ item: teacher, isUnavailable }">
                    <div :class="isUnavailable ? 'bg-red-50' : ''">
                      <div class="font-medium flex items-center gap-2">
                        {{ teacher.user?.name || teacher.name || 'Enseignant sans nom' }}
                        <span v-if="isUnavailable" class="text-xs text-red-600 font-normal">(Non disponible)</span>
                      </div>
                      <div v-if="teacher.user?.email" class="text-xs" :class="isUnavailable ? 'text-red-400' : 'text-gray-500'">
                        {{ teacher.user.email }}
                      </div>
                    </div>
                  </template>
                </Autocomplete>
              </div>

              <!-- Élève (optionnel) (masqué en mode édition) -->
              <div v-if="!editingLesson">
                <Autocomplete
                  v-model="form.student_id"
                  :items="students"
                  label="Élève (optionnel)"
                  placeholder="Rechercher un élève..."
                  :get-item-label="(student) => {
                    const name = student.user?.name || student.name || 'Élève sans nom'
                    const age = student.age ? ` (${student.age} ans)` : ''
                    return name + age
                  }"
                  :get-item-id="(student) => student.id"
                  :is-item-unavailable="(student) => !isStudentAvailable(student.id)"
                >
                  <template #item="{ item: student, isUnavailable }">
                    <div :class="isUnavailable ? 'bg-red-50' : ''">
                      <div class="font-medium flex items-center gap-2">
                        {{ student.user?.name || student.name || 'Élève sans nom' }}
                        <span v-if="student.age" class="text-xs" :class="isUnavailable ? 'text-red-400' : 'text-gray-500'">
                          ({{ student.age }} ans)
                        </span>
                        <span v-if="isUnavailable" class="text-xs text-red-600 font-normal">(Non disponible)</span>
                      </div>
                      <div v-if="student.user?.email" class="text-xs" :class="isUnavailable ? 'text-red-400' : 'text-gray-500'">
                        {{ student.user.email }}
                      </div>
                    </div>
                  </template>
                </Autocomplete>
              </div>
            </div>
          </div>

          <!-- Section 3: Détails du cours -->
          <div class="bg-green-50 rounded-lg p-6 space-y-4">
            <h4 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">📋 Détails du cours</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Classification pour les commissions (DCL/NDCL) - uniquement pour séances de base -->
              <div v-if="isBaseSession" class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Classification pour les commissions *
                </label>
                <div class="flex gap-6">
                  <div class="flex items-center">
                    <input
                      id="dcl"
                      v-model="form.est_legacy"
                      :value="false"
                      type="radio"
                      :required="isBaseSession"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                    />
                    <label for="dcl" class="ml-2 block text-sm font-medium text-gray-700">
                      DCL
                    </label>
                  </div>
                  <div class="flex items-center">
                    <input
                      id="ndcl"
                      v-model="form.est_legacy"
                      :value="true"
                      type="radio"
                      :required="isBaseSession"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                    />
                    <label for="ndcl" class="ml-2 block text-sm font-medium text-gray-700">
                      NDCL
                    </label>
                  </div>
                </div>
              </div>

              <!-- Déduction d'abonnement -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Déduction d'abonnement
                </label>
                <div class="space-y-2">
                  <div class="flex items-center">
                    <input
                      id="deduct_subscription"
                      v-model="form.deduct_from_subscription"
                      :value="true"
                      type="radio"
                      :disabled="editingLesson ? false : !form.student_id"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                    />
                    <label 
                      for="deduct_subscription" 
                      :class="[
                        'ml-2 block text-sm font-medium',
                        (editingLesson || form.student_id) ? 'text-gray-700' : 'text-gray-400'
                      ]"
                    >
                      Déduire d'un abonnement existant
                    </label>
                  </div>
                  <div class="flex items-center">
                    <input
                      id="no_deduct_subscription"
                      v-model="form.deduct_from_subscription"
                      :value="false"
                      type="radio"
                      :disabled="editingLesson ? false : !form.student_id"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                    />
                    <label 
                      for="no_deduct_subscription"
                      :class="[
                        'ml-2 block text-sm font-medium',
                        (editingLesson || form.student_id) ? 'text-gray-700' : 'text-gray-400'
                      ]"
                    >
                      Séance non incluse dans l'abonnement
                    </label>
                  </div>
                </div>
                <p v-if="editingLesson || form.student_id" class="text-xs text-gray-500 mt-2">
                  ⓘ Par défaut, le cours sera déduit d'un abonnement actif si disponible
                </p>
                <p v-else-if="!editingLesson" class="text-xs text-orange-600 mt-2">
                  ⚠️ Sélectionnez un élève pour activer cette option
                </p>
              </div>

              <!-- Durée (affichage uniquement) (masqué en mode édition) -->
              <div v-if="!editingLesson">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Durée (minutes)
                </label>
                <div class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-700">
                  {{ form.duration || 0 }} minutes
                </div>
                <p class="text-xs text-gray-500 mt-1">
                  ⓘ Définie automatiquement selon le type de cours sélectionné
                </p>
              </div>

              <!-- Prix (affichage uniquement) (masqué en mode édition) -->
              <div v-if="!editingLesson">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Prix (€)
                </label>
                <div class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-700">
                  {{ formatPrice(form.price || 0) }} €
                </div>
                <p class="text-xs text-gray-500 mt-1">
                  ⓘ Défini automatiquement selon le type de cours sélectionné
                </p>
              </div>

              <!-- Notes (masqué en mode édition) -->
              <div v-if="!editingLesson" class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea v-model="form.notes" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                          placeholder="Notes sur le cours..."></textarea>
              </div>
            </div>
          </div>

          <!-- Boutons -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <button type="button" @click="$emit('close')"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
              Annuler
            </button>
            <button type="submit" :disabled="saving"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50">
              {{ saving ? (editingLesson ? 'Modification...' : 'Création...') : (editingLesson ? 'Modifier le cours' : 'Créer le cours') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, watch, ref, nextTick } from 'vue'
import Autocomplete from '~/components/Autocomplete.vue'

interface OpenSlot {
  id: number
  day_of_week: number
  start_time: string
  end_time?: string
  discipline_id?: number
  discipline?: any
  duration?: number
  price?: number
}

interface LessonForm {
  teacher_id: number | null
  student_id: number | null
  course_type_id: number | null
  date: string
  time: string
  duration: number
  price: number
  notes: string
  // Champs pour les commissions
  est_legacy: boolean | null
  // Déduction d'abonnement (par défaut true)
  deduct_from_subscription: boolean | null
}

interface Props {
  show: boolean
  form: LessonForm
  selectedSlot: OpenSlot | null
  teachers: any[]
  students: any[]
  courseTypes: any[]
  availableDays: number[]
  saving: boolean
  editingLesson?: any | null
  openSlots?: OpenSlot[] // Créneaux disponibles pour trouver le créneau correspondant à une date
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'close': []
  'submit': [form: LessonForm]
}>()

const dayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']

// Référence pour le créneau sélectionné en mode édition
const selectedSlotId = ref<number | null>(null)

// Computed property pour déterminer si le type de cours sélectionné est une "séance de base"
// Une séance de base est un cours individuel (is_individual === true)
const isBaseSession = computed(() => {
  // En mode édition, vérifier le type de cours de la leçon en cours d'édition
  if (props.editingLesson && props.editingLesson.course_type) {
    return props.editingLesson.course_type.is_individual === true
  }
  
  // En mode création, vérifier le type de cours sélectionné
  if (!props.form.course_type_id || props.courseTypes.length === 0) {
    return false
  }
  
  const selectedCourseType = props.courseTypes.find(ct => ct.id === props.form.course_type_id)
  if (!selectedCourseType) {
    return false
  }
  
  // Une séance de base est un cours individuel
  return selectedCourseType.is_individual === true
})

// Fonction pour formater l'heure (HH:mm)
function formatTime(time: string | undefined): string {
  if (!time) return ''
  return time.substring(0, 5) // Retourne HH:mm
}

// Fonction appelée quand le créneau change
function onSlotChange() {
  if (!selectedSlotId.value || !props.openSlots) return
  
  const slot = props.openSlots.find(s => s.id === selectedSlotId.value)
  if (slot) {
    currentSelectedSlot.value = slot
    console.log('🎯 [CreateLessonModal] Créneau sélectionné manuellement:', {
      slot_id: slot.id,
      day_of_week: slot.day_of_week,
      start_time: slot.start_time,
      end_time: slot.end_time
    })
    
    // Si une date est déjà sélectionnée, vérifier qu'elle correspond au jour du créneau
    if (props.form.date) {
      const date = new Date(props.form.date + 'T00:00:00')
      const dayOfWeek = date.getDay()
      if (dayOfWeek !== slot.day_of_week) {
        // Trouver la prochaine date correspondant au jour du créneau
        const today = new Date()
        let daysToAdd = slot.day_of_week - today.getDay()
        if (daysToAdd < 0) daysToAdd += 7
        const nextDate = new Date(today)
        nextDate.setDate(today.getDate() + daysToAdd)
        props.form.date = nextDate.toISOString().split('T')[0]
        console.log('📅 [CreateLessonModal] Date ajustée au jour du créneau:', props.form.date)
      }
    }
  }
}

function getDayName(dayOfWeek: number): string {
  return dayNames[dayOfWeek] || 'Inconnu'
}

// Date minimale : pas de restriction (permet l'encodage dans le passé)
const minDate = computed(() => {
  // Retourner null pour permettre toutes les dates
  return null
})

// Génère les 4 prochaines dates valides pour le créneau sélectionné
const suggestedDates = computed(() => {
  const slotToUse = props.editingLesson ? currentSelectedSlot.value : props.selectedSlot
  if (!slotToUse) return []
  
  const dates: string[] = []
  const today = new Date()
  const targetDay = slotToUse.day_of_week
  
  for (let i = 0; i < 28; i++) { // 4 semaines
    const checkDate = new Date(today)
    checkDate.setDate(today.getDate() + i)
    
    if (checkDate.getDay() === targetDay) {
      dates.push(checkDate.toISOString().split('T')[0])
    }
    
    if (dates.length >= 4) break
  }
  
  return dates
})

// Formate une date pour l'affichage des suggestions
function formatSuggestedDate(dateStr: string): string {
  const date = new Date(dateStr + 'T00:00:00')
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const tomorrow = new Date(today)
  tomorrow.setDate(today.getDate() + 1)
  
  if (date.getTime() === today.getTime()) {
    return 'Aujourd\'hui'
  } else if (date.getTime() === tomorrow.getTime()) {
    return 'Demain'
  } else {
    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
  }
}

// Vérifie si une date est disponible
function isDateAvailable(dateStr: string): boolean {
  if (!dateStr) return false
  const date = new Date(dateStr + 'T00:00:00')
  const dayOfWeek = date.getDay()
  
  // En mode édition, permettre toutes les dates qui ont un créneau correspondant
  if (props.editingLesson) {
    // Vérifier si un créneau existe pour ce jour de la semaine
    // Les créneaux sont passés via props, mais on peut aussi vérifier availableDays
    return props.availableDays.includes(dayOfWeek)
  }
  
  // Si un créneau est sélectionné, vérifier uniquement ce jour
  if (props.selectedSlot) {
    return dayOfWeek === props.selectedSlot.day_of_week
  }
  
  // Sinon, vérifier tous les jours disponibles
  return props.availableDays.includes(dayOfWeek)
}

// Valide la date lors de la saisie
function validateDate(event: Event) {
  const input = event.target as HTMLInputElement
  const dateStr = input.value
  
  if (dateStr && !isDateAvailable(dateStr)) {
    console.warn('⚠️ Date invalide sélectionnée:', dateStr)
    
    // Suggérer automatiquement la prochaine date valide
    if (suggestedDates.value.length > 0) {
      const nextValidDate = suggestedDates.value[0]
      setTimeout(() => {
        props.form.date = nextValidDate
        console.log('✓ Date corrigée automatiquement:', nextValidDate)
      }, 100)
    }
  }
}

// Navigue vers la date précédente ou suivante du même jour de la semaine
function navigateDate(direction: number) {
  const slotToUse = props.editingLesson ? currentSelectedSlot.value : props.selectedSlot
  if (!props.form.date || !slotToUse) return
  
  // Parser la date en local (pas UTC) pour éviter les problèmes de timezone
  const [year, month, day] = props.form.date.split('-').map(Number)
  const currentDate = new Date(year, month - 1, day, 12, 0, 0) // Utiliser midi pour éviter les problèmes de timezone
  const targetDayOfWeek = slotToUse.day_of_week
  const currentDayOfWeek = currentDate.getDay()
  
  let daysToAdd = 0
  
  if (currentDayOfWeek === targetDayOfWeek) {
    // Si on est déjà sur le bon jour, avancer/reculer d'une semaine complète
    daysToAdd = direction * 7
    console.log('🔍 Navigation: déjà sur le bon jour', {
      currentDate: props.form.date,
      currentDay: currentDayOfWeek,
      direction,
      daysToAdd
    })
  } else {
    // Si on n'est pas sur le bon jour, trouver le prochain/précédent jour cible
    let diff = targetDayOfWeek - currentDayOfWeek
    
    if (direction > 0) {
      // Navigation vers l'avenir (flèche droite)
      // Toujours aller au jour cible suivant (semaine suivante si nécessaire)
      if (diff > 0) {
        // Le jour cible est plus tard cette semaine → aller directement à ce jour
        daysToAdd = diff
      } else {
        // Le jour cible est déjà passé cette semaine → aller à la semaine suivante
        // diff est négatif, donc 7 + diff donne le nombre de jours jusqu'au jour cible de la semaine suivante
        daysToAdd = 7 + diff
      }
      console.log('🔍 Navigation droite calculée', {
        currentDay: currentDayOfWeek,
        targetDay: targetDayOfWeek,
        diff,
        daysToAdd
      })
    } else {
      // Navigation vers le passé (flèche gauche)
      // Toujours aller au jour cible précédent (semaine précédente)
      // On trouve d'abord le jour cible de cette semaine, puis on recule d'une semaine
      // diff peut être positif ou négatif selon où on se trouve dans la semaine
      // Exemple: si on est vendredi (5) et cible mercredi (3), diff = -2
      //          si on est lundi (1) et cible mercredi (3), diff = 2
      // Dans les deux cas, on veut le mercredi précédent
      
      // Normaliser diff pour trouver le jour cible de cette semaine
      let daysToTargetThisWeek = diff
      if (daysToTargetThisWeek < 0) {
        // Le jour cible est déjà passé cette semaine
        daysToTargetThisWeek = 7 + diff
      }
      
      // Aller au jour cible de la semaine précédente
      daysToAdd = daysToTargetThisWeek - 7
    }
  }
  
  // Créer une nouvelle date en ajoutant les jours
  const newDate = new Date(currentDate)
  newDate.setDate(currentDate.getDate() + daysToAdd)
  
  // Vérifier que la nouvelle date correspond bien au jour du créneau
  const newDayOfWeek = newDate.getDay()
  // Formater la date en YYYY-MM-DD en local (pas UTC)
  const newDateStr = `${newDate.getFullYear()}-${String(newDate.getMonth() + 1).padStart(2, '0')}-${String(newDate.getDate()).padStart(2, '0')}`
  
  console.log('🔍 Navigation calculée', {
    currentDate: props.form.date,
    currentDay: currentDayOfWeek,
    targetDay: targetDayOfWeek,
    direction,
    daysToAdd,
    newDate: newDateStr,
    newDay: newDayOfWeek,
    expectedDay: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'][targetDayOfWeek],
    actualDay: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'][newDayOfWeek]
  })
  
  if (newDayOfWeek !== targetDayOfWeek) {
    console.warn('⚠️ Erreur de navigation : le jour ne correspond pas au créneau', {
      currentDay: currentDayOfWeek,
      targetDay: targetDayOfWeek,
      newDay: newDayOfWeek,
      daysToAdd,
      currentDate: props.form.date,
      newDate: newDateStr
    })
    return
  }
  
  // Permettre la navigation vers le passé pour encoder des cours dans le passé
  props.form.date = newDateStr
}

// Vérifie si on peut naviguer dans une direction donnée
// Toujours autoriser la navigation (vers le passé et l'avenir)
function canNavigateDate(direction: number): boolean {
  if (!props.form.date || !props.selectedSlot) return false
  
  // Permettre toujours la navigation (vers le passé et l'avenir)
  return true
}

function handleSubmit() {
  emit('submit', props.form)
}

// Formater le prix pour l'affichage
function formatPrice(price: number | string | null | undefined): string {
  // Convertir en nombre si c'est une chaîne
  const numPrice = typeof price === 'string' ? parseFloat(price) : (price || 0)
  // Vérifier que c'est un nombre valide
  if (isNaN(numPrice)) {
    return '0,00'
  }
  return numPrice.toFixed(2).replace('.', ',')
}

// Watcher pour auto-sélectionner le type de cours s'il n'y en a qu'un seul
watch(() => props.courseTypes, (newCourseTypes) => {
  if (props.show && newCourseTypes) {
    console.log('🔍 [CreateLessonModal] Props mis à jour:', {
      courseTypesCount: newCourseTypes.length,
      slotDisciplineId: props.selectedSlot?.discipline_id,
      slotDisciplineName: props.selectedSlot?.discipline?.name,
      types: newCourseTypes.map(ct => ct.name)
    })
    
    // Auto-sélectionner s'il n'y a qu'un seul type de cours
    if (newCourseTypes.length === 1 && !props.form.course_type_id) {
      const courseType = newCourseTypes[0]
      props.form.course_type_id = courseType.id
      // Pré-remplir durée et prix
      props.form.duration = courseType.duration_minutes || courseType.duration || 60
      props.form.price = courseType.price || 0
      console.log('✨ [CreateLessonModal] Type de cours auto-sélectionné:', courseType.name)
    }
  }
}, { deep: true, immediate: true })

// Watcher pour auto-remplir durée et prix quand un type de cours est sélectionné
watch(() => props.form.course_type_id, async (newCourseTypeId, oldCourseTypeId) => {
  // Mettre à jour automatiquement à chaque changement de type de cours
  if (newCourseTypeId && props.courseTypes.length > 0) {
    const selectedCourseType = props.courseTypes.find(ct => ct.id === newCourseTypeId)
    if (selectedCourseType) {
      // Toujours mettre à jour la durée et le prix selon le type de cours sélectionné
      props.form.duration = selectedCourseType.duration_minutes || selectedCourseType.duration || 60
      props.form.price = selectedCourseType.price || 0
      
      console.log('✨ [CreateLessonModal] Durée et prix mis à jour automatiquement:', {
        duration: props.form.duration,
        price: props.form.price,
        courseType: selectedCourseType.name,
        previousType: oldCourseTypeId
      })
      
      // Attendre que availableTimes soit recalculé avec la nouvelle durée
      await nextTick()
      // Auto-sélectionner la première heure disponible
      if (availableTimes.value.length > 0 && props.form.date) {
        props.form.time = availableTimes.value[0].value
        console.log('✨ [CreateLessonModal] Première heure disponible auto-sélectionnée après changement de type de cours:', availableTimes.value[0].value)
      }
    }
  }
}, { immediate: false })

// Charger les cours existants pour calculer les heures disponibles
const existingLessons = ref<any[]>([])
const loadingLessons = ref(false)

// Fonction pour charger les cours existants pour une date donnée
async function loadExistingLessons(date: string) {
  if (!date || !props.selectedSlot) {
    existingLessons.value = []
    return
  }
  
  try {
    loadingLessons.value = true
    const { $api } = useNuxtApp()
    const response = await $api.get('/lessons', {
      params: {
        date_from: date,
        date_to: date
      }
    })
    
    if (response.data.success) {
      existingLessons.value = response.data.data || []
    } else {
      existingLessons.value = []
    }
  } catch (err) {
    console.error('Erreur chargement cours existants:', err)
    existingLessons.value = []
  } finally {
    loadingLessons.value = false
  }
}

// Convertir une heure (HH:MM) en minutes depuis minuit
function timeToMinutes(time: string): number {
  const [hours, minutes] = time.split(':').map(Number)
  return hours * 60 + minutes
}

// Convertir des minutes depuis minuit en heure (HH:MM)
function minutesToTime(minutes: number): string {
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`
}

// Générer toutes les heures possibles pour le mode édition (00:00 à 23:30)
const allPossibleTimes = computed(() => {
  const times: { value: string; label: string }[] = []
  for (let hour = 0; hour < 24; hour++) {
    times.push({
      value: `${String(hour).padStart(2, '0')}:00`,
      label: `${String(hour).padStart(2, '0')}:00`
    })
    times.push({
      value: `${String(hour).padStart(2, '0')}:30`,
      label: `${String(hour).padStart(2, '0')}:30`
    })
  }
  return times
})

// Calculer les heures disponibles pour le créneau sélectionné
const availableTimes = computed(() => {
  // En mode édition, utiliser le créneau trouvé ou toutes les heures possibles
  if (props.editingLesson) {
    // Si un créneau est trouvé pour la date, utiliser les heures du créneau
    const slotToUse = currentSelectedSlot.value || props.selectedSlot
    if (slotToUse && props.form.date && props.form.duration) {
      const slot = slotToUse
      const duration = props.form.duration || 60
      const date = props.form.date
      
      // Extraire les heures de début et fin du créneau
      const slotStart = slot.start_time?.substring(0, 5) || '09:00'
      const slotEnd = slot.end_time?.substring(0, 5) || '18:00'
      
      const slotStartMinutes = timeToMinutes(slotStart)
      const slotEndMinutes = timeToMinutes(slotEnd)
      
      // Calculer le pas de temps (utiliser la durée du cours comme pas)
      const timeStep = duration
      
      // Générer toutes les heures possibles dans le créneau
      const allTimes: { value: string; label: string; minutes: number }[] = []
      
      for (let minutes = slotStartMinutes; minutes + duration <= slotEndMinutes; minutes += timeStep) {
        const timeStr = minutesToTime(minutes)
        allTimes.push({
          value: timeStr,
          label: timeStr,
          minutes
        })
      }
      
      // Filtrer les heures qui sont déjà complètes (max_slots atteint)
      const maxSlots = slot.max_slots || 1
      
      const available = allTimes.filter(time => {
        // Vérifier combien de cours se chevauchent avec cette heure
        const timeStart = new Date(`${date}T${time.value}:00`)
        const timeEnd = new Date(timeStart.getTime() + duration * 60000)
        
        let overlappingCount = 0
        
        for (const lesson of existingLessons.value) {
          // Exclure le cours en cours d'édition
          if (props.editingLesson && lesson.id === props.editingLesson.id) {
            continue
          }
          
          if (lesson.status === 'cancelled') continue
          
          const lessonStart = new Date(lesson.start_time)
          let lessonEnd: Date
          
          // Calculer la fin du cours existant
          if (lesson.end_time) {
            lessonEnd = new Date(lesson.end_time)
          } else if (lesson.course_type?.duration_minutes) {
            lessonEnd = new Date(lessonStart.getTime() + lesson.course_type.duration_minutes * 60000)
          } else {
            lessonEnd = new Date(lessonStart.getTime() + 60 * 60000) // 60 min par défaut
          }
          
          // Vérifier le chevauchement
          if (timeStart < lessonEnd && timeEnd > lessonStart) {
            overlappingCount++
          }
        }
        
        // L'heure est disponible si le nombre de cours qui se chevauchent est strictement inférieur à max_slots
        return overlappingCount < maxSlots
      })
      
      return available
    }
    // Sinon, retourner toutes les heures possibles
    return allPossibleTimes.value
  }
  
  const slotToUse = currentSelectedSlot.value || props.selectedSlot
  if (!slotToUse || !props.form.date || !props.form.duration) {
    return []
  }
  
  const slot = slotToUse
  const duration = props.form.duration || 60
  const date = props.form.date
  
  // Extraire les heures de début et fin du créneau
  const slotStart = slot.start_time?.substring(0, 5) || '09:00'
  const slotEnd = slot.end_time?.substring(0, 5) || '18:00'
  
  const slotStartMinutes = timeToMinutes(slotStart)
  const slotEndMinutes = timeToMinutes(slotEnd)
  
  // Calculer le pas de temps (utiliser la durée du cours comme pas)
  const timeStep = duration
  
  // Générer toutes les heures possibles dans le créneau
  const allTimes: { value: string; label: string; minutes: number }[] = []
  
  for (let minutes = slotStartMinutes; minutes + duration <= slotEndMinutes; minutes += timeStep) {
    const timeStr = minutesToTime(minutes)
    allTimes.push({
      value: timeStr,
      label: timeStr,
      minutes
    })
  }
  
  // Filtrer les heures qui sont déjà complètes (max_slots atteint)
  // Les plages complètes sont automatiquement supprimées du select
  const maxSlots = slot.max_slots || 1
  
  const available = allTimes.filter(time => {
    // Vérifier combien de cours se chevauchent avec cette heure
    const timeStart = new Date(`${date}T${time.value}:00`)
    const timeEnd = new Date(timeStart.getTime() + duration * 60000)
    
    let overlappingCount = 0
    
    for (const lesson of existingLessons.value) {
      if (lesson.status === 'cancelled') continue
      
      const lessonStart = new Date(lesson.start_time)
      let lessonEnd: Date
      
      // Calculer la fin du cours existant
      if (lesson.end_time) {
        lessonEnd = new Date(lesson.end_time)
      } else if (lesson.course_type?.duration_minutes) {
        lessonEnd = new Date(lessonStart.getTime() + lesson.course_type.duration_minutes * 60000)
      } else {
        lessonEnd = new Date(lessonStart.getTime() + 60 * 60000) // 60 min par défaut
      }
      
      // Vérifier le chevauchement : le nouveau cours chevauche si :
      // - Il commence avant la fin du cours existant ET
      // - - Il se termine après le début du cours existant
      if (timeStart < lessonEnd && timeEnd > lessonStart) {
        overlappingCount++
      }
    }
    
    // L'heure est disponible UNIQUEMENT si le nombre de cours qui se chevauchent est STRICTEMENT inférieur à max_slots
    // Si overlappingCount >= maxSlots, la plage est complète et sera supprimée du select
    const isAvailable = overlappingCount < maxSlots
    
    if (!isAvailable) {
      console.log(`🚫 [availableTimes] Plage ${time.value} complète (${overlappingCount}/${maxSlots} cours) - supprimée du select`)
    }
    
    return isAvailable
  })
  
  console.log(`✅ [availableTimes] ${available.length} plage(s) horaire(s) disponible(s) sur ${allTimes.length} possibles`)
  
  return available
})

// Watcher pour mettre à jour le créneau quand la date change en mode édition
const currentSelectedSlot = ref<OpenSlot | null>(props.selectedSlot)

watch(() => props.selectedSlot, (newSlot) => {
  currentSelectedSlot.value = newSlot
  if (newSlot && props.editingLesson) {
    selectedSlotId.value = newSlot.id
  }
})

// Initialiser selectedSlotId quand editingLesson change
watch(() => props.editingLesson, (newEditingLesson) => {
  if (newEditingLesson && currentSelectedSlot.value) {
    selectedSlotId.value = currentSelectedSlot.value.id
  } else if (!newEditingLesson) {
    selectedSlotId.value = null
  }
}, { immediate: true })

watch(() => props.form.date, async (newDate, oldDate) => {
  // En mode édition, trouver le créneau correspondant au nouveau jour de la semaine
  // Mais seulement si aucun créneau n'a été sélectionné manuellement
  if (props.editingLesson && newDate && props.openSlots && props.openSlots.length > 0) {
    const date = new Date(newDate + 'T00:00:00')
    const dayOfWeek = date.getDay() // 0 = dimanche, 1 = lundi, etc.
    
    // Si un créneau est déjà sélectionné manuellement, vérifier qu'il correspond au jour
    if (selectedSlotId.value) {
      const selectedSlot = props.openSlots.find(s => s.id === selectedSlotId.value)
      if (selectedSlot && selectedSlot.day_of_week === dayOfWeek) {
        // Le créneau sélectionné correspond au jour, tout est OK
        currentSelectedSlot.value = selectedSlot
        return
      } else if (selectedSlot && selectedSlot.day_of_week !== dayOfWeek) {
        // Le créneau sélectionné ne correspond pas au jour, trouver un créneau correspondant
        const matchingSlot = props.openSlots.find(slot => slot.day_of_week === dayOfWeek)
        if (matchingSlot) {
          selectedSlotId.value = matchingSlot.id
          currentSelectedSlot.value = matchingSlot
          console.log('🎯 [CreateLessonModal] Créneau ajusté pour correspondre à la date:', {
            date: newDate,
            day_of_week: dayOfWeek,
            slot_id: matchingSlot.id
          })
        } else {
          currentSelectedSlot.value = null
          selectedSlotId.value = null
          console.warn('⚠️ [CreateLessonModal] Aucun créneau trouvé pour le jour:', dayOfWeek)
        }
        return
      }
    }
    
    // Aucun créneau sélectionné manuellement, trouver automatiquement
    const matchingSlot = props.openSlots.find(slot => slot.day_of_week === dayOfWeek)
    if (matchingSlot) {
      currentSelectedSlot.value = matchingSlot
      selectedSlotId.value = matchingSlot.id
      console.log('🎯 [CreateLessonModal] Créneau mis à jour pour la nouvelle date:', {
        date: newDate,
        day_of_week: dayOfWeek,
        slot_id: matchingSlot.id,
        slot_start: matchingSlot.start_time,
        slot_end: matchingSlot.end_time
      })
    } else {
      currentSelectedSlot.value = null
      selectedSlotId.value = null
      console.warn('⚠️ [CreateLessonModal] Aucun créneau trouvé pour le jour:', dayOfWeek)
    }
  }
}, { immediate: true })

// Watcher pour charger les cours existants quand la date change
watch(() => props.form.date, async (newDate, oldDate) => {
  if (newDate && (currentSelectedSlot.value || props.editingLesson)) {
    await loadExistingLessons(newDate)
    // Attendre que le computed availableTimes soit recalculé
    await nextTick()
    // En mode édition, ne pas changer l'heure si elle est déjà définie et disponible
    if (props.editingLesson && props.form.time) {
      const isCurrentTimeAvailable = availableTimes.value.some(t => t.value === props.form.time)
      if (!isCurrentTimeAvailable && availableTimes.value.length > 0) {
        // L'heure actuelle n'est plus disponible, sélectionner la première disponible
        props.form.time = availableTimes.value[0].value
        console.log('⚠️ [CreateLessonModal] Heure actuelle non disponible, première heure disponible sélectionnée:', availableTimes.value[0].value)
      } else if (isCurrentTimeAvailable) {
        console.log('✅ [CreateLessonModal] Heure actuelle toujours disponible:', props.form.time)
      }
    } else if (!props.editingLesson && currentSelectedSlot.value) {
      // Auto-sélectionner la première heure disponible (toujours, même si une heure était déjà sélectionnée)
      // car la date a changé, donc l'heure précédente pourrait ne plus être valide
      if (availableTimes.value.length > 0 && props.form.course_type_id) {
        props.form.time = availableTimes.value[0].value
        console.log('✨ [CreateLessonModal] Première heure disponible auto-sélectionnée après changement de date:', availableTimes.value[0].value)
      } else if (availableTimes.value.length === 0) {
        props.form.time = ''
        console.log('⚠️ [CreateLessonModal] Aucune heure disponible pour cette date')
      }
    }
  } else {
    existingLessons.value = []
    if (!props.editingLesson) {
      props.form.time = ''
    }
  }
}, { immediate: true })

// Watcher pour auto-sélectionner la première heure disponible quand availableTimes change
watch(() => availableTimes.value, (newTimes, oldTimes) => {
  // En mode édition, ne pas changer l'heure automatiquement
  if (props.editingLesson) {
    return
  }
  
  // Auto-sélectionner la première heure disponible si :
  // - Il y a des heures disponibles
  // - La date et le type de cours sont définis
  // - Aucune heure n'est sélectionnée OU l'heure sélectionnée n'est plus disponible
  if (newTimes.length > 0 && props.form.date && props.form.course_type_id) {
    const currentTime = props.form.time
    const isCurrentTimeAvailable = currentTime && newTimes.some(t => t.value === currentTime)
    
    // Si aucune heure n'est sélectionnée ou si l'heure actuelle n'est plus disponible
    if (!currentTime || !isCurrentTimeAvailable) {
      props.form.time = newTimes[0].value
      console.log('✨ [CreateLessonModal] Première heure disponible auto-sélectionnée depuis availableTimes:', newTimes[0].value)
    }
  } else if (newTimes.length === 0 && props.form.time) {
    // Si plus aucune heure n'est disponible, réinitialiser
    props.form.time = ''
    console.log('⚠️ [CreateLessonModal] Plus d\'heures disponibles, heure réinitialisée')
  }
}, { immediate: true })

// Watcher pour recharger les cours quand le créneau change (via selectedSlot ou currentSelectedSlot)
watch(() => [props.selectedSlot, currentSelectedSlot.value, selectedSlotId.value], async ([newSlot, newCurrentSlot, newSlotId]) => {
  const slotToUse = props.editingLesson ? newCurrentSlot : newSlot
  if (slotToUse && props.form.date) {
    await loadExistingLessons(props.form.date)
    // Attendre que le computed availableTimes soit recalculé
    await nextTick()
    // Auto-sélectionner la première heure disponible si le type de cours est défini (seulement en mode création)
    if (availableTimes.value.length > 0 && props.form.course_type_id && !props.editingLesson) {
      props.form.time = availableTimes.value[0].value
      console.log('✨ [CreateLessonModal] Première heure disponible auto-sélectionnée après changement de créneau:', availableTimes.value[0].value)
    }
  } else {
    existingLessons.value = []
    if (!props.editingLesson) {
      props.form.time = ''
    }
  }
})

// Watcher pour recharger les cours quand la durée change (pour recalculer les heures disponibles)
watch(() => props.form.duration, async () => {
  if (props.form.date && props.selectedSlot && props.form.course_type_id) {
    // Les heures disponibles sont recalculées automatiquement via le computed
    // Mais on peut recharger les cours si nécessaire
    await loadExistingLessons(props.form.date)
    // Attendre que le computed availableTimes soit recalculé
    await nextTick()
    // Auto-sélectionner la première heure disponible (toujours, car la durée a changé)
    if (availableTimes.value.length > 0) {
      props.form.time = availableTimes.value[0].value
      console.log('✨ [CreateLessonModal] Première heure disponible auto-sélectionnée après changement de durée:', availableTimes.value[0].value)
    } else {
      props.form.time = ''
      console.log('⚠️ [CreateLessonModal] Plus d\'heures disponibles après changement de durée')
    }
  }
})

// Watcher pour recalculer la disponibilité quand l'heure change
watch(() => props.form.time, () => {
  // La disponibilité est recalculée automatiquement via les fonctions isTeacherAvailable et isStudentAvailable
  // Pas besoin de recharger les cours, ils sont déjà chargés pour la date
})

// Vérifier si un enseignant est disponible pour la plage horaire sélectionnée
function isTeacherAvailable(teacherId: number): boolean {
  if (!props.form.date || !props.form.time || !props.form.duration) {
    return true // Si pas de date/heure/durée, considérer comme disponible
  }
  
  const lessonStart = new Date(`${props.form.date}T${props.form.time}:00`)
  const lessonEnd = new Date(lessonStart.getTime() + props.form.duration * 60000)
  
  // Vérifier si l'enseignant a déjà un cours qui se chevauche
  for (const lesson of existingLessons.value) {
    if (lesson.status === 'cancelled') continue
    if (lesson.teacher_id !== teacherId) continue
    
    const existingStart = new Date(lesson.start_time)
    let existingEnd: Date
    
    // Calculer la fin du cours existant
    if (lesson.end_time) {
      existingEnd = new Date(lesson.end_time)
    } else if (lesson.course_type?.duration_minutes) {
      existingEnd = new Date(existingStart.getTime() + lesson.course_type.duration_minutes * 60000)
    } else {
      existingEnd = new Date(existingStart.getTime() + 60 * 60000) // 60 min par défaut
    }
    
    // Vérifier le chevauchement
    if (lessonStart < existingEnd && lessonEnd > existingStart) {
      return false // L'enseignant n'est pas disponible
    }
  }
  
  return true // L'enseignant est disponible
}

// Vérifier si un élève est disponible pour la plage horaire sélectionnée
function isStudentAvailable(studentId: number): boolean {
  if (!props.form.date || !props.form.time || !props.form.duration) {
    return true // Si pas de date/heure/durée, considérer comme disponible
  }
  
  const lessonStart = new Date(`${props.form.date}T${props.form.time}:00`)
  const lessonEnd = new Date(lessonStart.getTime() + props.form.duration * 60000)
  
  // Vérifier si l'élève a déjà un cours qui se chevauche
  for (const lesson of existingLessons.value) {
    if (lesson.status === 'cancelled') continue
    
    // Vérifier si l'élève est l'étudiant principal
    if (lesson.student_id === studentId) {
      const existingStart = new Date(lesson.start_time)
      let existingEnd: Date
      
      // Calculer la fin du cours existant
      if (lesson.end_time) {
        existingEnd = new Date(lesson.end_time)
      } else if (lesson.course_type?.duration_minutes) {
        existingEnd = new Date(existingStart.getTime() + lesson.course_type.duration_minutes * 60000)
      } else {
        existingEnd = new Date(existingStart.getTime() + 60 * 60000) // 60 min par défaut
      }
      
      // Vérifier le chevauchement
      if (lessonStart < existingEnd && lessonEnd > existingStart) {
        return false // L'élève n'est pas disponible
      }
    }
    
    // Vérifier si l'élève est dans la relation many-to-many
    if (lesson.students && Array.isArray(lesson.students)) {
      const isInStudents = lesson.students.some((s: any) => s.id === studentId)
      if (isInStudents) {
        const existingStart = new Date(lesson.start_time)
        let existingEnd: Date
        
        // Calculer la fin du cours existant
        if (lesson.end_time) {
          existingEnd = new Date(lesson.end_time)
        } else if (lesson.course_type?.duration_minutes) {
          existingEnd = new Date(existingStart.getTime() + lesson.course_type.duration_minutes * 60000)
        } else {
          existingEnd = new Date(existingStart.getTime() + 60 * 60000) // 60 min par défaut
        }
        
        // Vérifier le chevauchement
        if (lessonStart < existingEnd && lessonEnd > existingStart) {
          return false // L'élève n'est pas disponible
        }
      }
    }
  }
  
  return true // L'élève est disponible
}
</script>

