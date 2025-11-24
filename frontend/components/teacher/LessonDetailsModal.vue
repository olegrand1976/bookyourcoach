<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto">
      <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-2xl font-bold text-gray-900">Détails du cours</h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div v-if="lesson" class="space-y-6">
          <!-- Club -->
          <div class="bg-blue-50 rounded-lg p-4">
            <p class="text-sm text-blue-600 font-medium">Club</p>
            <p class="text-lg font-semibold text-blue-900">{{ lesson.club?.name || 'Non défini' }}</p>
          </div>

          <!-- Date et heure -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-600 font-medium">Date</p>
              <p class="text-lg font-semibold">{{ formatDate(lesson.start_time) }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600 font-medium">Horaire</p>
              <p class="text-lg font-semibold">{{ formatTime(lesson.start_time) }} - {{ formatTime(lesson.end_time) }}</p>
            </div>
          </div>

          <!-- Type de cours -->
          <div>
            <p class="text-sm text-gray-600 font-medium">Type de cours</p>
            <p class="text-lg font-semibold">{{ lesson.course_type?.name || 'Non défini' }}</p>
            <p class="text-sm text-gray-500">{{ lesson.duration }} minutes - {{ lesson.price }}€</p>
          </div>

          <!-- Élève(s) -->
          <div>
            <p class="text-sm text-gray-600 font-medium">Élève(s)</p>
            <!-- Vérifier d'abord la relation many-to-many (students) -->
            <div v-if="lesson.students && Array.isArray(lesson.students) && lesson.students.length > 0" class="mt-2 space-y-2">
              <div v-for="(student, index) in lesson.students" :key="student.id || index" class="flex items-center gap-2">
                <div class="bg-emerald-100 p-2 rounded-full">
                  <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
                <div>
                  <p class="text-lg font-semibold">{{ student.user?.name || student.name || 'Sans nom' }}</p>
                  <p v-if="student.age" class="text-sm text-gray-500">{{ student.age }} ans</p>
                </div>
              </div>
            </div>
            <!-- Sinon, vérifier la relation one-to-many (student) -->
            <div v-else-if="lesson.student?.user?.name || lesson.student?.name" class="flex items-center gap-2 mt-2">
              <div class="bg-emerald-100 p-2 rounded-full">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <div>
                <p class="text-lg font-semibold">{{ lesson.student?.user?.name || lesson.student?.name || 'Sans élève' }}</p>
                <p v-if="lesson.student?.age" class="text-sm text-gray-500">{{ lesson.student.age }} ans</p>
              </div>
            </div>
            <!-- Aucun élève -->
            <div v-else class="flex items-center gap-2 mt-2">
              <div class="bg-gray-100 p-2 rounded-full">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <div>
                <p class="text-lg font-semibold text-gray-500">Sans élève</p>
              </div>
            </div>
          </div>

          <!-- Statut -->
          <div>
            <p class="text-sm text-gray-600 font-medium">Statut</p>
            <span :class="getStatusClass(lesson.status)" class="inline-block px-3 py-1 rounded-full text-sm font-medium mt-2">
              {{ getStatusLabel(lesson.status) }}
            </span>
          </div>

          <!-- Notes -->
          <div v-if="lesson.notes">
            <p class="text-sm text-gray-600 font-medium">Notes</p>
            <p class="text-gray-700 mt-1">{{ lesson.notes }}</p>
          </div>

          <!-- Actions -->
          <div class="flex gap-3 pt-4 border-t">
            <button
              @click="$emit('request-replacement')"
              class="flex-1 bg-gradient-to-r from-orange-500 to-red-600 text-white px-4 py-3 rounded-lg hover:from-orange-600 hover:to-red-700 transition-all font-medium flex items-center justify-center gap-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
              </svg>
              Demander un remplacement
            </button>
            <button
              @click="$emit('close')"
              class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors font-medium"
            >
              Fermer
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  show: boolean
  lesson: any | null
}>()

defineEmits(['close', 'request-replacement'])

function formatDate(datetime: string): string {
  if (!datetime) return ''
  const date = new Date(datetime)
  return date.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

function formatTime(datetime: string): string {
  if (!datetime) return ''
  const date = new Date(datetime)
  return date.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    'confirmed': '✓ Confirmé',
    'pending': '⏳ En attente',
    'cancelled': '✗ Annulé',
    'completed': '✓ Terminé'
  }
  return labels[status] || status
}

function getStatusClass(status: string): string {
  const classes: Record<string, string> = {
    'confirmed': 'bg-green-100 text-green-800',
    'pending': 'bg-yellow-100 text-yellow-800',
    'cancelled': 'bg-red-100 text-red-800',
    'completed': 'bg-blue-100 text-blue-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>

