<template>
  <div
    v-if="show && participantId"
    :class="embedded ? '' : 'fixed inset-0 z-[60] overflow-y-auto'"
    :role="embedded ? undefined : 'dialog'"
    :aria-modal="embedded ? undefined : 'true'"
    :aria-labelledby="embedded ? undefined : titleId"
    @click.self="!embedded && $emit('close')"
  >
    <div :class="embedded ? '' : 'flex min-h-screen items-center justify-center px-4 py-8'">
      <div v-if="!embedded" class="fixed inset-0 bg-black/50" aria-hidden="true" @click="$emit('close')" />

      <div :class="embedded ? 'bg-white' : 'relative w-full max-w-3xl max-h-[90vh] flex flex-col rounded-xl bg-white shadow-xl overflow-hidden'">
        <div
          v-if="!embedded"
          class="px-6 py-4 border-b shrink-0"
          :class="type === 'student' ? 'bg-gradient-to-r from-emerald-500 to-teal-600' : 'bg-gradient-to-r from-blue-500 to-indigo-600'"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 text-white">
              <h2 :id="titleId" class="text-xl font-bold truncate">
                {{ headerTitle }}
              </h2>
              <p class="text-sm opacity-90 mt-0.5">
                {{ type === 'student' ? 'Fiche élève — planning' : 'Fiche enseignant — planning' }}
              </p>
            </div>
            <button
              type="button"
              class="text-white/90 hover:text-white p-1 rounded-lg hover:bg-white/10 shrink-0"
              aria-label="Fermer"
              @click="$emit('close')"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <nav class="mt-4 flex flex-wrap gap-1" role="tablist">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              type="button"
              role="tab"
              :aria-selected="activeTab === tab.id"
              class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors"
              :class="activeTab === tab.id
                ? 'bg-white text-gray-900 shadow-sm'
                : 'text-white/90 hover:bg-white/15'"
              @click="activeTab = tab.id"
            >
              {{ tab.label }}
            </button>
          </nav>
        </div>

        <div :class="embedded ? 'p-6' : 'flex-1 overflow-y-auto p-6 min-h-[12rem]'">
          <div v-if="loading" class="flex justify-center py-16">
            <div
              class="animate-spin h-10 w-10 border-2 rounded-full"
              :class="type === 'student' ? 'border-emerald-600 border-t-transparent' : 'border-blue-600 border-t-transparent'"
            />
          </div>

          <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            {{ error }}
          </div>

          <template v-else>
            <!-- Onglet Cours -->
            <div v-show="activeTab === 'lessons'" class="space-y-4">
              <div
                v-if="type === 'student'"
                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
              >
                <p class="text-sm text-gray-600">
                  {{ lessonsPeriodLabel }}
                </p>
                <label class="flex items-center gap-2 text-sm text-gray-700 shrink-0">
                  <span class="sr-only">Période</span>
                  <select
                    v-model="lessonPeriodMode"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                  >
                    <option
                      v-for="opt in periodFilterOptions"
                      :key="opt.value"
                      :value="opt.value"
                    >
                      {{ opt.label }}
                    </option>
                  </select>
                </label>
              </div>
              <p v-else class="text-sm text-gray-600">
                {{ lessonsPeriodLabel }}
              </p>
              <!-- Planifier l'avenir : prolonge les séries tant qu'un abonnement est actif -->
              <div
                v-if="type === 'student'"
                class="flex flex-wrap items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2"
              >
                <button
                  type="button"
                  class="px-3 py-1.5 text-sm font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50"
                  :disabled="planningFuture"
                  data-testid="plan-future"
                  @click="planFuture"
                >
                  {{ planningFuture ? 'Planification…' : 'Planifier l’avenir' }}
                </button>
                <p class="text-xs text-emerald-900 flex-1 min-w-[12rem]">
                  {{ planFutureMessage || 'Génère les prochains cours des séries de l’élève tant qu’un abonnement est actif (fait aussi automatiquement chaque nuit).' }}
                </p>
              </div>
              <p
                v-if="nonMaintainedLessonsCount > 0"
                class="text-xs text-red-700 bg-red-50 border border-red-100 rounded-lg px-3 py-2"
              >
                {{ nonMaintainedLessonsCount }} cours non maintenu{{ nonMaintainedLessonsCount > 1 ? 's' : '' }} ou supprimé{{ nonMaintainedLessonsCount > 1 ? 's' : '' }} sur la période
              </p>
              <p v-if="displayedLessons.length === 0" class="text-sm text-gray-500 py-8 text-center bg-gray-50 rounded-lg border border-gray-200">
                {{ type === 'student' ? 'Aucun cours sur cette période.' : 'Aucun cours prévu sur cette période.' }}
              </p>
              <ul v-else class="divide-y divide-gray-100 border border-gray-200 rounded-lg overflow-hidden">
                <li
                  v-for="lesson in displayedLessons"
                  :key="lesson.id"
                  class="px-4 py-3 hover:bg-gray-50/80 text-sm"
                  :class="lesson.deleted_at ? 'bg-gray-50' : (lesson.status === 'cancelled' ? 'bg-red-50/40' : (lesson.is_on_closure_day ? 'bg-orange-50/40' : ''))"
                >
                  <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                      <p class="font-medium text-gray-900" :class="lessonIsNonMaintained(lesson) ? 'line-through' : ''">
                        {{ formatLessonDate(lesson.start_time) }}
                        <span class="text-gray-500 font-normal">
                          · {{ formatLessonTime(lesson.start_time) }} – {{ formatLessonTime(lesson.end_time) }}
                        </span>
                      </p>
                      <p class="text-gray-700 mt-0.5">
                        {{ lesson.course_type?.name || 'Cours' }}
                      </p>
                      <p v-if="type === 'student'" class="text-gray-500 text-xs mt-0.5">
                        Coach : {{ lesson.teacher?.user?.name || '—' }}
                      </p>
                      <p v-else class="text-gray-500 text-xs mt-0.5">
                        Élève : {{ lessonStudentLabel(lesson) }}
                      </p>
                    </div>
                    <div class="flex flex-col items-start sm:items-end gap-1 min-w-0 max-w-full">
                      <span
                        v-for="tag in [lessonTag(lesson)]"
                        :key="`${lesson.id}-tag`"
                        class="inline-flex shrink-0 px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="tag.class"
                      >
                        {{ tag.label }}
                      </span>
                      <!-- Annulation : déduit ou non de l'abonnement (bien visible) -->
                      <span
                        v-for="impact in cancellationImpactTags(lesson)"
                        :key="`${lesson.id}-impact`"
                        class="inline-flex max-w-full px-2 py-0.5 rounded-full text-xs font-semibold"
                        :class="impact.class"
                        :data-test="`cancel-impact-${impact.kind}`"
                      >
                        {{ impact.label }}
                      </span>
                    </div>
                  </div>
                </li>
              </ul>
            </div>

            <!-- Onglet Coordonnées -->
            <div v-show="activeTab === 'contact'" class="space-y-4">
              <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div v-for="row in contactRows" :key="row.label" class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                  <dt class="text-gray-500 text-xs font-medium uppercase tracking-wide">{{ row.label }}</dt>
                  <dd class="mt-1 text-gray-900 font-medium break-words">
                    <a
                      v-if="row.href"
                      :href="row.href"
                      class="text-blue-700 hover:underline"
                    >{{ row.value }}</a>
                    <span v-else>{{ row.value }}</span>
                  </dd>
                </div>
              </dl>
            </div>

            <!-- Onglet Abonnements (élève uniquement) -->
            <div v-show="activeTab === 'subscriptions' && type === 'student'" class="space-y-4">
              <p v-if="subscriptions.length === 0" class="text-sm text-gray-500 py-8 text-center bg-gray-50 rounded-lg border border-gray-200">
                Aucun abonnement enregistré pour cet élève.
              </p>
              <div
                v-for="sub in subscriptions"
                :key="sub.id"
                class="rounded-lg border border-gray-200 bg-gray-50 p-4"
              >
                <div class="flex flex-wrap items-center gap-2 mb-2">
                  <h4>
                    <NuxtLink
                      :to="subscriptionInstanceLink(sub.id)"
                      class="font-semibold text-blue-700 hover:text-blue-900 hover:underline underline-offset-2"
                      title="Ouvrir l'abonnement"
                    >
                      {{ sub.subscription?.template?.name || sub.subscription?.name || `Abonnement #${sub.id}` }} ›
                    </NuxtLink>
                  </h4>
                  <span
                    class="px-2 py-0.5 text-xs font-medium rounded-full"
                    :class="subscriptionStatusClass(sub.status)"
                  >
                    {{ subscriptionStatusLabel(sub.status) }}
                  </span>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm text-gray-700">
                  <div>
                    <span class="text-gray-500">Cours utilisés</span>
                    <p class="font-medium">{{ sub.lessons_used ?? 0 }} / {{ subTotalLessons(sub) }}</p>
                  </div>
                  <div>
                    <span class="text-gray-500">Expiration</span>
                    <p class="font-medium">{{ sub.expires_at ? formatDateOnly(sub.expires_at) : '—' }}</p>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </div>

        <div class="px-6 py-3 border-t bg-gray-50 flex justify-end shrink-0">
          <button
            type="button"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
            @click="$emit('close')"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { subscriptionInstanceLink } from '~/utils/subscriptionLink'
