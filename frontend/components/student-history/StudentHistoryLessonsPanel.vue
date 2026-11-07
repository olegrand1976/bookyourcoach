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
            {{ lessonsView === 'upcoming' ? upcomingPeriod.label : pastPeriod?.label }}
          </p>
        </div>
      </div>
      <!-- Cours à venir / passés : deux onglets distincts -->
      <nav class="mt-4 -mb-4 flex gap-1" role="tablist" aria-label="Cours à venir ou passés">
        <button
          v-for="tab in lessonsTabs"
          :key="tab.id"
          type="button"
          role="tab"
          :aria-selected="lessonsView === tab.id"
          :data-testid="`lessons-tab-${tab.id}`"
          :class="[
            'inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-t-lg border border-b-0 transition-colors',
            lessonsView === tab.id
              ? 'bg-white text-purple-900 border-gray-200'
              : 'bg-transparent text-gray-600 border-transparent hover:text-gray-900 hover:bg-white/60',
          ]"
          @click="selectLessonsView(tab.id)"
        >
          <span :class="['w-2 h-2 rounded-full', tab.id === 'upcoming' ? 'bg-purple-500' : 'bg-gray-400']" aria-hidden="true"></span>
          {{ tab.label }}
          <span class="text-xs font-semibold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-700">{{ tab.count }}</span>
        </button>
      </nav>
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
            v-if="lessonStatusFilter !== 'all' || lessonPeriodMode !== 'all'"
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

      <template v-else>
        <div
          v-if="subscriptionsEarliestEndFormatted"
          class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800"
        >
          Fin théorique des abonnements à partir du <strong>{{ subscriptionsEarliestEndFormatted }}</strong>.
        </div>

        <div v-if="lessonsView === 'upcoming'" role="tabpanel" data-testid="lessons-panel-upcoming">
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

        <div v-else role="tabpanel" data-testid="lessons-panel-past">
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
import { ref, computed, watch } from 'vue'
import {
  STUDENT_LESSON_PERIOD_FILTER_OPTIONS,
  STUDENT_LESSON_STATUS_FILTER_OPTIONS,
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
const lessonPeriodMode = ref('all')
/** Onglet affiché : cours à venir ou cours passés. */
const lessonsView = ref('upcoming')
let lessonsViewChosen = false

const statusFilterOptions = STUDENT_LESSON_STATUS_FILTER_OPTIONS

const periodFilterOptions = STUDENT_LESSON_PERIOD_FILTER_OPTIONS

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
  const period = pastPeriod.value
  if (!period) return []
  return props.lessons
    .filter((lesson) =>
      lessonMatchesHistoryFilters(lesson, lessonStatusFilter.value, period.from, period.to, filterOptions),
    )
    .sort(comparePastLessonsForHistoryDisplay)
})

const upcomingGroupedByMonth = computed(() =>
  groupLessonsByMonth(upcomingLessonsSorted.value, compareLessonsForHistoryDisplay),
)

const pastGroupedByMonth = computed(() =>
  groupLessonsByMonth(pastLessonsSorted.value, comparePastLessonsForHistoryDisplay, {
    sortMonthsDescending: true,
  }),
)

const lessonsTabs = computed(() => [
  { id: 'upcoming', label: 'À venir', count: upcomingLessonsSorted.value.length },
  { id: 'past', label: 'Passés', count: pastLessonsSorted.value.length },
])

function selectLessonsView(id) {
  lessonsView.value = id
  lessonsViewChosen = true
}

// Aucun cours à venir mais un historique : ouvrir directement l'onglet « Passés »
// (tant que l'utilisateur n'a pas choisi d'onglet lui-même).
watch(
  () => [upcomingLessonsSorted.value.length, pastLessonsSorted.value.length],
  ([upcoming, past]) => {
    if (lessonsViewChosen) return
    lessonsView.value = upcoming === 0 && past > 0 ? 'past' : 'upcoming'
  },
  { immediate: true },
)

function resetFilters() {
  lessonStatusFilter.value = 'all'
  lessonPeriodMode.value = 'all'
}

defineExpose({ resetFilters })
</script>
