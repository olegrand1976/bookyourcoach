<template>
  <!-- Cohérence cours ↔ abonnements d'un élève : simulation, correction, annulation -->
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
      <div class="flex items-center gap-2">
        <button
          v-if="plan && (plan.has_changes || plan.uncovered.length)"
          type="button"
          class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
          @click="showDetails = !showDetails"
        >
          {{ showDetails ? 'Masquer le détail' : 'Voir le détail' }}
        </button>
        <template v-if="plan?.has_changes">
          <button
            v-if="!confirming"
            type="button"
            class="px-3 py-1.5 text-sm font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700 disabled:opacity-50"
            :disabled="busy"
            data-testid="coherence-apply"
            @click="confirming = true"
          >
            Remettre en cohérence
          </button>
          <template v-else>
            <button
              type="button"
              class="px-3 py-1.5 text-sm font-medium rounded-lg bg-amber-700 text-white hover:bg-amber-800 disabled:opacity-50"
              :disabled="busy"
              data-testid="coherence-confirm"
              @click="apply"
            >
              {{ busy ? 'Correction…' : 'Confirmer la correction' }}
            </button>
            <button type="button" class="px-2 py-1.5 text-sm text-gray-600 hover:underline" :disabled="busy" @click="confirming = false">
              Annuler
            </button>
          </template>
        </template>
      </div>
    </div>

    <p v-if="message" class="px-4 pb-3 text-sm" :class="messageIsError ? 'text-red-700' : 'text-green-700'">{{ message }}</p>

    <!-- Détail : avant / après par abonnement et cours déplacés -->
    <div v-if="showDetails && plan" class="border-t border-gray-100 px-4 py-3 space-y-3 text-sm">
      <table class="w-full text-left">
        <thead class="text-xs uppercase text-gray-500">
          <tr>
            <th class="py-1 pr-2 font-medium">Abonnement</th>
            <th class="py-1 pr-2 font-medium">Période</th>
            <th class="py-1 pr-2 font-medium text-right">Cours avant</th>
            <th class="py-1 pr-2 font-medium text-right">Cours après</th>
            <th class="py-1 font-medium">Compteur manuel</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="inst in plan.instances" :key="inst.id" class="border-t border-gray-100">
            <td class="py-1 pr-2">
              <NuxtLink :to="subscriptionInstanceLink(inst.id)" class="text-blue-700 hover:underline">{{ instanceLabel(inst) }}</NuxtLink>
            </td>
            <td class="py-1 pr-2 text-gray-600">{{ fmt(inst.started_at) }} → {{ fmt(inst.expires_at) }}</td>
            <td class="py-1 pr-2 text-right tabular-nums">{{ inst.manual_before + inst.linked_before }} / {{ inst.capacity || '∞' }}</td>
            <td class="py-1 pr-2 text-right tabular-nums font-medium">{{ inst.manual_after + inst.linked_after }} / {{ inst.capacity || '∞' }}</td>
            <td class="py-1 text-gray-600">
              <template v-if="inst.manual_reason">{{ inst.manual_before }} → {{ inst.manual_after }} ({{ manualReasonLabel(inst.manual_reason) }})</template>
              <template v-else-if="inst.manual_before">{{ inst.manual_before }} (conservé)</template>
              <template v-else>—</template>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="plan.moves.length">
        <p class="text-xs font-semibold uppercase text-gray-500 mb-1">Cours réaffectés ({{ plan.moves.length }})</p>
        <ul class="space-y-0.5 text-gray-700">
          <li v-for="m in plan.moves" :key="`m-${m.lesson_id}`">
            {{ fmt(m.date) }} : {{ shortLabel(m.from) }} → <strong>{{ shortLabel(m.to) }}</strong>
          </li>
        </ul>
      </div>
      <p v-if="plan.closure_detach.length" class="text-gray-700">
        {{ plan.closure_detach.length }} cours de jours de fermeture détaché(s) de leur abonnement
        ({{ plan.closure_detach.map((c) => fmt(c.date)).join(', ') }}).
      </p>
    </div>

    <!-- Historique des corrections, annulables -->
    <div v-if="history.length" class="border-t border-gray-100 px-4 py-3 text-sm">
      <p class="text-xs font-semibold uppercase text-gray-500 mb-1">Corrections appliquées</p>
      <ul class="space-y-1">
        <li v-for="(h, i) in history" :key="h.id" class="flex flex-wrap items-center gap-2 text-gray-700">
          <span>{{ fmtDateTime(h.created_at) }}<template v-if="h.applied_by"> par {{ h.applied_by }}</template> —
            {{ h.summary?.moved ?? 0 }} réaffecté(s), {{ h.summary?.closure_detached ?? 0 }} fermeture(s)</span>
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
import { computed, ref, watch } from 'vue'
import { subscriptionInstanceLink } from '~/utils/subscriptionLink'

interface PlanInstance {
  id: number
  number?: string | null
  name?: string | null
  started_at: string | null
  expires_at: string | null
  capacity: number
  manual_before: number
  manual_after: number
  manual_reason: 'doublon' | 'correction_manuelle' | null
  linked_before: number
  linked_after: number
}
interface Plan {
  students: { id: number; name: string }[]
  instances: PlanInstance[]
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
  summary?: Record<string, number>
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
const showDetails = ref(false)
const message = ref('')
const messageIsError = ref(false)

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

async function load() {
  if (!props.studentId) return
  loading.value = true
  loadError.value = ''
  try {
    const { $api } = useNuxtApp()
    const res = await $api.get(`/club/subscriptions/rebalance/students/${props.studentId}`)
    plan.value = res.data?.data?.plan ?? null
    history.value = res.data?.data?.history ?? []
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
    const res = await $api.post(`/club/subscriptions/rebalance/students/${props.studentId}/apply`)
    messageIsError.value = false
    message.value = res.data?.message || 'Abonnements remis en cohérence.'
    confirming.value = false
    await load()
    emit('changed')
  } catch (e: any) {
    messageIsError.value = true
    message.value = e?.response?.data?.message || 'La correction a échoué.'
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
  return inst.number || inst.name || `Abonnement #${inst.id}`
}
function shortLabel(id: number | null): string {
  if (id == null) return 'aucun abonnement'
  const inst = plan.value?.instances.find((i) => i.id === id)
  return inst ? `abonnement du ${fmt(inst.started_at)}` : `abonnement #${id}`
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
  void load()
}, { immediate: true })

defineExpose({ reload: load })
</script>
