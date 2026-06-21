<template>
  <div class="p-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Heures d'ouverture</h1>
      <p class="text-gray-600">Amplitude d'ouverture par créneau, calculée sur les cours réellement assurés.</p>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Du</label>
          <input
            v-model="dateFrom"
            type="date"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Au</label>
          <input
            v-model="dateTo"
            type="date"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
        <div>
          <button
            @click="loadReport"
            :disabled="loading"
            class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Calculer
          </button>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-4 text-gray-500">Calcul en cours...</p>
    </div>

    <!-- Erreur -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <p class="text-red-800">{{ error }}</p>
    </div>

    <!-- Résultat -->
    <div v-else-if="report" class="space-y-4">
      <!-- Niveau 1 : total de la période (dépliable) -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <button
          @click="totalExpanded = !totalExpanded"
          class="w-full flex items-center justify-between px-6 py-5 hover:bg-gray-50 transition-colors text-left"
        >
          <div>
            <p class="text-sm font-medium text-gray-500">
              Total période ({{ formatDate(report.period.date_from) }} → {{ formatDate(report.period.date_to) }})
            </p>
            <p class="text-3xl font-bold text-gray-900">{{ formatHours(report.total_hours) }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ report.days_count }} jour(s) avec cours</p>
          </div>
          <svg
            class="w-6 h-6 text-gray-400 transition-transform"
            :class="{ 'rotate-180': totalExpanded }"
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <!-- Niveau 2 : détail par jour -->
        <div v-if="totalExpanded" class="border-t border-gray-100">
          <div v-if="report.days.length === 0" class="px-6 py-6 text-gray-500">
            Aucun cours sur la période.
          </div>
          <table v-else class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
              <tr>
                <th class="px-6 py-3 text-left font-medium w-10"></th>
                <th class="px-6 py-3 text-left font-medium">Jour</th>
                <th class="px-6 py-3 text-right font-medium">Cours</th>
                <th class="px-6 py-3 text-right font-medium">Heures</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="day in report.days" :key="day.date">
                <tr
                  class="border-t border-gray-100 hover:bg-gray-50 cursor-pointer"
                  @click="toggleDay(day.date)"
                >
                  <td class="px-6 py-3">
                    <svg
                      class="w-4 h-4 text-gray-400 transition-transform"
                      :class="{ 'rotate-90': isDayExpanded(day.date) }"
                      fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                  </td>
                  <td class="px-6 py-3 font-medium text-gray-900 capitalize">{{ formatDayLabel(day.date) }}</td>
                  <td class="px-6 py-3 text-right text-gray-600">{{ day.lessons_count }}</td>
                  <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ formatHours(day.hours) }}</td>
                </tr>

                <!-- Niveau 3 : détail par créneau -->
                <tr v-if="isDayExpanded(day.date)" class="bg-gray-50/60">
                  <td></td>
                  <td colspan="3" class="px-6 py-3">
                    <div class="space-y-2">
                      <div
                        v-for="(c, idx) in day.creneaux"
                        :key="idx"
                        class="flex items-center justify-between bg-white border border-gray-200 rounded-lg px-4 py-2"
                      >
                        <div class="flex items-center gap-3">
                          <span
                            v-if="c.out_of_range"
                            class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700"
                          >Hors plage</span>
                          <span v-else class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                            Plage {{ c.slot_start }}–{{ c.slot_end }}
                          </span>
                          <span class="text-gray-700">
                            Réel : <strong>{{ c.first_start }}</strong> → <strong>{{ c.last_end }}</strong>
                          </span>
                          <span class="text-gray-400">·</span>
                          <span class="text-gray-500">{{ c.lessons_count }} cours</span>
                        </div>
                        <span class="font-semibold text-gray-900">{{ formatHours(c.hours) }}</span>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

definePageMeta({
  middleware: 'auth'
})

const { $api } = useNuxtApp()

const dateFrom = ref('')
const dateTo = ref('')
const loading = ref(false)
const error = ref('')
const report = ref(null)
const totalExpanded = ref(true)
const expandedDays = ref(new Set())

const pad = (n) => String(n).padStart(2, '0')
const toIso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`

const initPreviousMonth = () => {
  const now = new Date()
  const first = new Date(now.getFullYear(), now.getMonth() - 1, 1)
  const last = new Date(now.getFullYear(), now.getMonth(), 0)
  dateFrom.value = toIso(first)
  dateTo.value = toIso(last)
}

const toLocalDate = (iso) => new Date(iso + 'T12:00:00')

const formatDate = (iso) => {
  if (!iso) return ''
  return toLocalDate(iso).toLocaleDateString('fr-FR')
}

const formatDayLabel = (iso) =>
  toLocalDate(iso).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })

const formatHours = (decimal) => {
  const total = Math.round((decimal || 0) * 60)
  const h = Math.floor(total / 60)
  const m = total % 60
  if (h > 0 && m > 0) return `${h}h${pad(m)}`
  if (h > 0) return `${h}h`
  return `${m}min`
}

const isDayExpanded = (date) => expandedDays.value.has(date)
const toggleDay = (date) => {
  const next = new Set(expandedDays.value)
  next.has(date) ? next.delete(date) : next.add(date)
  expandedDays.value = next
}

const loadReport = async () => {
  loading.value = true
  error.value = ''
  try {
    const res = await $api.get('/club/planning/opening-hours', {
      params: { date_from: dateFrom.value, date_to: dateTo.value }
    })
    report.value = res.data?.data ?? null
    expandedDays.value = new Set()
    totalExpanded.value = true
  } catch (e) {
    error.value = e?.response?.data?.message || 'Erreur lors du calcul des heures.'
    report.value = null
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  initPreviousMonth()
  loadReport()
})
</script>
