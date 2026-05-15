<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Cours annulés</h1>
            <p class="text-gray-600 mt-1">
              Consultez qui a annulé et réactivez un cours ou une série récurrente.
            </p>
          </div>
          <div>
            <NuxtLink
              to="/club/planning"
              class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium"
            >
              ← Planning
            </NuxtLink>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-4">
          <h2 class="text-sm font-semibold text-gray-700">Filtres</h2>
          <button
            type="button"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
            :disabled="loading"
            @click="resetFilters"
          >
            Réinitialiser
          </button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Du</label>
            <input
              v-model="filters.dateFrom"
              type="date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
              @change="loadLessons(1)"
            >
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Au</label>
            <input
              v-model="filters.dateTo"
              type="date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
              @change="loadLessons(1)"
            >
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Annulé par</label>
            <select
              v-model="filters.cancelledByRole"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
              @change="loadLessons(1)"
            >
              <option value="">Tous</option>
              <option value="student">Élève</option>
              <option value="club">Club</option>
              <option value="teacher">Enseignant</option>
              <option value="unknown">Inconnu</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Recherche</label>
            <input
              v-model="filters.search"
              type="search"
              placeholder="Nom, motif…"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
            >
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-gray-500">
          Chargement…
        </div>

        <div v-else-if="lessons.length === 0" class="p-12 text-center text-gray-500">
          Aucun cours annulé sur cette période.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2.5 text-left font-medium text-gray-600">Date / heure</th>
                <th class="px-3 py-2.5 text-left font-medium text-gray-600">Élève</th>
                <th class="px-3 py-2.5 text-left font-medium text-gray-600">Enseignant</th>
                <th class="px-3 py-2.5 text-left font-medium text-gray-600">Annulé par</th>
                <th class="px-3 py-2.5 text-left font-medium text-gray-600">Annulé le</th>
                <th class="px-3 py-2.5 text-left font-medium text-gray-600">Motif</th>
                <th class="px-3 py-2.5 text-right font-medium text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="lesson in lessons"
                :key="lesson.id"
                class="hover:bg-gray-50/80"
              >
                <td class="px-3 py-2.5 whitespace-nowrap">
                  <span :class="lesson.is_past ? 'text-gray-500' : 'text-gray-900 font-medium'">
                    {{ formatLessonDate(lesson.start_time) }}
                  </span>
                  <span v-if="lesson.is_past" class="ml-1 text-xs text-gray-400">(passé)</span>
                </td>
                <td class="px-3 py-2.5">{{ lesson.student_name }}</td>
                <td class="px-3 py-2.5">{{ lesson.teacher_name }}</td>
                <td class="px-3 py-2.5">
                  {{ lesson.cancelled_by?.name || lesson.cancelled_by_label }}
                </td>
                <td class="px-3 py-2.5 whitespace-nowrap text-gray-600">
                  {{ formatCancelledAt(lesson.cancelled_at) }}
                </td>
                <td class="px-3 py-2.5 max-w-xs">
                  <span class="line-clamp-2" :title="lesson.notes_excerpt || ''">
                    {{ lesson.notes_excerpt || '—' }}
                  </span>
                  <span
                    v-if="lesson.cancellation_certificate_status === 'pending'"
                    class="inline-block mt-1 text-xs bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded"
                  >
                    Certificat en attente
                  </span>
                  <span
                    v-if="lesson.recurring_slot?.status === 'cancelled'"
                    class="inline-block mt-1 text-xs bg-violet-100 text-violet-800 px-1.5 py-0.5 rounded ml-1"
                  >
                    Récurrence annulée
                  </span>
                </td>
                <td class="px-3 py-2.5 text-right">
                  <button
                    type="button"
                    class="px-2.5 py-1 bg-green-600 text-white rounded-md hover:bg-green-700 text-xs font-medium disabled:opacity-50"
                    :disabled="processing"
                    @click="openReactivateModal(lesson)"
                  >
                    Réactiver
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-if="pagination && pagination.last_page > 1"
          class="px-4 py-3 border-t border-gray-100 flex items-center justify-between text-sm"
        >
          <span class="text-gray-500">{{ pagination.total }} cours</span>
          <div class="flex gap-2">
            <button
              type="button"
              class="px-3 py-1 border rounded-md disabled:opacity-40"
              :disabled="pagination.current_page <= 1 || loading"
              @click="loadLessons(pagination.current_page - 1)"
            >
              Préc.
            </button>
            <span class="px-2 py-1 text-gray-600">
              {{ pagination.current_page }} / {{ pagination.last_page }}
            </span>
            <button
              type="button"
              class="px-3 py-1 border rounded-md disabled:opacity-40"
              :disabled="pagination.current_page >= pagination.last_page || loading"
              @click="loadLessons(pagination.current_page + 1)"
            >
              Suiv.
            </button>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="reactivateModal.open"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeReactivateModal"
    >
      <div
        class="bg-white rounded-xl p-6 max-w-md w-full shadow-xl"
        role="dialog"
        aria-labelledby="reactivate-title"
      >
        <h3 id="reactivate-title" class="text-lg font-semibold text-gray-900 mb-2">
          Réactiver le cours
        </h3>
        <p class="text-sm text-gray-600 mb-4">
          {{ formatLessonDate(reactivateModal.lesson?.start_time) }} —
          {{ reactivateModal.lesson?.student_name }}
        </p>

        <div class="space-y-3 mb-4">
          <label class="flex items-start gap-2 text-sm">
            <input v-model="reactivateForm.scope" type="radio" value="single" class="mt-1">
            <span>Ce cours uniquement</span>
          </label>
          <label
            v-if="reactivateModal.lesson?.has_recurring_series"
            class="flex items-start gap-2 text-sm"
          >
            <input v-model="reactivateForm.scope" type="radio" value="all_future" class="mt-1">
            <span>Ce cours et toutes les séances futures annulées de la série</span>
          </label>
          <label
            v-if="reactivateModal.lesson?.recurring_slot?.status === 'cancelled'"
            class="flex items-start gap-2 text-sm"
          >
            <input v-model="reactivateForm.restoreRecurring" type="checkbox" class="mt-1 rounded">
            <span>Réactiver aussi le créneau récurrent</span>
          </label>
          <label
            v-if="reactivateModal.lesson?.cancellation_count_in_subscription"
            class="flex items-start gap-2 text-sm text-amber-800 bg-amber-50 p-2 rounded-lg"
          >
            <input v-model="reactivateForm.reattachSubscription" type="checkbox" class="mt-1 rounded">
            <span>Rattacher à l'abonnement (ce cours avait été compté à l'annulation)</span>
          </label>
        </div>

        <div
          v-if="reactivateModal.conflicts?.length"
          class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800"
        >
          <p class="font-medium mb-1">Conflits de planning</p>
          <ul class="list-disc pl-4 space-y-1">
            <li v-for="(c, i) in reactivateModal.conflicts" :key="i">
              {{ c.date }} : {{ c.message }}
            </li>
          </ul>
          <NuxtLink to="/club/planning" class="underline mt-2 inline-block">
            Voir le planning
          </NuxtLink>
        </div>

        <label class="block text-sm font-medium text-gray-700 mb-1">Commentaire (optionnel)</label>
        <textarea
          v-model="reactivateForm.reason"
          rows="2"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-4"
          placeholder="Motif de réactivation…"
        />

        <div class="flex justify-end gap-2">
          <button
            type="button"
            class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg text-sm"
            @click="closeReactivateModal"
          >
            Annuler
          </button>
          <button
            type="button"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium disabled:opacity-50"
            :disabled="processing"
            @click="confirmReactivate"
          >
            {{ processing ? 'Réactivation…' : 'Confirmer' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted, watch } from 'vue'
import { useToast } from '~/composables/useToast'

definePageMeta({
  middleware: ['auth'],
})

const { $api } = useNuxtApp()
const { success, error: showError } = useToast()

interface CancelledLessonRow {
  id: number
  start_time: string
  student_name: string
  teacher_name: string
  cancelled_at: string | null
  cancelled_by?: { id: number; name: string } | null
  cancelled_by_label: string
  notes_excerpt: string | null
  cancellation_certificate_status: string | null
  cancellation_count_in_subscription: boolean
  recurring_slot?: { id: number; status: string } | null
  has_recurring_series?: boolean
  is_past: boolean
}

const lessons = ref<CancelledLessonRow[]>([])
const loading = ref(true)
const processing = ref(false)
const pagination = ref<{ current_page: number; last_page: number; total: number } | null>(null)

const defaultFrom = () => {
  const d = new Date()
  d.setDate(d.getDate() - 90)
  return d.toISOString().slice(0, 10)
}

const filters = reactive({
  dateFrom: defaultFrom(),
  dateTo: '',
  cancelledByRole: '',
  search: '',
})

const reactivateModal = reactive<{
  open: boolean
  lesson: CancelledLessonRow | null
  conflicts: Array<{ date: string; message: string }> | null
}>({
  open: false,
  lesson: null,
  conflicts: null,
})

const reactivateForm = reactive({
  scope: 'single' as 'single' | 'all_future',
  restoreRecurring: true,
  reattachSubscription: false,
  reason: '',
})

let searchDebounce: ReturnType<typeof setTimeout> | null = null

function buildParams(page = 1) {
  const params: Record<string, string | number> = { page, per_page: 25 }
  if (filters.dateFrom) params.from = filters.dateFrom
  if (filters.dateTo) params.to = filters.dateTo
  if (filters.cancelledByRole) params.cancelled_by_role = filters.cancelledByRole
  if (filters.search.trim()) params.search = filters.search.trim()
  return params
}

async function loadLessons(page = 1) {
  try {
    loading.value = true
    const response = await $api.get('/club/lessons/cancelled', { params: buildParams(page) })
    if (response.data.success) {
      lessons.value = response.data.data || []
      pagination.value = response.data.pagination || null
    } else {
      showError(response.data.message || 'Erreur de chargement')
    }
  } catch (err: unknown) {
    console.error(err)
    showError('Erreur lors du chargement des cours annulés')
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.dateFrom = defaultFrom()
  filters.dateTo = ''
  filters.cancelledByRole = ''
  filters.search = ''
  loadLessons(1)
}

function formatLessonDate(iso: string | undefined) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('fr-BE', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatCancelledAt(iso: string | null) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('fr-BE', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function openReactivateModal(lesson: CancelledLessonRow) {
  if (lesson.is_past && !confirm('Ce cours est dans le passé. Confirmer la réactivation ?')) {
    return
  }

  reactivateModal.lesson = lesson
  reactivateModal.conflicts = null
  reactivateForm.scope = 'single'
  reactivateForm.restoreRecurring = lesson.recurring_slot?.status === 'cancelled'
  reactivateForm.reattachSubscription = false
  reactivateForm.reason = ''
  reactivateModal.open = true
}

function closeReactivateModal() {
  reactivateModal.open = false
  reactivateModal.lesson = null
  reactivateModal.conflicts = null
}

async function confirmReactivate() {
  if (!reactivateModal.lesson) return

  try {
    processing.value = true
    const response = await $api.post(`/club/lessons/${reactivateModal.lesson.id}/reactivate`, {
      reactivate_scope: reactivateForm.scope,
      restore_recurring_slot: reactivateForm.restoreRecurring,
      reattach_subscription: reactivateForm.reattachSubscription,
      reason: reactivateForm.reason || undefined,
    })

    if (response.data.success) {
      success(response.data.message || 'Cours réactivé')
      closeReactivateModal()
      await loadLessons(pagination.value?.current_page || 1)
    } else {
      showError(response.data.message || 'Réactivation impossible')
    }
  } catch (err: unknown) {
    const ax = err as { response?: { data?: { message?: string; conflicts?: Array<{ date: string; message: string }> } } }
    if (ax.response?.data?.conflicts?.length) {
      reactivateModal.conflicts = ax.response.data.conflicts
    }
    showError(ax.response?.data?.message || 'Erreur lors de la réactivation')
  } finally {
    processing.value = false
  }
}

watch(
  () => filters.search,
  () => {
    if (searchDebounce) clearTimeout(searchDebounce)
    searchDebounce = setTimeout(() => loadLessons(1), 400)
  }
)

onMounted(() => loadLessons())

onUnmounted(() => {
  if (searchDebounce) clearTimeout(searchDebounce)
})
</script>
