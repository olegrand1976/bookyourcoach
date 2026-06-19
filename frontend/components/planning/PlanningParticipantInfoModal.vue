<template>
  <div
    v-if="show && participantId"
    class="fixed inset-0 z-[60] overflow-y-auto"
    role="dialog"
    aria-modal="true"
    :aria-labelledby="titleId"
    @click.self="$emit('close')"
  >
    <div class="flex min-h-screen items-center justify-center px-4 py-8">
      <div class="fixed inset-0 bg-black/50" aria-hidden="true" @click="$emit('close')" />

      <div class="relative w-full max-w-3xl max-h-[90vh] flex flex-col rounded-xl bg-white shadow-xl overflow-hidden">
        <div
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

        <div class="flex-1 overflow-y-auto p-6 min-h-[12rem]">
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
              <p class="text-sm text-gray-600">
                {{ quarterPeriodLabel }}
              </p>
              <p
                v-if="type === 'student' && cancelledLessonsCount > 0"
                class="text-xs text-red-700 bg-red-50 border border-red-100 rounded-lg px-3 py-2"
              >
                {{ cancelledLessonsCount }} cours annulé{{ cancelledLessonsCount > 1 ? 's' : '' }} sur la période
              </p>
              <p v-if="displayedLessons.length === 0" class="text-sm text-gray-500 py-8 text-center bg-gray-50 rounded-lg border border-gray-200">
                {{ type === 'student' ? 'Aucun cours sur ce trimestre.' : 'Aucun cours prévu sur cette période.' }}
              </p>
              <ul v-else class="divide-y divide-gray-100 border border-gray-200 rounded-lg overflow-hidden">
                <li
                  v-for="lesson in displayedLessons"
                  :key="lesson.id"
                  class="px-4 py-3 hover:bg-gray-50/80 text-sm"
                  :class="lesson.status === 'cancelled' ? 'bg-red-50/40' : ''"
                >
                  <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                      <p class="font-medium text-gray-900">
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
                    <span
                      class="inline-flex shrink-0 px-2 py-0.5 rounded-full text-xs font-medium"
                      :class="statusBadgeClass(lesson.status)"
                    >
                      {{ statusLabel(lesson.status) }}
                    </span>
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
                  <h4 class="font-semibold text-gray-900">
                    {{ sub.subscription?.template?.name || sub.subscription?.name || `Abonnement #${sub.id}` }}
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
import {
  getCalendarQuarterBounds,
  lessonIsUpcoming,
  lessonMatchesHistoryFilters,
  resolveUpcomingLessonPeriod,
} from '~/composables/useStudentLessonHistoryFilters'
import {
  participantDisplayNameFromStudent,
  participantDisplayNameFromTeacher,
  formatParticipantPhone,
} from '~/composables/planning/usePlanningParticipant'

type ParticipantType = 'student' | 'teacher'

type TabId = 'lessons' | 'contact' | 'subscriptions'

const props = defineProps<{
  show: boolean
  type: ParticipantType
  participantId: number | null
  fallbackName?: string
}>()

defineEmits<{ close: [] }>()

const { $api } = useNuxtApp()

const loading = ref(false)
const error = ref('')
const activeTab = ref<TabId>('lessons')

const studentRecord = ref<Record<string, unknown> | null>(null)
const subscriptions = ref<any[]>([])
const allLessons = ref<any[]>([])
const teacherRecord = ref<Record<string, unknown> | null>(null)

const titleId = 'planning-participant-info-title'

const tabs = computed(() => {
  if (props.type === 'student') {
    return [
      { id: 'lessons' as TabId, label: 'Cours du trimestre' },
      { id: 'contact' as TabId, label: 'Coordonnées' },
      { id: 'subscriptions' as TabId, label: 'Abonnements' },
    ]
  }
  return [
    { id: 'lessons' as TabId, label: 'Cours' },
    { id: 'contact' as TabId, label: 'Coordonnées' },
  ]
})

const quarterPeriod = computed(() => resolveUpcomingLessonPeriod('upcoming_quarter'))

const quarterPeriodLabel = computed(() => {
  const q = getCalendarQuarterBounds()
  if (props.type === 'student') {
    return `Trimestre en cours (T${q.quarter} ${q.year}) — cours prévus, passés et annulés`
  }
  return `Trimestre en cours (T${q.quarter} ${q.year}) — ${quarterPeriod.value.label}`
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

/** Cours affichés dans l'onglet Cours (élève : tout le trimestre dont annulés ; coach : à venir hors annulés). */
const displayedLessons = computed(() => {
  if (props.type === 'student') {
    const bounds = getCalendarQuarterBounds()
    return allLessons.value
      .filter((lesson) => lessonMatchesHistoryFilters(lesson, 'all', bounds.start, bounds.end))
      .sort((a, b) => new Date(a.start_time).getTime() - new Date(b.start_time).getTime())
  }

  const period = quarterPeriod.value
  return allLessons.value
    .filter((lesson) => {
      if (!lessonIsUpcoming(lesson)) return false
      if (lesson.status === 'cancelled') return false
      return lessonMatchesHistoryFilters(lesson, 'all', period.from, period.to)
    })
    .sort((a, b) => new Date(a.start_time).getTime() - new Date(b.start_time).getTime())
})

const cancelledLessonsCount = computed(() => {
  if (props.type !== 'student') return 0
  return displayedLessons.value.filter((lesson) => lesson.status === 'cancelled').length
})

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
    loadData(type, id)
  },
)

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
      const period = quarterPeriod.value
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

function statusLabel(status: string): string {
  const map: Record<string, string> = {
    confirmed: 'Confirmé',
    pending: 'En attente',
    completed: 'Terminé',
    cancelled: 'Annulé',
  }
  return map[status] ?? status
}

function statusBadgeClass(status: string): string {
  const map: Record<string, string> = {
    confirmed: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-gray-100 text-gray-700',
    cancelled: 'bg-red-100 text-red-800',
  }
  return map[status] ?? 'bg-blue-100 text-blue-800'
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

function subTotalLessons(sub: { remaining_lessons?: number; lessons_used?: number; subscription?: { template?: { total_lessons?: number; free_lessons?: number } } }): string {
  const template = sub.subscription?.template
  if (template) {
    return String((template.total_lessons || 0) + (template.free_lessons || 0))
  }
  const used = sub.lessons_used ?? 0
  const rem = sub.remaining_lessons ?? 0
  return String(used + rem)
}
</script>
