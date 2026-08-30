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
                    <div class="w-40 flex-shrink-0 flex flex-wrap items-center gap-2">
                      <button
                        type="button"
                        class="text-sm font-medium text-left rounded px-1 -mx-1 focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:no-underline text-gray-700 cursor-pointer hover:text-blue-600 hover:underline"
                        :title="dateCreateTitle(d)"
                        :disabled="!firstCreatablePlage(d)"
                        @click.stop="createFromDate(slot, d)"
                      >
                        {{ formatDateShort(d.date) }}
                      </button>
                      <span
                        v-if="d.is_closure_day"
                        class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800"
                      >
                        Congés
                      </span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                      <button
                        v-for="plage in d.plages"
                        :key="plage.time"
                        type="button"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded text-sm cursor-pointer transition-opacity hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-slate-400"
                        :class="plageClass(plage, d.is_closure_day)"
                        :title="plageDetailTitle(plage, d.is_closure_day)"
                        @click.stop="openPlageDetail(slot, d, plage)"
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

    <!-- Modale détail plage -->
    <div
      v-if="detailOpen && detail"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
      @click.self="closePlageDetail"
    >
      <div
        ref="detailDialogRef"
        role="dialog"
        aria-modal="true"
        aria-labelledby="availability-plage-detail-title"
        tabindex="-1"
        class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto outline-none"
      >
        <div class="px-5 py-4 border-b border-gray-200 flex items-start justify-between gap-3">
          <div>
            <h3 id="availability-plage-detail-title" class="text-lg font-semibold text-gray-900">
              {{ detail.slot.slot_name }} — {{ detail.plage.time }}
            </h3>
            <p class="text-sm text-gray-600 mt-1">
              {{ formatDateShort(detail.date.date) }}
              <span class="text-gray-400">·</span>
              {{ getDayName(detail.slot.day_of_week) }}
              <span class="text-gray-400">·</span>
              {{ detail.slot.time_range }}
            </p>
          </div>
          <button
            type="button"
            class="text-gray-400 hover:text-gray-600 text-xl leading-none px-1"
            aria-label="Fermer"
            @click="closePlageDetail"
          >
            ×
          </button>
        </div>

        <div class="px-5 py-4 space-y-4">
          <div class="flex flex-wrap gap-2">
            <span
              class="inline-flex items-center px-2 py-1 rounded text-sm"
              :class="plageClass(detail.plage, detail.date.is_closure_day)"
            >
              {{ detail.plage.occupied }}/{{ detail.plage.max_slots }} occupé(s)
              · {{ detail.plage.remaining }} restant(s)
            </span>
            <span
              v-if="detail.plage.is_recurring"
              class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800"
            >
              Récurrence 26 sem.
            </span>
            <span
              v-if="detail.date.is_closure_day"
              class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800"
            >
              Jour de congés
            </span>
          </div>

          <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-2">Cours sur cette plage</h4>
            <ul v-if="detail.plage.lessons?.length" class="divide-y divide-gray-100 border border-gray-200 rounded-lg overflow-hidden">
              <li
                v-for="lesson in detail.plage.lessons"
                :key="lesson.id"
                class="px-3 py-2 text-sm text-gray-800 bg-white"
              >
                <div class="font-medium">
                  {{ lesson.student_name || 'Élève inconnu' }}
                  <span class="text-gray-400 font-normal">avec</span>
                  {{ lesson.teacher_name || 'Moniteur inconnu' }}
                </div>
                <div class="text-xs text-gray-500 mt-0.5">
                  <span v-if="lesson.course_type">{{ lesson.course_type }} · </span>
                  {{ lesson.status }}
                </div>
              </li>
            </ul>
            <p v-else class="text-sm text-gray-500">Aucun cours sur cette plage.</p>
          </div>
        </div>

        <div class="px-5 py-4 border-t border-gray-200 flex flex-wrap justify-end gap-2">
          <button
            type="button"
            class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200"
            @click="closePlageDetail"
          >
            Fermer
          </button>
          <button
            v-if="canCreateFromDetail"
            type="button"
            class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700"
            @click="createFromDetail"
          >
            Créer un cours
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
type PlageLesson = {
  id: number
  start_time: string
  status: string
  teacher_name?: string | null
  student_name?: string | null
  course_type?: string | null
}

