<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Annuler le cours</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Informations du cours -->
      <div class="mb-4 p-4 bg-gray-50 rounded-lg">
        <div class="space-y-2 text-sm">
          <div>
            <span class="font-medium text-gray-700">Type de cours:</span>
            <span class="ml-2 text-gray-900">{{ lesson?.course_type?.name || lesson?.courseType?.name || 'N/A' }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Date:</span>
            <span class="ml-2 text-gray-900">{{ formatFullDate(lesson?.start_time) }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Heure:</span>
            <span class="ml-2 text-gray-900">{{ formatTime(lesson?.start_time) }} - {{ formatTime(lesson?.end_time) }}</span>
          </div>
          <div v-if="lesson?.teacher">
            <span class="font-medium text-gray-700">Enseignant:</span>
            <span class="ml-2 text-gray-900">{{ lesson.teacher?.user?.name || lesson.teacher?.name || 'N/A' }}</span>
          </div>
        </div>
      </div>

      <!-- Formulaire de raison -->
      <div class="mb-6">
        <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
          Raison de l'annulation <span class="text-red-500">*</span>
        </label>
        <textarea
          id="reason"
          v-model="reason"
          rows="4"
          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          placeholder="Veuillez indiquer la raison de l'annulation..."
          required
        ></textarea>
        <p class="mt-1 text-xs text-gray-500">
          Cette raison sera envoyée au responsable du club et à l'enseignant prévu pour le cours.
        </p>
      </div>

      <!-- Actions -->
      <div class="flex justify-end space-x-3">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors"
        >
          Annuler
        </button>
        <button
          @click="confirmCancel"
          :disabled="!reason.trim() || processing"
          class="px-4 py-2 text-sm text-white bg-red-600 rounded-md hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
        >
          <svg v-if="processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>{{ processing ? 'Annulation...' : 'Confirmer l\'annulation' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  lesson: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'success'])

const { $api } = useNuxtApp()
const reason = ref('')
const processing = ref(false)

const formatFullDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

const formatTime = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const confirmCancel = async () => {
  if (!reason.value.trim()) {
    alert('Veuillez indiquer la raison de l\'annulation')
    return
  }

  try {
    processing.value = true
    
    const response = await $api.put(`/student/bookings/${props.lesson.id}/cancel`, {
      reason: reason.value.trim()
    })
    
    if (response.data.success) {
      emit('success')
      emit('close')
    } else {
      alert(response.data.message || 'Erreur lors de l\'annulation')
    }
  } catch (err) {
    console.error('Erreur lors de l\'annulation:', err)
    alert(err.response?.data?.message || 'Erreur lors de l\'annulation du cours')
  } finally {
    processing.value = false
  }
}
</script>
