<template>
  <div class="open-slot-card border-2 border-gray-200 rounded-xl p-4 hover:shadow-lg transition-all bg-white">
    <!-- En-tête -->
    <div class="flex items-start justify-between mb-3">
      <div class="flex-1">
        <div class="flex items-center space-x-2 mb-1">
          <span class="text-2xl">🕐</span>
          <h3 class="text-lg font-bold text-gray-800">{{ dayName }}</h3>
        </div>
        
        <div class="text-sm text-gray-600">
          {{ slot.start_time.substring(0, 5) }} - {{ slot.end_time.substring(0, 5) }}
        </div>
      </div>
      
      <!-- Badge discipline -->
      <span 
        v-if="slot.discipline_id"
        class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-medium"
      >
        {{ getDisciplineName(slot.discipline_id) }}
      </span>
    </div>
    
    <!-- Informations -->
    <div class="space-y-2 mb-4">
      <div class="flex items-center justify-between text-sm">
        <span class="text-gray-600">Capacité</span>
        <span class="font-semibold text-gray-800">{{ slot.max_capacity || 5 }} cours simultanés</span>
      </div>
      
      <div v-if="lessonsCount !== null" class="flex items-center justify-between text-sm">
        <span class="text-gray-600">Cours cette semaine</span>
        <span class="font-semibold" :class="lessonsCount > 0 ? 'text-green-600' : 'text-gray-400'">
          {{ lessonsCount }}
        </span>
      </div>
    </div>
    
    <!-- Types de cours autorisés -->
    <div class="mb-4">
      <div class="flex items-center justify-between mb-2">
        <h4 class="text-sm font-semibold text-gray-700">Types autorisés</h4>
        <span class="text-xs text-gray-500">({{ courseTypesCount }})</span>
      </div>
      
      <div v-if="courseTypesCount > 0" class="space-y-1.5">
        <div 
          v-for="courseType in displayedCourseTypes" 
          :key="courseType.id"
          class="flex items-center justify-between text-xs bg-gray-50 px-2 py-1.5 rounded"
        >
          <span class="font-medium text-gray-700">{{ courseType.name }}</span>
          <div class="flex items-center space-x-2 text-gray-500">
            <span>⏱️ {{ courseType.duration_minutes || courseType.duration || 60 }}min</span>
            <span>💰 {{ courseType.price || 0 }}€</span>
          </div>
        </div>
        
        <!-- Afficher "X autres" si plus de 3 types -->
        <div v-if="courseTypesCount > 3" class="text-xs text-gray-500 text-center py-1">
          + {{ courseTypesCount - 3 }} autre(s)
        </div>
      </div>
      
      <div v-else class="text-xs text-gray-400 text-center py-2">
        Aucun type configuré
      </div>
    </div>
    
    <!-- Actions -->
    <div class="flex items-center justify-between gap-2 pt-3 border-t border-gray-200">
      <button
        @click="$emit('edit', slot)"
        class="flex-1 px-3 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-sm font-medium transition-colors flex items-center justify-center space-x-1"
      >
        <span>📝</span>
        <span>Éditer</span>
      </button>
      
      <button
        @click="$emit('manage-types', slot)"
        class="flex-1 px-3 py-2 bg-purple-50 text-purple-600 hover:bg-purple-100 rounded-lg text-sm font-medium transition-colors flex items-center justify-center space-x-1"
      >
        <span>🎯</span>
        <span>Types</span>
      </button>
      
      <button
        @click="$emit('delete', slot)"
        class="px-3 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-sm font-medium transition-colors"
        title="Supprimer"
      >
        🗑️
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  slot: {
    type: Object,
    required: true
  },
  availableDisciplines: {
    type: Array,
    default: () => []
  },
  lessonsCount: {
    type: Number,
    default: null
  }
})

defineEmits(['edit', 'manage-types', 'delete'])

const dayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']

const dayName = computed(() => dayNames[props.slot.day_of_week] || `Jour ${props.slot.day_of_week}`)

const getDisciplineName = (disciplineId) => {
  const discipline = props.availableDisciplines.find(d => d.id === parseInt(disciplineId))
  return discipline?.name || `Discipline ${disciplineId}`
}

const courseTypesCount = computed(() => {
  return props.slot.course_types?.length || props.slot.courseTypes?.length || 0
})

const displayedCourseTypes = computed(() => {
  const types = props.slot.course_types || props.slot.courseTypes || []
  return types.slice(0, 3) // Afficher max 3 types
})
</script>




