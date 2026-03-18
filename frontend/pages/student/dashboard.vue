<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Indicateur de chargement -->
    <div v-if="isLoading" class="fixed inset-0 bg-white bg-opacity-75 flex items-center justify-center z-50">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <p class="text-gray-600">Chargement de votre dashboard...</p>
      </div>
    </div>

    <!-- Contenu principal -->
    <div v-if="!isLoading" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-6 md:mb-8">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
          Bienvenue{{ studentFirstName ? ` ${studentFirstName}` : '' }}
        </h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">
          Consultez vos prochains cours et votre planning
        </p>
      </div>

      <!-- Vue globale ou par élève (si plusieurs élèves liés) -->
      <StudentViewSwitcher @scope-changed="refreshData" />

      <!-- Stats principales -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Prochain cours -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
          <div class="flex items-start justify-between">
            <div class="flex items-start flex-1">
              <div class="p-3 bg-blue-100 rounded-lg flex-shrink-0">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <div class="ml-4 flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-600 mb-2">Prochain cours</p>
                <div v-if="nextLesson" class="space-y-1">
                  <p class="text-sm font-semibold text-gray-900">{{ formatDayDate(nextLesson.start_time) }}</p>
                  <p class="text-sm text-gray-700">{{ formatTime(nextLesson.start_time) }}</p>
                  <p class="text-xs text-gray-600">{{ nextLesson.teacher?.user?.name || nextLesson.teacher?.name || 'Enseignant' }}</p>
                </div>
                <div v-else class="text-sm text-gray-500">
                  Aucun cours à venir
                </div>
              </div>
            </div>
            <NuxtLink to="/student/schedule" class="text-blue-600 hover:text-blue-800 text-sm font-medium ml-4 flex-shrink-0">
              Voir →
            </NuxtLink>
          </div>
        </div>

        <!-- Abonnements actifs -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="p-3 bg-emerald-100 rounded-lg">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Abonnements actifs</p>
                <p class="text-2xl font-semibold text-gray-900">{{ activeSubscriptions.length }}</p>
              </div>
            </div>
            <NuxtLink to="/student/subscriptions" class="text-emerald-600 hover:text-emerald-800 text-sm font-medium">
              Voir →
            </NuxtLink>
          </div>
        </div>
      </div>

      <!-- Annulations du mois (en premier) -->
      <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-red-50 to-rose-50">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Annulations du mois</h3>
            <NuxtLink to="/student/schedule" class="text-red-600 hover:text-red-800 text-sm font-medium">
              Voir mon planning →
            </NuxtLink>
          </div>
          <p class="text-sm text-gray-600 mt-1">{{ currentMonthLabel }}</p>
        </div>
        <div class="p-6 space-y-3">
          <div v-if="cancellationsThisMonth.length === 0" class="text-center text-gray-500 py-6">
            Aucune annulation ce mois-ci.
          </div>
          <div
            v-for="lesson in cancellationsThisMonth"
            :key="`cancelled-month-${lesson.id}`"
            class="flex flex-col md:flex-row md:items-center gap-3 p-4 bg-red-50 rounded-lg border border-red-100"
          >
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <p class="font-medium text-gray-900 line-through">
                  {{ lesson.course_type?.name || lesson.courseType?.name || 'Cours' }}
                </p>
                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                  Annulé
                </span>
                <span
                  v-if="lesson.cancellation_reason === 'medical'"
                  class="px-2 py-1 text-xs font-medium bg-blue-50 text-blue-800 rounded-full"
                >
                  Raison médicale
                </span>
                <span
                  :class="getCancellationSubscriptionImpactClass(lesson)"
                  class="px-2 py-1 text-xs font-medium rounded-full"
                >
                  {{ getCancellationSubscriptionImpact(lesson) }}
                </span>
                <span
                  v-if="shouldShowCertificateStatusBadge(lesson)"
                  :class="getCertificateStatusClass(lesson.cancellation_certificate_status)"
                  class="px-2 py-1 text-xs font-medium rounded-full"
                >
                  {{ getCertificateStatusLabel(lesson.cancellation_certificate_status) }}
                </span>
              </div>
              <p v-if="studentScopeStore.apiScopeParam === 'all' && (lesson.student?.user?.name || lesson.student?.name)" class="text-xs text-gray-500 mt-1">
                {{ lesson.student?.user?.name || lesson.student?.name }}
              </p>
              <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-sm text-gray-600">
                <span>{{ formatFullDate(lesson.start_time) }}</span>
                <span>{{ formatTime(lesson.start_time) }}</span>
                <span>{{ lesson.teacher?.user?.name || lesson.teacher?.name || 'Enseignant' }}</span>
              </div>
              <div v-if="lesson.cancellation_reason === 'medical' && (lesson.cancellation_certificate_status === 'rejected' || lesson.cancellation_certificate_status === 'closed')" class="mt-3 pt-3 border-t border-red-200">
                <p class="text-xs text-gray-600 mb-2">
                  <template v-if="lesson.cancellation_certificate_status === 'rejected'">Motif du refus :</template>
                  <template v-else>Raison de la clôture :</template>
                  {{ lesson.cancellation_certificate_rejection_reason || '—' }}
                </p>
                <div v-if="lesson.cancellation_certificate_status === 'rejected'" class="flex flex-wrap items-center gap-2">
                  <button
                    type="button"
                    @click="openResubmitCertificate(lesson)"
                    :disabled="resubmitLoading === lesson.id"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 disabled:opacity-50"
                  >
                    <span v-if="resubmitLoading === lesson.id" class="animate-spin">⏳</span>
                    <span>{{ resubmitLoading === lesson.id ? 'Envoi...' : 'Choisir un nouveau fichier et renvoyer le certificat' }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Cours à venir (détails) -->
      <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Cours à venir</h3>
            <NuxtLink to="/student/schedule" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
              Voir mon planning complet →
            </NuxtLink>
          </div>
        </div>
        <div class="p-6">
          <div v-if="upcomingLessons.length === 0" class="text-center text-gray-500 py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="text-lg mb-2">Aucun cours à venir</p>
            <p class="text-sm text-gray-400">Vos prochains cours apparaîtront ici</p>
          </div>
          <div v-else class="space-y-3">
            <div 
              v-for="lesson in upcomingLessons" 
              :key="lesson.id"
              :class="lesson.status === 'cancelled' ? 'bg-red-50 border border-red-100' : 'bg-gray-50 hover:bg-gray-100'"
              class="flex flex-col md:flex-row md:items-center gap-3 p-4 rounded-lg transition-colors"
            >
              <div class="flex items-start md:items-center gap-3 flex-1 min-w-0 overflow-hidden">
                <div :class="lesson.status === 'cancelled' ? 'bg-red-100' : 'bg-blue-100'" class="p-2.5 rounded-lg flex-shrink-0">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="flex-1 min-w-0 overflow-hidden">
                  <div class="flex items-center gap-2 flex-wrap">
                    <p :class="lesson.status === 'cancelled' ? 'line-through text-gray-700' : 'text-gray-900'" class="font-medium truncate">
                      {{ lesson.course_type?.name || lesson.courseType?.name || 'Cours' }}
                    </p>
                    <span
                      v-if="lesson.status === 'cancelled'"
                      class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full"
                    >
                      Annulé
                    </span>
                  </div>
                  <p v-if="studentScopeStore.apiScopeParam === 'all' && (lesson.student?.user?.name || lesson.student?.name)" class="text-xs text-gray-500 mt-0.5 truncate">
                    {{ lesson.student?.user?.name || lesson.student?.name }}
                  </p>
                  <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-sm text-gray-600">
                    <span class="flex items-center shrink-0">
                      <svg class="w-4 h-4 mr-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      {{ formatFullDate(lesson.start_time) }}
                    </span>
                    <span class="flex items-center shrink-0">
                      <svg class="w-4 h-4 mr-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      {{ formatTime(lesson.start_time) }}
                    </span>
                    <span class="flex items-center min-w-0 max-w-full">
                      <svg class="w-4 h-4 mr-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      <span class="truncate">{{ lesson.teacher?.user?.name || lesson.teacher?.name || 'Enseignant' }}</span>
                    </span>
                  </div>
                </div>
              </div>
              <div v-if="lesson.status !== 'cancelled'" class="flex justify-end md:justify-start shrink-0 pt-1 md:pt-0 border-t border-gray-200/60 md:border-t-0 md:border-none w-full md:w-auto">
                <button
                  @click.stop="openCancelModal(lesson)"
                  class="min-h-[44px] w-full md:w-auto md:min-w-[100px] inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors"
                >
                  <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                  <span>Annuler</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Abonnements actifs -->
      <div v-if="activeSubscriptions.length > 0" class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-emerald-50 to-green-50">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Mes abonnements actifs</h3>
            <NuxtLink to="/student/subscriptions" class="text-emerald-600 hover:text-emerald-800 text-sm font-medium">
              Voir tous mes abonnements →
            </NuxtLink>
          </div>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div 
              v-for="subscription in activeSubscriptions.slice(0, 2)" 
              :key="subscription.id"
              class="p-4 bg-gray-50 rounded-lg border border-gray-200"
            >
              <div class="flex items-start justify-between mb-2">
                <div>
                  <p class="font-semibold text-gray-900">{{ subscription.subscription?.subscription?.name || 'Abonnement' }}</p>
                  <p v-if="subscription.subscription?.subscription_number" class="text-xs text-gray-500 mt-1">
                    Réf: {{ subscription.subscription.subscription_number }}
                  </p>
                </div>
                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                  Actif
                </span>
              </div>
              <div class="mt-3">
                <div class="flex items-center justify-between text-sm mb-1">
                  <span class="text-gray-600">Cours utilisés</span>
                  <span class="font-semibold text-gray-900">
                    {{ subscription.lessons_used }} / {{ getTotalLessons(subscription) }}
                  </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div 
                    class="bg-emerald-600 h-2 rounded-full transition-all"
                    :style="{ width: `${getUsagePercentage(subscription)}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions rapides -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Action rapide vers le planning -->
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-xl font-semibold mb-2">Consultez votre planning</h3>
              <p class="text-blue-100">Visualisez tous vos cours et réservations en un coup d'œil</p>
            </div>
            <NuxtLink 
              to="/student/schedule"
              class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-blue-50 transition-colors shadow-md hover:shadow-lg"
            >
              Voir mon planning →
            </NuxtLink>
          </div>
        </div>

        <!-- Action rapide vers les abonnements -->
        <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-xl font-semibold mb-2">Gérez vos abonnements</h3>
              <p class="text-emerald-100">Souscrivez à un nouvel abonnement ou consultez vos abonnements actifs</p>
            </div>
            <NuxtLink 
              to="/student/subscriptions/subscribe"
              class="bg-white text-emerald-600 px-6 py-3 rounded-lg font-semibold hover:bg-emerald-50 transition-colors shadow-md hover:shadow-lg"
            >
              Créer un abonnement →
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>

    <!-- Modale d'annulation -->
    <CancelLessonModal
      v-if="selectedLessonForCancel"
      :lesson="selectedLessonForCancel"
      @close="closeCancelModal"
      @success="handleCancelSuccess"
    />

    <!-- Input caché pour renvoi de certificat -->
    <input
      ref="resubmitFileInputRef"
      type="file"
      accept=".pdf,image/jpeg,image/jpg,image/png"
      class="hidden"
      @change="onResubmitCertificateFile"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useStudentData } from '~/composables/useStudentData'
