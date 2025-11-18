<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-2xl font-bold text-gray-900">
            Créer un nouveau cours
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
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- 1. Type de cours -->
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

          <!-- 2. Date -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Date *
              <span v-if="selectedSlot" class="text-xs text-blue-600 ml-2 font-medium">
                (Uniquement les {{ getDayName(selectedSlot.day_of_week) }}s)
              </span>
              <span v-else-if="availableDays.length > 0" class="text-xs text-gray-500 ml-2">
                (Jours disponibles: {{ availableDays.map(d => getDayName(d)).join(', ') }})
              </span>
            </label>
            <input 
              v-model="form.date" 
              type="date" 
              required
              :min="minDate"
              @input="validateDate"
              :class="[
                'w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500',
                form.date && !isDateAvailable(form.date) ? 'border-red-500 bg-red-50' : 'border-gray-300'
              ]" />
            <p v-if="form.date && !isDateAvailable(form.date)" class="text-xs text-red-600 mt-1">
              ⚠️ Cette date doit être un {{ getDayName(selectedSlot?.day_of_week || 0) }}
            </p>
            <p v-else-if="form.date && selectedSlot" class="text-xs text-green-600 mt-1">
              ✓ Date valide pour ce créneau
            </p>
            <!-- Suggestions de dates -->
            <div v-if="selectedSlot && suggestedDates.length > 0" class="mt-2">
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

          <!-- 3. Heure -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Heure *</label>
            <select 
              v-model="form.time" 
              required
              :disabled="!availableTimes.length"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
              <option :value="''">
                {{ availableTimes.length === 0 ? 'Aucune heure disponible' : 'Sélectionnez une heure' }}
              </option>
              <option v-for="time in availableTimes" :key="time.value" :value="time.value">
                {{ time.label }}
              </option>
            </select>
            <p v-if="selectedSlot && form.date && availableTimes.length === 0" class="text-xs text-red-600 mt-1">
              ⚠️ Aucune plage horaire disponible pour cette date. Le créneau est complet (toutes les plages sont occupées).
            </p>
            <p v-else-if="selectedSlot && form.date && availableTimes.length > 0" class="text-xs text-green-600 mt-1">
              ✓ {{ availableTimes.length }} plage(s) horaire(s) disponible(s) (les plages complètes sont automatiquement masquées)
            </p>
            <p v-if="loadingLessons" class="text-xs text-gray-500 mt-1">
              🔄 Chargement des cours existants...
            </p>
          </div>

          <!-- 4. Enseignant -->
          <div>
            <Autocomplete
              v-model="form.teacher_id"
              :items="teachers"
              label="Enseignant"
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

          <!-- 5. Élève (optionnel) -->
          <div>
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

          <!-- 6. Durée (affichage uniquement) -->
          <div>
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

          <!-- 7. Prix (affichage uniquement) -->
          <div>
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

          <!-- 8. Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="form.notes" rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                      placeholder="Notes sur le cours..."></textarea>
          </div>

          <!-- 9. Classification DCL/NDCL pour les commissions -->
          <div class="border-t pt-4">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              Classification pour les commissions
            </label>
            <div class="space-y-3">
              <div class="flex items-center">
                <input
                  id="dcl"
                  v-model="form.est_legacy"
                  :value="false"
                  type="radio"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                />
                <label for="dcl" class="ml-2 block text-sm text-gray-700">
                  <span class="font-medium">DCL</span> (Déclaré) - Commission standard
                </label>
              </div>
              <div class="flex items-center">
                <input
                  id="ndcl"
                  v-model="form.est_legacy"
                  :value="true"
                  type="radio"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                />
                <label for="ndcl" class="ml-2 block text-sm text-gray-700">
                  <span class="font-medium">NDCL</span> (Non Déclaré) - Commission legacy
                </label>
              </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">
              ⓘ Cette classification détermine le type de commission pour l'enseignant dans les rapports de paie.
            </p>
          </div>

          <!-- 10. Date de paiement et montant (optionnel) -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Date de paiement (optionnel)
              </label>
              <input
                v-model="form.date_paiement"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                placeholder="Date de paiement"
              />
              <p class="mt-1 text-xs text-gray-500">
                Détermine le mois de commission dans les rapports de paie
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Montant payé (optionnel)
              </label>
              <input
                v-model.number="form.montant"
                type="number"
                step="0.01"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                placeholder="Montant réellement payé"
              />
              <p class="mt-1 text-xs text-gray-500">
                Montant réellement payé (peut différer du prix du cours)
              </p>
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
              {{ saving ? 'Création...' : 'Créer le cours' }}
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
  date_paiement: string | null
  montant: number | null
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
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'close': []
  'submit': [form: LessonForm]
}>()

const dayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']

function getDayName(dayOfWeek: number): string {
  return dayNames[dayOfWeek] || 'Inconnu'
}

// Date minimale : aujourd'hui
const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

// Génère les 4 prochaines dates valides pour le créneau sélectionné
const suggestedDates = computed(() => {
  if (!props.selectedSlot) return []
  
  const dates: string[] = []
  const today = new Date()
  const targetDay = props.selectedSlot.day_of_week
  
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

// Calculer les heures disponibles pour le créneau sélectionné
const availableTimes = computed(() => {
  if (!props.selectedSlot || !props.form.date || !props.form.duration) {
    return []
  }
  
  const slot = props.selectedSlot
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

// Watcher pour charger les cours existants quand la date change
watch(() => props.form.date, async (newDate) => {
  if (newDate && props.selectedSlot) {
    await loadExistingLessons(newDate)
    // Attendre que le computed availableTimes soit recalculé
    await nextTick()
    // Auto-sélectionner la première heure disponible (toujours, même si une heure était déjà sélectionnée)
    // car la date a changé, donc l'heure précédente pourrait ne plus être valide
    if (availableTimes.value.length > 0 && props.form.course_type_id) {
      props.form.time = availableTimes.value[0].value
      console.log('✨ [CreateLessonModal] Première heure disponible auto-sélectionnée après changement de date:', availableTimes.value[0].value)
    } else if (availableTimes.value.length === 0) {
      props.form.time = ''
      console.log('⚠️ [CreateLessonModal] Aucune heure disponible pour cette date')
    }
  } else {
    existingLessons.value = []
    props.form.time = ''
  }
}, { immediate: true })

// Watcher pour auto-sélectionner la première heure disponible quand availableTimes change
watch(() => availableTimes.value, (newTimes, oldTimes) => {
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

// Watcher pour recharger les cours quand le créneau change
watch(() => props.selectedSlot, async (newSlot) => {
  if (newSlot && props.form.date) {
    await loadExistingLessons(props.form.date)
    // Attendre que le computed availableTimes soit recalculé
    await nextTick()
    // Auto-sélectionner la première heure disponible si le type de cours est défini
    if (availableTimes.value.length > 0 && props.form.course_type_id) {
      props.form.time = availableTimes.value[0].value
      console.log('✨ [CreateLessonModal] Première heure disponible auto-sélectionnée après changement de créneau:', availableTimes.value[0].value)
    }
  } else {
    existingLessons.value = []
    props.form.time = ''
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

