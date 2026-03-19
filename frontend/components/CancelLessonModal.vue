<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Annuler le cours</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Infos cours -->
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

      <!-- Règle selon délai -->
      <div v-if="hoursUntilStart >= 8" class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
        Annulation plus de 8 h avant le cours : le cours ne sera pas décompté de votre abonnement. Vous pouvez préciser une raison (optionnel).
      </div>
      <div v-else class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
        Annulation à moins de 8 h du cours : indiquez la raison. Si <strong>médicale</strong>, joignez un certificat (PDF ou photo). Sans certificat médical, le cours sera compté dans votre abonnement.
      </div>

      <!-- Raison obligatoire si < 8h -->
      <div v-if="hoursUntilStart < 8" class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Raison de l'annulation <span class="text-red-500">*</span></label>
        <select
          v-model="cancellationReason"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">Choisir...</option>
          <option value="medical">Médicale</option>
          <option value="other">Autre</option>
        </select>
      </div>

      <!-- Certificat médical (obligatoire si raison médicale et < 8h) -->
      <div v-if="hoursUntilStart < 8 && cancellationReason === 'medical'" class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Certificat médical (PDF ou photo) <span class="text-red-500">*</span></label>
        <input
          type="file"
          accept=".pdf,image/jpeg,image/jpg,image/png"
          class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
          @change="onCertificateChange"
        />
        <p class="mt-1 text-xs text-gray-500">PDF ou image (JPG, PNG), max 10 Mo. Avec ce justificatif, le cours ne sera pas décompté.</p>
      </div>

      <!-- Précisions (optionnel) -->
      <div class="mb-6">
        <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
          {{ hoursUntilStart >= 8 ? 'Précisions (optionnel)' : 'Précisions (optionnel)' }}
        </label>
        <textarea
          id="reason"
          v-model="reason"
          rows="3"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          placeholder="Détails éventuels..."
        ></textarea>
        <p class="mt-1 text-xs text-gray-500">Envoyé au club et à l'enseignant.</p>
      </div>

      <div class="flex justify-end space-x-3">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors"
        >
          Annuler
        </button>
        <button
          @click="confirmCancel"
          :disabled="!canSubmit || processing"
          class="px-4 py-2 text-sm text-white bg-red-600 rounded-md hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
        >
          <svg v-if="processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
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
const cancellationReason = ref('')
const certificateFile = ref(null)
const processing = ref(false)

const hoursUntilStart = computed(() => {
  if (!props.lesson?.start_time) return 24
  const start = new Date(props.lesson.start_time).getTime()
  const now = Date.now()
  return Math.max(0, (start - now) / (1000 * 60 * 60))
})

const isLateCancel = computed(() => hoursUntilStart.value < 8)

const canSubmit = computed(() => {
  if (isLateCancel.value) {
    if (!cancellationReason.value) return false
    if (cancellationReason.value === 'medical' && !certificateFile.value) return false
  }
  return true
})

function onCertificateChange(e) {
  const f = e.target?.files?.[0]
  certificateFile.value = f || null
}

const formatFullDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

const formatTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

const confirmCancel = async () => {
  if (!canSubmit.value) {
    if (isLateCancel.value && !cancellationReason.value) {
      alert('Veuillez indiquer la raison de l\'annulation.')
    } else if (isLateCancel.value && cancellationReason.value === 'medical' && !certificateFile.value) {
      alert('Pour une raison médicale, le certificat (PDF ou photo) est obligatoire.')
    }
    return
  }

  try {
    processing.value = true
    const studentId = props.lesson.student_id ?? props.lesson.student?.id
    const url = `/student/bookings/${props.lesson.id}/cancel`
    const reasonValue = cancellationReason.value || ''
    const fileValue = certificateFile.value

    if (isLateCancel.value && fileValue) {
      const formData = new FormData()
      formData.append('cancellation_reason', reasonValue || 'medical')
      if (reason.value.trim()) formData.append('reason', reason.value.trim())
      formData.append('cancellation_certificate', fileValue)
      if (studentId) formData.append('active_student_id', String(studentId))
      const response = await $api.post(url, formData)
      if (response.data?.success) {
        emit('success')
        emit('close')
      } else {
        alert(response.data?.message || response.data?.errors?.cancellation_reason?.[0] || 'Erreur lors de l\'annulation')
      }
    } else {
      const payload = {
        reason: reason.value.trim(),
        cancellation_reason: isLateCancel.value ? cancellationReason.value : undefined,
        active_student_id: studentId
      }
      const response = await $api.put(url, payload)
      if (response.data.success) {
        emit('success')
        emit('close')
      } else {
        alert(response.data.message || 'Erreur lors de l\'annulation')
      }
    }
  } catch (err) {
    console.error('Erreur annulation:', err)
    const msg = err.response?.data?.message ?? err.response?.data?.errors?.cancellation_certificate?.[0] ?? 'Erreur lors de l\'annulation du cours'
    alert(msg)
  } finally {
    processing.value = false
  }
}
</script>
