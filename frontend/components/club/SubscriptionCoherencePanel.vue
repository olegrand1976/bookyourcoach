<template>
  <!-- Fiche de cohérence cours ↔ abonnements d'un élève : état, proposition modifiable, application, annulation.
       Rédigée pour le responsable du club : chaque étape dit ce qu'elle montre et ce qui va se passer. -->
  <section class="mx-5 mt-5 rounded-lg border bg-white" :class="borderClass" data-testid="subscription-coherence">
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
      <div class="min-w-0">
        <p class="text-sm font-semibold text-gray-900">Vérification des abonnements</p>
        <p class="text-xs text-gray-500">Chaque cours de l'élève doit être décompté sur le bon abonnement.</p>
        <p v-if="loading" class="mt-1 text-sm text-gray-500">Vérification…</p>
        <p v-else-if="loadError" class="mt-1 text-sm text-red-700">{{ loadError }}</p>
        <p v-else-if="isClean" class="mt-1 text-sm text-green-700">
          ✓ Tout est en ordre : chaque cours est décompté sur le bon abonnement.
        </p>
        <p v-else-if="plan" class="mt-1 text-sm text-amber-800">
          <strong>À vérifier :</strong> {{ issueSummary }}
        </p>
        <p v-if="plan && plan.students.length > 1" class="text-xs text-gray-500 mt-0.5">
          Abonnement partagé entre : {{ plan.students.map((s) => s.name).join(', ') }}
        </p>
      </div>
      <button
        v-if="plan"
        type="button"
        class="px-3 py-1.5 text-sm font-medium rounded-lg border"
        :class="showSheet || isClean ? 'border-gray-300 text-gray-700 hover:bg-gray-50' : 'border-amber-500 text-amber-800 bg-amber-50 hover:bg-amber-100'"
        data-testid="coherence-open-sheet"
        @click="showSheet = !showSheet"
      >
        {{ showSheet ? 'Masquer le détail' : (isClean ? 'Voir le détail' : 'Voir le détail et corriger') }}
      </button>
    </div>

    <p v-if="message" class="px-4 pb-3 text-sm" :class="messageIsError ? 'text-red-700' : 'text-green-700'">{{ message }}</p>

    <div v-if="showSheet && plan" class="border-t border-gray-100 px-4 py-4 space-y-6 text-sm" data-testid="coherence-sheet">
      <!-- Mode d'emploi -->
      <details class="rounded-lg bg-blue-50 border border-blue-100 px-3 py-2 text-blue-900">
        <summary class="cursor-pointer font-medium">Comment fonctionne cette vérification ?</summary>
        <ul class="mt-2 ml-4 list-disc space-y-1 text-xs leading-relaxed">
          <li>Les abonnements se consomment <strong>l'un après l'autre</strong> : chaque cours est décompté sur le plus ancien abonnement qui a encore de la place et qui couvre ce type de cours.</li>
          <li>Un cours qui tombe un <strong>jour de fermeture</strong> du club n'est jamais décompté.</li>
          <li><strong>Séances ajoutées à la main</strong> : séances décomptées sans cours dans le planning (par exemple la reprise d'un ancien carnet). Si elles font doublon avec des cours enregistrés, la vérification propose de les remettre à zéro.</li>
          <li>Un cours <strong>sans abonnement</strong> n'est décompté nulle part : il faut ajouter ou renouveler un abonnement pour le couvrir.</li>
          <li><strong>Rien n'est modifié</strong> tant que vous n'avez pas cliqué sur « Appliquer ». Vous pouvez ajuster la proposition, et chaque correction appliquée reste annulable.</li>
        </ul>
      </details>

      <!-- Étape 1 : ce qui va changer -->
      <div>
        <p class="font-semibold text-gray-900 mb-2"><span class="step">1</span> Ce que la vérification propose</p>
        <ul v-if="!isClean" class="space-y-1.5" data-testid="coherence-summary">
          <li v-if="plan.moves.length" class="flex gap-2">
            <span aria-hidden="true">↔</span>
            <span><strong>{{ plan.moves.length }} cours à réaffecter</strong> : {{ plural(plan.moves.length, 'il sera décompté', 'ils seront décomptés') }} sur un autre abonnement, dans l'ordre de consommation.</span>
          </li>
          <li v-if="plan.closure_detach.length" class="flex gap-2">
            <span aria-hidden="true">🚫</span>
            <span><strong>{{ plan.closure_detach.length }} {{ plural(plan.closure_detach.length, 'cours tombe', 'cours tombent') }} un jour de fermeture</strong> ({{ plan.closure_detach.map((c) => fmt(c.date)).join(', ') }}) : {{ plural(plan.closure_detach.length, 'il ne sera plus décompté', 'ils ne seront plus décomptés') }}.</span>
          </li>
          <li v-if="plan.manual_changes" class="flex gap-2">
            <span aria-hidden="true">✎</span>
            <span><strong>{{ plan.manual_changes }} {{ plural(plan.manual_changes, 'compteur', 'compteurs') }} de séances ajoutées à la main à corriger</strong> (détail sur l'abonnement concerné ci-dessous).</span>
          </li>
          <li v-if="plan.uncovered.length" class="flex gap-2 text-red-700">
            <span aria-hidden="true">⚠</span>
            <span><strong>{{ plan.uncovered.length }} cours sans abonnement</strong> : les abonnements de l'élève sont pleins. Ajoutez ou renouvelez un abonnement pour {{ plural(plan.uncovered.length, 'le', 'les') }} décompter.</span>
          </li>
        </ul>
        <p v-else class="text-gray-600">Aucune correction nécessaire. Vous pouvez tout de même consulter ou ajuster la répartition ci-dessous.</p>
      </div>

      <!-- Étape 2 : abonnements -->
      <div>
        <p class="font-semibold text-gray-900"><span class="step">2</span> Les abonnements de l'élève</p>
        <p class="text-xs text-gray-500 mb-2">Nombre de séances décomptées sur chaque abonnement, aujourd'hui et après la correction.</p>
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
              <span class="text-xs px-2 py-0.5 rounded-full whitespace-nowrap" :class="statusClass(inst.status)">{{ statusLabel(inst.status) }}</span>
            </div>
            <p class="text-xs text-gray-600 mt-0.5">
              <template v-if="inst.expires_at">Valable du {{ fmt(inst.started_at) }} au {{ fmt(inst.expires_at) }}</template>
              <template v-else>Commencé le {{ fmt(inst.started_at) }}, sans date de fin</template>
            </p>

            <div class="mt-3">
              <div class="flex items-baseline justify-between text-xs text-gray-600">
                <span>Aujourd'hui : {{ inst.manual_before + inst.linked_before }} / {{ capLabel(inst) }}</span>
                <span :class="capacityHintClass(inst)">{{ capacityHint(inst) }}</span>
              </div>
              <div v-if="inst.capacity > 0" class="mt-1 h-2 rounded-full bg-gray-200 overflow-hidden" aria-hidden="true">
                <div class="h-full rounded-full" :class="overCapacity(inst) ? 'bg-red-500' : 'bg-emerald-500'" :style="{ width: `${fillPercent(inst)}%` }" />
              </div>
              <p class="mt-1 text-base font-semibold tabular-nums" :class="overCapacity(inst) ? 'text-red-700' : 'text-gray-900'" data-testid="coherence-instance-total">
                Après correction : {{ totalAfter(inst) }} / {{ capLabel(inst) }}
              </p>
              <p class="text-xs text-gray-500">
                dont {{ assignedCount(inst.id) }} {{ plural(assignedCount(inst.id), 'cours du planning', 'cours du planning') }}
                et {{ normalizedManual(inst.id) }} {{ plural(normalizedManual(inst.id), 'séance ajoutée', 'séances ajoutées') }} à la main
              </p>
            </div>

            <label class="mt-2 flex items-center justify-between gap-2 text-xs text-gray-600">
              <span title="Séances décomptées sans cours dans le planning (ex. reprise d'un ancien carnet)">Séances ajoutées à la main</span>
              <input
                v-model.number="manualEdits[inst.id]"
                type="number"
                min="0"
                max="1000"
                class="w-16 rounded border border-gray-300 px-2 py-1 text-right text-sm"
                :data-testid="`coherence-manual-${inst.id}`"
              >
            </label>
            <p v-if="inst.manual_reason" class="mt-1 text-xs text-amber-800">
              Proposition : passer de {{ inst.manual_before }} à {{ inst.manual_after }}, {{ manualReasonLabel(inst.manual_reason) }}.
            </p>
          </div>
        </div>
      </div>

      <!-- Étape 3 : répartition des cours -->
      <div>
        <div class="flex flex-wrap items-end justify-between gap-2">
          <div>
            <p class="font-semibold text-gray-900"><span class="step">3</span> Les cours ({{ plan.lessons.length }})</p>
            <p class="text-xs text-gray-500">Pour chaque cours, l'abonnement sur lequel il est décompté. Vous pouvez choisir un autre abonnement dans la liste.</p>
          </div>
          <label class="flex items-center gap-2 text-xs text-gray-700">
            <input v-model="onlyChanges" type="checkbox" class="rounded border-gray-300">
            Seulement les cours qui changent
          </label>
        </div>
        <div class="mt-2 mb-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-600">
          <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded bg-amber-100 border border-amber-300" />change d'abonnement</span>
          <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded bg-red-100 border border-red-300" />aucun abonnement (non décompté)</span>
          <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded bg-white border border-gray-300" />inchangé</span>
          <span v-if="uncoveredCount" class="text-red-700 font-medium">{{ uncoveredCount }} {{ plural(uncoveredCount, 'cours', 'cours') }} sans abonnement après correction</span>
        </div>
        <div class="overflow-x-auto rounded-lg border border-gray-200">
          <table class="w-full text-left">
            <thead class="bg-gray-50 text-xs text-gray-600">
              <tr>
                <th class="px-3 py-2 font-medium">Cours</th>
                <th class="px-3 py-2 font-medium">Décompté aujourd'hui sur</th>
                <th class="px-3 py-2 font-medium">Sera décompté sur</th>
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
                    :aria-label="`Abonnement pour le cours du ${fmt(row.date)}`"
                    :data-testid="`coherence-select-${row.lesson_id}`"
                  >
                    <option v-for="id in row.eligible" :key="id" :value="id">{{ shortLabel(id) }}</option>
                    <option :value="null">Aucun abonnement (non décompté)</option>
                  </select>
                  <span v-if="assignmentEdits[row.lesson_id] !== row.proposed" class="ml-2 text-xs text-blue-700">
                    modifié par vous (proposé : {{ shortLabel(row.proposed) }})
                  </span>
                </td>
              </tr>
              <tr v-if="!visibleLessons.length">
                <td colspan="3" class="px-3 py-4 text-center text-gray-500">
                  Aucun cours ne change. Décochez « Seulement les cours qui changent » pour voir tous les cours.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Étape 4 : appliquer -->
      <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
        <p class="font-semibold text-gray-900"><span class="step">4</span> Appliquer</p>
        <p v-if="!canApply" class="mt-1 text-gray-600">Rien à appliquer.</p>
        <template v-else>
          <p class="mt-1 text-xs text-gray-600">
            Les cours seront décomptés comme indiqué à l'étape 3 et les compteurs des abonnements recalculés.
            Vous pourrez annuler cette correction ensuite.
          </p>
          <p v-if="confirming" class="mt-2 text-sm text-amber-900 font-medium">
            Confirmez-vous la correction<template v-if="editCount"> avec vos {{ editCount }} {{ plural(editCount, 'modification', 'modifications') }}</template> ?
          </p>
          <div class="mt-2 flex flex-wrap items-center gap-2">
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
                {{ busy ? 'Application…' : 'Oui, appliquer' }}
              </button>
              <button type="button" class="px-2 py-1.5 text-sm text-gray-600 hover:underline" :disabled="busy" @click="confirming = false">
                Non, revenir
              </button>
            </template>
            <button
              v-if="editCount"
              type="button"
              class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
              :disabled="busy"
              data-testid="coherence-reset"
              @click="resetEdits"
            >
              Effacer mes modifications
            </button>
          </div>
        </template>
      </div>
    </div>

    <!-- Historique des corrections, annulables -->
    <div v-if="history.length" class="border-t border-gray-100 px-4 py-3 text-sm">
      <p class="text-xs font-semibold uppercase text-gray-500">Corrections déjà appliquées</p>
      <p class="text-xs text-gray-500 mb-1">Seule la plus récente peut être annulée : les cours et compteurs reviennent alors à leur état d'avant.</p>
      <ul class="space-y-1">
        <li v-for="(h, i) in history" :key="h.id" class="flex flex-wrap items-center gap-2 text-gray-700">
          <span>Le {{ fmtDateTime(h.created_at) }}<template v-if="h.applied_by"> par {{ h.applied_by }}</template> :
            {{ historySummary(h) }}</span>
          <span v-if="h.reverted_at" class="text-xs text-gray-500">(annulée le {{ fmtDateTime(h.reverted_at) }})</span>
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
const onlyChanges = ref(true)
const message = ref('')
const messageIsError = ref(false)
/** Carnet retenu par cours (initialisé sur la proposition) et compteurs manuels par carnet. */
const assignmentEdits = reactive<Record<number, number | null>>({})
const manualEdits = reactive<Record<number, number>>({})

const borderClass = computed(() => {
  if (!plan.value) return 'border-gray-200'
  return plan.value.has_changes || plan.value.uncovered.length ? 'border-amber-300' : 'border-green-200'
})
const isClean = computed(() => Boolean(plan.value && !plan.value.has_changes && !plan.value.uncovered.length))
/** Résumé d'une ligne pour l'en-tête (le détail est à l'étape 1). */
const issueSummary = computed(() => {
  const p = plan.value
  if (!p) return ''
  const parts: string[] = []
  if (p.moves.length) parts.push(`${p.moves.length} cours à réaffecter`)
  if (p.closure_detach.length) parts.push(`${p.closure_detach.length} cours un jour de fermeture`)
  if (p.manual_changes) parts.push(`${p.manual_changes} ${plural(p.manual_changes, 'compteur', 'compteurs')} à corriger`)
  if (p.uncovered.length) parts.push(`${p.uncovered.length} cours sans abonnement`)
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
/** Un cours « change » s'il est proposé ailleurs, modifié à la main ou sans abonnement : la liste ne bouge pas pendant la saisie. */
const visibleLessons = computed(() => {
  const rows = plan.value?.lessons ?? []
  if (!onlyChanges.value) return rows
  return rows.filter((row) => row.proposed !== row.current
    || assignmentEdits[row.lesson_id] !== row.proposed
    || assignmentEdits[row.lesson_id] == null)
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
function fillPercent(inst: PlanInstance): number {
  return inst.capacity > 0 ? Math.min(100, Math.round((totalAfter(inst) / inst.capacity) * 100)) : 0
}
function capacityHint(inst: PlanInstance): string {
  if (inst.capacity <= 0) return 'sans limite'
  const free = inst.capacity - totalAfter(inst)
  if (free < 0) return `dépassé de ${-free}`
  if (free === 0) return 'complet'
  return `${free} ${plural(free, 'place libre', 'places libres')}`
}
function capacityHintClass(inst: PlanInstance): string {
  if (overCapacity(inst)) return 'font-semibold text-red-700'
  return inst.capacity > 0 && totalAfter(inst) === inst.capacity ? 'text-gray-700' : 'text-emerald-700'
}
function plural(n: number, one: string, many: string): string {
  return n > 1 ? many : one
}
function historySummary(h: HistoryRow): string {
  const moved = Number(h.summary?.moved ?? 0)
  const closure = Number(h.summary?.closure_detached ?? 0)
  const parts = [`${moved} ${plural(moved, 'cours réaffecté', 'cours réaffectés')}`]
  if (closure) parts.push(`${closure} ${plural(closure, 'cours de fermeture détaché', 'cours de fermeture détachés')}`)
  if (h.summary?.customized) parts.push('proposition ajustée à la main')
  return parts.join(', ')
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
  return inst.name ? `${inst.name} du ${fmt(inst.started_at)}` : `Abonnement du ${fmt(inst.started_at)}`
}
function shortLabel(id: number | null): string {
  if (id == null) return 'Aucun abonnement'
  const inst = plan.value?.instances.find((i) => i.id === id)
  return inst ? instanceLabel(inst) : `Abonnement #${id}`
}
function statusLabel(status?: string | null): string {
  return { active: 'Actif', completed: 'Terminé', expired: 'Expiré', cancelled: 'Annulé' }[status ?? ''] ?? (status ?? '')
}
function statusClass(status?: string | null): string {
  return { active: 'bg-green-100 text-green-800', expired: 'bg-red-100 text-red-800' }[status ?? ''] ?? 'bg-gray-100 text-gray-700'
}
function manualReasonLabel(reason: string): string {
  return reason === 'doublon'
    ? 'car ces séances font doublon avec des cours déjà enregistrés dans le planning'
    : 'car ce nombre venait d’une correction manuelle du compteur'
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

<style scoped>
.step {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.25rem;
  height: 1.25rem;
  margin-right: 0.375rem;
  border-radius: 9999px;
  background: #1f2937;
  color: #fff;
  font-size: 0.7rem;
}
</style>