import {
  type LessonPeriodMode,
  STUDENT_LESSON_PERIOD_FILTER_OPTIONS,
  getCalendarQuarterBounds,
  isFullHistoryPeriodMode,
  lessonIsUpcoming,
  lessonMatchesHistoryFilters,
  resolveFullLessonPeriod,
  resolveUpcomingLessonPeriod,
} from '~/composables/useStudentLessonHistoryFilters'
import {
  participantDisplayNameFromStudent,
  participantDisplayNameFromTeacher,
  formatParticipantPhone,
} from '~/composables/planning/usePlanningParticipant'
import { getLessonDisplayStatus, type LessonDisplayStatusInput } from '~/composables/useLessonDisplayStatus'
import {
  getCancellationSubscriptionImpact,
  getCancellationSubscriptionImpactClass,
} from '~/composables/useCancellationLabels'

type ParticipantType = 'student' | 'teacher'

type TabId = 'lessons' | 'contact' | 'subscriptions'

const props = defineProps<{
  show: boolean
  type: ParticipantType
  participantId: number | null
  fallbackName?: string
  /** Fiche élève : seulement l'onglet cours, sans overlay ni en-tête. */
  embedded?: boolean
}>()

defineEmits<{ close: [] }>()

const { $api } = useNuxtApp()

