<template>
  <!-- Si tu vois encore une erreur avec motion.div: rebuild l’image Docker frontend ou lance npm run dev depuis ce repo après git pull. -->
  <div class="min-h-screen bg-gray-50">
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
              Journal des actions sur les cours
            </h1>
            <p class="mt-1 text-sm text-gray-600">
              Historique des créations, modifications, annulations et liaisons d'abonnement
            </p>
          </div>
          <div>
            <NuxtLink
              to="/club/planning"
              class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Retour au planning
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label for="filter-student" class="block text-sm font-medium text-gray-700 mb-1">
              Élève
            </label>
            <select
              id="filter-student"
              v-model="filters.student_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @change="loadLogs(1)"
            >
              <option value="">
                Tous les élèves
              </option>
              <option
                v-for="s in students"
                :key="s.id"
                :value="String(s.id)"
              >
                {{ studentLabel(s) }}
              </option>
            </select>
          </div>

          <div>
            <label for="filter-action" class="block text-sm font-medium text-gray-700 mb-1">
              Type d'action
            </label>
            <select
              id="filter-action"
              v-model="filters.action"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @change="loadLogs(1)"
            >
              <option value="">
                Toutes les actions
              </option>
              <option
                v-for="t in actionTypes"
                :key="t.value"
                :value="t.value"
              >
                {{ t.label }}
              </option>
            </select>
          </div>

          <div>
            <label for="filter-from" class="block text-sm font-medium text-gray-700 mb-1">
              Du
            </label>
            <input
              id="filter-from"
              v-model="filters.from"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @change="loadLogs(1)"
            >
          </div>

          <div>
            <label for="filter-to" class="block text-sm font-medium text-gray-700 mb-1">
              Au
            </label>
            <input
              id="filter-to"
              v-model="filters.to"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @change="loadLogs(1)"
            >
          </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
          <button
            type="button"
            class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
            @click="resetFilters"
          >
            Réinitialiser
          </button>
        </div>
      </div>

      <div v-if="error" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        {{ error }}
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div
          v-if="loading"
          class="py-12 text-center text-sm text-gray-500"
        >
          Chargement du journal…
        </div>

        <p
          v-else-if="logs.length === 0"
          class="py-12 text-center text-sm text-gray-500"
        >
          Aucune action enregistrée pour ces critères.
        </p>

        <div
          v-else
          class="overflow-x-auto"
        >
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700">
                  Date action
                </th>
                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700">
                  Type
                </th>
                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700">
                  Élève
                </th>
                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700">
                  Cours
                </th>
                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700">
                  Abonnement
                </th>
                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700">
                  Effectué par
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="row in logs"
                :key="row.id"
                class="hover:bg-gray-50/80"
              >
                <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                  {{ formatDate(row.created_at) }}
                </td>
                <td class="px-4 py-3">
                  <span
                    class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="actionBadgeClass(row.action)"
                  >
                    {{ row.action_label }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-900 max-w-[10rem] truncate" :title="row.student_name">
                  {{ row.student_name }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  <template v-if="row.lesson_start_time">
                    {{ formatLessonDate(row.lesson_start_time) }}
                    <span v-if="row.lesson_id" class="text-gray-400 text-xs block">#{{ row.lesson_id }}</span>
                  </template>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="px-4 py-3 text-gray-600 max-w-[12rem] truncate" :title="row.subscription_label || ''">
                  {{ row.subscription_label || '—' }}
                </td>
                <td class="px-4 py-3 text-gray-700 max-w-[10rem] truncate" :title="row.performed_by?.name">
                  {{ row.performed_by?.name || '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-if="pagination && pagination.last_page > 1"
          class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50"
        >
          <p class="text-sm text-gray-600">
            Page {{ pagination.current_page }} / {{ pagination.last_page }}
            ({{ pagination.total }} entrées)
          </p>
          <div class="flex gap-2">
            <button
              type="button"
              class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg disabled:opacity-40"
              :disabled="pagination.current_page <= 1"
              @click="loadLogs(pagination.current_page - 1)"
            >
              Précédent
            </button>
            <button
              type="button"
              class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg disabled:opacity-40"
              :disabled="pagination.current_page >= pagination.last_page"
              @click="loadLogs(pagination.current_page + 1)"
            >
              Suivant
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'default',
  middleware: ['auth']
})

type ActionType = { value: string; label: string }

type LogRow = {
  id: number
  action: string
  action_label: string
  created_at: string | null
  lesson_id: number | null
  lesson_start_time: string | null
  student_id: number | null
  student_name: string
  subscription_instance_id: number | null
  subscription_label: string | null
  performed_by?: { name?: string; role?: string }
}

type StudentOption = {
  id: number
  name?: string | null
  email?: string | null
  user?: { name?: string }
  first_name?: string | null
  last_name?: string | null
  student_first_name?: string | null
  student_last_name?: string | null
}

const { $api } = useNuxtApp()

const logs = ref<LogRow[]>([])
const students = ref<StudentOption[]>([])
const actionTypes = ref<ActionType[]>([])
const loading = ref(true)
const error = ref('')
const pagination = ref<{
  current_page: number
  last_page: number
  total: number
} | null>(null)

const filters = reactive({
  student_id: '',
  action: '',
  from: '',
  to: ''
})

/** Aligné sur ClubController::getStudents (objet plat, pas user.name) et students.vue getStudentName */
function studentLabel(s: StudentOption): string {
  if (s.name && String(s.name).trim()) {
    return String(s.name).trim()
  }
  if (s.user?.name && String(s.user.name).trim()) {
    return String(s.user.name).trim()
  }
  const fromUser = [s.first_name, s.last_name].filter(Boolean).join(' ').trim()
  if (fromUser) return fromUser
  const fromStudent = [s.student_first_name, s.student_last_name].filter(Boolean).join(' ').trim()
  if (fromStudent) return fromStudent
  if (s.email && String(s.email).trim()) {
    return String(s.email).trim()
  }
  return `Élève #${s.id}`
}

function formatDate(iso: string | null): string {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(iso))
}

function formatLessonDate(iso: string): string {
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(iso))
}

