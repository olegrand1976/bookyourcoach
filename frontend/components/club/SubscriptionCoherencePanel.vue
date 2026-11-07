<template>
  <!-- Fiche de cohérence cours ↔ abonnements d'un élève : état, proposition modifiable, application, annulation -->
  <section class="mx-5 mt-5 rounded-lg border bg-white" :class="borderClass" data-testid="subscription-coherence">
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
      <div class="min-w-0">
        <p class="text-sm font-semibold text-gray-900">Cohérence des abonnements</p>
        <p v-if="loading" class="text-sm text-gray-500">Vérification…</p>
        <p v-else-if="loadError" class="text-sm text-red-700">{{ loadError }}</p>
        <p v-else-if="plan && !plan.has_changes && !plan.uncovered.length" class="text-sm text-green-700">
          ✓ Les cours sont correctement répartis sur les abonnements.
        </p>
        <p v-else-if="plan" class="text-sm text-amber-800">
          <template v-if="plan.has_changes">{{ changesLabel }}</template>
          <template v-if="plan.uncovered.length">
            <span v-if="plan.has_changes"> · </span>
            <strong>{{ plan.uncovered.length }} cours sans abonnement</strong> (capacité dépassée : un nouvel abonnement est nécessaire)
          </template>
        </p>
        <p v-if="plan && plan.students.length > 1" class="text-xs text-gray-500 mt-0.5">
          Abonnement partagé : {{ plan.students.map((s) => s.name).join(', ') }}
        </p>
      </div>
      <button
        v-if="plan"
        type="button"
        class="px-3 py-1.5 text-sm font-medium rounded-lg border"
        :class="showSheet ? 'border-gray-300 text-gray-700 hover:bg-gray-50' : 'border-amber-500 text-amber-800 bg-amber-50 hover:bg-amber-100'"
        data-testid="coherence-open-sheet"
        @click="showSheet = !showSheet"
      >
        {{ showSheet ? 'Fermer la fiche' : 'Ouvrir la fiche de cohérence' }}
      </button>
    </div>

    <p v-if="message" class="px-4 pb-3 text-sm" :class="messageIsError ? 'text-red-700' : 'text-green-700'">{{ message }}</p>

    <!-- Fiche : abonnements (compteurs recalculés en direct) + répartition des cours modifiable -->
    <div v-if="showSheet && plan" class="border-t border-gray-100 px-4 py-4 space-y-5 text-sm" data-testid="coherence-sheet">
      <div>
        <p class="text-xs font-semibold uppercase text-gray-500 mb-2">Abonnements</p>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
          <div
            v-for="inst in plan.instances"
            :key="inst.id"
            class="rounded-lg border p-3"
            :class="overCapacity(inst) ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-gray-50'"
            :data-testid="`coherence-instance-${inst.id}`"
          >
            <div class="flex items-start justify-between gap-2">
              <NuxtLink :to="subscriptionInstanceLink(inst.id)" class="font-medium text-blue-700 hover:underline" :title="inst.number || undefined">
                {{ instanceLabel(inst) }}
              </NuxtLink>
              <span class="text-xs text-gray-500 whitespace-nowrap">{{ statusLabel(inst.status) }}</span>
            </div>
            <p class="text-xs text-gray-600 mt-0.5">{{ fmt(inst.started_at) }} → {{ fmt(inst.expires_at) }}</p>
            <div class="mt-2 flex items-end justify-between gap-2">
              <div>
                <p class="text-xs text-gray-500">Avant : {{ inst.manual_before + inst.linked_before }} / {{ capLabel(inst) }}</p>
                <p class="text-base font-semibold tabular-nums" :class="overCapacity(inst) ? 'text-red-700' : 'text-gray-900'" data-testid="coherence-instance-total">
                  Après : {{ totalAfter(inst) }} / {{ capLabel(inst) }}
                </p>
              </div>
              <label class="text-xs text-gray-600 text-right">
                Cours manuels
                <input
                  v-model.number="manualEdits[inst.id]"
                  type="number"
                  min="0"
                  max="1000"
                  class="mt-0.5 w-16 rounded border border-gray-300 px-2 py-1 text-right text-sm"
                  :data-testid="`coherence-manual-${inst.id}`"
                >
              </label>
            </div>
            <p v-if="inst.manual_reason" class="mt-1 text-xs text-amber-800">
              Manuel proposé : {{ inst.manual_before }} → {{ inst.manual_after }} ({{ manualReasonLabel(inst.manual_reason) }})
            </p>
          </div>
        </div>
      </div>

      <div>
        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
          <p class="text-xs font-semibold uppercase text-gray-500">
            Répartition des cours ({{ plan.lessons.length }}) · {{ uncoveredCount }} sans abonnement
          </p>
          <label class="flex items-center gap-2 text-xs text-gray-700">
            <input v-model="onlyChanges" type="checkbox" class="rounded border-gray-300">
            Seulement les cours qui changent
          </label>
        </div>
        <div class="overflow-x-auto rounded-lg border border-gray-200">
          <table class="w-full text-left">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
              <tr>
                <th class="px-3 py-2 font-medium">Date</th>
                <th class="px-3 py-2 font-medium">Actuellement</th>
                <th class="px-3 py-2 font-medium">Abonnement retenu</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in visibleLessons"
                :key="row.lesson_id"
                class="border-t border-gray-100"
                :class="rowClass(row)"
                :data-testid="`coherence-lesson-${row.lesson_id}`"
              >
                <td class="px-3 py-1.5 whitespace-nowrap">
                  {{ fmt(row.date) }} <span class="text-gray-500">{{ row.time }}</span>
                  <span v-if="!row.past" class="ml-1 text-xs text-purple-700">à venir</span>
                </td>
                <td class="px-3 py-1.5 text-gray-600">{{ shortLabel(row.current) }}</td>
                <td class="px-3 py-1.5">
                  <select
                    v-model="assignmentEdits[row.lesson_id]"
                    class="rounded border border-gray-300 bg-white px-2 py-1 text-sm"
                    :data-testid="`coherence-select-${row.lesson_id}`"
                  >
                    <option v-for="id in row.eligible" :key="id" :value="id">{{ shortLabel(id) }}</option>
                    <option :value="null">Aucun abonnement</option>
                  </select>
                  <span v-if="assignmentEdits[row.lesson_id] !== row.proposed" class="ml-2 text-xs text-blue-700">
                    modifié (proposé : {{ shortLabel(row.proposed) }})
                  </span>
                </td>
              </tr>
              <tr v-if="!visibleLessons.length">
                <td colspan="3" class="px-3 py-4 text-center text-gray-500">Aucun cours ne change.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="plan.closure_detach.length" class="mt-2 text-xs text-gray-600">
          {{ plan.closure_detach.length }} cours de jours de fermeture seront détachés de leur abonnement
          ({{ plan.closure_detach.map((c) => fmt(c.date)).join(', ') }}).
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <template v-if="canApply">
          <button
            v-if="!confirming"
            type="button"
            class="px-3 py-1.5 text-sm font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700 disabled:opacity-50"
            :disabled="busy"
            data-testid="coherence-apply"
            @click="confirming = true"
          >
            {{ editCount ? `Appliquer avec mes modifications (${editCount})` : 'Appliquer la proposition' }}
          </button>
          <template v-else>
            <button
              type="button"
              class="px-3 py-1.5 text-sm font-medium rounded-lg bg-amber-700 text-white hover:bg-amber-800 disabled:opacity-50"
              :disabled="busy"
              data-testid="coherence-confirm"
              @click="apply"
            >
              {{ busy ? 'Application…' : 'Confirmer' }}
            </button>
            <button type="button" class="px-2 py-1.5 text-sm text-gray-600 hover:underline" :disabled="busy" @click="confirming = false">
              Annuler
            </button>
          </template>
        </template>
        <button
          v-if="editCount"
          type="button"
          class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
          :disabled="busy"
          data-testid="coherence-reset"
          @click="resetEdits"
        >
          Revenir à la proposition
        </button>
        <span class="text-xs text-gray-500">Chaque application reste annulable ci-dessous.</span>
      </div>
    </div>

    <!-- Historique des corrections, annulables -->
    <div v-if="history.length" class="border-t border-gray-100 px-4 py-3 text-sm">
      <p class="text-xs font-semibold uppercase text-gray-500 mb-1">Corrections appliquées</p>
      <ul class="space-y-1">
        <li v-for="(h, i) in history" :key="h.id" class="flex flex-wrap items-center gap-2 text-gray-700">
          <span>{{ fmtDateTime(h.created_at) }}<template v-if="h.applied_by"> par {{ h.applied_by }}</template> —
            {{ h.summary?.moved ?? 0 }} réaffecté(s), {{ h.summary?.closure_detached ?? 0 }} fermeture(s)<template v-if="h.summary?.customized">, ajustée</template></span>
          <span v-if="h.reverted_at" class="text-xs text-gray-500">annulée le {{ fmtDateTime(h.reverted_at) }}</span>
          <button
            v-else-if="i === firstActiveIndex"
            type="button"
            class="text-xs font-medium text-red-700 hover:underline disabled:opacity-50"
            :disabled="busy"
            data-testid="coherence-revert"
            @click="revert(h.id)"
          >
            Annuler cette correction
          </button>
        </li>
      </ul>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { subscriptionInstanceLink } from '~/utils/subscriptionLink'

