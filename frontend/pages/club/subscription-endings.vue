<template>
  <!-- Suivi des fins d'abonnement : tout le club, que l'élève ait cours prochainement ou non. -->
  <div class="max-w-6xl mx-auto px-4 py-6">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
      <div>
        <NuxtLink to="/club/subscriptions" class="text-sm text-blue-600 hover:text-blue-800">← Abonnements</NuxtLink>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Fins d'abonnement</h1>
        <p class="text-sm text-gray-600 mt-1">
          Abonnements actifs en fin de vie (≤ 3 cours ou ≤ 14 jours), derniers abonnements et fins récentes sans renouvellement.
        </p>
      </div>
      <button
        type="button"
        class="px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white hover:bg-gray-50"
        :disabled="loading"
        @click="load"
      >
        Actualiser
      </button>
    </div>

    <div class="flex flex-wrap gap-2 mb-5">
      <button
        v-for="c in categoryFilters"
        :key="c.id"
        type="button"
        class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors"
        :class="filter === c.id ? c.active : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
        @click="filter = c.id"
      >
        {{ c.label }} ({{ c.id === 'all' ? items.length : counts[c.id] ?? 0 }})
      </button>
    </div>

    <div v-if="loading" class="text-sm text-gray-500 py-8">Chargement…</div>
    <div v-else-if="error" class="text-sm text-red-700 bg-red-50 rounded-lg p-4">{{ error }}</div>
    <p v-else-if="visibleItems.length === 0" class="text-sm text-gray-600 bg-white rounded-lg border border-gray-200 p-6">
      Aucune fin d'abonnement à suivre.
    </p>

    <div v-else class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
          <tr>
            <th class="px-4 py-3 text-left">Élève</th>
            <th class="px-4 py-3 text-left">Situation</th>
            <th class="px-4 py-3 text-left">Abonnement</th>
            <th class="px-4 py-3 text-left">Prochain cours</th>
            <th class="px-4 py-3 text-left">Dernier abonnement</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="item in visibleItems" :key="`${item.student_id}-${item.subscription_instance_id}`">
            <td class="px-4 py-3">
              <button
                type="button"
                class="font-medium text-blue-700 hover:text-blue-900 hover:underline text-left"
                :title="`Ouvrir la fiche de ${item.student_name}`"
                @click="openStudent(item.student_id)"
              >
                {{ item.student_name }}
              </button>
            </td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="categoryMeta[item.category].badge">
                {{ categoryMeta[item.category].label }}
              </span>
              <div class="text-xs text-gray-600 mt-1">{{ situation(item) }}</div>
            </td>
            <td class="px-4 py-3 text-gray-700">
              {{ item.subscription_label }}
              <div v-if="item.expires_at" class="text-xs text-gray-500">expire le {{ formatDate(item.expires_at) }}</div>
            </td>
            <td class="px-4 py-3 text-gray-700">{{ formatLesson(item.next_lesson_at) }}</td>
            <td class="px-4 py-3">
              <label v-if="item.category !== 'ended'" class="inline-flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                  :checked="item.is_last_subscription"
                  :disabled="savingId === item.subscription_instance_id"
                  @change="toggleLast(item, ($event.target as HTMLInputElement).checked)"
                />
                <span class="text-xs text-gray-600">Fin de la récurrence</span>
              </label>
              <span v-else class="text-xs text-gray-400">—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <StudentPanel
      :student-id="panelStudentId"
      :tab="panelTab"
      @update:tab="panelTab = $event"
      @updated="load"
      @close="panelStudentId = null"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import StudentPanel from '~/components/club/StudentPanel.vue'

definePageMeta({
  middleware: ['auth'],
})

type Category = 'critical' | 'urgent' | 'watch' | 'last' | 'ended'
type Item = {
  category: Category
  student_id: number
  student_name: string
  subscription_instance_id: number
  subscription_label: string
  is_last_subscription: boolean
  remaining_lessons: number
  expires_at: string | null
  days_until_expiry: number | null
  ended_on: string | null
  next_lesson_at: string | null
}

