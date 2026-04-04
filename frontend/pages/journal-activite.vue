<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Journal d’activité</h1>
          <p class="mt-1 text-sm md:text-base text-gray-600">
            « Note rapide » et « Enregistrer en vocal » ouvrent la même fenêtre : saisie au clavier ou bouton
            <span class="font-medium text-gray-800">Dictée vocale</span> dans la modale.
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700"
            @click="openQuickNote(null)"
          >
            Note rapide
          </button>
          <button
            type="button"
            class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 rounded-lg border-2 border-indigo-600 text-indigo-700 text-sm font-medium hover:bg-indigo-50"
            @click="openQuickNote(null)"
          >
            Enregistrer en vocal
          </button>
        </div>
      </div>

      <div v-if="loading" class="flex justify-center py-16">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" aria-hidden="true" />
      </div>

      <div v-else-if="filteredLessons.length === 0" class="bg-white rounded-xl shadow p-8 text-center text-gray-600">
        Aucun cours sur cette période. Ajustez la période ou consultez le dashboard enseignant.
      </div>

      <ul v-else class="space-y-3">
        <li
          v-for="lesson in filteredLessons"
          :key="lesson.id"
          class="bg-white rounded-xl shadow border border-gray-100 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
        >
          <div class="min-w-0 flex-1">
            <p class="font-semibold text-gray-900">{{ formatLessonTitle(lesson) }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ lesson.club?.name }}</p>
            <p v-if="lesson.notes" class="text-sm text-gray-700 mt-2 line-clamp-2">{{ lesson.notes }}</p>
            <p v-else class="text-sm text-gray-400 mt-2 italic">Pas encore de note</p>
          </div>
          <div class="flex flex-wrap gap-2 shrink-0">
            <button
              type="button"
              class="min-h-[44px] px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700"
              @click="openQuickNote(lesson.id)"
            >
              Note rapide
            </button>
            <button
              type="button"
              class="min-h-[44px] px-3 py-2 rounded-lg border border-indigo-600 text-indigo-700 text-sm font-medium hover:bg-indigo-50"
              @click="openQuickNote(lesson.id)"
            >
              Enregistrer en vocal
            </button>
          </div>
        </li>
      </ul>

      <div class="mt-6 flex flex-wrap items-center gap-3 text-sm text-gray-600">
        <span>Période :</span>
        <select
          v-model="selectedPeriod"
          class="min-h-[40px] rounded-lg border border-gray-300 px-3 py-1.5"
          @change="loadLessons"
        >
          <option value="week">Cette semaine</option>
          <option value="month">Ce mois</option>
          <option value="quarter">3 mois</option>
        </select>
      </div>
    </div>

    <TeacherActivityQuickNoteModal
      :show="quickNoteOpen"
      :lessons="lessons"
      :initial-lesson-id="quickNoteInitialLessonId"
      @close="closeQuickNote"
      @saved="onNoteSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

definePageMeta({
  middleware: ['auth'],
  layout: 'default',
})

const authStore = useAuthStore()
const { $api } = useNuxtApp()

if (!authStore.canActAsTeacher) {
  throw createError({
    statusCode: 403,
    statusMessage: 'Accès réservé aux enseignants',
  })
}

const loading = ref(true)
const lessons = ref<any[]>([])
const selectedPeriod = ref('month')

const quickNoteOpen = ref(false)
const quickNoteInitialLessonId = ref<number | null>(null)

const filteredLessons = computed(() => {
  const list = [...lessons.value]
  return list.sort((a, b) => {
    const ta = new Date(a.start_time).getTime()
    const tb = new Date(b.start_time).getTime()
    return tb - ta
  })
})

function formatLessonTitle(lesson: any): string {
  const d = lesson.start_time ? new Date(lesson.start_time) : null
  const dateStr = d
    ? d.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' })
    : ''
  const timeStr = d
    ? d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    : ''
  const student =
    lesson.student?.user?.name ||
    (Array.isArray(lesson.students) && lesson.students[0]?.user?.name) ||
    'Élève'
  const type = lesson.course_type?.name || 'Cours'
  return `${dateStr} ${timeStr} — ${type} — ${student}`
}

/** Même modale pour « Note rapide » et « Enregistrer en vocal » : dictée via le bouton dans la fenêtre. */
function openQuickNote(lessonId: number | null) {
  quickNoteInitialLessonId.value = lessonId
  quickNoteOpen.value = true
}

function closeQuickNote() {
  quickNoteOpen.value = false
}

function onNoteSaved(lessonId: number, notes: string) {
  const l = lessons.value.find((x) => Number(x.id) === Number(lessonId))
  if (l) {
    l.notes = notes || null
  }
}

async function loadLessons() {
  loading.value = true
  try {
    const res = await ($api as any).get('/teacher/lessons', {
      params: { limit: 100, period: selectedPeriod.value },
    })
    const data = res.data?.data ?? res.data ?? []
    lessons.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error(e)
    lessons.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadLessons()
})
</script>
