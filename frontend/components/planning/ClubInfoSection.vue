<template>
  <CollapsibleSection
    title="Informations du club"
    icon="🏢"
    :default-expanded="true"
  >
    <div v-if="clubProfile" class="space-y-4">
      <!-- Informations principales -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-blue-50 p-4 rounded-lg">
          <h3 class="text-sm font-semibold text-gray-600 mb-2">Nom du club</h3>
          <p class="text-lg font-bold text-gray-800">{{ clubProfile.name }}</p>
        </div>
        
        <div class="bg-green-50 p-4 rounded-lg">
          <h3 class="text-sm font-semibold text-gray-600 mb-2">Disciplines</h3>
          <div class="flex flex-wrap gap-2">
            <span 
              v-for="disciplineId in clubProfile.disciplines" 
              :key="disciplineId"
              class="px-3 py-1 bg-green-200 text-green-800 rounded-full text-sm font-medium"
            >
              {{ getDisciplineName(disciplineId) }}
            </span>
          </div>
        </div>
      </div>
      
      <!-- Statistiques rapides -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="text-center p-3 bg-gray-50 rounded-lg">
          <div class="text-2xl font-bold text-blue-600">{{ stats.courseTypes }}</div>
          <div class="text-xs text-gray-600">Types de cours</div>
        </div>
        
        <div class="text-center p-3 bg-gray-50 rounded-lg">
          <div class="text-2xl font-bold text-purple-600">{{ stats.openSlots }}</div>
          <div class="text-xs text-gray-600">Créneaux ouverts</div>
        </div>
        
        <div class="text-center p-3 bg-gray-50 rounded-lg">
          <div class="text-2xl font-bold text-green-600">{{ stats.lessonsThisWeek }}</div>
          <div class="text-xs text-gray-600">Cours cette semaine</div>
        </div>
        
        <div class="text-center p-3 bg-gray-50 rounded-lg">
          <div class="text-2xl font-bold text-orange-600">{{ stats.teachers }}</div>
          <div class="text-xs text-gray-600">Enseignants</div>
        </div>
      </div>
    </div>
    
    <div v-else class="text-center text-gray-500 py-8">
      Chargement des informations du club...
    </div>
  </CollapsibleSection>
</template>

<script setup>
import { computed } from 'vue'
import CollapsibleSection from './CollapsibleSection.vue'

const props = defineProps({
  clubProfile: {
    type: Object,
    default: null
  },
  availableDisciplines: {
    type: Array,
    default: () => []
  },
  courseTypes: {
    type: Array,
    default: () => []
  },
  openSlots: {
    type: Array,
    default: () => []
  },
  lessons: {
    type: Array,
    default: () => []
  },
  teachers: {
    type: Array,
    default: () => []
  }
})

const getDisciplineName = (disciplineId) => {
  const discipline = props.availableDisciplines.find(d => d.id === parseInt(disciplineId))
  return discipline?.name || `Discipline ${disciplineId}`
}

const stats = computed(() => ({
  courseTypes: props.courseTypes.length,
  openSlots: props.openSlots.length,
  lessonsThisWeek: props.lessons.length,
  teachers: props.teachers.length
}))
</script>