const categoryMeta: Record<Category, { label: string; badge: string }> = {
  critical: { label: 'Critique', badge: 'bg-red-600 text-white' },
  urgent: { label: 'Urgent', badge: 'bg-amber-400 text-gray-900' },
  watch: { label: 'À surveiller', badge: 'bg-gray-200 text-gray-800' },
  last: { label: 'Fin prévue', badge: 'bg-purple-100 text-purple-800' },
  ended: { label: 'Terminé, non renouvelé', badge: 'bg-slate-700 text-white' },
}

const categoryFilters: { id: Category | 'all'; label: string; active: string }[] = [
  { id: 'all', label: 'Tout', active: 'bg-gray-900 text-white border-gray-900' },
  { id: 'critical', label: 'Critique', active: 'bg-red-600 text-white border-red-600' },
  { id: 'urgent', label: 'Urgent', active: 'bg-amber-400 text-gray-900 border-amber-400' },
  { id: 'watch', label: 'À surveiller', active: 'bg-gray-600 text-white border-gray-600' },
  { id: 'last', label: 'Derniers abonnements', active: 'bg-purple-600 text-white border-purple-600' },
  { id: 'ended', label: 'Terminés non renouvelés', active: 'bg-slate-700 text-white border-slate-700' },
]

const route = useRoute()
const items = ref<Item[]>([])
const counts = ref<Record<string, number>>({})
const loading = ref(false)
const error = ref('')
const filter = ref<Category | 'all'>((route.query.filter as Category) || 'all')
const savingId = ref<number | null>(null)
const panelStudentId = ref<number | null>(null)
const panelTab = ref('abonnements')

const visibleItems = computed(() =>
  filter.value === 'all' ? items.value : items.value.filter((i) => i.category === filter.value),
)

function situation(item: Item): string {
  if (item.category === 'ended') {
    return item.ended_on ? `Terminé le ${formatDate(item.ended_on)}` : 'Terminé'
  }
  const parts = [
    item.remaining_lessons <= 0 ? 'Plus aucun cours restant' : `${item.remaining_lessons} cours restant(s)`,
  ]
  if (item.days_until_expiry !== null && item.days_until_expiry <= 14) {
    parts.push(item.days_until_expiry <= 0 ? 'expire aujourd’hui' : `expire dans ${item.days_until_expiry} j`)
  }
  return parts.join(' · ')
}

function formatDate(v: string): string {
  const d = new Date(`${v}T00:00:00`)
  return Number.isNaN(d.getTime()) ? v : d.toLocaleDateString('fr-BE')
}

function formatLesson(v: string | null): string {
  if (!v) return '—'
  const d = new Date(v.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return v
  return d.toLocaleString('fr-BE', { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

function openStudent(id: number) {
  panelTab.value = 'abonnements'
  panelStudentId.value = id
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { $api } = useNuxtApp()
    const res = await $api.get('/club/subscriptions/end-tracking')
    items.value = res.data?.data?.items ?? []
    counts.value = res.data?.data?.counts ?? {}
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Impossible de charger le suivi des fins d’abonnement.'
  } finally {
    loading.value = false
  }
}

async function toggleLast(item: Item, checked: boolean) {
  const { $api } = useNuxtApp()
  const { success, error: showError } = useToast()
  savingId.value = item.subscription_instance_id
  try {
    const res = await $api.put(`/club/subscriptions/${item.subscription_instance_id}/last-subscription`, {
      is_last_subscription: checked,
    })
    success(res.data?.message || 'Enregistré')
    await load()
  } catch (e: any) {
    showError(e?.response?.data?.message || 'Enregistrement impossible')
    await load()
  } finally {
    savingId.value = null
  }
}

onMounted(load)
</script>