type Plage = {
  time: string
  max_slots: number
  occupied: number
  remaining: number
  is_recurring?: boolean
  lessons?: PlageLesson[]
}

type SlotDate = {
  date: string
  day_of_week: number
  is_closure_day?: boolean
  plages: Plage[]
}

type Slot = {
  slot_id: number
  slot_name: string
  time_range: string
  day_of_week: number
  dates: SlotDate[]
}

type Week = {
  week_start: string
  week_end: string
  slots: Slot[]
}

type PlageDetail = {
  slot: Slot
  date: SlotDate
  plage: Plage
}

const { $api } = useNuxtApp()

const weeks = ref<Week[]>([])
const loading = ref(true)
const weeksCount = ref(4)

const detailOpen = ref(false)
const detail = ref<PlageDetail | null>(null)
const detailDialogRef = ref<HTMLElement | null>(null)

const canCreateFromDetail = computed(() => {
  if (!detail.value) return false
  if (detail.value.date.is_closure_day) return false
  return detail.value.plage.remaining > 0
})

onMounted(() => {
  loadData()
  if (typeof window !== 'undefined') {
    window.addEventListener('keydown', onDetailKeydown)
  }
})

onBeforeUnmount(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('keydown', onDetailKeydown)
  }
})

function onDetailKeydown(event: KeyboardEvent) {
  if (!detailOpen.value) return
  if (event.key === 'Escape') {
    event.preventDefault()
    closePlageDetail()
  }
}

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

function firstCreatablePlage(d: SlotDate): Plage | null {
  if (d.is_closure_day) return null
  return d.plages.find((p) => p.remaining > 0) ?? null
}

function dateCreateTitle(d: SlotDate): string | undefined {
  if (d.is_closure_day) return 'Jour de congés'
  if (!firstCreatablePlage(d)) return 'Aucune place restante ce jour'
  return `Créer un cours le ${formatDateShort(d.date)}`
}

function createFromDate(slot: Slot, d: SlotDate) {
  const plage = firstCreatablePlage(d)
  if (!plage) return
  goToCreateLessonForPlage(slot, d.date, plage.time)
}

function plageClass(plage: { remaining: number; max_slots: number }, isClosureDay?: boolean): string {
  if (isClosureDay) return 'bg-gray-100 text-gray-500 cursor-pointer'
  if (plage.remaining <= 0) return 'bg-red-100 text-red-800'
  if (plage.remaining < plage.max_slots) return 'bg-amber-100 text-amber-800'
  return 'bg-emerald-100 text-emerald-800'
}

function plageDetailTitle(plage: Plage, isClosureDay?: boolean): string {
  if (isClosureDay) return `Détail ${plage.time} — jour de congés`
  if (plage.is_recurring) return `Détail ${plage.time} — disponible sur 26 semaines`
  return `Détail ${plage.time} — ${plage.remaining}/${plage.max_slots} restant(s)`
}

async function openPlageDetail(slot: Slot, date: SlotDate, plage: Plage) {
  detail.value = { slot, date, plage }
  detailOpen.value = true
  await nextTick()
  detailDialogRef.value?.focus()
}

function closePlageDetail() {
  detailOpen.value = false
  detail.value = null
}

function createFromDetail() {
  if (!detail.value || !canCreateFromDetail.value) return
  goToCreateLessonForPlage(detail.value.slot, detail.value.date.date, detail.value.plage.time)
}

/**
 * Navigation vers la modale de création planning (date + heure fixées).
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