const loading = ref(false)
const error = ref('')
const planningFuture = ref(false)
const planFutureMessage = ref('')
const activeTab = ref<TabId>('lessons')
const lessonPeriodMode = ref<LessonPeriodMode>('all')

const periodFilterOptions = STUDENT_LESSON_PERIOD_FILTER_OPTIONS

const studentRecord = ref<Record<string, unknown> | null>(null)
const subscriptions = ref<any[]>([])
const allLessons = ref<any[]>([])
const teacherRecord = ref<Record<string, unknown> | null>(null)

const titleId = 'planning-participant-info-title'

const tabs = computed(() => {
  if (props.type === 'student') {
    return [
      { id: 'lessons' as TabId, label: 'Cours' },
      { id: 'contact' as TabId, label: 'Coordonnées' },
      { id: 'subscriptions' as TabId, label: 'Abonnements' },
    ]
  }
  return [
    { id: 'lessons' as TabId, label: 'Cours' },
    { id: 'contact' as TabId, label: 'Coordonnées' },
  ]
})

const teacherQuarterPeriod = computed(() => resolveUpcomingLessonPeriod('upcoming_quarter'))

const studentDisplayPeriod = computed(() => resolveFullLessonPeriod(lessonPeriodMode.value))

const lessonsPeriodLabel = computed(() => {
  if (props.type === 'student') {
    return studentDisplayPeriod.value.label
  }
  const q = getCalendarQuarterBounds()
  return `Trimestre en cours (T${q.quarter} ${q.year}) — ${teacherQuarterPeriod.value.label}`
})

const headerTitle = computed(() => {
  if (props.type === 'student' && studentRecord.value) {
    return participantDisplayNameFromStudent(studentRecord.value as Parameters<typeof participantDisplayNameFromStudent>[0])
  }
  if (props.type === 'teacher' && teacherRecord.value) {
    return participantDisplayNameFromTeacher(teacherRecord.value as Parameters<typeof participantDisplayNameFromTeacher>[0])
  }
  return props.fallbackName || (props.type === 'student' ? 'Élève' : 'Enseignant')
})

