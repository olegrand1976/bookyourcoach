<template>
  <div
    v-if="total > 0"
    class="flex flex-col gap-2 rounded-lg border border-amber-200 bg-amber-50/70 px-3 py-2 sm:flex-row sm:items-center sm:justify-between"
    data-testid="planning-day-anomaly-bar"
  >
    <p class="m-0 text-sm text-amber-900">
      <span class="font-semibold">⚠ {{ total }} à traiter</span>
      <span class="text-amber-800"> :
        <template v-for="(entry, i) in summary" :key="entry.kind">
          <template v-if="i > 0"> · </template>{{ glyph(entry.kind) }}{{ anomalyCountLabel(entry.kind, entry.count) }}
        </template>
      </span>
    </p>
    <div class="flex flex-wrap items-center gap-1" role="group" aria-label="Filtrer les anomalies">
      <button
        type="button"
        class="rounded-md px-2 py-1 text-xs font-medium ring-1 transition-colors"
        :class="activeFilter === 'all' ? 'bg-amber-700 text-white ring-amber-700' : 'bg-white text-amber-900 ring-amber-300 hover:bg-amber-100'"
        data-filter="all"
        @click="emit('update:activeFilter', 'all')"
      >
        Tous ({{ total }})
      </button>
      <!-- Jamais désactivé : les incohérences ne sont comptées qu'après le premier clic (chargement paresseux). -->
      <button
        v-for="kind in ANOMALY_KINDS_ORDER"
        :key="kind"
        type="button"
        class="rounded-md px-2 py-1 text-xs font-medium ring-1 transition-colors"
        :class="activeFilter === kind ? 'bg-amber-700 text-white ring-amber-700' : 'bg-white text-amber-900 ring-amber-300 hover:bg-amber-100'"
        :data-filter="kind"
        @click="emit('update:activeFilter', kind)"
      >
        {{ shortLabel(kind) }} ({{ countFor(kind) }})
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  ANOMALY_KINDS_ORDER,
  anomalyCountLabel,
  summarizeAnomalies,
  type PlanningAnomaly,
  type PlanningAnomalyKind,
} from '~/composables/planning/usePlanningPlaceholders'

export type AnomalyFilter = PlanningAnomalyKind | 'all'

const props = withDefaults(defineProps<{
  anomalies: PlanningAnomaly[]
  activeFilter?: AnomalyFilter
}>(), {
  activeFilter: 'all',
})

const emit = defineEmits<{
  (e: 'update:activeFilter', value: AnomalyFilter): void
}>()

const summary = computed(() => summarizeAnomalies(props.anomalies))
const total = computed(() => props.anomalies.length)

function countFor(kind: PlanningAnomalyKind): number {
  return summary.value.find((s) => s.kind === kind)?.count ?? 0
}

function glyph(kind: PlanningAnomalyKind): string {
  return kind === 'generation_gap' ? '⚠' : kind === 'student_cancellation' ? '🙋' : kind === 'club_cancellation' ? '🏛' : '⇄'
}

function shortLabel(kind: PlanningAnomalyKind): string {
  return kind === 'generation_gap' ? 'Trous' : kind === 'student_cancellation' ? 'Élève' : kind === 'club_cancellation' ? 'Club' : 'Incohérences'
}
</script>
