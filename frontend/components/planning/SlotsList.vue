<template>
  <div class="bg-white shadow rounded-lg p-4 md:p-6">
    <!-- Header avec bouton toggle -->
    <div class="flex items-center justify-between mb-4">
      <button 
        @click="isOpen = !isOpen"
        class="flex items-center gap-3 text-left flex-1">
        <svg 
          class="w-5 h-5 text-gray-600 transition-transform"
          :class="{ 'rotate-180': isOpen }"
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
        <div>
          <h2 class="text-lg md:text-xl font-semibold text-gray-900">Créneaux horaires</h2>
          <p class="text-xs md:text-sm text-gray-500 mt-1">
            {{ slots.length }} créneau{{ slots.length > 1 ? 'x' : '' }} configuré{{ slots.length > 1 ? 's' : '' }}
            • Cliquez pour {{ isOpen ? 'masquer' : 'voir' }}
          </p>
        </div>
      </button>
      
      <button 
        @click="$emit('create-slot')"
        class="px-3 py-2 md:px-4 md:py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 text-sm md:text-base">
        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span class="hidden sm:inline">Nouveau</span>
      </button>
    </div>
    
    <!-- Contenu déroulant -->
    <div v-if="isOpen" class="mt-4 border-t border-gray-200 pt-4">
      <div v-if="slots.length > 0">
        <!-- Vue Desktop/Tablette : 7 colonnes par jour -->
        <div class="hidden md:grid md:grid-cols-7 gap-3">
          <div v-for="day in 7" :key="day" class="space-y-2">
            <!-- En-tête du jour -->
            <div class="text-center font-semibold text-sm text-gray-700 bg-gray-100 rounded-lg py-2">
              {{ getDayName((day) % 7) }}
            </div>
            
            <!-- Créneaux du jour -->
            <div class="space-y-2">
              <div 
                v-for="slot in getSlotsByDay((day) % 7)" 
                :key="slot.id"
                @click="handleSlotClick(slot)"
                class="border-2 rounded-lg p-2 cursor-pointer transition-all hover:shadow-md"
                :class="[
                  selectedSlotId === slot.id 
                    ? 'border-green-500 bg-green-50 shadow-lg' 
                    : 'border-gray-200 hover:border-blue-500',
                  { 'opacity-50': !slot.is_active }
                ]">
                <!-- Heure -->
                <div class="text-xs font-semibold text-gray-900 mb-1">
                  {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
                </div>
                
                <!-- Discipline -->
                <div class="text-xs text-gray-700 mb-1 truncate" :title="slot.discipline?.name">
                  {{ slot.discipline?.name || 'N/A' }}
                </div>
                
                <!-- Infos rapides -->
                <div class="flex items-center gap-1 text-xs text-gray-500">
                  <span v-if="slot.duration">⏱ {{ slot.duration }}m</span>
                  <span v-if="slot.price">• {{ formatPrice(slot.price) }}€</span>
                </div>
                
                <!-- Actions -->
                <div class="flex gap-1 mt-2">
                  <button 
                    @click.stop="$emit('edit-slot', slot)"
                    class="flex-1 px-2 py-1 text-xs text-blue-600 hover:bg-blue-50 rounded transition-colors"
                    title="Modifier">
                    ✏️
                  </button>
                  <button 
                    @click.stop="$emit('delete-slot', slot)"
                    class="flex-1 px-2 py-1 text-xs text-red-600 hover:bg-red-50 rounded transition-colors"
                    title="Supprimer">
                    🗑️
                  </button>
                </div>
              </div>
              
              <!-- Placeholder si aucun créneau -->
              <div v-if="getSlotsByDay((day) % 7).length === 0" 
                   class="text-center py-4 text-xs text-gray-400">
                Aucun créneau
              </div>
            </div>
          </div>
        </div>
        
        <!-- Vue Mobile : petites cartes -->
        <div class="md:hidden space-y-3">
          <div 
            v-for="slot in slots" 
            :key="slot.id"
            @click="handleSlotClick(slot)"
            class="border-2 rounded-lg p-3 cursor-pointer transition-all hover:shadow-md"
            :class="[
              selectedSlotId === slot.id 
                ? 'border-green-500 bg-green-50 shadow-lg' 
                : 'border-gray-200 hover:border-blue-500',
              { 'opacity-50': !slot.is_active }
            ]">
            <div class="flex items-start justify-between mb-2">
              <!-- Jour -->
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {{ getDayName(slot.day_of_week) }}
              </span>
              
              <!-- Statut -->
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="slot.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                {{ slot.is_active ? '✓' : '✗' }}
              </span>
            </div>
            
            <!-- Horaire -->
            <div class="text-sm font-semibold text-gray-900 mb-1">
              🕐 {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
            </div>
            
            <!-- Discipline -->
            <div class="text-sm text-gray-700 mb-2">
              📚 {{ slot.discipline?.name || 'Discipline non définie' }}
            </div>
            
            <!-- Infos -->
            <div class="flex items-center gap-3 text-xs text-gray-500 mb-2">
              <span v-if="slot.duration">⏱ {{ slot.duration }} min</span>
              <span v-if="slot.price">💰 {{ formatPrice(slot.price) }} €</span>
              <span v-if="slot.max_capacity">👤 {{ slot.max_capacity }}</span>
            </div>
            
            <!-- Actions -->
            <div class="flex gap-2 mt-2">
              <button 
                @click.stop="$emit('edit-slot', slot)"
                class="flex-1 px-3 py-1.5 text-xs text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors font-medium">
                ✏️ Modifier
              </button>
              <button 
                @click.stop="$emit('delete-slot', slot)"
                class="flex-1 px-3 py-1.5 text-xs text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors font-medium">
                🗑️ Supprimer
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-8 text-gray-500">
        Aucun créneau horaire configuré. Créez-en un pour commencer.
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface OpenSlot {
  id: number
  day_of_week: number
  start_time: string
  end_time: string
  discipline_id: number
  discipline?: any
  duration?: number
  price?: number
  max_capacity?: number
  max_slots?: number
  is_active: boolean
}

interface Props {
  slots: OpenSlot[]
  selectedSlotId?: number | null
}

const props = withDefaults(defineProps<Props>(), {
  selectedSlotId: null
})

const emit = defineEmits<{
  'create-slot': []
  'edit-slot': [slot: OpenSlot]
  'delete-slot': [slot: OpenSlot]
  'select-slot': [slot: OpenSlot]
}>()

const isOpen = ref(false) // Fermé par défaut
const dayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']

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

function getSlotsByDay(dayOfWeek: number): OpenSlot[] {
  return props.slots.filter(slot => slot.day_of_week === dayOfWeek)
}

function handleSlotClick(slot: OpenSlot) {
  emit('select-slot', slot)
  // Fermer automatiquement le dropdown après sélection
  isOpen.value = false
}
</script>