import { useAuthStore } from '~/stores/auth'
import {
  getCancellationSubscriptionImpact,
  getCancellationSubscriptionImpactClass,
  getCertificateStatusLabel,
  getCertificateStatusClass,
  shouldShowCertificateStatusBadge,
} from '~/composables/useCancellationLabels'
import StudentViewSwitcher from '~/components/student/StudentViewSwitcher.vue'

definePageMeta({
  middleware: ['auth', 'student'],
  layout: 'student'
})

const { $api } = useNuxtApp()
const authStore = useAuthStore()
const studentScopeStore = useStudentScopeStore()

// State
const upcomingLessons = ref<any[]>([])
const cancelledLessons = ref<any[]>([])
const cancellationsThisMonth = ref<any[]>([])
const activeSubscriptions = ref<any[]>([])
const isLoading = ref(true)
const selectedLessonForCancel = ref<any>(null)
const resubmitFileInputRef = ref<HTMLInputElement | null>(null)
const currentResubmitLesson = ref<any>(null)
const resubmitLoading = ref<number | null>(null)

// State pour le prénom (chargé depuis le profil)
const studentFirstName = ref<string>('')

// Fonction pour extraire le prénom depuis différentes sources
const extractFirstName = (data: any): string => {
  // Priorité 1: student.first_name
  if (data?.student?.first_name) {
    return data.student.first_name
  }
  // Priorité 2: user.first_name
  if (data?.user?.first_name) {
    return data.user.first_name
  }
  // Priorité 3: premier mot de user.name
  if (data?.user?.name) {
    const nameParts = data.user.name.trim().split(/\s+/)
    return nameParts[0] || ''
  }
  // Fallback: depuis le store auth
  if (authStore.user?.first_name) {
    return authStore.user.first_name
  }
  if (authStore.user?.name) {
    const nameParts = authStore.user.name.trim().split(/\s+/)
    return nameParts[0] || ''
  }
  return ''
}

