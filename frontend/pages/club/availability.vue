<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Plages restant disponibles</h1>
            <p class="text-gray-600 mt-1">Par semaine et par créneau — capacité restante par plage horaire</p>
          </div>
          <div class="flex items-center gap-3">
            <NuxtLink
              to="/club/planning"
              class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              <span>Planning</span>
            </NuxtLink>
            <NuxtLink
              to="/club/dashboard"
              class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
            >
              <span>← Dashboard</span>
            </NuxtLink>
          </div>
        </div>
      </div>

      <!-- Options -->
      <div class="bg-white rounded-lg shadow-sm p-4 mb-6 flex flex-wrap items-center gap-4">
        <label class="flex items-center gap-2">
          <span class="text-sm font-medium text-gray-700">Nombre de semaines</span>
          <select
            v-model="weeksCount"
            class="rounded border-gray-300 text-sm"
            @change="loadData"
          >
            <option v-for="n in 12" :key="n" :value="n">{{ n }}</option>
          </select>
        </label>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="bg-white rounded-lg shadow-sm p-12 text-center">
        <p class="text-gray-500">Chargement des plages disponibles...</p>
      </div>

      <!-- Empty -->
      <div v-else-if="!weeks.length" class="bg-white rounded-lg shadow-sm p-12 text-center">
        <p class="text-gray-500">Aucun créneau ouvert ou aucune donnée pour la période.</p>
      </div>

      <!-- Contenu par semaine -->
      <div v-else class="space-y-8">
        <section
          v-for="week in weeks"
          :key="week.week_start"
          class="bg-white rounded-lg shadow-sm overflow-hidden"
        >
          <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
              Semaine du {{ formatDateShort(week.week_start) }} au {{ formatDateShort(week.week_end) }}
            </h2>
          </div>
          <div class="p-6 space-y-6">
            <div
              v-for="slot in week.slots"
              :key="`${week.week_start}-${slot.slot_id}`"
              class="border border-gray-200 rounded-lg overflow-hidden"
            >
              <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex flex-wrap items-center gap-2">
                <span class="font-medium text-gray-900">{{ slot.slot_name }}</span>
                <span class="text-sm text-gray-500">{{ slot.time_range }}</span>
                <span class="text-xs text-gray-400">{{ getDayName(slot.day_of_week) }}</span>
              </div>
              <div class="p-4">
                <div v-if="!slot.dates.length" class="text-sm text-gray-500">
                  Aucune occurrence cette semaine pour ce créneau.
                </div>
                <div v-else class="space-y-4">
                  <div
                    v-for="d in slot.dates"
                    :key="d.date"
                    class="flex flex-wrap items-baseline gap-4"
                  >
                    <button
                      type="button"
                      class="w-28 flex-shrink-0 text-sm font-medium text-left rounded px-1 -mx-1 focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:no-underline text-gray-700 cursor-pointer hover:text-blue-600 hover:underline"
                      :title="d.plages.length ? `Créer un cours le ${formatDateShort(d.date)}` : undefined"
                      :disabled="!d.plages.length"
                      @click.stop="d.plages.length && goToCreateLessonForPlage(slot, d.date, d.plages[0].time)"
                    >
                      {{ formatDateShort(d.date) }}
                    </button>
                    <div class="flex flex-wrap gap-2">
                      <button
                        v-for="plage in d.plages"
                        :key="plage.time"
                        type="button"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded text-sm cursor-pointer transition-opacity hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-slate-400"
                        :class="plageClass(plage)"
                        :title="plage.is_recurring ? `Créer un cours à ${plage.time} — disponible sur 26 semaines` : `Créer un cours à ${plage.time} — ${plage.remaining}/${plage.max_slots} restant(s)`"
                        @click.stop="goToCreateLessonForPlage(slot, d.date, plage.time)"
                      >
                        <span class="font-mono">{{ plage.time }}</span>
                        <span class="text-xs opacity-90">{{ plage.remaining }}/{{ plage.max_slots }}</span>
                        <span v-if="plage.is_recurring" class="text-xs text-blue-600 font-normal" title="Disponible sur 26 semaines">26 sem.</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { $api } = useNuxtApp()

const weeks = ref<Array<{
  week_start: string
  week_end: string
  slots: Array<{
    slot_id: number
    slot_name: string
    time_range: string
    day_of_week: number
    dates: Array<{
      date: string
      day_of_week: number
      plages: Array<{ time: string; max_slots: number; occupied: number; remaining: number; is_recurring?: boolean }>
    }>
  }>
}>>([])
const loading = ref(true)
const weeksCount = ref(4)

onMounted(() => {
  loadData()
})

async function loadData() {
  try {
    loading.value = true
    const res = await $api.get('/club/planning/availability-by-week', {
      params: { weeks: weeksCount.value }
    })
    if (res.data?.success && Array.isArray(res.data.weeks)) {
      weeks.value = res.data.weeks
    } else {
      weeks.value = []
    }
  } catch (e) {
    console.error(e)
    weeks.value = []
  } finally {
    loading.value = false
  }
}

function formatDateShort(dateStr: string): string {
  if (!dateStr) return ''
  const d = new Date(dateStr + 'T00:00:00')
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
}

function getDayName(dayOfWeek: number): string {
  const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
  return days[dayOfWeek] ?? ''
}

function plageClass(plage: { remaining: number; max_slots: number }): string {
  if (plage.remaining <= 0) return 'bg-red-100 text-red-800'
  if (plage.remaining < plage.max_slots) return 'bg-amber-100 text-amber-800'
  return 'bg-emerald-100 text-emerald-800'
}

/**
 * Clic sur le jour ou sur une plage horaire → ouvre la modale de création avec date et heure fixées (même modale que le planning).
 */
function goToCreateLessonForPlage(
  slot: { slot_id: number },
  date: string,
  time: string
) {
  navigateTo({
    path: '/club/planning',
    query: { slot_id: String(slot.slot_id), date, time }
  })
}
</script>
