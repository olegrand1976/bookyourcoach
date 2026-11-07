<template>
  <!-- Veille abonnements : élèves qui ont cours prochainement avec un abonnement en fin de vie,
       classés par urgence (mêmes règles que l'e-mail envoyé 2 jours avant les cours). -->
  <section class="bg-white rounded-lg border border-gray-200 shadow-sm" aria-labelledby="expiring-subs-title">
    <header class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h3 id="expiring-subs-title" class="text-lg font-semibold text-gray-900">
          Abonnements à renouveler — cours des {{ days }} prochains jours
        </h3>
        <p class="text-sm text-gray-600 mt-0.5">
          Élèves qui ont cours bientôt et dont l’abonnement arrive en fin de vie (≤ 3 cours ou ≤ 14 jours).
        </p>
      </div>
      <div class="flex flex-wrap gap-2 text-xs font-semibold">
        <span class="px-2.5 py-1 rounded-full bg-red-600 text-white">{{ counts.critical }} critique(s)</span>
        <span class="px-2.5 py-1 rounded-full bg-amber-400 text-gray-900">{{ counts.urgent }} urgent(s)</span>
        <span class="px-2.5 py-1 rounded-full bg-gray-200 text-gray-800">{{ counts.watch }} à surveiller</span>
      </div>
    </header>

    <div v-if="loading" class="px-5 py-6 text-sm text-gray-500">Chargement…</div>
    <div v-else-if="error" class="px-5 py-4 text-sm text-red-700 bg-red-50">{{ error }}</div>
    <p v-else-if="items.length === 0" class="px-5 py-6 text-sm text-gray-600">
      Aucun abonnement en fin de vie parmi les élèves qui ont cours prochainement.
    </p>

    <div v-else class="divide-y divide-gray-100">
      <template v-for="level in levels" :key="level.id">
        <div v-if="byLevel[level.id].length" class="px-5 py-3" :class="level.bg">
          <h4 class="text-sm font-semibold mb-2" :class="level.text">{{ level.label }} ({{ byLevel[level.id].length }})</h4>
          <ul class="space-y-1.5">
            <li
              v-for="item in byLevel[level.id]"
              :key="`${item.student_id}-${item.subscription_instance_id}`"
              class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm"
            >
              <button
                type="button"
                class="font-medium text-blue-700 hover:text-blue-900 hover:underline"
                :title="`Ouvrir la fiche de ${item.student_name}`"
                @click="emit('open-student', item.student_id)"
              >
                {{ item.student_name }}
              </button>
              <span class="text-gray-700">{{ item.reasons.join(' · ') }}</span>
              <span class="text-gray-500 text-xs">
                cours {{ formatLesson(item.next_lesson_at) }}<template v-if="item.subscription_label"> · {{ item.subscription_label }}</template>
              </span>
            </li>
          </ul>
        </div>
      </template>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'

type Level = 'critical' | 'urgent' | 'watch'
type Item = {
  level: Level
  student_id: number
  student_name: string
  next_lesson_at: string | null
  subscription_instance_id: number | null
  subscription_label: string | null
  reasons: string[]
}

const props = withDefaults(defineProps<{ days?: number }>(), { days: 7 })
const emit = defineEmits<{ 'open-student': [studentId: number] }>()

const levels: { id: Level; label: string; bg: string; text: string }[] = [
  { id: 'critical', label: 'Critique — à traiter avant le cours', bg: 'bg-red-50', text: 'text-red-800' },
  { id: 'urgent', label: 'Urgent — à renouveler cette semaine', bg: 'bg-amber-50', text: 'text-amber-800' },
  { id: 'watch', label: 'À surveiller', bg: '', text: 'text-gray-700' },
]

const items = ref<Item[]>([])
const counts = ref<Record<Level, number>>({ critical: 0, urgent: 0, watch: 0 })
const loading = ref(false)
const error = ref('')

const byLevel = computed(() => ({
  critical: items.value.filter((i) => i.level === 'critical'),
  urgent: items.value.filter((i) => i.level === 'urgent'),
  watch: items.value.filter((i) => i.level === 'watch'),
}))

function formatLesson(v: string | null): string {
  if (!v) return '—'
  const d = new Date(v.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return v
  return d.toLocaleString('fr-BE', { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { $api } = useNuxtApp()
    const res = await $api.get('/club/subscriptions/expiring', { params: { days: props.days } })
    items.value = res.data?.data?.items ?? []
    counts.value = { critical: 0, urgent: 0, watch: 0, ...(res.data?.data?.counts ?? {}) }
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Impossible de charger la veille des abonnements.'
  } finally {
    loading.value = false
  }
}

defineExpose({ reload: load })
onMounted(load)
</script>
