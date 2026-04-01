<template>
  <div
    v-if="show && payload"
    class="fixed inset-0 z-[70] overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="schedule-conflict-title"
  >
    <div class="flex min-h-screen items-center justify-center px-4 py-8">
      <div class="fixed inset-0 bg-black/50" aria-hidden="true" @click="emitClose" />
      <div class="relative w-full max-w-2xl rounded-xl bg-white shadow-xl">
        <div class="border-b border-gray-200 px-6 py-4">
          <h2 id="schedule-conflict-title" class="text-lg font-semibold text-gray-900">
            Conflit : impossible de créer la série
          </h2>
          <p class="mt-1 text-sm text-gray-600">{{ payload.message }}</p>
          <p v-if="payload.hint" class="mt-2 text-xs text-gray-500 whitespace-pre-line">{{ payload.hint }}</p>
        </div>

        <div class="max-h-[60vh] overflow-y-auto px-6 py-4 space-y-4">
          <p class="text-sm text-gray-700">
            Chaque bloc ci-dessous est une <strong>cause réelle</strong> en base. Vous pouvez modifier ou libérer la ressource, puis réessayer la création.
          </p>

          <div
            v-for="group in aggregatedGroups"
            :key="group.key"
            class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3"
          >
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div>
                <span class="text-xs font-semibold uppercase tracking-wide text-violet-700">
                  {{ group.typeLabel }}
                </span>
                <p class="text-sm text-gray-800 mt-1">{{ group.summaryLine }}</p>
                <p v-if="group.datesPreview" class="text-xs text-gray-500 mt-1">
                  Occurrences concernées (extrait) : {{ group.datesPreview }}
                  <span v-if="group.dateCount > 8"> — {{ group.dateCount }} date(s) au total</span>
                </p>
              </div>
            </div>

            <div v-if="group.blockingLesson" class="rounded-md bg-white border border-gray-200 p-3 text-sm">
              <p class="font-medium text-gray-900">Cours planifié</p>
              <ul class="mt-1 space-y-0.5 text-gray-700 text-xs sm:text-sm">
                <li><span class="text-gray-500">N° cours</span> {{ group.blockingLesson.id }}</li>
                <li>
                  <span class="text-gray-500">Période</span>
                  {{ group.blockingLesson.start_time }} → {{ group.blockingLesson.end_time }}
                  <span class="text-gray-400">(heure locale club)</span>
                </li>
                <li v-if="group.blockingLesson.teacher_name">
                  <span class="text-gray-500">Enseignant</span> {{ group.blockingLesson.teacher_name }}
                </li>
                <li v-if="group.blockingLesson.student_name">
                  <span class="text-gray-500">Élève</span> {{ group.blockingLesson.student_name }}
                </li>
                <li v-if="group.blockingLesson.status">
                  <span class="text-gray-500">Statut</span> {{ group.blockingLesson.status }}
                </li>
              </ul>
              <div class="mt-3 flex flex-wrap gap-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700"
                  @click="emit('edit-lesson', group.lessonId!)"
                >
                  Modifier le cours
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-900 hover:bg-amber-100"
                  @click="emit('cancel-lesson', group.lessonId!)"
                >
                  Annuler ce cours
                </button>
              </div>
            </div>
            <div v-else-if="group.lessonId != null" class="rounded-md bg-white border border-gray-200 p-3 text-sm">
              <p class="text-gray-700">
                Cours <span class="font-mono font-medium">#{{ group.lessonId }}</span> — l’API n’a pas renvoyé le détail ; vous pouvez quand même modifier ou annuler ce cours.
              </p>
              <div class="mt-3 flex flex-wrap gap-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700"
                  @click="emit('edit-lesson', group.lessonId!)"
                >
                  Modifier le cours
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-900 hover:bg-amber-100"
                  @click="emit('cancel-lesson', group.lessonId!)"
                >
                  Annuler ce cours
                </button>
              </div>
            </div>

            <div v-if="group.blockingRecurring" class="rounded-md bg-white border border-violet-200 p-3 text-sm">
              <p class="font-medium text-gray-900">Réservation récurrente (abonnement)</p>
              <ul class="mt-1 space-y-0.5 text-gray-700 text-xs sm:text-sm">
                <li><span class="text-gray-500">N° réservation</span> {{ group.blockingRecurring.id ?? group.slotId }}</li>
                <li>
                  <span class="text-gray-500">Jour</span>
                  {{ weekdayLabel(group.blockingRecurring.day_of_week) }}
                  · {{ formatHm(group.blockingRecurring.start_time) }} – {{ formatHm(group.blockingRecurring.end_time) }}
                </li>
                <li v-if="group.blockingRecurring.teacher_name">
                  <span class="text-gray-500">Enseignant</span> {{ group.blockingRecurring.teacher_name }}
                </li>
                <li v-if="group.blockingRecurring.student_name">
                  <span class="text-gray-500">Élève</span> {{ group.blockingRecurring.student_name }}
                </li>
                <li v-if="group.blockingRecurring.start_date && group.blockingRecurring.end_date">
                  <span class="text-gray-500">Période</span>
                  {{ group.blockingRecurring.start_date }} → {{ group.blockingRecurring.end_date }}
                </li>
              </ul>
              <div class="mt-3 flex flex-wrap gap-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-violet-700"
                  @click="emit('release-recurring', group.slotId!)"
                >
                  Libérer ce créneau récurrent
                </button>
                <NuxtLink
                  to="/club/recurring-slots"
                  class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                  @click="emitClose"
                >
                  Ouvrir la liste des récurrences
                </NuxtLink>
              </div>
            </div>
            <div v-else-if="group.slotId != null" class="rounded-md bg-white border border-violet-200 p-3 text-sm">
              <p class="text-gray-700">
                Réservation récurrente <span class="font-mono font-medium">#{{ group.slotId }}</span> — détail non fourni ; libérez-la ou ouvrez la liste pour la modifier.
              </p>
              <div class="mt-3 flex flex-wrap gap-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-violet-700"
                  @click="emit('release-recurring', group.slotId!)"
                >
                  Libérer ce créneau récurrent
                </button>
                <NuxtLink
                  to="/club/recurring-slots"
                  class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                  @click="emitClose"
                >
                  Ouvrir la liste des récurrences
                </NuxtLink>
              </div>
            </div>
          </div>

          <div v-if="orphanMessages.length" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
            <p class="font-medium">Autres messages</p>
            <ul class="mt-1 list-inside list-disc text-xs">
              <li v-for="(o, i) in orphanMessages" :key="i">{{ o }}</li>
            </ul>
          </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
          <button
            type="button"
            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            @click="emitClose"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