interface PlanInstance {
  id: number
  number?: string | null
  name?: string | null
  status?: string | null
  started_at: string | null
  expires_at: string | null
  capacity: number
  manual_before: number
  manual_after: number
  manual_reason: 'doublon' | 'correction_manuelle' | null
  had_reset?: boolean
  linked_before: number
  linked_after: number
}
interface PlanLesson {
  lesson_id: number
  date: string
  time: string
  status: string
  past: boolean
  current: number | null
  proposed: number | null
  eligible: number[]
}
interface Plan {
  students: { id: number; name: string }[]
  instances: PlanInstance[]
  lessons: PlanLesson[]
  moves: { lesson_id: number; date: string; from: number | null; to: number | null }[]
  closure_detach: { lesson_id: number; date: string; from: number }[]
  uncovered: { lesson_id: number; date: string }[]
  manual_changes: number
  has_changes: boolean
}
interface HistoryRow {
  id: number
  created_at: string
  applied_by?: string | null
  summary?: Record<string, number | boolean>
  reverted_at?: string | null
}

const props = defineProps<{ studentId: number | string }>()
const emit = defineEmits<{ changed: [] }>()

const plan = ref<Plan | null>(null)
const history = ref<HistoryRow[]>([])
const loading = ref(false)
const loadError = ref('')
const busy = ref(false)
const confirming = ref(false)
const showSheet = ref(false)
const onlyChanges = ref(false)
const message = ref('')
const messageIsError = ref(false)
/** Carnet retenu par cours (initialisé sur la proposition) et compteurs manuels par carnet. */
const assignmentEdits = reactive<Record<number, number | null>>({})
const manualEdits = reactive<Record<number, number>>({})

