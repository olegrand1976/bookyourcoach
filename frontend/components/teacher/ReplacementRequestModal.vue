<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-2xl font-bold text-gray-900">Demander un remplacement</h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Infos du cours -->
        <div v-if="lesson" class="bg-blue-50 rounded-lg p-4 mb-6">
          <h4 class="font-semibold text-blue-900 mb-2">Cours à remplacer</h4>
          <div class="text-sm text-blue-800 space-y-1">
            <p><strong>Date :</strong> {{ formatDate(lesson.start_time) }}</p>
            <p><strong>Horaire :</strong> {{ formatTime(lesson.start_time) }} - {{ formatTime(lesson.end_time) }}</p>
            <p><strong>Élève :</strong> {{ lesson.student?.user?.name || 'Sans élève' }}</p>
            <p><strong>Type :</strong> {{ lesson.course_type?.name }}</p>
          </div>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Sélection du professeur de remplacement -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Professeur de remplacement *
            </label>
            <select
              v-model="form.replacement_teacher_id"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            >
              <option :value="null">Sélectionnez un enseignant</option>
              <option
                v-for="teacher in availableTeachers"
                :key="teacher.id"
                :value="teacher.id"
              >
                {{ teacher.user?.name || teacher.name }}
                <template v-if="teacher.specialties">
                  - {{ teacher.specialties }}
                </template>
              </option>
            </select>
          </div>

          <!-- Raison du remplacement -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Raison du remplacement *
            </label>
            <select
              v-model="form.reason"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            >
              <option value="">Sélectionnez une raison</option>
              <option value="Indisponibilité personnelle">Indisponibilité personnelle</option>
              <option value="Problème de santé">Problème de santé</option>
              <option value="Urgence familiale">Urgence familiale</option>
              <option value="Conflit d'horaire">Conflit d'horaire</option>
              <option value="Autre">Autre</option>
            </select>
          </div>

          <!-- Notes supplémentaires -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Notes supplémentaires (optionnel)
            </label>
            <textarea
              v-model="form.notes"
              rows="4"
              placeholder="Informations complémentaires pour le professeur de remplacement..."
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 resize-none"
            ></textarea>
          </div>

          <!-- Message d'erreur -->
          <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-red-800 text-sm">{{ error }}</p>
          </div>

          <!-- Boutons -->
          <div class="flex gap-3 pt-4">
            <button
              type="button"
              @click="$emit('close')"
              class="flex-1 px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors font-medium"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="flex-1 bg-gradient-to-r from-orange-500 to-red-600 text-white px-6 py-3 rounded-lg hover:from-orange-600 hover:to-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all font-medium flex items-center justify-center gap-2"
            >
              <svg v-if="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ loading ? 'Envoi...' : 'Envoyer la demande' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{
  show: boolean
  lesson: any | null
  availableTeachers: any[]
}>()

const emit = defineEmits(['close', 'success'])

const { $api } = useNuxtApp()
const loading = ref(false)
const error = ref('')

const form = ref({
  replacement_teacher_id: null as number | null,
  reason: '',
  notes: ''
})

// Réinitialiser le formulaire quand la modale s'ouvre
watch(() => props.show, (isShown) => {
  if (isShown) {
    form.value = {
      replacement_teacher_id: null,
      reason: '',
      notes: ''
    }
    error.value = ''
  }
})

async function handleSubmit() {
  if (!props.lesson) return

  loading.value = true
  error.value = ''

  try {
    const response = await $api.post('/teacher/lesson-replacements', {
      lesson_id: props.lesson.id,
      replacement_teacher_id: form.value.replacement_teacher_id,
      reason: form.value.reason,
      notes: form.value.notes
    })

    console.log('✅ Demande de remplacement créée:', response.data)
    emit('success')
    emit('close')
  } catch (err: any) {
    console.error('❌ Erreur:', err)
    error.value = err.response?.data?.message || 'Erreur lors de l\'envoi de la demande'
  } finally {
    loading.value = false
  }
}

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
</script>

