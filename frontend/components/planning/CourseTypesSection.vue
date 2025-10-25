<template>
  <CollapsibleSection
    title="Types de cours disponibles"
    icon="📚"
    :count="courseTypes.length"
    :default-expanded="true"
  >
    <template #actions>
      <button
        @click.stop="openAddModal"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2"
      >
        <span>➕</span>
        <span>Nouveau type</span>
      </button>
    </template>

    <!-- Liste des types de cours -->
    <div v-if="courseTypes.length > 0" class="space-y-3">
      <div 
        v-for="courseType in courseTypes" 
        :key="courseType.id"
        class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between">
          <!-- Informations du type -->
          <div class="flex-1">
            <div class="flex items-center space-x-3 mb-2">
              <h3 class="text-lg font-semibold text-gray-800">{{ courseType.name }}</h3>
              
              <!-- Badge discipline -->
              <span 
                v-if="courseType.discipline_id"
                class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium"
              >
                {{ getDisciplineName(courseType.discipline_id) }}
              </span>
              <span 
                v-else
                class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs font-medium"
              >
                Générique
              </span>
              
              <!-- Badge actif/inactif -->
              <span 
                :class="courseType.is_active !== false ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                class="px-2 py-1 rounded text-xs font-medium"
              >
                {{ courseType.is_active !== false ? '✓ Actif' : '✗ Inactif' }}
              </span>
            </div>
            
            <!-- Détails -->
            <div class="flex flex-wrap gap-4 text-sm text-gray-600">
              <div class="flex items-center space-x-1">
                <span>⏱️</span>
                <span><strong>{{ courseType.duration_minutes || courseType.duration || 60 }}</strong> min</span>
              </div>
              
              <div class="flex items-center space-x-1">
                <span>💰</span>
                <span><strong>{{ courseType.price || 0 }}</strong> €</span>
              </div>
              
              <div class="flex items-center space-x-1">
                <span>👥</span>
                <span v-if="courseType.is_individual">
                  Individuel
                </span>
                <span v-else>
                  Groupe ({{ courseType.min_participants || 2 }}-{{ courseType.max_participants || 8 }})
                </span>
              </div>
            </div>
            
            <!-- Description si présente -->
            <p v-if="courseType.description" class="text-sm text-gray-500 mt-2">
              {{ courseType.description }}
            </p>
          </div>
          
          <!-- Actions -->
          <div class="flex items-center space-x-2 ml-4">
            <button
              @click="$emit('edit', courseType)"
              class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
              title="Éditer"
            >
              📝
            </button>
            
            <button
              @click="$emit('toggle-active', courseType)"
              :class="courseType.is_active !== false ? 'text-orange-600 hover:bg-orange-50' : 'text-green-600 hover:bg-green-50'"
              class="p-2 rounded-lg transition-colors"
              :title="courseType.is_active !== false ? 'Désactiver' : 'Activer'"
            >
              {{ courseType.is_active !== false ? '⏸️' : '▶️' }}
            </button>
            
            <button
              @click="$emit('delete', courseType)"
              class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
              title="Supprimer"
            >
              🗑️
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- État vide -->
    <div v-else class="text-center py-12 text-gray-500">
      <div class="text-4xl mb-4">📚</div>
      <p class="text-lg mb-2">Aucun type de cours configuré</p>
      <p class="text-sm">Cliquez sur "Nouveau type" pour en ajouter un</p>
    </div>
  </CollapsibleSection>
</template>

<script setup>
import CollapsibleSection from './CollapsibleSection.vue'

const props = defineProps({
  courseTypes: {
    type: Array,
    required: true
  },
  availableDisciplines: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['add', 'edit', 'toggle-active', 'delete'])

const getDisciplineName = (disciplineId) => {
  const discipline = props.availableDisciplines.find(d => d.id === parseInt(disciplineId))
  return discipline?.name || `Discipline ${disciplineId}`
}

const openAddModal = () => {
  emit('add')
}
</script>




