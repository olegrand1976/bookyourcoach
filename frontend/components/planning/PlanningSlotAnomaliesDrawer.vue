<template>
  <div
    v-if="open && entries.length"
    class="border-t border-gray-200 bg-white px-3 py-2.5 sm:px-4"
    data-testid="planning-anomalies-drawer"
  >
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">
      À traiter sur cette plage ({{ entries.length }})
    </p>
    <ul class="flex flex-col gap-2">
      <li
        v-for="entry in entries"
        :key="entryKey(entry)"
        class="rounded-lg border px-3 py-2 flex flex-col gap-1.5"
        :class="rowClass(entry)"
        :data-anomaly-kind="primaryKind(entry)"
      >
        <div class="flex flex-wrap items-center gap-1.5 text-sm">
          <span
            v-for="a in entry.anomalies"
            :key="a.kind + (a.detail ?? '')"
            class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[11px] font-bold uppercase tracking-wide"
            :class="badgeClass(a.kind)"
            :title="a.detail"
          >
            {{ badgeGlyph(a.kind) }} {{ badgeLabel(a) }}
          </span>
          <span class="font-medium text-gray-900 tabular-nums">{{ timeRange(entry.placeholder) }}</span>
          <span class="text-gray-400">·</span>
          <span class="font-medium text-gray-800 truncate">{{ entry.studentsLabel || 'Élève' }}</span>
          <span v-if="entry.teacherName" class="text-gray-600 truncate">· 🎓 {{ entry.teacherName }}</span>
          <span class="text-gray-500">· série n°{{ entry.placeholder.recurring_slot_id ?? '—' }}</span>
        </div>

        <p class="text-xs text-gray-600 leading-snug m-0">{{ description(entry) }}</p>

        <div class="flex flex-col gap-1 sm:flex-row sm:flex-wrap sm:items-center sm:gap-1.5">
          <button
            type="button"
            class="justify-center px-2 py-1.5 text-xs rounded-md transition-colors inline-flex items-center gap-1 bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="isClosure || isMaterializing(entry)"
            :title="isClosure ? 'Jour fermé' : 'Créer le cours de la série à cette date (comme la génération automatique)'"
            data-action="materialize"
            @click.stop.prevent="emit('materialize', entry.placeholder)"
          >
            {{ isMaterializing(entry) ? '…' : 'Réactiver le cours prévu' }}
          </button>
          <button
            type="button"
            class="justify-center px-2 py-1.5 text-xs rounded-md transition-colors inline-flex items-center gap-1 bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="isClosure"
            :title="isClosure ? 'Jour fermé : création désactivée' : 'Créer un cours ponctuel sur cette place'"
            data-action="create-here"
            @click.stop.prevent="emit('create-here', entry.placeholder)"
          >
            Ajouter un cours ici
          </button>
          <button
            type="button"
            class="justify-center px-2 py-1.5 text-xs rounded-md transition-colors inline-flex items-center gap-1 bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
            title="Retirer cette occurrence ou terminer la série"
            :disabled="isReleasing(entry)"
            data-action="release"
            @click.stop.prevent="emit('release', entry.placeholder)"
          >
            {{ isReleasing(entry) ? '…' : 'Retirer' }}
          </button>
          <NuxtLink
            to="/club/recurring-slots"
            class="inline-flex items-center justify-center gap-1 px-2 py-1.5 text-xs rounded-md border border-violet-300 text-violet-700 bg-violet-50 hover:bg-violet-100 transition-colors"
            @click.stop
          >
            Liste récurrences
          </NuxtLink>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import {
  ANOMALY_LABELS,
  placeholderKey,
  type PlanningAnomaly,
  type PlanningAnomalyKind,
  type PlanningLessonLike,
} from '~/composables/planning/usePlanningPlaceholders'

export type AnomalyDrawerEntry = {
  placeholder: PlanningLessonLike
  anomalies: PlanningAnomaly[]
  studentsLabel?: string
  teacherName?: string
}

const props = withDefaults(defineProps<{
  open: boolean
  entries: AnomalyDrawerEntry[]
  isClosure?: boolean
  materializingId?: number | null
  releasingId?: number | null
}>(), {
  isClosure: false,
  materializingId: null,
  releasingId: null,
})

const emit = defineEmits<{
  (e: 'materialize', placeholder: PlanningLessonLike): void
  (e: 'create-here', placeholder: PlanningLessonLike): void
  (e: 'release', placeholder: PlanningLessonLike): void
}>()

function entryKey(entry: AnomalyDrawerEntry): string {
  return placeholderKey(entry.placeholder)
}

function primaryKind(entry: AnomalyDrawerEntry): PlanningAnomalyKind | undefined {
  return entry.anomalies[0]?.kind
}

function isMaterializing(entry: AnomalyDrawerEntry): boolean {
  return props.materializingId != null && props.materializingId === entry.placeholder.recurring_slot_id
}

function isReleasing(entry: AnomalyDrawerEntry): boolean {
  return props.releasingId != null && props.releasingId === entry.placeholder.recurring_slot_id
}

function hm(iso: string | null | undefined): string {
  if (!iso) return '--:--'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return String(iso).substring(11, 16) || '--:--'
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}

function timeRange(p: PlanningLessonLike): string {
  return `${hm(p.start_time)}–${hm(p.end_time)}`
}

function description(entry: AnomalyDrawerEntry): string {
  const main = entry.anomalies.find((a) => a.kind !== 'inconsistency') ?? entry.anomalies[0]
  if (!main) return ''
  switch (main.kind) {
    case 'generation_gap':
      return 'Occurrence attendue, aucun cours : trou de génération à vérifier.'
    case 'student_cancellation':
      return `${main.detail ?? "Annulé par l'élève"} — place récupérable pour un cours ponctuel${main.countsInSubscription ? ' ; séance comptée dans l’abonnement' : ''}.`
    case 'club_cancellation':
      return `${main.detail ?? 'Annulé par le club'} — la séance n’est pas déduite.`
    default:
      return main.detail ?? ''
  }
}

function badgeLabel(a: PlanningAnomaly): string {
  if (a.kind === 'inconsistency' && a.detail) return a.detail.replace(/_/g, ' ')
  return ANOMALY_LABELS[a.kind].split(' ')[0]
}

function badgeGlyph(kind: PlanningAnomalyKind): string {
  return kind === 'generation_gap' ? '⚠' : kind === 'student_cancellation' ? '🙋' : kind === 'club_cancellation' ? '🏛' : '⇄'
}

function badgeClass(kind: PlanningAnomalyKind): string {
  return kind === 'generation_gap'
    ? 'bg-amber-100 text-amber-900'
    : kind === 'student_cancellation'
      ? 'bg-sky-100 text-sky-900'
      : kind === 'club_cancellation'
        ? 'bg-gray-100 text-gray-700'
        : 'bg-violet-100 text-violet-900'
}

function rowClass(entry: AnomalyDrawerEntry): string {
  switch (primaryKind(entry)) {
    case 'generation_gap':
      return 'border-amber-300 bg-amber-50/60'
    case 'student_cancellation':
      return 'border-sky-300 bg-sky-50/60'
    case 'club_cancellation':
      return 'border-gray-200 bg-gray-50'
    default:
      return 'border-violet-300 bg-violet-50/60'
  }
}
</script>
