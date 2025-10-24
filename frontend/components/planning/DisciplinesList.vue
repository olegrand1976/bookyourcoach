<template>
  <div class="bg-white shadow rounded-lg p-6">
    <div class="mb-4">
      <h2 class="text-xl font-semibold text-gray-900">Disciplines actives</h2>
      <p class="text-sm text-gray-500 mt-1">
        Disciplines configurées pour votre club
      </p>
    </div>

    <div v-if="disciplines.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="discipline in disciplines" 
           :key="discipline.id"
           class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <h3 class="font-medium text-gray-900 mb-2">{{ discipline.name }}</h3>
            <div v-if="discipline.settings" class="space-y-1 text-sm text-gray-600">
              <p v-if="discipline.settings.duration">
                ⏱️ Durée: {{ discipline.settings.duration }} min
              </p>
              <p v-if="discipline.settings.price">
                💰 Prix: {{ formatPrice(discipline.settings.price) }} €
              </p>
              <p v-if="discipline.settings.max_participants">
                👥 Participants: {{ discipline.settings.min_participants || 1 }}-{{ discipline.settings.max_participants }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-8 text-gray-500">
      Aucune discipline active
    </div>
  </div>
</template>

<script setup lang="ts">
interface DisciplineSettings {
  duration: number
  price: number
  max_participants: number
  min_participants: number
  notes?: string
}

interface ClubDiscipline {
  id: number
  name: string
  description?: string
  is_active: boolean
  settings?: DisciplineSettings
}

interface Props {
  disciplines: ClubDiscipline[]
}

defineProps<Props>()

function formatPrice(price: number | string): string {
  const numPrice = typeof price === 'string' ? parseFloat(price) : price
  return isNaN(numPrice) ? '0.00' : numPrice.toFixed(2)
}
</script>