export type ScheduleConflictPayload = {
  message: string
  hint?: string | null
  conflicts: Record<string, unknown>[]
}

const props = defineProps<{
  show: boolean
  payload: ScheduleConflictPayload | null
}>()

const emit = defineEmits<{
  close: []
  'edit-lesson': [lessonId: number]
  'cancel-lesson': [lessonId: number]
  'release-recurring': [slotId: number]
}>()

const WEEKDAYS = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']

function weekdayLabel(d: unknown): string {
  const n = typeof d === 'number' ? d : parseInt(String(d), 10)
  if (Number.isNaN(n) || n < 0 || n > 6) return '—'
  return WEEKDAYS[n] ?? '—'
}

function formatHm(t: unknown): string {
  if (t == null) return '—'
  const s = String(t)
  return s.length >= 5 ? s.slice(0, 5) : s
}

function typeLabel(t: unknown): string {
  switch (t) {
    case 'teacher_unavailable':
      return 'Enseignant indisponible'
    case 'student_unavailable':
      return 'Élève indisponible'
    case 'recurring_duplicate':
      return 'Doublon de réservation récurrente'
    case 'lesson_overlap':
      return 'Cours déjà présent sur ce créneau'
    default:
      return String(t || 'Conflit')
  }
}

type AggGroup = {
  key: string
  lessonId?: number
  slotId?: number
  types: Set<string>
  dates: string[]
  blockingLesson?: Record<string, unknown>
  blockingRecurring?: Record<string, unknown>
  typeLabel: string
  summaryLine: string
  datesPreview: string
  dateCount: number
}

const aggregatedGroups = computed((): AggGroup[] => {
  const allConflicts = props.payload?.conflicts ?? []
  const map = new Map<string, AggGroup>()

  for (const c of allConflicts) {
    const lessonId = typeof c.lesson_id === 'number' ? c.lesson_id : null
    const slotId = typeof c.recurring_slot_id === 'number' ? c.recurring_slot_id : null
    const date = typeof c.date === 'string' ? c.date : ''
    const typ = c.type as string

    if (lessonId == null && slotId == null) {
      continue
    }

    const key = lessonId != null ? `L:${lessonId}` : `R:${slotId}`

    if (!map.has(key)) {
      const bl = c.blocking_lesson as Record<string, unknown> | undefined
      const br =
        (c.blocking_recurring_slot as Record<string, unknown> | undefined) ||
        (c.recurring_slot as Record<string, unknown> | undefined)

      map.set(key, {
        key,
        lessonId: lessonId ?? undefined,
        slotId: slotId ?? undefined,
        types: new Set(),
        dates: [],
        blockingLesson: bl,
        blockingRecurring: br,
        typeLabel: '',
        summaryLine: '',
        datesPreview: '',
        dateCount: 0,
      })
    }
    const g = map.get(key)!
    if (typ) {
      g.types.add(typ)
    }
    if (date && !g.dates.includes(date)) {
      g.dates.push(date)
    }
    const bl = c.blocking_lesson as Record<string, unknown> | undefined
    const br =
      (c.blocking_recurring_slot as Record<string, unknown> | undefined) ||
      (c.recurring_slot as Record<string, unknown> | undefined)
    if (bl) {
      g.blockingLesson = bl
    }
    if (br) {
      g.blockingRecurring = br
    }
    if (lessonId != null) {
      g.lessonId = lessonId
    }
    if (slotId != null) {
      g.slotId = slotId
    }
  }

  const list: AggGroup[] = []
  for (const g of map.values()) {
    const typesArr = [...g.types]
    g.typeLabel = typesArr.map(typeLabel).join(' · ') || 'Conflit'
    g.dateCount = g.dates.length
    g.dates.sort()
    g.datesPreview = g.dates.slice(0, 8).join(', ')
    const firstMsg = allConflicts.find((c) => {
      if (g.lessonId != null && c.lesson_id === g.lessonId) {
        return true
      }
      if (g.slotId != null && c.recurring_slot_id === g.slotId) {
        return true
      }
      return false
    })
    g.summaryLine =
      (typeof firstMsg?.message === 'string' && firstMsg.message) ||
      'Ce créneau entre en conflit avec une ressource existante.'
    list.push(g)
  }
  return list
})

const orphanMessages = computed(() => {
  const conflicts = props.payload?.conflicts ?? []
  const out: string[] = []
  for (const c of conflicts) {
    const lessonId = typeof c.lesson_id === 'number' ? c.lesson_id : null
    const slotId = typeof c.recurring_slot_id === 'number' ? c.recurring_slot_id : null
    if (lessonId != null || slotId != null) {
      continue
    }
    const msg = [c.date, c.message].filter(Boolean).join(' — ')
    if (msg) {
      out.push(String(msg))
    }
  }
  return out
})

function emitClose() {
  emit('close')
}
</script>
