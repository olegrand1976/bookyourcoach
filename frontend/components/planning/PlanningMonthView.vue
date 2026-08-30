<template>
  <div
    class="planning-month-view"
    :class="compact ? 'planning-month-view--compact' : ''"
    data-testid="planning-month-view"
  >
    <div
      v-if="showNav"
      class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-3 sm:mb-4"
    >
      <h3 class="text-base sm:text-lg font-semibold text-gray-900 capitalize">
        {{ monthLabel }}
      </h3>
      <div class="flex items-center gap-2 flex-wrap">
        <button
          type="button"
          class="min-h-[40px] min-w-[40px] flex items-center justify-center rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50"
          :disabled="loading || !canPrev"
          aria-label="Mois précédent"
          @click="$emit('prev-month')"
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
          aria-label="Mois suivant"
          @click="$emit('next-month')"
        >
          →
        </button>
      </div>
    </div>
    <p
      v-else
      class="text-sm font-semibold text-gray-800 capitalize mb-2 text-center"
    >
      {{ monthLabel }}
    </p>

    <div class="grid grid-cols-7 gap-0.5 sm:gap-1 mb-1">
      <div
        v-for="label in weekDayLabels"
        :key="label"
        class="p-1 text-center text-[10px] sm:text-xs font-medium text-gray-500 bg-gray-50 rounded"
      >
        {{ label }}
      </div>
    </div>

    <div class="grid grid-cols-7 gap-0.5 sm:gap-1">
      <button
        v-for="day in calendarDays"
        :key="day.date"
        type="button"
        class="text-left border rounded transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
        :class="cellClass(day)"
        :disabled="!day.isCurrentMonth && !allowAdjacentClick"
        :aria-label="ariaLabelForDay(day)"
        @click="onDayClick(day)"
      >
        <div class="flex items-center justify-between gap-0.5 mb-0.5">
          <span
            class="text-xs sm:text-sm font-medium tabular-nums"
            :class="dayNumberClass(day)"
          >
            {{ day.day }}
          </span>
          <span
            v-if="isClosure(day.date)"
            class="text-[9px] sm:text-[10px] font-semibold text-gray-600"
            title="Jour fermé"
          >
            Congé
          </span>
        </div>
        <div
          v-if="countFor(day.date) > 0"
          class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] sm:text-xs font-semibold"
          :class="countBadgeClass(day.date)"
        >
          {{ countFor(day.date) }}
          <span v-if="!compact" class="ml-0.5 font-normal opacity-90">cours</span>
        </div>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  buildMonthCalendarDays,
  getMonthName,
  type MonthCalendarDay,
} from '~/composables/planning/useDateHelpers'

const props = withDefaults(
  defineProps<{
    displayDate: Date
    /** Compteurs YYYY-MM-DD → nombre de cours (hors annulés) */
    lessonCounts?: Record<string, number>
    closureDates?: string[]
    selectedYmd?: string | null
    compact?: boolean
    showNav?: boolean
    loading?: boolean
    canPrev?: boolean
    canNext?: boolean
    allowAdjacentClick?: boolean
  }>(),
  {
    lessonCounts: () => ({}),
    closureDates: () => [],
    selectedYmd: null,
    compact: false,
    showNav: true,
    loading: false,
    canPrev: true,
    canNext: true,
    allowAdjacentClick: false,
  },
)

const emit = defineEmits<{
  'select-day': [ymd: string]
  'prev-month': []
  'next-month': []
  'go-today': []
}>()

const weekDayLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

const calendarDays = computed(() => buildMonthCalendarDays(props.displayDate))

const monthLabel = computed(() => {
  const name = getMonthName(props.displayDate, 'long')
  const year = props.displayDate.getFullYear()
  return `${name} ${year}`
})

const closureSet = computed(() => new Set(props.closureDates))

function countFor(ymd: string): number {
  return props.lessonCounts[ymd] ?? 0
}

function isClosure(ymd: string): boolean {
  return closureSet.value.has(ymd)
}

function cellClass(day: MonthCalendarDay): string {
  const base = props.compact
    ? 'min-h-[52px] sm:min-h-[64px] p-1'
    : 'min-h-[72px] sm:min-h-[96px] p-1.5 sm:p-2'
  const classes = [base]

  if (!day.isCurrentMonth) {
    classes.push('bg-gray-50 text-gray-400 border-gray-100')
  } else if (isClosure(day.date)) {
    classes.push('bg-gray-100 border-gray-300')
  } else {
    classes.push('bg-white border-gray-200 hover:bg-blue-50/60')
  }

  if (day.isToday) {
    classes.push('ring-1 ring-blue-400 border-blue-300')
  }
  if (props.selectedYmd && day.date === props.selectedYmd) {
    classes.push('ring-2 ring-blue-600 border-blue-500')
  }

  return classes.join(' ')
}

function dayNumberClass(day: MonthCalendarDay): string {
  if (!day.isCurrentMonth) return 'text-gray-400'
  if (day.isToday) return 'text-blue-700 font-bold'
  return 'text-gray-900'
}

function countBadgeClass(ymd: string): string {
  if (isClosure(ymd)) return 'bg-gray-200 text-gray-700'
  const n = countFor(ymd)
  if (n >= 8) return 'bg-indigo-100 text-indigo-800'
  if (n >= 4) return 'bg-blue-100 text-blue-800'
  return 'bg-emerald-100 text-emerald-800'
}

function ariaLabelForDay(day: MonthCalendarDay): string {
  const n = countFor(day.date)
  const closure = isClosure(day.date) ? ', jour fermé' : ''
  return `${day.date}, ${n} cours${closure}`
}

function onDayClick(day: MonthCalendarDay) {
  if (!day.isCurrentMonth && !props.allowAdjacentClick) return
  emit('select-day', day.date)
}
</script>
