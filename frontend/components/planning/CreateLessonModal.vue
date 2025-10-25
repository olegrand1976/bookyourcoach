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
          <!-- Enseignant -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Enseignant *</label>
            <select v-model.number="form.teacher_id" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
              <option :value="null">Sélectionnez un enseignant</option>
              <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                {{ teacher.user?.name || teacher.name }}
              </option>
            </select>
          </div>

          <!-- Étudiant (optionnel) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Étudiant (optionnel)</label>
            <select v-model.number="form.student_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
              <option :value="null">Aucun étudiant assigné</option>
              <option v-for="student in students" :key="student.id" :value="student.id">
                {{ student.user?.name || student.name }}
                <template v-if="student.age"> ({{ student.age }} ans)</template>
              </option>
            </select>
          </div>

          <!-- Type de cours -->
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
                  ⚠️ Aucun type de cours configuré pour ce créneau
                  <br>
                  <span class="text-xs">Veuillez d'abord associer des types de cours à ce créneau.</span>
                </p>
                <p v-else-if="selectedSlot && courseTypes.length > 0" class="text-xs text-green-600 mt-1">
                  ✓ {{ courseTypes.length }} type(s) de cours disponible(s) pour ce créneau
                </p>
          </div>

          <!-- Date -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Date *
              <span v-if="availableDays.length > 0" class="text-xs text-gray-500 ml-2">
                (Jours disponibles: {{ availableDays.map(d => getDayName(d)).join(', ') }})
              </span>
            </label>
            <input v-model="form.date" type="date" required
                   :class="[
                     'w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500',
                     form.date && !isDateAvailable(form.date) ? 'border-red-500 bg-red-50' : 'border-gray-300'
                   ]" />
            <p v-if="form.date && !isDateAvailable(form.date)" class="text-xs text-red-600 mt-1">
              ⚠️ Cette date ne correspond à aucun créneau disponible
            </p>
          </div>

          <!-- Heure -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Heure *</label>
            <input v-model="form.time" type="time" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
          </div>

          <!-- Durée -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Durée (minutes) *</label>
            <input v-model.number="form.duration" type="number" min="15" step="5" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
          </div>

          <!-- Prix -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Prix (€) *</label>
            <input v-model.number="form.price" type="number" min="0" step="0.01" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="form.notes" rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                      placeholder="Notes sur le cours..."></textarea>
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
import { computed, watch } from 'vue'

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

function isDateAvailable(dateStr: string): boolean {
  if (!dateStr) return false
  const date = new Date(dateStr)
  const dayOfWeek = date.getDay()
  return props.availableDays.includes(dayOfWeek)
}

function handleSubmit() {
  emit('submit', props.form)
}

// Watcher pour débugger les types de cours reçus
watch(() => props.courseTypes, (newCourseTypes) => {
  if (props.show && newCourseTypes) {
    console.log('🔍 [CreateLessonModal] Props mis à jour:', {
      courseTypesCount: newCourseTypes.length,
      slotDisciplineId: props.selectedSlot?.discipline_id,
      slotDisciplineName: props.selectedSlot?.discipline?.name,
      types: newCourseTypes.map(ct => ct.name)
    })
  }
}, { deep: true })
</script>

