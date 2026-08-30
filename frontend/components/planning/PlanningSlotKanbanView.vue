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

    <template v-else>
      <div
        v-if="topScrollbar"
        ref="topScrollEl"
        class="overflow-x-auto overflow-y-hidden mb-1 h-3"
        data-testid="kanban-top-scroll"
        aria-hidden="true"
        @scroll="onTopScroll"
      >
        <div :style="{ width: `${scrollContentWidth}px`, height: '1px' }" />
      </div>

      <div
        ref="mainScrollEl"
        class="overflow-x-auto pb-2"
        data-testid="kanban-main-scroll"
        role="region"
        aria-label="Colonnes par jour du créneau"
        @scroll="onMainScroll"
      >
        <div ref="contentEl" class="inline-flex flex-col min-w-min gap-0">
          <!-- En-têtes jours -->
          <div class="flex gap-3">
            <div
              v-for="col in columns"
              :key="`h-${col.ymd}`"
              class="shrink-0 w-[220px] sm:w-[240px] rounded-t-lg border border-b-0 border-gray-200"
              :class="col.isClosure ? 'bg-gray-200' : 'bg-white'"
              :data-ymd="col.ymd"
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
            </div>
          </div>

          <!-- + Cours haut -->
          <div class="flex gap-3">
            <div
              v-for="col in columns"
              :key="`ct-${col.ymd}`"
              class="shrink-0 w-[220px] sm:w-[240px] p-2 border-x border-gray-200 bg-white"
            >
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
          </div>

          <!-- Corps : plages en rangées -->
          <template v-if="plageMatrix.length > 0">
            <template v-for="band in plageMatrix" :key="band.plageKey">
              <div
                class="flex gap-3"
                :data-plage="band.plageKey"
              >
                <div
                  v-for="(col, colIdx) in columns"
                  :key="`pt-${band.plageKey}-${col.ymd}`"
                  class="shrink-0 w-[220px] sm:w-[240px] px-2 pt-2 border-x border-gray-200"
                  :class="[
                    stripeBandClass(band.stripeIndex),
                    col.isClosure ? 'opacity-50' : '',
                    colIdx === 0 ? 'rounded-tl-md' : '',
                    colIdx === columns.length - 1 ? 'rounded-tr-md' : '',
                  ]"
                >
                  <p class="text-xs font-bold tracking-wide text-gray-700 px-1 tabular-nums">
                    {{ band.label }}
                  </p>
                </div>
              </div>

              <div
                v-for="(row, rowIdx) in band.rows"
                :key="`pr-${band.plageKey}-${row.rowKey}`"
                class="flex gap-3 items-stretch"
              >
                <div
                  v-for="(col, colIdx) in columns"
                  :key="`pc-${band.plageKey}-${row.rowKey}-${col.ymd}`"
                  class="shrink-0 w-[220px] sm:w-[240px] px-2 py-1 border-x border-gray-200 flex"
                  :class="[
                    stripeBandClass(band.stripeIndex),
                    col.isClosure ? 'opacity-50' : '',
                    rowIdx === band.rows.length - 1 && colIdx === 0 ? 'rounded-bl-md pb-2' : '',
                    rowIdx === band.rows.length - 1 && colIdx === columns.length - 1 ? 'rounded-br-md pb-2' : '',
                    rowIdx === band.rows.length - 1 ? 'pb-2' : '',
                  ]"
                >
                  <template
                    v-for="lesson in [row.cells[colIdx]]"
                    :key="lesson ? String(lesson.id) : `empty-${band.plageKey}-${row.rowKey}-${col.ymd}`"
                  >
                    <button
                      v-if="lesson"
                      type="button"
                      class="w-full text-left rounded-md border border-gray-200 bg-white px-2.5 py-2 shadow-sm hover:shadow transition-shadow min-h-[72px] h-full box-border"
                      :class="cardAccentClass(lesson)"
                      @click="$emit('select-lesson', lesson, col.ymd)"
                    >
                      <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[10px] font-medium text-gray-500 tabular-nums">
                          {{ formatLessonTime(lesson.start_time) }}
                        </span>
                        <span
                          v-if="lesson.is_recurring_placeholder"
                          class="text-[9px] font-bold uppercase text-violet-700 bg-violet-100 px-1 rounded"
                        >Série</span>
                      </div>
                      <p class="text-xs font-medium text-gray-900 truncate">
                        {{
                          lesson.course_type?.name
                            || (lesson.is_recurring_placeholder ? 'Réservation récurrente' : 'Cours')
                        }}
                      </p>
                      <p class="text-[11px] text-gray-600 truncate mt-0.5">
                        {{ studentLabel(lesson) }}
                      </p>
                      <p class="text-[11px] text-gray-500 truncate">
                        {{ teacherLabel(lesson) }}
                      </p>
                    </button>
                    <div
                      v-else
                      class="w-full min-h-[72px] h-full box-border rounded-md border border-dashed border-gray-200/80 bg-white/50"
                      aria-hidden="true"
                    />
                  </template>
                </div>
              </div>
            </template>
          </template>
          <div
            v-else
            class="flex gap-3"
          >
            <div
              v-for="col in columns"
              :key="`empty-${col.ymd}`"
              class="shrink-0 w-[220px] sm:w-[240px] px-2 py-4 border-x border-gray-200 bg-gray-50 text-xs text-gray-400 text-center"
            >
              Aucun cours
            </div>
          </div>

          <!-- + Cours bas -->
          <div class="flex gap-3">
            <div
              v-for="col in columns"
              :key="`cb-${col.ymd}`"
              class="shrink-0 w-[220px] sm:w-[240px] p-2 border border-t-0 border-gray-200 bg-white rounded-b-lg"
            >
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
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { buildKanbanPlageAlignedRows } from '~/composables/planning/usePlanningCalendar'
import { resolveLessonPrimaryStudentId } from '~/composables/planning/usePlanningParticipant'