/** Cours affichés dans l'onglet Cours (élève : période choisie ; coach : à venir hors annulés). */
const displayedLessons = computed(() => {
  if (props.type === 'student') {
    const lessons = isFullHistoryPeriodMode(lessonPeriodMode.value)
      ? allLessons.value
      : allLessons.value.filter((lesson) => {
          const { from, to } = studentDisplayPeriod.value
          return lessonMatchesHistoryFilters(lesson, 'all', from, to)
        })

    return [...lessons].sort(
      (a, b) => new Date(a.start_time).getTime() - new Date(b.start_time).getTime(),
    )
  }

  const period = teacherQuarterPeriod.value
  return allLessons.value
    .filter((lesson) => {
      if (!lessonIsUpcoming(lesson)) return false
      if (lesson.status === 'cancelled') return false
      return lessonMatchesHistoryFilters(lesson, 'all', period.from, period.to)
    })
    .sort((a, b) => new Date(a.start_time).getTime() - new Date(b.start_time).getTime())
})

const nonMaintainedLessonsCount = computed(() =>
  displayedLessons.value.filter((lesson) => lessonIsNonMaintained(lesson) || lesson.deleted_at).length,
)

function lessonIsNonMaintained(lesson: { status?: string; is_on_closure_day?: boolean }) {
  return lesson.status === 'cancelled' || Boolean(lesson.is_on_closure_day)
}

const contactRows = computed(() => {
  if (props.type === 'student') {
    const s = studentRecord.value as {
      email?: string
      phone?: string
      date_of_birth?: string
      user?: { email?: string; phone?: string }
    } | null
    const user = s?.user
    const email = (s?.email || user?.email || '').toString().trim() || '—'
    const phone = formatParticipantPhone(s?.phone, user?.phone) || '—'
    const rows = [
      { label: 'E-mail', value: email, href: email.includes('@') ? `mailto:${email}` : undefined },
      { label: 'Téléphone', value: phone, href: phone !== '—' ? `tel:${phone.replace(/\s/g, '')}` : undefined },
    ]
    if (s?.date_of_birth) {
      rows.push({ label: 'Date de naissance', value: formatDateOnly(s.date_of_birth) })
    }
    return rows
  }

  const t = teacherRecord.value as {
    user?: {
      email?: string
      phone?: string
      city?: string
      postal_code?: string
    }
  } | null
  const user = t?.user
  const email = user?.email?.trim() || '—'
  const phone = formatParticipantPhone(user?.phone) || '—'
  const cityLine = [user?.postal_code, user?.city].filter(Boolean).join(' ').trim()
  const rows = [
    { label: 'E-mail', value: email, href: email.includes('@') ? `mailto:${email}` : undefined },
    { label: 'Téléphone', value: phone, href: phone !== '—' ? `tel:${phone.replace(/\s/g, '')}` : undefined },
  ]
  if (cityLine) {
    rows.push({ label: 'Localité', value: cityLine })
  }
  return rows
})

watch(
  () => [props.show, props.type, props.participantId] as const,
  ([visible, type, id]) => {
    if (!visible || !id) return
    activeTab.value = 'lessons'
    lessonPeriodMode.value = 'all'
    loadData(type, id)
  },
  // Immédiat : intégré dans la fiche élève, le composant est monté déjà visible.
  { immediate: true },
)

async function planFuture() {
  if (props.type !== 'student' || !props.participantId) return
  planningFuture.value = true
  planFutureMessage.value = ''
  try {
    const res = await $api.post(`/club/students/${props.participantId}/plan-future`)
    planFutureMessage.value = res.data?.message || 'Planning mis à jour.'
    await loadData('student', Number(props.participantId))
  } catch (e: any) {
    planFutureMessage.value = e?.response?.data?.message || 'La planification a échoué.'
  } finally {
    planningFuture.value = false
  }
}

