<template>
  <div class="planning-quarter-view space-y-4" data-testid="planning-quarter-view">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h3 class="text-base sm:text-lg font-semibold text-gray-900">
        {{ quarterLabel }}
      </h3>
      <div class="flex items-center gap-2 flex-wrap">
        <button
          type="button"
          class="min-h-[40px] min-w-[40px] flex items-center justify-center rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50"
          :disabled="loading || !canPrev"
          aria-label="Trimestre précédent"
          @click="$emit('prev-quarter')"
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
          aria-label="Trimestre suivant"
          @click="$emit('next-quarter')"
        >
          →
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <PlanningMonthView
        v-for="monthDate in monthAnchors"
        :key="formatDateToISO(monthDate)"
        :display-date="monthDate"
        :lesson-counts="lessonCounts"
        :closure-dates="closureDates"
        :selected-ymd="selectedYmd"
        :loading="loading"
        compact
        :show-nav="false"
        @select-day="$emit('select-day', $event)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import PlanningMonthView from '~/components/planning/PlanningMonthView.vue'
import {
  formatDateToISO,
  getQuarterMonthAnchors,
} from '~/composables/planning/useDateHelpers'

const props = withDefaults(
  defineProps<{
    displayDate: Date
    lessonCounts?: Record<string, number>
    closureDates?: string[]
    selectedYmd?: string | null
    loading?: boolean
    canPrev?: boolean
    canNext?: boolean
  }>(),
  {
    lessonCounts: () => ({}),
    closureDates: () => [],
    selectedYmd: null,
    loading: false,
    canPrev: true,
    canNext: true,
  },
)

defineEmits<{
  'select-day': [ymd: string]
  'prev-quarter': []
  'next-quarter': []
  'go-today': []
}>()

const monthAnchors = computed(() => getQuarterMonthAnchors(props.displayDate))

const quarterLabel = computed(() => {
  const q = Math.floor(props.displayDate.getMonth() / 3) + 1
  return `T${q} ${props.displayDate.getFullYear()}`
})
</script>
