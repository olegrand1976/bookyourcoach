<template>
  <CollapsibleSection
    title="Calendrier & Planification"
    icon="📅"
    :default-expanded="true"
  >
    <template #actions>
      <div class="flex items-center space-x-2">
        <!-- Toggle vue semaine/jour -->
        <div class="bg-gray-100 rounded-lg p-1 flex">
          <button
            @click.stop="$emit('change-view', 'week')"
            :class="viewMode === 'week' ? 'bg-white shadow' : 'text-gray-600'"
            class="px-4 py-2 rounded-md text-sm font-medium transition-all"
          >
            🗓️ Semaine
          </button>
          <button
            @click.stop="$emit('change-view', 'day')"
            :class="viewMode === 'day' ? 'bg-white shadow' : 'text-gray-600'"
            class="px-4 py-2 rounded-md text-sm font-medium transition-all"
          >
            📅 Jour
          </button>
        </div>
      </div>
    </template>

    <!-- Info box -->
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
      <div class="flex items-start space-x-3">
        <span class="text-2xl">👆</span>
        <div class="text-sm text-green-800">
          <p class="font-semibold mb-1">Créer un cours</p>
          <p>Cliquez sur un créneau coloré dans le calendrier pour créer un nouveau cours. Les types de cours disponibles seront automatiquement filtrés selon la configuration du créneau.</p>
        </div>
      </div>
    </div>

    <!-- Le contenu du calendrier sera inséré ici via slot -->
    <slot></slot>
  </CollapsibleSection>
</template>

<script setup>
const props = defineProps({
  viewMode: {
    type: String,
    default: 'week'
  }
})

defineEmits(['change-view'])
</script>