const borderClass = computed(() => {
  if (!plan.value) return 'border-gray-200'
  return plan.value.has_changes || plan.value.uncovered.length ? 'border-amber-300' : 'border-green-200'
})
const changesLabel = computed(() => {
  const p = plan.value
  if (!p) return ''
  const parts: string[] = []
  if (p.moves.length) parts.push(`${p.moves.length} cours à réaffecter`)
  if (p.closure_detach.length) parts.push(`${p.closure_detach.length} cours de fermeture encore rattaché(s)`)
  if (p.manual_changes) parts.push(`${p.manual_changes} compteur(s) manuel(s) à corriger`)
  return parts.join(' · ')
})
const firstActiveIndex = computed(() => history.value.findIndex((h) => !h.reverted_at))

const assignmentOverrides = computed(() =>
  (plan.value?.lessons ?? [])
    .filter((row) => assignmentEdits[row.lesson_id] !== row.proposed)
    .map((row) => ({ lesson_id: row.lesson_id, instance_id: assignmentEdits[row.lesson_id] ?? null })),
)
const manualOverrides = computed(() =>
  (plan.value?.instances ?? [])
    .filter((inst) => normalizedManual(inst.id) !== inst.manual_after)
    .map((inst) => ({ instance_id: inst.id, value: normalizedManual(inst.id) })),
)
const editCount = computed(() => assignmentOverrides.value.length + manualOverrides.value.length)
const canApply = computed(() => Boolean(plan.value && (plan.value.has_changes || editCount.value > 0)))
const uncoveredCount = computed(() => (plan.value?.lessons ?? []).filter((row) => assignmentEdits[row.lesson_id] == null).length)
const visibleLessons = computed(() => {
  const rows = plan.value?.lessons ?? []
  if (!onlyChanges.value) return rows
  return rows.filter((row) => assignmentEdits[row.lesson_id] !== row.current)
})

