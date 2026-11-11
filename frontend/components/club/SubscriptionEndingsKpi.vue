<template>
  <!-- Indicateur du tableau de bord : fins d'abonnement de tout le club (détail : /club/subscription-endings). -->
  <NuxtLink
    to="/club/subscription-endings"
    class="block bg-white rounded-xl shadow-lg p-5 hover:shadow-xl transition-shadow"
    aria-label="Ouvrir le suivi des fins d'abonnement"
  >
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center">
        <div class="p-3 bg-rose-100 rounded-lg">
          <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Fins d'abonnement à traiter</p>
          <p class="text-2xl font-semibold text-gray-900">
            <template v-if="loading">…</template>
            <template v-else>{{ toHandle }}</template>
          </p>
        </div>
      </div>
      <div v-if="!loading && !error" class="flex flex-wrap gap-2 text-xs font-semibold">
        <span class="px-2.5 py-1 rounded-full bg-red-600 text-white">{{ counts.critical }} critique(s)</span>
        <span class="px-2.5 py-1 rounded-full bg-amber-400 text-gray-900">{{ counts.urgent }} urgent(s)</span>
        <span class="px-2.5 py-1 rounded-full bg-gray-200 text-gray-800">{{ counts.watch }} à surveiller</span>
        <span class="px-2.5 py-1 rounded-full bg-slate-700 text-white">{{ counts.ended }} non renouvelé(s)</span>
        <span class="px-2.5 py-1 rounded-full bg-purple-100 text-purple-800">{{ counts.last }} fin(s) prévue(s)</span>
      </div>
      <span v-else-if="error" class="text-xs text-red-700">{{ error }}</span>
      <span class="text-rose-600 text-sm font-medium">Voir le suivi →</span>
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'

const counts = ref({ critical: 0, urgent: 0, watch: 0, last: 0, ended: 0 })
const loading = ref(false)
const error = ref('')

/** À traiter = à renouveler ou à relancer ; les « fins prévues » (dernier abonnement) n'en font pas partie. */
const toHandle = computed(() => counts.value.critical + counts.value.urgent + counts.value.watch + counts.value.ended)

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { $api } = useNuxtApp()
    const res = await $api.get('/club/subscriptions/end-tracking')
    counts.value = { ...counts.value, ...(res.data?.data?.counts ?? {}) }
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Indicateur indisponible'
  } finally {
    loading.value = false
  }
}

defineExpose({ reload: load })
onMounted(load)
</script>