async function loadData(type: ParticipantType, id: number) {
  loading.value = true
  error.value = ''
  studentRecord.value = null
  teacherRecord.value = null
  subscriptions.value = []
  allLessons.value = []

  try {
    if (type === 'student') {
      const res = await $api.get(`/club/students/${id}/history`)
      if (!res.data?.success) {
        throw new Error(res.data?.message || 'Impossible de charger la fiche élève')
      }
      const data = res.data.data
      studentRecord.value = data.student ?? null
      subscriptions.value = data.subscriptions ?? []
      allLessons.value = data.lessons ?? []
    } else {
      const period = teacherQuarterPeriod.value
      const from = period.from.toISOString().split('T')[0]
      const to = period.to.toISOString().split('T')[0]

      const [teachersRes, lessonsRes] = await Promise.all([
        $api.get('/club/teachers'),
        $api.get('/lessons', {
          params: { date_from: from, date_to: to, teacher_id: id, order: 'asc' },
        }),
      ])

      const teachers = teachersRes.data?.teachers ?? teachersRes.data?.data ?? []
      teacherRecord.value = teachers.find((t: { id: number }) => Number(t.id) === id) ?? null

      if (lessonsRes.data?.success) {
        allLessons.value = (lessonsRes.data.data ?? []).filter(
          (l: { teacher_id?: number }) => Number(l.teacher_id) === id,
        )
      }
    }
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } }; message?: string }
    error.value = err?.response?.data?.message || err?.message || 'Erreur de chargement'
  } finally {
    loading.value = false
  }
}

function formatLessonDate(iso: string): string {
  return new Intl.DateTimeFormat('fr-FR', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(iso))
}

function formatLessonTime(iso: string): string {
  return new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit' }).format(new Date(iso))
}

function formatDateOnly(iso: string): string {
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(iso))
}

function lessonStudentLabel(lesson: { student?: { user?: { name?: string } }; students?: Array<{ user?: { name?: string } }> }): string {
  if (lesson.student?.user?.name) return lesson.student.user.name
  if (lesson.students?.length) {
    return lesson.students.map((s) => s.user?.name).filter(Boolean).join(', ') || '—'
  }
  return '—'
}

function lessonTag(lesson: LessonDisplayStatusInput): { label: string; class: string } {
  const status = getLessonDisplayStatus(lesson)
  return { label: status.label, class: status.class }
}

/**
 * Cours annulé (fiche élève) : tag « Déduit » (rouge) / « Non déduit » (vert) de l'abonnement,
 * ou « En attente » (ambre) tant qu'un certificat médical n'est pas accepté.
 * Tableau vide ou d'un seul élément, pour un v-for sans balise conteneur.
 */
function cancellationImpactTags(lesson: {
  status?: string
  deleted_at?: string | null
  cancellation_reason?: string
  cancellation_certificate_status?: string
  cancellation_count_in_subscription?: boolean
}): { kind: 'deducted' | 'not-deducted' | 'pending'; label: string; class: string }[] {
  if (props.type !== 'student' || lesson.status !== 'cancelled' || lesson.deleted_at) return []
  const kind = lesson.cancellation_reason === 'medical' && lesson.cancellation_certificate_status !== 'accepted'
    ? 'pending'
    : (lesson.cancellation_count_in_subscription ? 'deducted' : 'not-deducted')
  return [{
    kind,
    label: getCancellationSubscriptionImpact(lesson),
    class: getCancellationSubscriptionImpactClass(lesson),
  }]
}

function subscriptionStatusLabel(status: string): string {
  const map: Record<string, string> = {
    active: 'Actif',
    completed: 'Terminé',
    expired: 'Expiré',
    cancelled: 'Annulé',
  }
  return map[status] ?? status
}

function subscriptionStatusClass(status: string): string {
  const map: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    completed: 'bg-gray-100 text-gray-800',
    expired: 'bg-red-100 text-red-800',
    cancelled: 'bg-yellow-100 text-yellow-800',
  }
  return map[status] ?? 'bg-gray-100 text-gray-800'
}

function subTotalLessons(sub: { remaining_bookable?: number; remaining_consumed?: number; remaining_lessons?: number; lessons_used?: number; subscription?: { template?: { total_lessons?: number; free_lessons?: number } } }): string {
  const template = sub.subscription?.template
  if (template) {
    return String((template.total_lessons || 0) + (template.free_lessons || 0))
  }
  const used = sub.lessons_used ?? 0
  const rem = sub.remaining_bookable ?? sub.remaining_consumed ?? sub.remaining_lessons ?? 0
  return String(used + rem)
}
</script>