function actionBadgeClass(action: string): string {
  if (action.includes('deleted')) return 'bg-red-100 text-red-800'
  if (action.includes('cancelled') || action === 'student_cancelled') return 'bg-amber-100 text-amber-800'
  if (action === 'created' || action === 'reactivated') return 'bg-emerald-100 text-emerald-800'
  if (action.includes('subscription')) return 'bg-blue-100 text-blue-800'
  if (action.includes('certificate')) return 'bg-purple-100 text-purple-800'
  return 'bg-gray-100 text-gray-800'
}

async function loadStudents() {
  try {
    const perPage = 1000
    const baseParams = { per_page: perPage, page: 1, status: 'all' as const }
    const res = await $api.get('/club/students', { params: baseParams })
    if (!res.data?.success) {
      students.value = []
      return
    }
    let list: StudentOption[] = Array.isArray(res.data.data) ? res.data.data : []
    const lastPage = res.data.pagination?.last_page ?? 1
    if (lastPage > 1) {
      const all = [...list]
      for (let page = 2; page <= lastPage; page++) {
        try {
          const next = await $api.get('/club/students', {
            params: { ...baseParams, page },
          })
          if (next.data?.success && Array.isArray(next.data.data)) {
            all.push(...next.data.data)
          }
        } catch {
          break
        }
      }
      list = all
    }
    students.value = list
  } catch {
    students.value = []
  }
}

async function loadLogs(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const params: Record<string, string | number> = { page, per_page: 25 }
    if (filters.student_id) params.student_id = filters.student_id
    if (filters.action) params.action = filters.action
    if (filters.from) params.from = filters.from
    if (filters.to) params.to = filters.to

    const res = await $api.get('/club/lesson-action-logs', { params })
    logs.value = res.data?.data ?? []
    pagination.value = res.data?.pagination ?? null
    if (res.data?.action_types?.length) {
      actionTypes.value = res.data.action_types
    }
  } catch (e: unknown) {
    const err = e as { data?: { message?: string }; message?: string }
    error.value = err?.data?.message || err?.message || 'Impossible de charger le journal.'
    logs.value = []
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.student_id = ''
  filters.action = ''
  filters.from = ''
  filters.to = ''
  loadLogs(1)
}

onMounted(async () => {
  const d = new Date()
  d.setDate(d.getDate() - 90)
  filters.from = d.toISOString().slice(0, 10)
  await Promise.all([loadStudents(), loadLogs()])
})
</script>
