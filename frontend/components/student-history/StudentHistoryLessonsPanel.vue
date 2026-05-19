<template>
  <section class="rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-4 py-4 sm:px-5 bg-gradient-to-r from-purple-50 to-indigo-50 border-b border-gray-200">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Calendrier des cours
          </h3>
          <p class="text-sm text-gray-600 mt-1">
            {{ upcomingPeriod.label }}
            <span v-if="showPastLessons && pastPeriod"> · {{ pastPeriod.label }}</span>
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-sm">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white border border-purple-200 text-purple-900 font-medium">
            <span class="w-2 h-2 rounded-full bg-purple-500" aria-hidden="true"></span>
            À venir : {{ upcomingLessonsSorted.length }}
          </span>
          <span
            v-if="pastLessonsAvailableCount > 0"
            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white border border-gray-200 text-gray-700"
          >
            Passés : {{ showPastLessons ? pastLessonsSorted.length : pastLessonsAvailableCount }}
          </span>
        </div>
      </div>
    </div>

    <div class="sticky top-0 z-10 px-4 py-4 sm:px-5 bg-white/95 backdrop-blur border-b border-gray-100 space-y-4">
      <div>
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Statut</p>
        <div class="flex flex-wrap gap-2" role="group" aria-label="Filtrer par statut">
          <button
            v-for="opt in statusFilterOptions"
            :key="opt.value"
            type="button"
            :class="[
              'px-3 py-1.5 text-sm font-medium rounded-full border transition-colors',
              lessonStatusFilter === opt.value
                ? 'bg-purple-600 text-white border-purple-600'
                : 'bg-white text-gray-700 border-gray-300 hover:border-purple-300 hover:bg-purple-50',
            ]"
            @click="lessonStatusFilter = opt.value"
          >
            {{ opt.label }}
          </button>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label for="lesson-period-filter" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
            Période
          </label>
          <select
            id="lesson-period-filter"
            v-model="lessonPeriodMode"
            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
          >
            <option v-for="opt in periodFilterOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>
        <div class="flex flex-col justify-end gap-2">
          <button
            v-if="pastLessonsAvailableCount > 0"
            type="button"
            :class="[
              'w-full px-4 py-2.5 text-sm font-medium rounded-lg border transition-colors',
              showPastLessons
                ? 'bg-gray-800 text-white border-gray-800 hover:bg-gray-900'
                : 'bg-white text-gray-800 border-gray-300 hover:bg-gray-50',
            ]"
            :aria-expanded="showPastLessons"
            @click="showPastLessons = !showPastLessons"
          >
            <template v-if="showPastLessons">Masquer les cours passés ({{ pastLessonsSorted.length }})</template>
            <template v-else>Afficher les cours passés ({{ pastLessonsAvailableCount }})</template>
          </button>
          <button
            v-if="lessonStatusFilter !== 'all' || lessonPeriodMode !== 'upcoming_quarter' || showPastLessons"
            type="button"
            class="w-full px-3 py-2 text-sm text-purple-700 hover:underline"
            @click="resetFilters"
          >
            Réinitialiser les filtres
          </button>
        </div>
      </div>
    </div>

    <div class="p-4 sm:p-5 space-y-8">
      <div v-if="!lessons?.length" class="rounded-lg bg-gray-50 py-10 text-center">
        <p class="text-gray-500">Aucun cours pour cet élève</p>
      </div>

      <div
        v-else-if="upcomingLessonsSorted.length === 0 && (!showPastLessons || pastLessonsSorted.length === 0)"
        class="rounded-lg bg-gray-50 py-10 text-center"
      >
        <p class="text-gray-500">Aucun cours ne correspond aux filtres sélectionnés.</p>
      </div>

      <template v-else>
        <div
          v-if="subscriptionsEarliestEndFormatted"
          class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800"
        >
          Fin théorique des abonnements à partir du <strong>{{ subscriptionsEarliestEndFormatted }}</strong>.
        </div>

        <div>
          <h4 class="text-base font-semibold text-gray-900 mb-1 flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-purple-500" aria-hidden="true"></span>
            Cours à venir
            <span class="text-sm font-normal text-gray-500">({{ upcomingLessonsSorted.length }})</span>
          </h4>
          <p class="text-sm text-gray-500 mb-4">Prochaines séances dans la période sélectionnée</p>

          <div
            v-if="upcomingLessonsSorted.length === 0"
            class="rounded-lg border border-dashed border-gray-300 bg-gray-50 py-8 text-center text-gray-500 text-sm"
          >
            Aucun cours à venir pour ces filtres.
          </div>
          <template v-else>
            <div
              class="hidden md:grid md:grid-cols-12 gap-2 px-4 py-2 mb-2 bg-gray-100 rounded-lg border border-gray-200 text-xs font-semibold text-gray-600 uppercase tracking-wide"
            >
              <div class="col-span-2">Date</div>
              <div class="col-span-1">Heure</div>
              <div class="col-span-2">Type</div>
              <div class="col-span-2">Enseignant</div>
              <div class="col-span-1">Statut</div>
              <div class="col-span-1">Prix</div>
              <div class="col-span-2">Lieu</div>
              <div class="col-span-1 text-right">Action</div>
            </div>
            <div v-for="monthGroup in upcomingGroupedByMonth" :key="`up-${monthGroup.key}`" class="mb-6 last:mb-0">
              <p class="text-sm font-semibold text-purple-800 capitalize mb-2 px-1">{{ monthGroup.label }}</p>
              <div class="border border-gray-200 rounded-lg overflow-hidden">
                <StudentHistoryLessonRow
                  v-for="lesson in monthGroup.lessons"
                  :key="lesson.id"
                  :lesson="lesson"
                  :uncovered="isLessonUncovered(lesson)"
                  :certificate-action-loading="certificateActionLoading"
                  @edit="emit('edit-lesson', $event)"
                  @download-certificate="emit('download-certificate', $event)"
                  @accept-certificate="emit('accept-certificate', $event)"
                  @reject-certificate="emit('reject-certificate', $event)"
                />
              </div>
            </div>
          </template>
        </div>

        <div v-if="showPastLessons" class="pt-6 border-t border-gray-200">
          <h4 class="text-base font-semibold text-gray-900 mb-1 flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-gray-400" aria-hidden="true"></span>
            Cours passés
            <span class="text-sm font-normal text-gray-500">({{ pastLessonsSorted.length }})</span>
          </h4>
          <p v-if="pastPeriod" class="text-sm text-gray-500 mb-4">{{ pastPeriod.label }} — du plus récent au plus ancien</p>

          <div
            v-if="pastLessonsSorted.length === 0"
            class="rounded-lg border border-dashed border-gray-300 bg-gray-50 py-8 text-center text-gray-500 text-sm"
          >
            Aucun cours passé pour ces filtres.
          </div>
          <template v-else>
            <div v-for="monthGroup in pastGroupedByMonth" :key="`past-${monthGroup.key}`" class="mb-6 last:mb-0">
              <p class="text-sm font-semibold text-gray-600 capitalize mb-2 px-1">{{ monthGroup.label }}</p>
              <div class="border border-gray-200 rounded-lg overflow-hidden opacity-95">
                <StudentHistoryLessonRow
                  v-for="lesson in monthGroup.lessons"
                  :key="lesson.id"
                  :lesson="lesson"
                  is-past
                  :certificate-action-loading="certificateActionLoading"
                  @edit="emit('edit-lesson', $event)"
                  @download-certificate="emit('download-certificate', $event)"
                  @accept-certificate="emit('accept-certificate', $event)"
                  @reject-certificate="emit('reject-certificate', $event)"
                />
              </div>
            </div>
          </template>
        </div>
      </template>
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
  compareLessonsForHistoryDisplay,
  comparePastLessonsForHistoryDisplay,
  groupLessonsByMonth,
  lessonMatchesHistoryFilters,
  resolvePastLessonPeriod,
  resolveUpcomingLessonPeriod,
} from '~/composables/useStudentLessonHistoryFilters'