function normalizedManual(id: number): number {
  const v = Number(manualEdits[id])
  return Number.isFinite(v) && v >= 0 ? Math.floor(v) : 0
}
function assignedCount(id: number): number {
  return (plan.value?.lessons ?? []).filter((row) => assignmentEdits[row.lesson_id] === id).length
}
function totalAfter(inst: PlanInstance): number {
  return normalizedManual(inst.id) + assignedCount(inst.id)
}
function overCapacity(inst: PlanInstance): boolean {
  return inst.capacity > 0 && totalAfter(inst) > inst.capacity
}
function capLabel(inst: PlanInstance): string {
  return inst.capacity > 0 ? String(inst.capacity) : '∞'
}
function rowClass(row: PlanLesson): string {
  const chosen = assignmentEdits[row.lesson_id]
  if (chosen == null) return 'bg-red-50'
  if (chosen !== row.current) return 'bg-amber-50'
  return ''
}

function resetEdits() {
  for (const key of Object.keys(assignmentEdits)) delete assignmentEdits[Number(key)]
  for (const key of Object.keys(manualEdits)) delete manualEdits[Number(key)]
  for (const row of plan.value?.lessons ?? []) assignmentEdits[row.lesson_id] = row.proposed
  for (const inst of plan.value?.instances ?? []) manualEdits[inst.id] = inst.manual_after
  confirming.value = false
}

async function load() {
  if (!props.studentId) return
  loading.value = true
  loadError.value = ''
  try {
    const { $api } = useNuxtApp()
    const res = await $api.get(`/club/subscriptions/rebalance/students/${props.studentId}`)
    plan.value = res.data?.data?.plan ?? null
    history.value = res.data?.data?.history ?? []
    if (plan.value && !Array.isArray(plan.value.lessons)) plan.value.lessons = []
    resetEdits()
  } catch (e: any) {
    loadError.value = e?.response?.data?.message || 'Vérification impossible.'
  } finally {
    loading.value = false
  }
}

async function apply() {
  busy.value = true
  message.value = ''
  try {
    const { $api } = useNuxtApp()
    const payload: Record<string, unknown> = {}
    if (assignmentOverrides.value.length) payload.assignments = assignmentOverrides.value
    if (manualOverrides.value.length) payload.manual = manualOverrides.value
    const res = await $api.post(`/club/subscriptions/rebalance/students/${props.studentId}/apply`, payload)
    messageIsError.value = false
    message.value = res.data?.message || 'Abonnements remis en cohérence.'
    confirming.value = false
    await load()
    emit('changed')
  } catch (e: any) {
    messageIsError.value = true
    message.value = e?.response?.data?.message || 'L’application a échoué.'
  } finally {
    busy.value = false
  }
}

async function revert(id: number) {
  busy.value = true
  message.value = ''
  try {
    const { $api } = useNuxtApp()
    const res = await $api.post(`/club/subscriptions/rebalance/${id}/revert`)
    messageIsError.value = false
    message.value = res.data?.message || 'Correction annulée.'
    await load()
    emit('changed')
  } catch (e: any) {
    messageIsError.value = true
    message.value = e?.response?.data?.message || 'L’annulation a échoué.'
  } finally {
    busy.value = false
  }
}

function instanceLabel(inst: PlanInstance): string {
  return `Abonnement du ${fmt(inst.started_at)}`
}
function shortLabel(id: number | null): string {
  if (id == null) return 'Aucun abonnement'
  const inst = plan.value?.instances.find((i) => i.id === id)
  return inst ? instanceLabel(inst) : `Abonnement #${id}`
}
function statusLabel(status?: string | null): string {
  return { active: 'Actif', completed: 'Terminé', expired: 'Expiré', cancelled: 'Annulé' }[status ?? ''] ?? (status ?? '')
}
function manualReasonLabel(reason: string): string {
  return reason === 'doublon' ? 'doublon avec les cours enregistrés' : 'issu d’une correction manuelle'
}
function fmt(ymd: string | null): string {
  if (!ymd) return '—'
  const [y, m, d] = ymd.slice(0, 10).split('-')
  return `${d}/${m}/${y}`
}
function fmtDateTime(iso: string): string {
  return new Date(iso).toLocaleString('fr-BE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

watch(() => props.studentId, () => {
  plan.value = null
  history.value = []
  message.value = ''
  confirming.value = false
  showSheet.value = false
  void load()
}, { immediate: true })

defineExpose({ reload: load })
</script>
