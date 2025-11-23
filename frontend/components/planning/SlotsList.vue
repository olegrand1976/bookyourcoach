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
          <h2 class="text-lg md:text-xl font-semibold text-gray-900">
            Créneaux horaires
          </h2>
          <div v-if="selectedSlotId && selectedSlot" class="mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm font-medium text-green-800 mb-1">Créneau sélectionné :</p>
            <div class="flex flex-wrap items-center gap-2 md:gap-3 text-xs md:text-sm text-gray-700">
              <span class="inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span class="font-semibold">{{ getDayName(selectedSlot.day_of_week) }}</span>
              </span>
              <span class="inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ formatTime(selectedSlot.start_time) }} - {{ formatTime(selectedSlot.end_time) }}</span>
              </span>
              <span v-if="selectedSlot.discipline?.name" class="inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                <span>{{ selectedSlot.discipline.name }}</span>
              </span>
              <span v-if="selectedSlot.duration" class="inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ selectedSlot.duration }} min</span>
              </span>
              <span v-if="selectedSlot.price !== null && selectedSlot.price !== undefined" class="inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ formatPrice(selectedSlot.price) }} €</span>
              </span>
            </div>
          </div>
          <p class="text-xs md:text-sm text-gray-500 mt-1">
            <span v-if="slots.length > 0">
              {{ slots.length }} créneau{{ slots.length > 1 ? 'x' : '' }} configuré{{ slots.length > 1 ? 's' : '' }}
              <span v-if="!selectedSlotId || !selectedSlot">
                • Cliquez pour {{ isOpen ? 'masquer' : 'voir' }}
              </span>
            </span>
            <span v-else class="text-amber-600 font-medium">
              ⚠️ Aucun créneau configuré • Cliquez pour voir les options
            </span>
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

      <div v-else class="text-center py-12 text-gray-500">
        <div class="text-6xl mb-4">🕐</div>
        <p class="text-lg font-semibold text-gray-700 mb-2">Aucun créneau horaire configuré</p>
        <p class="text-sm text-gray-500 mb-6">
          Les créneaux horaires permettent de définir les plages horaires disponibles pour créer des cours.
        </p>
        <button 
          @click="$emit('create-slot')"
          class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium shadow-md hover:shadow-lg">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Créer mon premier créneau horaire
        </button>
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

const isOpen = ref(true) // Ouvert par défaut pour faciliter la visualisation
const dayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']

// Trouver le créneau sélectionné pour l'affichage dans le titre
const selectedSlot = computed(() => {
  if (!props.selectedSlotId) return null
  return props.slots.find(slot => slot.id === props.selectedSlotId) || null
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

function getSlotsByDay(dayOfWeek: number): OpenSlot[] {
  return props.slots.filter(slot => slot.day_of_week === dayOfWeek)
}

function handleSlotClick(slot: OpenSlot) {
  emit('select-slot', slot)
  // Fermer automatiquement le dropdown après sélection
  isOpen.value = false
}
</script>

