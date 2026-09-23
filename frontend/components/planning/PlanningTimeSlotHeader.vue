<template>
  <div
    class="bg-gradient-to-r from-blue-600 to-blue-700 px-3 py-2.5 sm:px-4 sm:py-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
    :class="accentClass"
    data-testid="planning-timeslot-header"
  >
    <div class="flex flex-col gap-1.5 min-w-0 sm:flex-1">
      <div class="flex flex-wrap items-center gap-2 sm:gap-3">
        <svg class="w-5 h-5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-white font-semibold text-base sm:text-lg">{{ time }}</span>

        <!-- Jauge de voies : ● cours · ⇄ série attendue · ◐ place libérée · ○ libre -->
        <span
          class="inline-flex items-center gap-0.5 font-mono text-sm leading-none text-white/95 select-none"
          :title="gaugeTitle"
          data-testid="lane-gauge"
        >
          <template v-if="condensed">
            <span class="tabular-nums">{{ availability.occupied }}/{{ availability.maxSlots }}</span>
          </template>
          <template v-else>
            <span
              v-for="(lane, i) in availability.lanes"
              :key="i"
              :class="laneClass(lane.kind)"
              :title="lane.label"
              :data-lane="lane.kind"
            >{{ laneGlyph(lane.kind) }}</span>
          </template>
        </span>

        <span class="text-blue-100/95 text-xs sm:text-sm">
          {{ availability.occupied }}/{{ availability.maxSlots }} voie{{ availability.maxSlots > 1 ? 's' : '' }}
          <template v-if="availability.free > 0"> · {{ availability.free }} libre{{ availability.free > 1 ? 's' : '' }}</template>
          <template v-else-if="availability.occupied > availability.maxSlots"> · surbooking</template>
        </span>

        <span
          v-if="hasTeacherConflict"
          class="inline-flex items-center gap-1 rounded-md bg-red-600 px-2 py-0.5 text-xs font-bold text-white shadow-sm"
          title="Conflit : le même enseignant a deux cours qui se chevauchent sur cette plage."
        >
          <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
            <rect x="6" y="10" width="12" height="4" rx="1" fill="currentColor" />
          </svg>
          Sens interdit
        </span>
      </div>

      <!-- Anomalies de la plage : un bouton par famille présente, ouvre/ferme le tiroir -->
      <div v-if="summary.length" class="flex flex-wrap items-center gap-1.5">
        <button
          v-for="entry in summary"
          :key="entry.kind"
          type="button"
          class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-semibold ring-1 transition-colors"
          :class="chipClass(entry.kind)"
          :aria-expanded="drawerOpen ? 'true' : 'false'"
          :data-anomaly-kind="entry.kind"
          @click.stop="emit('toggle-drawer')"
        >
          <span aria-hidden="true">{{ chipGlyph(entry.kind) }}</span>
          {{ anomalyCountLabel(entry.kind, entry.count) }}
          <span aria-hidden="true" class="opacity-80">{{ drawerOpen ? '▾' : '▸' }}</span>
        </button>
      </div>
    </div>

    <button
      v-if="canCreate"
      type="button"
      :disabled="isClosure"
      class="w-full sm:w-auto shrink-0 justify-center px-3 py-2 sm:py-1.5 text-sm bg-white text-blue-700 rounded-lg hover:bg-blue-50 transition-colors flex items-center gap-2 font-medium shadow-sm disabled:opacity-50 disabled:pointer-events-none"
      title="Créer un cours à cette heure"
      data-testid="create-lesson"
      @click.stop="emit('create-lesson')"
    >
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
      </svg>
      Créer un cours
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  anomalyCountLabel,
  summarizeAnomalies,
  type LaneKind,
  type PlanningAnomaly,
  type PlanningAnomalyKind,
  type TimeSlotAvailability,
} from '~/composables/planning/usePlanningPlaceholders'

/** Au-delà, la jauge se condense en « occupé/max ». */
const GAUGE_MAX_LANES = 4

const props = withDefaults(defineProps<{
  time: string
  availability: TimeSlotAvailability
  anomalies?: PlanningAnomaly[]
  hasTeacherConflict?: boolean
  isClosure?: boolean
  drawerOpen?: boolean
  canCreate?: boolean
}>(), {
  anomalies: () => [],
  hasTeacherConflict: false,
  isClosure: false,
  drawerOpen: false,
  canCreate: true,
})

const emit = defineEmits<{
  (e: 'create-lesson'): void
  (e: 'toggle-drawer'): void
}>()

const summary = computed(() => summarizeAnomalies(props.anomalies))
const condensed = computed(() => props.availability.lanes.length > GAUGE_MAX_LANES)

const gaugeTitle = computed(() => {
  const a = props.availability
  const parts = [`${a.occupied} occupée${a.occupied > 1 ? 's' : ''} sur ${a.maxSlots}`]
  if (a.free > 0) parts.push(`${a.free} libre${a.free > 1 ? 's' : ''}`)
  return parts.join(' · ')
})

/** Liseré gauche : repère de balayage vertical (ambre = place récupérable, émeraude = de la place, blanc = plein). */
const accentClass = computed(() => {
  const a = props.availability
  if (a.hasCancelledPlaceholder) return 'border-l-4 border-l-amber-400'
  if (a.free > 0 || a.hasRecurringPlaceholder) return 'border-l-4 border-l-emerald-400'
  return 'border-l-4 border-l-white/25'
})

function laneGlyph(kind: LaneKind): string {
  return kind === 'lesson' ? '●' : kind === 'series' ? '⇄' : kind === 'freed' ? '◐' : '○'
}

function laneClass(kind: LaneKind): string {
  return kind === 'lesson'
    ? 'text-white'
    : kind === 'series'
      ? 'text-violet-200'
      : kind === 'freed'
        ? 'text-amber-300'
        : 'text-white/45'
}

function chipGlyph(kind: PlanningAnomalyKind): string {
  return kind === 'generation_gap' ? '⚠' : kind === 'student_cancellation' ? '🙋' : kind === 'club_cancellation' ? '🏛' : '⇄'
}

function chipClass(kind: PlanningAnomalyKind): string {
  return kind === 'generation_gap'
    ? 'bg-amber-400/35 text-white ring-white/30 hover:bg-amber-400/50'
    : kind === 'student_cancellation'
      ? 'bg-sky-300/30 text-white ring-white/30 hover:bg-sky-300/45'
      : kind === 'club_cancellation'
        ? 'bg-white/15 text-blue-50 ring-white/20 hover:bg-white/25'
        : 'bg-violet-400/35 text-white ring-white/30 hover:bg-violet-400/50'
}
</script>