const props = defineProps({
  lessons: { type: Array, default: () => [] },
  subscriptionsEarliestEndFormatted: { type: String, default: '' },
  certificateActionLoading: { type: [Number, null], default: null },
  isLessonUncovered: { type: Function, required: true },
})

const emit = defineEmits([
  'edit-lesson',
  'download-certificate',
  'accept-certificate',
  'reject-certificate',
])

const lessonStatusFilter = ref('all')
const lessonPeriodMode = ref('upcoming_quarter')
const showPastLessons = ref(false)

const statusFilterOptions = [
  { value: 'all', label: 'Tous' },
  { value: 'pending', label: 'En attente' },
  { value: 'confirmed', label: 'Confirmés' },
  { value: 'completed', label: 'Terminés' },
  { value: 'cancelled', label: 'Annulés' },
]

const periodFilterOptions = [
  { value: 'upcoming_quarter', label: 'Trimestre en cours' },
  { value: 'quarter_all', label: 'Trimestre complet' },
  { value: 'with_previous_quarter', label: '2 trimestres' },
]

const filterOptions = { alwaysShowPendingMedicalCerts: true }

const upcomingPeriod = computed(() => resolveUpcomingLessonPeriod(lessonPeriodMode.value))
const pastPeriod = computed(() => resolvePastLessonPeriod(lessonPeriodMode.value))

const upcomingLessonsSorted = computed(() => {
  const { from, to } = upcomingPeriod.value
  return props.lessons
    .filter((lesson) => lessonMatchesHistoryFilters(lesson, lessonStatusFilter.value, from, to, filterOptions))
    .sort(compareLessonsForHistoryDisplay)
})

const pastLessonsSorted = computed(() => {
  if (!showPastLessons.value) return []
  const period = pastPeriod.value
  if (!period) return []
  return props.lessons
    .filter((lesson) =>
      lessonMatchesHistoryFilters(lesson, lessonStatusFilter.value, period.from, period.to, filterOptions),
    )
    .sort(comparePastLessonsForHistoryDisplay)
})

const pastLessonsAvailableCount = computed(() => {
  const period = pastPeriod.value
  if (!period) return 0
  return props.lessons.filter((lesson) =>
    lessonMatchesHistoryFilters(lesson, lessonStatusFilter.value, period.from, period.to, filterOptions),
  ).length
})

const upcomingGroupedByMonth = computed(() =>
  groupLessonsByMonth(upcomingLessonsSorted.value, compareLessonsForHistoryDisplay),
)

const pastGroupedByMonth = computed(() =>
  groupLessonsByMonth(pastLessonsSorted.value, comparePastLessonsForHistoryDisplay, {
    sortMonthsDescending: true,
  }),
)

function resetFilters() {
  lessonStatusFilter.value = 'all'
  lessonPeriodMode.value = 'upcoming_quarter'
  showPastLessons.value = false
}

defineExpose({ resetFilters })
</script>
