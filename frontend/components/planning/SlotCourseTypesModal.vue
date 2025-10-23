<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
      <!-- En-tête -->
      <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-blue-50">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-bold text-gray-800 mb-1">
              🎯 Gérer les types de cours
            </h2>
            <p class="text-sm text-gray-600" v-if="slot">
              {{ dayName }} • {{ slot.start_time.substring(0, 5) }} - {{ slot.end_time.substring(0, 5) }}
            </p>
          </div>
          
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-white rounded-lg"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
      
      <!-- Contenu scrollable -->
      <div class="flex-1 overflow-y-auto p-6">
        <!-- Information -->
        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
          <div class="flex items-start space-x-3">
            <span class="text-2xl">ℹ️</span>
            <div class="text-sm text-yellow-800">
              <p class="font-semibold mb-1">Filtrage automatique</p>
              <p>Seuls les types de cours correspondant à la discipline du créneau ({{ getDisciplineName(slot?.discipline_id) }}) sont affichés ci-dessous.</p>
            </div>
          </div>
        </div>
        
        <!-- Liste des types de cours disponibles -->
        <div class="space-y-3">
          <div 
            v-for="courseType in availableCourseTypes" 
            :key="courseType.id"
            class="border-2 rounded-lg p-4 transition-all cursor-pointer"
            :class="isSelected(courseType.id) ? 'border-purple-500 bg-purple-50' : 'border-gray-200 hover:border-purple-300'"
            @click="toggleCourseType(courseType.id)"
          >
            <div class="flex items-start">
              <!-- Checkbox -->
              <div class="mt-1 mr-4">
                <input
                  type="checkbox"
                  :checked="isSelected(courseType.id)"
                  class="w-5 h-5 text-purple-600 rounded border-gray-300 focus:ring-purple-500"
                  @click.stop="toggleCourseType(courseType.id)"
                >
              </div>
              
              <!-- Informations du type -->
              <div class="flex-1">
                <div class="flex items-center space-x-2 mb-2">
                  <h3 class="text-lg font-semibold text-gray-800">{{ courseType.name }}</h3>
                  
                  <span 
                    v-if="courseType.discipline_id"
                    class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium"
                  >
                    {{ getDisciplineName(courseType.discipline_id) }}
                  </span>
                </div>
                
                <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                  <div class="flex items-center space-x-1">
                    <span>⏱️</span>
                    <span>{{ courseType.duration_minutes || courseType.duration || 60 }} min</span>
                  </div>
                  
                  <div class="flex items-center space-x-1">
                    <span>💰</span>
                    <span>{{ courseType.price || 0 }} €</span>
                  </div>
                  
                  <div class="flex items-center space-x-1">
                    <span>👥</span>
                    <span v-if="courseType.is_individual">Individuel</span>
                    <span v-else>Groupe ({{ courseType.min_participants || 2 }}-{{ courseType.max_participants || 8 }})</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- État vide -->
        <div v-if="availableCourseTypes.length === 0" class="text-center py-12 text-gray-500">
          <div class="text-4xl mb-4">📚</div>
          <p class="text-lg mb-2">Aucun type de cours disponible</p>
          <p class="text-sm">Créez d'abord des types de cours correspondant à la discipline du créneau</p>
        </div>
      </div>
      
      <!-- Pied de page -->
      <div class="p-6 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between mb-4">
          <div class="text-sm text-gray-600">
            <span class="font-semibold text-gray-800">{{ selectedCount }}</span> type(s) sélectionné(s)
          </div>
        </div>
        
        <div class="flex items-center justify-end space-x-3">
          <button
            @click="$emit('close')"
            class="px-6 py-2 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors font-medium"
          >
            Annuler
          </button>
          
          <button
            @click="saveSelection"
            :disabled="saving"
            class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  slot: {
    type: Object,
    default: null
  },
  availableCourseTypes: {
    type: Array,
    default: () => []
  },
  availableDisciplines: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'save'])

const saving = ref(false)
const selectedCourseTypeIds = ref([])

const dayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']

const dayName = computed(() => {
  if (!props.slot) return ''
  return dayNames[props.slot.day_of_week] || `Jour ${props.slot.day_of_week}`
})

const getDisciplineName = (disciplineId) => {
  if (!disciplineId) return 'Aucune discipline'
  const discipline = props.availableDisciplines.find(d => d.id === parseInt(disciplineId))
  return discipline?.name || `Discipline ${disciplineId}`
}

const selectedCount = computed(() => selectedCourseTypeIds.value.length)

const isSelected = (courseTypeId) => {
  return selectedCourseTypeIds.value.includes(courseTypeId)
}

const toggleCourseType = (courseTypeId) => {
  const index = selectedCourseTypeIds.value.indexOf(courseTypeId)
  if (index > -1) {
    selectedCourseTypeIds.value.splice(index, 1)
  } else {
    selectedCourseTypeIds.value.push(courseTypeId)
  }
}

const saveSelection = async () => {
  saving.value = true
  try {
    await emit('save', {
      slotId: props.slot.id,
      courseTypeIds: selectedCourseTypeIds.value
    })
  } finally {
    saving.value = false
  }
}

// Initialiser la sélection quand la modale s'ouvre
watch(() => props.show, (newShow) => {
  if (newShow && props.slot) {
    // Récupérer les IDs des types déjà assignés au créneau
    const existingTypes = props.slot.course_types || props.slot.courseTypes || []
    selectedCourseTypeIds.value = existingTypes.map(ct => ct.id)
  }
}, { immediate: true })
</script>




