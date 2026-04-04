<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="quick-note-title"
      @click.self="$emit('close')"
    >
      <div class="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="p-5 sm:p-6 border-b border-gray-100 flex items-start justify-between gap-3">
          <div>
            <h2 id="quick-note-title" class="text-xl font-bold text-gray-900">
              Note d’activité
            </h2>
            <p class="text-sm text-gray-500 mt-1">
              Même saisie que les notes rapides : texte libre ou dictée vocale (navigateur compatible).
            </p>
          </div>
          <button
            type="button"
            class="shrink-0 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100"
            aria-label="Fermer"
            @click="$emit('close')"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-5 sm:p-6 space-y-4">
          <div>
            <label for="quick-note-lesson" class="block text-sm font-medium text-gray-700 mb-1">Cours</label>
            <select
              id="quick-note-lesson"
              v-model.number="selectedLessonId"
              class="w-full min-h-[44px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option :value="null" disabled>Sélectionner un cours</option>
              <option v-for="l in sortedLessons" :key="l.id" :value="l.id">
                {{ formatLessonOption(l) }}
              </option>
            </select>
          </div>

          <div>
            <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
              <label for="quick-note-body" class="block text-sm font-medium text-gray-700">Note</label>
              <span class="text-xs text-gray-400">{{ notes.length }}/1000</span>
            </div>
            <textarea
              id="quick-note-body"
              v-model="notes"
              rows="6"
              maxlength="1000"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-y min-h-[120px]"
              placeholder="Saisissez votre note ou utilisez le micro…"
            />
          </div>

          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="inline-flex items-center justify-center gap-2 min-h-[44px] px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              :class="isListening
                ? 'bg-red-600 text-white hover:bg-red-700'
                : 'bg-indigo-600 text-white hover:bg-indigo-700'"
              :disabled="!speechSupported || saving"
              @click="toggleVoice"
            >
              <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                />
              </svg>
              {{ isListening ? 'Arrêter la dictée' : 'Dictée vocale' }}
            </button>
            <p v-if="!speechSupported" class="text-xs text-amber-700 self-center">
              Dictée non disponible sur ce navigateur (essayez Chrome ou Edge).
            </p>
            <p v-else-if="speechError" class="text-xs text-red-600 self-center">{{ speechError }}</p>
          </div>
        </div>

        <div class="p-5 sm:p-6 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
          <button
            type="button"
            class="min-h-[44px] px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50"
            :disabled="saving"
            @click="$emit('close')"
          >
            Annuler
          </button>
          <button
            type="button"
            class="min-h-[44px] px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-50"
            :disabled="saving || !selectedLessonId"
            @click="save"
          >
            {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue'

const props = defineProps<{
  show: boolean
  lessons: any[]
  /** Pré-sélection au clic « Note rapide » / « Enregistrer en vocal » sur une ligne */
  initialLessonId?: number | null
}>()

const emit = defineEmits<{
  close: []
  saved: [lessonId: number, notes: string]
}>()

const { $api } = useNuxtApp()
const { success, error: toastError } = useToast()

const selectedLessonId = ref<number | null>(null)
const notes = ref('')
const saving = ref(false)
const isListening = ref(false)
const speechError = ref('')
let recognition: any = null

const sortedLessons = computed(() => {
  const list = [...(props.lessons || [])]
  return list.sort((a, b) => {
    const ta = new Date(a.start_time).getTime()
    const tb = new Date(b.start_time).getTime()
    return tb - ta
  })
})

const speechSupported = computed(() => {
  if (typeof window === 'undefined') return false
  return !!(window as any).SpeechRecognition || !!(window as any).webkitSpeechRecognition
})

function formatLessonOption(l: any): string {
  const d = l.start_time ? new Date(l.start_time) : null
  const dateStr = d
    ? d.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' })
    : ''
  const timeStr = d
    ? d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    : ''
  const student =
    l.student?.user?.name ||
    (Array.isArray(l.students) && l.students[0]?.user?.name) ||
    'Élève'
  const type = l.course_type?.name || 'Cours'
  return `${dateStr} ${timeStr} — ${type} — ${student}`
}

function lessonById(id: number | null): any | null {
  if (id == null) return null
  return props.lessons.find((l) => Number(l.id) === Number(id)) || null
}

function syncNotesFromSelection() {
  const l = lessonById(selectedLessonId.value)
  notes.value = (l?.notes as string) || ''
}

watch(
  () => props.show,
  (open) => {
    if (!open) {
      stopVoice()
      speechError.value = ''
      return
    }
    selectedLessonId.value =
      props.initialLessonId != null ? Number(props.initialLessonId) : sortedLessons.value[0]?.id ?? null
    syncNotesFromSelection()
  }
)

watch(selectedLessonId, () => {
  if (props.show) {
    syncNotesFromSelection()
  }
})

function getRecognition(): any | null {
  if (typeof window === 'undefined') return null
  const Ctor = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition
  if (!Ctor) return null
  const r = new Ctor()
  r.lang = 'fr-FR'
  r.continuous = true
  r.interimResults = true
  r.onresult = (event: any) => {
    let chunk = ''
    for (let i = event.resultIndex; i < event.results.length; i++) {
      if (event.results[i].isFinal) {
        chunk += event.results[i][0].transcript
      }
    }
    if (!chunk.trim()) return
    const sep = notes.value && !notes.value.endsWith(' ') ? ' ' : ''
    notes.value = `${notes.value}${sep}${chunk.trim()}`.slice(0, 1000)
  }
  r.onerror = (e: any) => {
    speechError.value = e.error === 'not-allowed' ? 'Micro refusé ou bloqué.' : (e.message || 'Erreur dictée')
    isListening.value = false
  }
  r.onend = () => {
    isListening.value = false
  }
  return r
}

function startVoice() {
  speechError.value = ''
  try {
    recognition = getRecognition()
    if (!recognition) return
    recognition.start()
    isListening.value = true
  } catch {
    speechError.value = 'Impossible de démarrer la dictée.'
    isListening.value = false
  }
}

function stopVoice() {
  try {
    recognition?.stop?.()
  } catch {
    /* ignore */
  }
  recognition = null
  isListening.value = false
}

function toggleVoice() {
  if (isListening.value) {
    stopVoice()
  } else {
    startVoice()
  }
}

async function save() {
  if (!selectedLessonId.value) {
    toastError('Choisissez un cours.')
    return
  }
  saving.value = true
  try {
    const response = await ($api as any).put(`/lessons/${selectedLessonId.value}`, {
      notes: notes.value.trim() || null,
    })
    if (response.data?.success === false) {
      throw new Error(response.data?.message || 'Échec')
    }
    success('Note enregistrée.')
    emit('saved', selectedLessonId.value, notes.value.trim())
    emit('close')
  } catch (e: any) {
    toastError(e.response?.data?.message || e.message || 'Erreur lors de l’enregistrement')
  } finally {
    saving.value = false
  }
}

onUnmounted(() => {
  stopVoice()
})
</script>