// Computed pour le prochain cours
const nextLesson = computed(() => {
  if (upcomingLessons.value.length === 0) return null
  return upcomingLessons.value.find((lesson: any) => lesson.status !== 'cancelled') || null
})

// Libellé du mois en cours (pour la section Annulations du mois)
const currentMonthLabel = computed(() => {
  const d = new Date()
  return d.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
})

// Methods
const loadUpcomingLessons = async () => {
  try {
    const params = { active_student_id: studentScopeStore.apiScopeParam }
    const response = await $api.get('/student/bookings', { params })
    if (response.data.success) {
      const bookings = response.data.data || []
      const now = new Date()
      const oneMonthLater = new Date(now)
      oneMonthLater.setMonth(oneMonthLater.getMonth() + 1)
      upcomingLessons.value = bookings
        .filter((lesson: any) => {
          if (!lesson.start_time) return false
          const lessonDate = new Date(lesson.start_time)
          return lessonDate > now && lessonDate <= oneMonthLater && ['confirmed', 'pending', 'cancelled'].includes(lesson.status)
        })
        .sort((a: any, b: any) => {
          const dateA = new Date(a.start_time)
          const dateB = new Date(b.start_time)
          return dateA.getTime() - dateB.getTime()
        })
      cancelledLessons.value = bookings
        .filter((lesson: any) => {
          if (!lesson.start_time) return false
          const lessonDate = new Date(lesson.start_time)
          return lessonDate > now && lessonDate <= oneMonthLater && lesson.status === 'cancelled'
        })
        .sort((a: any, b: any) => {
          const dateA = new Date(b.start_time)
          const dateB = new Date(a.start_time)
          return dateA.getTime() - dateB.getTime()
        })
      cancellationsThisMonth.value = bookings
        .filter((lesson: any) => {
          if (!lesson.start_time || lesson.status !== 'cancelled') return false
          const d = new Date(lesson.start_time)
          const today = new Date()
          return d.getFullYear() === today.getFullYear() && d.getMonth() === today.getMonth()
        })
        .sort((a: any, b: any) => {
          const dateA = new Date(b.start_time)
          const dateB = new Date(a.start_time)
          return dateA.getTime() - dateB.getTime()
        })
    }
  } catch (err) {
    console.error('Error loading upcoming lessons:', err)
    // Fallback: essayer lesson-history
    try {
      const { loadLessonHistory } = useStudentData()
      const history = await loadLessonHistory(20)
      const now = new Date()
      const oneMonthLater = new Date(now)
      oneMonthLater.setMonth(oneMonthLater.getMonth() + 1)
      upcomingLessons.value = history
        .filter((lesson: any) => {
          if (!lesson.start_time) return false
          const lessonDate = new Date(lesson.start_time)
          return lessonDate > now && lessonDate <= oneMonthLater && ['confirmed', 'pending', 'cancelled'].includes(lesson.status)
        })
        .sort((a: any, b: any) => {
          const dateA = new Date(a.start_time)
          const dateB = new Date(b.start_time)
          return dateA.getTime() - dateB.getTime()
        })
      cancelledLessons.value = history
        .filter((lesson: any) => {
          if (!lesson.start_time) return false
          const lessonDate = new Date(lesson.start_time)
          return lessonDate > now && lessonDate <= oneMonthLater && lesson.status === 'cancelled'
        })
        .sort((a: any, b: any) => {
          const dateA = new Date(b.start_time)
          const dateB = new Date(a.start_time)
          return dateA.getTime() - dateB.getTime()
        })
      const today = new Date()
      cancellationsThisMonth.value = history
        .filter((lesson: any) => {
          if (!lesson.start_time || lesson.status !== 'cancelled') return false
          const d = new Date(lesson.start_time)
          return d.getFullYear() === today.getFullYear() && d.getMonth() === today.getMonth()
        })
        .sort((a: any, b: any) => {
          const dateA = new Date(b.start_time)
          const dateB = new Date(a.start_time)
          return dateA.getTime() - dateB.getTime()
        })
    } catch (historyErr) {
      console.error('Error loading from history:', historyErr)
    }
  }
}

