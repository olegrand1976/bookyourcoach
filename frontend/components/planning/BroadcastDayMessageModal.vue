<template>
  <div
    v-if="show"
    class="fixed inset-0 z-[60] overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="broadcast-day-title"
    @click.self="$emit('close')"
  >
    <div class="flex min-h-screen items-center justify-center px-4 py-8">
      <div class="fixed inset-0 bg-black/50" aria-hidden="true" @click="$emit('close')" />

      <div class="relative w-full max-w-2xl max-h-[90vh] flex flex-col rounded-xl bg-white shadow-xl overflow-hidden">
        <div class="px-6 py-4 border-b shrink-0 bg-gradient-to-r from-amber-500 to-orange-600">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 text-white">
              <h2 id="broadcast-day-title" class="text-xl font-bold truncate">
                📣 Message collectif
              </h2>
              <p class="text-sm opacity-90 mt-0.5">
                {{ dateLabel }}
              </p>
            </div>
            <button
              type="button"
              class="text-white/90 hover:text-white p-1 rounded-lg hover:bg-white/10 shrink-0"
              aria-label="Fermer"
              @click="$emit('close')"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-6">
          <p class="text-sm text-gray-600">
            Envoyez un email à tous les enseignants et élèves ayant un cours ce jour-là
            (ex. annulation exceptionnelle). Décochez les personnes à exclure.
          </p>

          <!-- Destinataires -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <fieldset class="border border-gray-200 rounded-xl p-4 bg-blue-50/40">
              <div class="flex items-center justify-between mb-2">
                <legend class="text-sm font-semibold text-blue-800">
                  Enseignants ({{ checkedTeacherIds.length }}/{{ teachers.length }})
                </legend>
                <button
                  v-if="teachers.length"
                  type="button"
                  class="text-xs text-blue-700 underline hover:text-blue-900"
                  @click="toggleAll('teacher')"
                >
                  {{ allTeachersChecked ? 'Tout décocher' : 'Tout cocher' }}
                </button>
              </div>
              <p v-if="!teachers.length" class="text-xs text-gray-500 py-2">Aucun enseignant ce jour.</p>
              <ul v-else class="space-y-1 max-h-48 overflow-y-auto">
                <li v-for="t in teachers" :key="`t-${t.id}`">
                  <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" v-model="teacherChecked[t.id]" class="rounded border-gray-300 text-blue-600" />
                    <span class="truncate">{{ t.name }}</span>
                  </label>
                </li>
              </ul>
            </fieldset>

            <fieldset class="border border-gray-200 rounded-xl p-4 bg-emerald-50/40">
              <div class="flex items-center justify-between mb-2">
                <legend class="text-sm font-semibold text-emerald-800">
                  Élèves ({{ checkedStudentIds.length }}/{{ students.length }})
                </legend>
                <button
                  v-if="students.length"
                  type="button"
                  class="text-xs text-emerald-700 underline hover:text-emerald-900"
                  @click="toggleAll('student')"
                >
                  {{ allStudentsChecked ? 'Tout décocher' : 'Tout cocher' }}
                </button>
              </div>
              <p v-if="!students.length" class="text-xs text-gray-500 py-2">Aucun élève ce jour.</p>
              <ul v-else class="space-y-1 max-h-48 overflow-y-auto">
                <li v-for="s in students" :key="`s-${s.id}`">
                  <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" v-model="studentChecked[s.id]" class="rounded border-gray-300 text-emerald-600" />
                    <span class="truncate">{{ s.name }}</span>
                  </label>
                </li>
              </ul>
            </fieldset>
          </div>

          <!-- Message -->
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sujet</label>
              <input
                v-model="subject"
                type="text"
                maxlength="200"
                placeholder="Ex : Annulation des cours du jour"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
              <textarea
                v-model="body"
                rows="6"
                maxlength="20000"
                placeholder="Votre message…"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500"
              />
            </div>
          </div>

          <p v-if="errorMessage" class="text-sm text-red-700 bg-red-50 border border-red-100 rounded-lg px-3 py-2">
            {{ errorMessage }}
          </p>
        </div>

        <div class="px-6 py-4 border-t shrink-0 flex items-center justify-between gap-3 bg-gray-50">
          <span class="text-sm text-gray-600">
            {{ totalChecked }} destinataire(s) sélectionné(s)
          </span>
          <div class="flex gap-2">
            <button
              type="button"
              class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100"
              :disabled="sending"
              @click="$emit('close')"
            >
              Annuler
            </button>
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-orange-600 hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="!canSend || sending"
              @click="handleSend"
            >
              {{ sending ? 'Envoi…' : 'Envoyer' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useToast } from '~/composables/useToast'

interface Recipient {
  id: number
  name: string
}

const props = defineProps<{
  show: boolean
  dateLabel: string
  teachers: Recipient[]
  students: Recipient[]
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'sent'): void
}>()

const { $api } = useNuxtApp()
const { success: toastSuccess, error: toastError } = useToast()

const subject = ref('')
const body = ref('')
const sending = ref(false)
const errorMessage = ref('')
const teacherChecked = ref<Record<number, boolean>>({})
const studentChecked = ref<Record<number, boolean>>({})

// Réinitialise les cases (toutes cochées) et les champs à chaque ouverture
watch(
  () => props.show,
  (open) => {
    if (!open) return
    subject.value = ''
    body.value = ''
    errorMessage.value = ''
    teacherChecked.value = Object.fromEntries(props.teachers.map((t) => [t.id, true]))
    studentChecked.value = Object.fromEntries(props.students.map((s) => [s.id, true]))
  },
  { immediate: true }
)

const checkedTeacherIds = computed(() => props.teachers.filter((t) => teacherChecked.value[t.id]).map((t) => t.id))
const checkedStudentIds = computed(() => props.students.filter((s) => studentChecked.value[s.id]).map((s) => s.id))
const totalChecked = computed(() => checkedTeacherIds.value.length + checkedStudentIds.value.length)
const allTeachersChecked = computed(() => props.teachers.length > 0 && checkedTeacherIds.value.length === props.teachers.length)
const allStudentsChecked = computed(() => props.students.length > 0 && checkedStudentIds.value.length === props.students.length)
const canSend = computed(() => totalChecked.value > 0 && subject.value.trim() !== '' && body.value.trim() !== '')

function toggleAll(kind: 'teacher' | 'student') {
  if (kind === 'teacher') {
    const next = !allTeachersChecked.value
    teacherChecked.value = Object.fromEntries(props.teachers.map((t) => [t.id, next]))
  } else {
    const next = !allStudentsChecked.value
    studentChecked.value = Object.fromEntries(props.students.map((s) => [s.id, next]))
  }
}

async function handleSend() {
  errorMessage.value = ''
  if (!canSend.value) return

  if (!confirm(`Confirmer l’envoi à ${totalChecked.value} destinataire(s) (${checkedTeacherIds.value.length} enseignant(s), ${checkedStudentIds.value.length} élève(s)) ?`)) {
    return
  }

  sending.value = true
  try {
    const res = await $api.post('/club/communications/send', {
      selection_mode: 'selected',
      subject: subject.value,
      body: body.value,
      teacher_ids: checkedTeacherIds.value,
      student_ids: checkedStudentIds.value,
    })
    if (res.data?.success) {
      toastSuccess(res.data.message || 'Message envoyé.')
      emit('sent')
      emit('close')
    } else {
      errorMessage.value = res.data?.message || 'Erreur lors de l’envoi.'
    }
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || err.message || 'Erreur réseau.'
    toastError(errorMessage.value)
  } finally {
    sending.value = false
  }
}
</script>