export type KanbanLessonCard = {
  id: number | string
  start_time: string
  end_time?: string
  status?: string | null
  is_recurring_placeholder?: boolean
  course_type?: { name?: string } | null
  student?: { id?: number; user?: { name?: string } | null } | null
  students?: Array<{ id?: number; user?: { name?: string } | null }>
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
    topScrollbar?: boolean
  }>(),
  {
    slotLabel: '',
    loading: false,
    canPrev: true,
    canNext: true,
    prevLabel: 'Période précédente',
    nextLabel: 'Période suivante',
    topScrollbar: false,
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

const topScrollEl = ref<HTMLElement | null>(null)
const mainScrollEl = ref<HTMLElement | null>(null)
const contentEl = ref<HTMLElement | null>(null)
const scrollContentWidth = ref(0)
let syncingScroll = false

const aligned = computed(() =>
  buildKanbanPlageAlignedRows(props.columns, {
    getStudentRowKey: studentRowKey,
    getStudentSortKey: studentLabel,
  }),
)

const plageMatrix = computed(() => {
  const { plages, byYmd } = aligned.value
  /** ymd → plageKey → band (évite find() dans les boucles) */
  const bandIndex = new Map<string, Map<string, (typeof byYmd)[string][number]>>()
  for (const col of props.columns) {
    const byPlage = new Map<string, (typeof byYmd)[string][number]>()
    for (const band of byYmd[col.ymd] ?? []) {
      byPlage.set(band.plageKey, band)
    }
    bandIndex.set(col.ymd, byPlage)
  }

  return plages.map((p, stripeIndex) => {
    const sample = props.columns[0]
      ? bandIndex.get(props.columns[0].ymd)?.get(p.key)
      : undefined
    const rowKeys = sample?.rowKeys ?? []
    const label = sample?.label ?? p.label
    return {
      plageKey: p.key,
      label,
      stripeIndex,
      rows: rowKeys.map((rowKey, ri) => ({
        rowKey,
        cells: props.columns.map((col) => {
          const band = bandIndex.get(col.ymd)?.get(p.key)
          return band?.slots[ri] ?? null
        }),
      })),
    }
  })
})

function measureScrollWidth() {
  scrollContentWidth.value = contentEl.value?.scrollWidth ?? 0
}

function onTopScroll() {
  if (syncingScroll || !topScrollEl.value || !mainScrollEl.value) return
  syncingScroll = true
  mainScrollEl.value.scrollLeft = topScrollEl.value.scrollLeft
  syncingScroll = false
}

function onMainScroll() {
  if (syncingScroll || !topScrollEl.value || !mainScrollEl.value) return
  syncingScroll = true
  topScrollEl.value.scrollLeft = mainScrollEl.value.scrollLeft
  syncingScroll = false
}

let resizeObserver: ResizeObserver | null = null

function bindResizeObserver() {
  resizeObserver?.disconnect()
  resizeObserver = null
  if (!contentEl.value || typeof ResizeObserver === 'undefined') return
  resizeObserver = new ResizeObserver(() => measureScrollWidth())
  resizeObserver.observe(contentEl.value)
}

onMounted(() => {
  void nextTick(() => {
    measureScrollWidth()
    bindResizeObserver()
  })
})

onBeforeUnmount(() => {
  resizeObserver?.disconnect()
  resizeObserver = null
})

watch(
  () => [props.columns, props.topScrollbar] as const,
  async () => {
    await nextTick()
    measureScrollWidth()
    bindResizeObserver()
  },
  { deep: true },
)

function formatLessonTime(datetime: string): string {
  const date = new Date(datetime)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function studentRowKey(lesson: KanbanLessonCard): string {
  const sid = resolveLessonPrimaryStudentId(lesson)
  if (sid != null) return `id:${sid}`
  const label = studentLabel(lesson)
  if (label && label !== 'Élève —') return `name:${label}`
  return `lesson:${lesson.id}`
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