const loadActiveSubscriptions = async () => {
  try {
    const params = { active_student_id: studentScopeStore.apiScopeParam }
    const response = await $api.get('/student/subscriptions', { params })
    if (response.data.success) {
      const subscriptions = response.data.data || []
      activeSubscriptions.value = subscriptions.filter((sub: any) => sub.status === 'active')
    }
  } catch (err) {
    console.error('Error loading subscriptions:', err)
  }
}

const loadStudentProfile = async () => {
  try {
    const response = await $api.get('/student/profile')
    if (response.data.success && response.data.data) {
      const firstName = extractFirstName(response.data.data)
      if (firstName) {
        studentFirstName.value = firstName
      }
    }
  } catch (err) {
    console.error('Error loading student profile:', err)
    // Fallback: utiliser le store auth
    const firstName = extractFirstName({ user: authStore.user })
    if (firstName) {
      studentFirstName.value = firstName
    }
  }
}

const formatDate = (dateString: string | null): string => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short'
  })
}

const formatFullDate = (dateString: string | null): string => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

const formatTime = (dateString: string | null): string => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const openResubmitCertificate = (lesson: any) => {
  currentResubmitLesson.value = lesson
  resubmitFileInputRef.value?.click()
}

const onResubmitCertificateFile = async (e: Event) => {
  const lesson = currentResubmitLesson.value
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  currentResubmitLesson.value = null
  if (!lesson || !file) return
  try {
    resubmitLoading.value = lesson.id
    const formData = new FormData()
    formData.append('cancellation_certificate', file)
    if (studentScopeStore.apiScopeParam && studentScopeStore.apiScopeParam !== 'all') {
      formData.append('active_student_id', String(studentScopeStore.apiScopeParam))
    }
    const url = `/student/bookings/${lesson.id}/cancellation-certificate/resubmit`
    const response = await $api.post(url, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    if (response.data?.success) {
      await loadUpcomingLessons()
    } else {
      alert(response.data?.message || 'Erreur lors de l\'envoi du certificat.')
    }
  } catch (err: any) {
    const msg = err.response?.data?.message ?? err.response?.data?.errors?.cancellation_certificate?.[0] ?? 'Erreur lors de l\'envoi du certificat.'
    alert(msg)
  } finally {
    resubmitLoading.value = null
  }
}

const formatDayDate = (dateString: string | null): string => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

const getTotalLessons = (subscription: any): number => {
  const template = subscription.subscription?.template || subscription.subscription?.subscription?.template
  if (!template) return 0
  return (template.total_lessons || 0) + (template.free_lessons || 0)
}

const getUsagePercentage = (subscription: any): number => {
  const total = getTotalLessons(subscription)
  if (total === 0) return 0
  return Math.round((subscription.lessons_used / total) * 100)
}

const openCancelModal = (lesson: any) => {
  selectedLessonForCancel.value = lesson
}

const closeCancelModal = () => {
  selectedLessonForCancel.value = null
}

const handleCancelSuccess = () => {
  closeCancelModal()
  loadUpcomingLessons()
}

const refreshData = async () => {
  try {
    await Promise.all([
      loadUpcomingLessons(),
      loadActiveSubscriptions()
    ])
  } catch (error) {
    console.error('Erreur lors du rechargement des données:', error)
  }
}

onMounted(async () => {
  try {
    isLoading.value = true
    if (!authStore.user) {
      await authStore.fetchUser()
    }
    await studentScopeStore.loadLinkedAccounts()
    await loadStudentProfile()
    await Promise.all([
      loadUpcomingLessons(),
      loadActiveSubscriptions()
    ])
  } catch (error) {
    console.error('Error loading dashboard data:', error)
  } finally {
    isLoading.value = false
  }
})
</script>
