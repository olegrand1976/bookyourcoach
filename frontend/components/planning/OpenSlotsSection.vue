<template>
  <CollapsibleSection
    title="Créneaux disponibles"
    icon="🕐"
    :count="openSlots.length"
    :default-expanded="true"
  >
    <template #actions>
      <button
        @click.stop="$emit('add')"
        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center space-x-2"
      >
        <span>➕</span>
        <span>Nouveau créneau</span>
      </button>
    </template>

    <!-- Grille de cartes -->
    <div v-if="openSlots.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <OpenSlotCard
        v-for="slot in sortedSlots"
        :key="slot.id"
        :slot="slot"
        :available-disciplines="availableDisciplines"
        :lessons-count="getLessonsCountForSlot(slot)"
        @edit="$emit('edit', $event)"
        @manage-types="$emit('manage-types', $event)"
        @delete="$emit('delete', $event)"
      />
    </div>
    
    <!-- État vide -->
    <div v-else class="text-center py-12 text-gray-500">
      <div class="text-4xl mb-4">🕐</div>
      <p class="text-lg mb-2">Aucun créneau configuré</p>
      <p class="text-sm">Cliquez sur "Nouveau créneau" pour en ajouter un</p>
    </div>
    
    <!-- Aide rapide -->
    <div v-if="openSlots.length > 0" class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
      <div class="flex items-start space-x-3">
        <span class="text-2xl">💡</span>
        <div class="text-sm text-blue-800">
          <p class="font-semibold mb-1">Astuce :</p>
          <p>Cliquez sur "Types" pour définir quels types de cours peuvent être créés sur ce créneau. Seuls les types configurés apparaîtront lors de la création d'un cours.</p>
        </div>
      </div>
    </div>
  </CollapsibleSection>
</template>

<script setup>
import { computed } from 'vue'
import CollapsibleSection from './CollapsibleSection.vue'
import OpenSlotCard from './OpenSlotCard.vue'

const props = defineProps({
  openSlots: {
    type: Array,
    required: true
  },
  availableDisciplines: {
    type: Array,
    default: () => []
  },
  lessons: {
    type: Array,
    default: () => []
  }
})

defineEmits(['add', 'edit', 'manage-types', 'delete'])

// Trier les créneaux par jour puis par heure
const sortedSlots = computed(() => {
  return [...props.openSlots].sort((a, b) => {
    if (a.day_of_week !== b.day_of_week) {
      return a.day_of_week - b.day_of_week
    }
    return a.start_time.localeCompare(b.start_time)
  })
})

// Compter les cours pour un créneau donné cette semaine
const getLessonsCountForSlot = (slot) => {
  // TODO: Implémenter le comptage réel des cours pour ce créneau
  // Pour l'instant, retourner null
  return null
}
</script>




