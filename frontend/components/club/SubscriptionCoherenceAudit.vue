<template>
  <!-- Contrôle du club : élèves dont les cours ne sont pas correctement répartis sur leurs abonnements -->
  <section class="bg-white rounded-lg shadow-sm p-4 mb-6" data-testid="subscription-coherence-audit">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-base font-semibold text-gray-900">Cohérence cours ↔ abonnements</h2>
        <p class="text-sm text-gray-600">
          Les abonnements se consomment l'un après l'autre. Le contrôle repère les cours mal rattachés, les jours de fermeture
          encore décomptés, les compteurs manuels en doublon et les cours sans abonnement.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 text-gray-800 hover:bg-gray-50 disabled:opacity-50"
          :disabled="loading || bulkRunning"
          data-testid="coherence-audit-run"
          @click="runAudit"
        >
          {{ loading ? 'Contrôle en cours…' : (rows ? 'Relancer le contrôle' : 'Contrôler les élèves') }}
        </button>
        <template v-if="pendingRows.length">
          <button
            v-if="!confirmAll"
            type="button"
            class="px-3 py-2 text-sm font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700 disabled:opacity-50"
            :disabled="bulkRunning"
            @click="confirmAll = true"
          >
            Tout corriger ({{ pendingRows.length }})
          </button>
          <template v-else>
            <button
              type="button"
              class="px-3 py-2 text-sm font-medium rounded-lg bg-amber-700 text-white hover:bg-amber-800 disabled:opacity-50"
              :disabled="bulkRunning"
              @click="applyAll"
            >
              {{ bulkRunning ? `Correction ${bulkDone}/${bulkTotal}…` : 'Confirmer : tout corriger' }}
            </button>
            <button type="button" class="text-sm text-gray-600 hover:underline" :disabled="bulkRunning" @click="confirmAll = false">Annuler</button>
          </template>
        </template>
      </div>
    </div>

    <p v-if="error" class="mt-3 text-sm text-red-700">{{ error }}</p>

    <div v-if="rows" class="mt-4">
      <p v-if="!rows.length" class="text-sm text-green-700">✓ Tous les élèves sont cohérents.</p>
      <template v-else>
        <p class="text-sm text-gray-700 mb-2">
          {{ rows.length }} élève(s) ou famille(s) concerné(s). Chaque correction reste annulable depuis la fiche de l'élève
          (onglet Abonnements).
        </p>
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="text-xs uppercase text-gray-500 border-b border-gray-200">
              <tr>
                <th class="py-2 pr-3 font-medium">Élève(s)</th>
                <th class="py-2 pr-3 font-medium text-right">À réaffecter</th>
                <th class="py-2 pr-3 font-medium text-right">Fermetures</th>
                <th class="py-2 pr-3 font-medium text-right">Manuels</th>
                <th class="py-2 pr-3 font-medium text-right">Sans abonnement</th>
                <th class="py-2 font-medium text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row.student_ids.join('-')" class="border-b border-gray-100">
                <td class="py-2 pr-3">
                  <span v-for="(s, i) in row.students" :key="s.id">
                    <NuxtLink
                      :to="{ path: '/club/students', query: { student: String(s.id), tab: 'abonnements' } }"
                      class="text-blue-700 hover:underline"
                    >{{ s.name }}</NuxtLink><span v-if="i < row.students.length - 1">, </span>
                  </span>
                </td>
                <td class="py-2 pr-3 text-right tabular-nums">{{ row.moved }}</td>
                <td class="py-2 pr-3 text-right tabular-nums">{{ row.closure_detached }}</td>
                <td class="py-2 pr-3 text-right tabular-nums">{{ row.manual_changes }}</td>
                <td class="py-2 pr-3 text-right tabular-nums" :class="row.uncovered ? 'text-red-700 font-semibold' : ''">{{ row.uncovered }}</td>
                <td class="py-2 text-right whitespace-nowrap">
                  <span v-if="row.state === 'done'" class="text-green-700">✓ Corrigé</span>
                  <span v-else-if="row.state === 'error'" class="text-red-700" :title="row.stateMessage">Échec</span>
                  <button
                    v-else-if="row.has_changes"
                    type="button"
                    class="px-2 py-1 text-xs font-medium rounded border border-amber-300 text-amber-800 hover:bg-amber-50 disabled:opacity-50"
                    :disabled="row.state === 'running' || bulkRunning"
                    @click="applyRow(row)"
                  >
                    {{ row.state === 'running' ? 'Correction…' : 'Corriger' }}
                  </button>
                  <span v-else class="text-xs text-gray-500">Nouvel abonnement à prévoir</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

interface AuditRow {
  student_ids: number[]
  students: { id: number; name: string }[]
  moved: number
  closure_detached: number
  uncovered: number
  manual_changes: number
  has_changes: boolean
  state?: 'running' | 'done' | 'error'
  stateMessage?: string
}

const emit = defineEmits<{ changed: [] }>()

const rows = ref<AuditRow[] | null>(null)
const loading = ref(false)
const error = ref('')
const confirmAll = ref(false)
const bulkRunning = ref(false)
const bulkDone = ref(0)
const bulkTotal = ref(0)

const pendingRows = computed(() => (rows.value ?? []).filter((r) => r.has_changes && r.state !== 'done'))

async function runAudit() {
  loading.value = true
  error.value = ''
  confirmAll.value = false
  try {
    const { $api } = useNuxtApp()
    const res = await $api.get('/club/subscriptions/rebalance/audit')
    rows.value = (res.data?.data ?? []) as AuditRow[]
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Le contrôle a échoué.'
  } finally {
    loading.value = false
  }
}

async function applyRow(row: AuditRow): Promise<boolean> {
  row.state = 'running'
  try {
    const { $api } = useNuxtApp()
    await $api.post(`/club/subscriptions/rebalance/students/${row.student_ids[0]}/apply`)
    row.state = 'done'
    emit('changed')
    return true
  } catch (e: any) {
    row.state = 'error'
    row.stateMessage = e?.response?.data?.message || 'Échec'
    return false
  }
}

async function applyAll() {
  const targets = [...pendingRows.value]
  bulkRunning.value = true
  bulkDone.value = 0
  bulkTotal.value = targets.length
  // Une famille à la fois : chaque correction est une transaction indépendante et annulable.
  for (const row of targets) {
    await applyRow(row)
    bulkDone.value++
  }
  bulkRunning.value = false
  confirmAll.value = false
}
</script>
