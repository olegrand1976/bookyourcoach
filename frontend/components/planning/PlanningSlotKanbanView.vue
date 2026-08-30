<template>
  <div class="planning-slot-kanban" data-testid="planning-slot-kanban">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-3 sm:mb-4">
      <div class="min-w-0">
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 truncate">
          {{ title }}
        </h3>
        <p v-if="slotLabel" class="text-sm text-gray-600 mt-0.5">
          {{ slotLabel }}
        </p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <button
          type="button"
          class="min-h-[40px] min-w-[40px] flex items-center justify-center rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50"
          :disabled="loading || !canPrev"
          :aria-label="prevLabel"
          @click="$emit('prev')"
        >
          ←
        </button>
        <button
          type="button"
          class="min-h-[40px] px-3 py-2 text-sm font-medium rounded-lg border border-blue-300 text-blue-700 hover:bg-blue-50 transition-colors disabled:opacity-50"
          :disabled="loading"
          @click="$emit('go-today')"
        >
          Aujourd'hui
        </button>
        <button
          type="button"
          class="min-h-[40px] min-w-[40px] flex items-center justify-center rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50"
          :disabled="loading || !canNext"
          :aria-label="nextLabel"
          @click="$emit('next')"
        >
          →
        </button>
      </div>
    </div>

    <div
      v-if="columns.length === 0"
      class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-8 text-center text-amber-950 text-sm"
    >
      Aucune occurrence de ce créneau sur la période.
    </div>

    <div
      v-else
      class="flex gap-3 overflow-x-auto pb-2 snap-x snap-mandatory"
      role="list"
      aria-label="Colonnes par jour du créneau"
    >
      <section
        v-for="col in columns"
        :key="col.ymd"
        class="snap-start shrink-0 w-[220px] sm:w-[240px] flex flex-col rounded-lg border border-gray-200 bg-gray-50"
        role="listitem"
        :data-ymd="col.ymd"
      >
        <header
          class="sticky top-0 z-[1] border-b border-gray-200 rounded-t-lg"
          :class="col.isClosure ? 'bg-gray-200' : 'bg-white'"
        >
          <button
            type="button"
            class="w-full text-left px-3 py-2 hover:bg-blue-50/80 transition-colors rounded-t-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-inset"
            :aria-label="`Voir le jour ${col.label}`"
            @click="$emit('select-day', col.ymd)"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900 capitalize truncate">
                  {{ col.label }}
                </p>
                <p class="text-xs text-gray-500 tabular-nums">
                  {{ columnCountLabel(col) }}
                </p>
              </div>
              <span
                v-if="col.isClosure"
                class="shrink-0 text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded bg-gray-300 text-gray-800"
              >
                Congé
              </span>
            </div>
          </button>
        </header>

        <div class="p-2 border-b border-gray-200 bg-white">
          <button
            type="button"
            :class="createLessonBtnClass"
            :disabled="col.isClosure || loading"
            title="Créer un cours ce jour"
            data-testid="kanban-create-top"
            @click.stop="$emit('create-lesson', col.ymd)"
          >
            + Cours
          </button>
        </div>

        <div
          class="flex-1 p-2 space-y-3"
          :class="col.isClosure ? 'opacity-50' : ''"
        >
          <template v-if="(alignedByYmd[col.ymd] ?? []).length > 0">
            <div
              v-for="band in alignedByYmd[col.ymd]"
              :key="`${col.ymd}-${band.hourKey}`"
              class="rounded-md px-1.5 py-1.5 space-y-2"
              :class="stripeBandClass(band.stripeIndex)"
              :data-hour="band.hourKey"
            >
              <p class="text-xs font-bold uppercase tracking-wide text-gray-700 px-1">
                {{ band.label }}
              </p>
              <template v-for="(slot, slotIdx) in band.slots" :key="slot ? String(slot.id) : `empty-${band.hourKey}-${slotIdx}`">
                <button
                  v-if="slot"
                  type="button"
                  class="w-full text-left rounded-md border border-gray-200 bg-white px-2.5 py-2 shadow-sm hover:shadow transition-shadow min-h-[72px] box-border"
                  :class="cardAccentClass(slot)"
                  @click="$emit('select-lesson', slot, col.ymd)"
                >
                  <div class="flex items-center justify-between gap-1 mb-1">
                    <span class="text-[10px] font-medium text-gray-500 tabular-nums">
                      {{ formatLessonTime(slot.start_time) }}
                    </span>
                    <span
                      v-if="slot.is_recurring_placeholder"
                      class="text-[9px] font-bold uppercase text-violet-700 bg-violet-100 px-1 rounded"
                    >Série</span>
                  </div>
                  <p class="text-xs font-medium text-gray-900 truncate">
                    {{ slot.course_type?.name || (slot.is_recurring_placeholder ? 'Réservation récurrente' : 'Cours') }}
                  </p>
                  <p class="text-[11px] text-gray-600 truncate mt-0.5">
                    {{ studentLabel(slot) }}
                  </p>
                  <p class="text-[11px] text-gray-500 truncate">
                    {{ teacherLabel(slot) }}
                  </p>
                </button>
                <div
                  v-else
                  class="w-full min-h-[72px] box-border rounded-md border border-dashed border-gray-200/80 bg-white/50"
                  aria-hidden="true"
                />
              </template>
            </div>
          </template>
          <p
            v-else
            class="text-xs text-gray-400 text-center py-4"
          >
            Aucun cours
          </p>
        </div>

        <footer class="p-2 border-t border-gray-200 bg-white rounded-b-lg">
          <button
            type="button"
            :class="createLessonBtnClass"
            :disabled="col.isClosure || loading"
            title="Créer un cours ce jour"
            data-testid="kanban-create-bottom"
            @click.stop="$emit('create-lesson', col.ymd)"
          >
            + Cours
          </button>
        </footer>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { buildKanbanHourAlignedRows } from '~/composables/planning/usePlanningCalendar'

