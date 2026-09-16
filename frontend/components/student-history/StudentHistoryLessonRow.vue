<template>
  <div
    :class="[
      'rounded-lg md:rounded-none p-4 md:py-3 border-b border-gray-100 last:border-b-0 md:grid md:grid-cols-12 md:gap-2 md:items-center transition-colors',
      showAsUncovered
        ? 'bg-red-50 border-red-200 md:border-0'
        : displayStatus.isDeleted
          ? 'bg-gray-100/80 hover:bg-gray-100 md:border-0 opacity-90'
          : displayStatus.isClosureDay
            ? 'bg-orange-50/40 hover:bg-orange-50/70 md:border-0'
            : isPast
              ? 'bg-gray-50/80 hover:bg-gray-100/80 md:border-0 opacity-95'
              : 'bg-white hover:bg-purple-50/40 md:border-0',
    ]"
  >
    <div class="md:col-span-2 mb-2 md:mb-0">
      <span class="text-xs text-gray-500 md:hidden">Date</span>
      <p class="font-semibold text-gray-900">{{ formatDateShort(lesson.start_time) }}</p>
      <p class="text-xs text-gray-500">{{ formatWeekday(lesson.start_time) }}</p>
    </div>
    <div class="md:col-span-1 mb-2 md:mb-0">
      <span class="text-xs text-gray-500 md:hidden">Heure</span>
      <p class="font-medium text-gray-900">
        {{ formatTimeOnly(lesson.start_time) }} – {{ formatTimeOnly(lesson.end_time) }}
      </p>
    </div>
    <div class="md:col-span-2 mb-2 md:mb-0">
      <span class="text-xs text-gray-500 md:hidden">Type</span>
      <p class="font-medium text-gray-900">{{ lesson.course_type?.name || 'Cours' }}</p>
      <div class="flex flex-wrap gap-1 mt-1">
        <span
          v-if="lesson.subscription_instances?.length"
          class="px-1.5 py-0.5 text-xs bg-green-100 text-green-800 rounded"
        >✓ Abo</span>
        <span v-else class="px-1.5 py-0.5 text-xs bg-orange-100 text-orange-800 rounded">Séance libre</span>
        <span
          v-if="showAsUncovered"
          class="px-1.5 py-0.5 text-xs font-bold bg-red-500 text-white rounded"
        >⚠️ Non couvert</span>
        <span
          v-else-if="lesson.subscription_coverage?.is_future && lesson.subscription_coverage?.is_covered"
          class="px-1.5 py-0.5 text-xs bg-blue-100 text-blue-800 rounded"
        >✓ Couvert</span>
        <span :class="displayStatus.class" class="px-1.5 py-0.5 text-xs rounded md:hidden">
          {{ displayStatus.label }}
        </span>
        <span
          v-if="lesson.status === 'cancelled' && (lesson.cancellation_count_in_subscription !== undefined || lesson.cancellation_reason === 'medical')"
          :class="getCancellationSubscriptionImpactClass(lesson)"
          class="px-1.5 py-0.5 text-xs rounded"
        >
          {{ getCancellationSubscriptionImpact(lesson) }}
        </span>
      </div>
    </div>
    <div class="md:col-span-2 mb-2 md:mb-0">
      <span class="text-xs text-gray-500 md:hidden">Enseignant</span>
      <p class="text-gray-900">{{ lesson.teacher?.user?.name || '—' }}</p>
    </div>
    <div class="hidden md:block md:col-span-1">
      <span :class="statusPillClass">{{ displayStatus.label }}</span>
    </div>
    <div class="md:col-span-1 mb-2 md:mb-0">
      <span class="text-xs text-gray-500 md:hidden">Prix</span>
      <p class="text-gray-900">{{ formatPrice(lesson.price || 0) }} €</p>
    </div>
    <div class="md:col-span-2 mb-2 md:mb-0">
      <span class="text-xs text-gray-500 md:hidden">Lieu</span>
      <p class="text-gray-700 text-sm">{{ lesson.location?.name || '—' }}</p>
    </div>
    <div class="md:col-span-1 flex justify-end items-start gap-2 mt-2 md:mt-0 flex-wrap">
      <template v-if="lesson.status === 'cancelled' && lesson.cancellation_reason === 'medical' && lesson.cancellation_certificate_path">
        <span
          :class="certificateStatusClass(lesson.cancellation_certificate_status)"
          class="px-1.5 py-0.5 text-xs font-medium rounded"
        >
          {{ getCertificateStatusLabel(lesson.cancellation_certificate_status) }}
        </span>
        <button
          type="button"
          class="px-2 py-1 text-xs text-blue-600 hover:underline"
          @click="emit('download-certificate', lesson.id)"
        >
          Télécharger
        </button>
        <template v-if="lesson.cancellation_certificate_status === 'pending'">
          <button
            type="button"
            class="px-2 py-1 text-xs font-medium text-white bg-emerald-600 rounded hover:bg-emerald-700 disabled:opacity-50"
            :disabled="certificateActionLoading === lesson.id"
            @click="emit('accept-certificate', lesson)"
          >
            Accepter
          </button>
          <button
            type="button"
            class="px-2 py-1 text-xs font-medium text-white bg-red-600 rounded hover:bg-red-700 disabled:opacity-50"
            :disabled="certificateActionLoading === lesson.id"
            @click="emit('reject-certificate', lesson)"
          >
            Refuser
          </button>
        </template>
      </template>
      <button
        v-if="lesson.status !== 'cancelled' || !lesson.cancellation_certificate_path"
        type="button"
        class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        title="Modifier la déduction d'abonnement"
        @click="emit('edit', lesson)"
      >
        Modifier
      </button>
    </div>
    <div
      v-if="showAsUncovered"
      class="md:col-span-12 mt-2 bg-red-100 border border-red-200 rounded p-2 text-red-800 text-sm"
    >
      {{ lesson.subscription_coverage?.warning || 'Cours futur non couvert par un abonnement actif.' }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  getCancellationSubscriptionImpact,
  getCancellationSubscriptionImpactClass,
  getCertificateStatusLabel,
} from '~/composables/useCancellationLabels'
import {
  getLessonDisplayStatus,
  getLessonDisplayStatusPillClassFromStatus,
  type LessonDisplayStatusInput,
} from '~/composables/useLessonDisplayStatus'

