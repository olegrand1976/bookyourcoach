<template>
  <div class="bg-white shadow rounded-lg p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">Créneaux disponibles</h2>
        <p class="text-sm text-gray-500 mt-1">Cliquez sur un créneau pour créer un cours</p>
      </div>
      <button 
        @click="$emit('create-lesson')"
        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nouveau cours
      </button>
    </div>

    <!-- Grille des créneaux par jour -->
    <div v-if="slotsByDay && Object.keys(slotsByDay).length > 0" class="space-y-6">
      <div v-for="(daySlots, day) in slotsByDay" :key="day" class="border border-gray-200 rounded-lg p-4">
        <!-- En-tête du jour -->
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">
            {{ getDayName(parseInt(day)) }}
          </h3>
          <span class="text-sm text-gray-500">
            {{ daySlots.length }} créneau{{ daySlots.length > 1 ? 'x' : '' }}
          </span>
        </div>

        <!-- Liste des créneaux pour ce jour -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div 
            v-for="slot in daySlots" 
            :key="slot.id"
            @click="handleSlotClick(slot)"
            class="border-2 border-gray-200 rounded-lg p-4 cursor-pointer transition-all hover:border-blue-500 hover:shadow-md"
            :class="{ 'opacity-50': !slot.is_active }"
          >
            <!-- Horaires -->
            <div class="flex items-center gap-2 mb-3">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-base font-semibold text-gray-900">
                {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
              </span>
            </div>

            <!-- Discipline -->
            <div class="mb-3">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                    :class="getDisciplineColorClass(slot.discipline_id)">
                {{ slot.discipline?.name || 'Discipline non définie' }}
              </span>
            </div>

            <!-- Informations -->
            <div class="space-y-2 text-sm text-gray-600">
              <div v-if="slot.duration" class="flex items-center gap-2">
                <span>⏱️</span>
                <span>{{ slot.duration }} min</span>
              </div>
              <div v-if="slot.price" class="flex items-center gap-2">
                <span>💰</span>
                <span>{{ formatPrice(slot.price) }} €</span>
              </div>
              <div v-if="slot.max_capacity" class="flex items-center gap-2">
                <span>👥</span>
                <span>{{ slot.max_capacity }} participant{{ slot.max_capacity > 1 ? 's' : '' }}</span>
              </div>
              <div v-if="slot.max_slots && slot.max_slots > 1" class="flex items-center gap-2 text-blue-600 font-medium">
                <span>📅</span>
                <span>× {{ slot.max_slots }} plages simultanées</span>
              </div>
            </div>

            <!-- Bouton CTA -->
            <div class="mt-4 pt-3 border-t border-gray-200">
              <button class="w-full px-3 py-2 bg-blue-50 text-blue-700 rounded-md hover:bg-blue-100 transition-colors text-sm font-medium">
                + Créer un cours
              </button>
            </div>

            <!-- Badge statut -->
            <div class="mt-2">
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                    :class="slot.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'">
                {{ slot.is_active ? '✓ Actif' : 'Inactif' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- État vide -->
    <div v-else class="text-center py-12 text-gray-500">
      <div class="text-4xl mb-4">📅</div>
      <p class="text-lg mb-2">Aucun créneau disponible</p>
      <p class="text-sm">Créez des créneaux horaires pour commencer à planifier vos cours</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface OpenSlot {
  id: number
  day_of_week: number
  start_time: string
  end_time: string
  discipline_id: number | null
  discipline?: any
  duration?: number
  price?: number
  max_capacity?: number
  max_slots?: number
  is_active: boolean
}

interface Props {
  slots: OpenSlot[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'select-slot': [slot: OpenSlot]
  'create-lesson': []
}>()

const dayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']

function handleSlotClick(slot: OpenSlot) {
  console.log('🎯 [AvailableSlotsGrid] Créneau cliqué', {
    slotId: slot.id,
    slotDisciplineId: slot.discipline_id,
    slotDisciplineName: slot.discipline?.name,
    slotData: {
      day_of_week: slot.day_of_week,
      start_time: slot.start_time,
      end_time: slot.end_time,
      duration: slot.duration,
      price: slot.price,
      discipline: slot.discipline
    }
  })
  emit('select-slot', slot)
}

// Grouper les créneaux par jour
const slotsByDay = computed(() => {
  const grouped: Record<number, OpenSlot[]> = {}
  
  props.slots.forEach(slot => {
    if (!grouped[slot.day_of_week]) {
      grouped[slot.day_of_week] = []
    }
    grouped[slot.day_of_week].push(slot)
  })
  
  // Trier les créneaux de chaque jour par heure de début
  Object.keys(grouped).forEach(day => {
    grouped[parseInt(day)].sort((a, b) => a.start_time.localeCompare(b.start_time))
  })
  
  return grouped
})

function getDayName(dayOfWeek: number): string {
  return dayNames[dayOfWeek] || 'Inconnu'
}

function formatTime(time: string): string {
  return time?.substring(0, 5) || ''
}

function formatPrice(price: number | string): string {
  const numPrice = typeof price === 'string' ? parseFloat(price) : price
  return isNaN(numPrice) ? '0.00' : numPrice.toFixed(2)
}

function getDisciplineColorClass(disciplineId: number | null): string {
  const colors = [
    'bg-blue-100 text-blue-800',
    'bg-green-100 text-green-800',
    'bg-purple-100 text-purple-800',
    'bg-orange-100 text-orange-800',
    'bg-pink-100 text-pink-800',
    'bg-indigo-100 text-indigo-800',
    'bg-amber-100 text-amber-800',
    'bg-red-100 text-red-800',
    'bg-cyan-100 text-cyan-800',
    'bg-teal-100 text-teal-800',
  ]
  
  const index = (disciplineId || 0) % colors.length
  return colors[index]
}
</script>

