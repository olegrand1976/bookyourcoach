<template>
  <div class="bg-white shadow rounded-lg p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">Créneaux horaires</h2>
        <p class="text-sm text-gray-500 mt-1">Gérez vos créneaux disponibles pour les cours</p>
      </div>
      <button 
        @click="$emit('create-slot')"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nouveau créneau
      </button>
    </div>
    
    <!-- Liste des créneaux -->
    <div v-if="slots.length > 0" class="space-y-3">
      <div v-for="slot in slots" 
           :key="slot.id"
           class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <!-- Jour et horaire -->
            <div class="flex items-center gap-2 mb-2">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {{ getDayName(slot.day_of_week) }}
              </span>
              <span class="text-sm font-semibold text-gray-900">
                {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
              </span>
            </div>
    
            <!-- Discipline -->
            <h3 class="font-medium text-gray-900 mb-2">
              {{ slot.discipline?.name || 'Discipline non définie' }}
            </h3>

            <!-- Informations du créneau -->
            <div class="flex items-center gap-4 text-sm text-gray-500">
              <span v-if="slot.duration">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ slot.duration }} min
              </span>
              <span v-if="slot.price">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ formatPrice(slot.price) }} €
              </span>
              <span v-if="slot.max_capacity">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                {{ slot.max_capacity }} {{ slot.max_capacity === 1 ? 'participant' : 'participants' }}
              </span>
              <span v-if="slot.max_slots && slot.max_slots > 1" class="font-medium text-blue-600">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                × {{ slot.max_slots }} plages simultanées
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col items-end gap-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="slot.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
              {{ slot.is_active ? 'Actif' : 'Inactif' }}
            </span>
            <div class="flex gap-2">
              <button 
                @click="$emit('edit-slot', slot)"
                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                title="Modifier">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button 
                @click="$emit('delete-slot', slot)"
                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                title="Supprimer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-8 text-gray-500">
      Aucun créneau horaire configuré. Créez-en un pour commencer.
    </div>
  </div>
</template>

<script setup lang="ts">
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
}

defineProps<Props>()

defineEmits<{
  'create-slot': []
  'edit-slot': [slot: OpenSlot]
  'delete-slot': [slot: OpenSlot]
}>()

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
</script>