/** Cours tel qu’exposé par GET /club/students/:id/history pour la liste historique. */
export interface StudentHistoryLessonRowModel extends LessonDisplayStatusInput {
  id: number
  start_time: string
  end_time: string
  price?: number | string | null
  course_type?: { name?: string | null } | null
  teacher?: { user?: { name?: string | null } | null } | null
  location?: { name?: string | null } | null
  subscription_instances?: unknown[] | null
  subscription_coverage?: {
    is_future?: boolean
    is_covered?: boolean
    warning?: string | null
  } | null
  cancellation_count_in_subscription?: boolean
  cancellation_certificate_path?: string | null
  cancellation_certificate_status?: string | null
}

const props = defineProps<{
  lesson: StudentHistoryLessonRowModel
  uncovered?: boolean
  isPast?: boolean
  certificateActionLoading?: number | null
}>()

const emit = defineEmits<{
  edit: [lesson: StudentHistoryLessonRowModel]
  'download-certificate': [lessonId: number]
  'accept-certificate': [lesson: StudentHistoryLessonRowModel]
  'reject-certificate': [lesson: StudentHistoryLessonRowModel]
}>()

const displayStatus = computed(() => getLessonDisplayStatus(props.lesson))
const statusPillClass = computed(() =>
  getLessonDisplayStatusPillClassFromStatus(displayStatus.value),
)
/** Un jour de fermeture n'est pas une séance « non couverte » à alerter. */
const showAsUncovered = computed(
  () => Boolean(props.uncovered) && !displayStatus.value.isClosureDay && !displayStatus.value.isDeleted,
)

function formatDateShort(dateTime: string) {
  return new Date(dateTime).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
}

function formatWeekday(dateTime: string) {
  return new Date(dateTime).toLocaleDateString('fr-FR', { weekday: 'long' })
}

function formatTimeOnly(dateTime: string) {
  return new Date(dateTime).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function formatPrice(price: number | string) {
  const num = typeof price === 'string' ? parseFloat(price) : price
  return Number.isNaN(num) ? '0.00' : num.toFixed(2)
}

function certificateStatusClass(status: string | null | undefined) {
  const map: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-800',
    accepted: 'bg-emerald-100 text-emerald-800',
    rejected: 'bg-red-100 text-red-800',
    closed: 'bg-gray-100 text-gray-700',
  }
  return (status && map[status]) || 'bg-gray-100 text-gray-700'
}
</script>