export type KanbanLessonCard = {
  id: number | string
  start_time: string
  end_time?: string
  status?: string | null
  is_recurring_placeholder?: boolean
  course_type?: { name?: string } | null
  student?: { user?: { name?: string } | null } | null
  students?: Array<{ user?: { name?: string } | null }>
  teacher?: { user?: { name?: string } | null } | null
  student_id?: number | null
  teacher_id?: number | null
}

export type KanbanColumn = {
  ymd: string
  label: string
  isClosure: boolean
  lessons: KanbanLessonCard[]
}

const props = withDefaults(
  defineProps<{
    title: string
    slotLabel?: string
    columns: KanbanColumn[]
    loading?: boolean
    canPrev?: boolean
    canNext?: boolean
    prevLabel?: string
    nextLabel?: string
  }>(),
  {
    slotLabel: '',
    loading: false,
    canPrev: true,
    canNext: true,
    prevLabel: 'Période précédente',
    nextLabel: 'Période suivante',
  },
)

defineEmits<{
  prev: []
  next: []
  'go-today': []
  'select-day': [ymd: string]
  'select-lesson': [lesson: KanbanLessonCard, ymd: string]
  'create-lesson': [ymd: string]
}>()

const createLessonBtnClass =
  'w-full min-h-[36px] text-sm font-medium rounded-md border border-dashed border-blue-300 text-blue-700 hover:bg-blue-50 disabled:opacity-40 disabled:cursor-not-allowed'

const alignedByYmd = computed(() =>
  buildKanbanHourAlignedRows(props.columns, studentLabel).byYmd,
)

function formatLessonTime(datetime: string): string {
  const date = new Date(datetime)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function studentLabel(lesson: KanbanLessonCard): string {
  const names: string[] = []
  if (lesson.student?.user?.name) names.push(lesson.student.user.name)
  if (Array.isArray(lesson.students)) {
    for (const s of lesson.students) {
      const n = s?.user?.name
      if (n && !names.includes(n)) names.push(n)
    }
  }
  if (names.length) return names.join(', ')
  if (lesson.student_id != null) return `Élève #${lesson.student_id}`
  return 'Élève —'
}

function columnCountLabel(col: KanbanColumn): string {
  const real = col.lessons.filter((l) => !l.is_recurring_placeholder).length
  const placeholders = col.lessons.length - real
  if (placeholders <= 0) return `${real} cours`
  if (real <= 0) return `${placeholders} série${placeholders > 1 ? 's' : ''}`
  return `${real} cours · ${placeholders} série${placeholders > 1 ? 's' : ''}`
}

function teacherLabel(lesson: KanbanLessonCard): string {
  return lesson.teacher?.user?.name || 'Coach —'
}

/** Couleur de bande uniquement (cartes restent blanches pour hauteur visuelle stable). */
function stripeBandClass(stripeIndex: number): string {
  return stripeIndex % 2 === 0 ? 'bg-sky-50/80' : 'bg-amber-50/80'
}

function cardAccentClass(lesson: KanbanLessonCard): string {
  if (lesson.is_recurring_placeholder) {
    return 'ring-1 ring-violet-200 border-violet-200'
  }
  if (lesson.status === 'cancelled') {
    return 'opacity-70 border-orange-200'
  }
  return ''
}
</script>
