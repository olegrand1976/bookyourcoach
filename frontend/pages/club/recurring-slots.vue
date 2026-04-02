<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Créneaux Récurrents</h1>
            <p class="text-gray-600">Gérez les créneaux récurrents réservés pour les abonnements</p>
          </div>
          <NuxtLink
            to="/club/subscriptions"
            class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors flex items-center space-x-2"
          >
            <span>←</span>
            <span>Abonnements</span>
          </NuxtLink>
        </div>
      </div>

      <!-- Filtres -->
      <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-4">
          <h2 class="text-sm font-semibold text-gray-700">Filtres</h2>
          <button
            type="button"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium self-start sm:self-auto"
            :disabled="loading"
            @click="resetFilters"
          >
            Réinitialiser
          </button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Statut</label>
            <select
              v-model="filters.status"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @change="loadRecurringSlots"
            >
              <option value="">Tous</option>
              <option value="active">Actif</option>
              <option value="cancelled">Annulé</option>
              <option value="expired">Expiré</option>
              <option value="paused">En pause</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Jour</label>
            <select
              v-model="filters.dayOfWeek"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @change="loadRecurringSlots"
            >
              <option value="">Tous</option>
              <option value="0">Dimanche</option>
              <option value="1">Lundi</option>
              <option value="2">Mardi</option>
              <option value="3">Mercredi</option>
              <option value="4">Jeudi</option>
              <option value="5">Vendredi</option>
              <option value="6">Samedi</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Enseignant</label>
            <select
              v-model="filters.teacherId"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @change="loadRecurringSlots"
            >
              <option value="">Tous</option>
              <option
                v-for="t in teachers"
                :key="t.id"
                :value="String(t.id)"
              >
                {{ t.user?.name || `Enseignant #${t.id}` }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Élève</label>
            <select
              v-model="filters.studentId"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @change="loadRecurringSlots"
            >
              <option value="">Tous</option>
              <option
                v-for="s in students"
                :key="s.id"
                :value="String(s.id)"
              >
                {{ formatStudentFilterLabel(s) }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Période — du</label>
            <input
              v-model="filters.dateFrom"
              type="date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @change="loadRecurringSlots"
            >
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">au</label>
            <input
              v-model="filters.dateTo"
              type="date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @change="loadRecurringSlots"
            >
          </div>
          <div class="sm:col-span-2 lg:col-span-2 xl:col-span-2">
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Recherche</label>
            <input
              v-model="filters.search"
              type="search"
              placeholder="Nom élève / prof, n° abonnement, modèle…"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              autocomplete="off"
            >
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-12">
        <p class="text-gray-500">Chargement des créneaux récurrents...</p>
      </div>

      <!-- Résultats vides (filtres actifs) -->
      <div
        v-else-if="recurringSlots.length === 0 && hasActiveFilters"
        class="bg-white rounded-lg shadow-sm p-12 text-center"
      >
        <div class="text-6xl mb-4">🔍</div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Aucun résultat</h3>
        <p class="text-gray-600 mb-4">
          Aucun créneau ne correspond à ces critères. Modifiez les filtres ou réinitialisez-les.
        </p>
        <button
          type="button"
          class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
          @click="resetFilters"
        >
          Réinitialiser les filtres
        </button>
      </div>

      <!-- Empty State (aucun filtre) -->
      <div
        v-else-if="recurringSlots.length === 0"
        class="bg-white rounded-lg shadow-sm p-12 text-center"
      >
        <div class="text-6xl mb-4">🕐</div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Aucun créneau récurrent</h3>
        <p class="text-gray-600 mb-4">
          Les créneaux récurrents sont créés automatiquement lorsque vous créez un cours pour un élève avec un abonnement actif.
        </p>
        <NuxtLink
          to="/club/planning"
          class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
        >
          Créer un cours
        </NuxtLink>
      </div>

      <!-- Liste tabulaire des créneaux récurrents -->
      <div
        v-else
        class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
      >
        <div class="overflow-x-auto">
          <table class="min-w-[960px] w-full text-sm text-left">
            <thead class="text-xs font-semibold text-gray-600 uppercase tracking-wide bg-gray-50 border-b border-gray-200">
              <tr>
                <th scope="col" class="px-3 py-3 whitespace-nowrap">
                  Jour
                </th>
                <th scope="col" class="px-3 py-3 whitespace-nowrap">
                  Horaire
                </th>
                <th scope="col" class="px-3 py-3">
                  Fréq.
                </th>
                <th scope="col" class="px-3 py-3 min-w-[8rem]">
                  Élève
                </th>
                <th scope="col" class="px-3 py-3 min-w-[8rem]">
                  Enseignant
                </th>
                <th scope="col" class="px-3 py-3 min-w-[7rem]">
                  Abonnement
                </th>
                <th scope="col" class="px-3 py-3 whitespace-nowrap">
                  Période
                </th>
                <th scope="col" class="px-3 py-3 whitespace-nowrap">
                  Statut
                </th>
                <th scope="col" class="px-3 py-3 max-w-[10rem]">
                  Notes
                </th>
                <th scope="col" class="px-3 py-3 text-right whitespace-nowrap">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="slot in recurringSlots"
                :key="slot.id"
                class="hover:bg-violet-50/40 align-top"
              >
                <td class="px-3 py-2.5 font-medium text-gray-900 whitespace-nowrap">
                  {{ getDayName(slot.day_of_week) }}
                </td>
                <td class="px-3 py-2.5 tabular-nums text-gray-800 whitespace-nowrap">
                  {{ formatTime(slot.start_time) }} – {{ formatTime(slot.end_time) }}
                </td>
                <td class="px-3 py-2.5 text-gray-700 whitespace-nowrap">
                  {{ getIntervalLabel(slot) }}
                </td>
                <td class="px-3 py-2.5 text-gray-900">
                  {{ getStudentName(slot) }}
                </td>
                <td class="px-3 py-2.5 text-gray-900">
                  {{ slot.teacher?.user?.name || 'Non défini' }}
                </td>
                <td class="px-3 py-2.5 text-gray-800">
                  {{ getSubscriptionName(slot) }}
                </td>
                <td class="px-3 py-2.5 text-gray-700 whitespace-nowrap tabular-nums text-xs sm:text-sm">
                  {{ formatDate(slot.start_date) }} → {{ formatDate(slot.end_date) }}
                </td>
                <td class="px-3 py-2.5">
                  <span
                    :class="getStatusClass(slot.status)"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ getStatusLabel(slot.status) }}
                  </span>
                </td>
                <td class="px-3 py-2.5 text-gray-600 text-xs max-w-[10rem]">
                  <span
                    v-if="slot.notes"
                    class="line-clamp-2"
                    :title="slot.notes"
                  >{{ slot.notes }}</span>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="px-3 py-2.5 text-right">
                  <div class="inline-flex flex-wrap items-center justify-end gap-1">
                    <button
                      v-if="slot.status === 'active'"
                      type="button"
                      :disabled="processing"
                      class="px-2.5 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 text-xs font-medium disabled:opacity-50"
                      @click="releaseSlot(slot.id)"
                    >
                      Libérer
                    </button>
                    <button
                      v-if="slot.status === 'cancelled'"
                      type="button"
                      :disabled="processing"
                      class="px-2.5 py-1 bg-green-600 text-white rounded-md hover:bg-green-700 text-xs font-medium disabled:opacity-50"
                      @click="reactivateSlot(slot.id)"
                    >
                      Réactiver
                    </button>
                    <button
                      type="button"
                      class="px-2.5 py-1 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 text-xs font-medium"
                      @click="viewDetails(slot.id)"
                    >
                      Détails
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="px-3 py-2 text-xs text-gray-500 border-t border-gray-100 bg-gray-50/80">
          {{ recurringSlots.length }} créneau(x) affiché(s)
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { useToast } from '~/composables/useToast'

const { $api } = useNuxtApp()
const { success, error: showError } = useToast()

const recurringSlots = ref([])
const teachers = ref([])
const students = ref([])
const loading = ref(true)
const processing = ref(false)

const filters = reactive({
  status: '',
  dayOfWeek: '',
  teacherId: '',
  studentId: '',
  dateFrom: '',
  dateTo: '',
  search: ''
})

const hasActiveFilters = computed(() => {
  if (filters.status) return true
  if (filters.dayOfWeek !== '') return true
  if (filters.teacherId) return true
  if (filters.studentId) return true
  if (filters.dateFrom) return true
  if (filters.dateTo) return true
  if (filters.search.trim()) return true
  return false
})

let searchDebounceTimer = null

function buildQueryParams() {
  const params = {}
  if (filters.status) {
    params.status = filters.status
  }
  if (filters.dayOfWeek !== '') {
    params.day_of_week = parseInt(filters.dayOfWeek, 10)
  }
  if (filters.teacherId) {
    params.teacher_id = parseInt(filters.teacherId, 10)
  }
  if (filters.studentId) {
    params.student_id = parseInt(filters.studentId, 10)
  }
  if (filters.dateFrom) {
    params.date_from = filters.dateFrom
  }
  if (filters.dateTo) {
    params.date_to = filters.dateTo
  }
  const q = filters.search.trim()
  if (q) {
    params.search = q
  }
  return params
}

async function loadTeachers() {
  try {
    const response = await $api.get('/club/teachers')
    if (response.data.success) {
      teachers.value = response.data.teachers || response.data.data || []
    }
  } catch (err) {
    console.error('Erreur chargement enseignants:', err)
  }
}

async function loadStudents() {
  try {
    const response = await $api.get('/club/students', {
      params: { per_page: 1000, page: 1, status: 'active' }
    })
    if (!response.data.success) return
    let list = response.data.data || []
    const pag = response.data.pagination
    if (pag && pag.last_page > 1) {
      for (let page = 2; page <= pag.last_page; page++) {
        try {
          const next = await $api.get('/club/students', {
            params: { per_page: 1000, page, status: 'active' }
          })
          if (next.data.success && next.data.data) {
            list = list.concat(next.data.data)
          }
        } catch (e) {
          console.warn('Page élèves', page, e)
        }
      }
    }
    students.value = list
  } catch (err) {
    console.error('Erreur chargement élèves:', err)
  }
}

async function loadRecurringSlots() {
  try {
    loading.value = true
    const response = await $api.get('/club/recurring-slots', {
      params: buildQueryParams()
    })
    if (response.data.success) {
      recurringSlots.value = response.data.data || []
    } else {
      showError('Erreur lors du chargement des créneaux récurrents')
    }
  } catch (err) {
    console.error('Erreur:', err)
    showError('Erreur lors du chargement des créneaux récurrents')
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.status = ''
  filters.dayOfWeek = ''
  filters.teacherId = ''
  filters.studentId = ''
  filters.dateFrom = ''
  filters.dateTo = ''
  filters.search = ''
  loadRecurringSlots()
}

watch(
  () => filters.search,
  () => {
    clearTimeout(searchDebounceTimer)
    searchDebounceTimer = setTimeout(() => {
      loadRecurringSlots()
    }, 400)
  }
)

onMounted(async () => {
  await Promise.all([loadTeachers(), loadStudents(), loadRecurringSlots()])
})

onUnmounted(() => {
  clearTimeout(searchDebounceTimer)
})

async function releaseSlot(id) {
  if (!confirm('Êtes-vous sûr de vouloir libérer ce créneau récurrent ?')) {
    return
  }

  try {
    processing.value = true
    const response = await $api.post(`/club/recurring-slots/${id}/release`, {
      reason: 'Libération manuelle depuis l\'interface'
    })

    if (response.data.success) {
      success('Créneau libéré avec succès')
      await loadRecurringSlots()
    } else {
      showError(response.data.message || 'Erreur lors de la libération')
    }
  } catch (err) {
    console.error('Erreur:', err)
    showError('Erreur lors de la libération du créneau')
  } finally {
    processing.value = false
  }
}

async function reactivateSlot(id) {
  if (!confirm('Êtes-vous sûr de vouloir réactiver ce créneau récurrent ?')) {
    return
  }

  try {
    processing.value = true
    const response = await $api.post(`/club/recurring-slots/${id}/reactivate`, {
      reason: 'Réactivation manuelle depuis l\'interface'
    })

    if (response.data.success) {
      success('Créneau réactivé avec succès')
      await loadRecurringSlots()
    } else {
      showError(response.data.message || 'Erreur lors de la réactivation')
    }
  } catch (err) {
    console.error('Erreur:', err)
    showError('Erreur lors de la réactivation du créneau')
  } finally {
    processing.value = false
  }
}

function viewDetails(id) {
  // TODO: Ouvrir une modale avec les détails
  alert(`Détails du créneau #${id} - À implémenter`)
}

function getDayName(dayOfWeek) {
  const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
  return days[dayOfWeek] || 'Inconnu'
}

function getIntervalLabel(slot) {
  const n = Math.max(1, Math.min(52, parseInt(String(slot?.recurring_interval ?? 1), 10) || 1))
  if (n === 1) return '1 sem.'
  return `${n} sem.`
}

function formatTime(time) {
  if (!time) return 'N/A'
  return time.substring(0, 5) // HH:mm
}

function formatDate(date) {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

function getStatusClass(status) {
  const classes = {
    active: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    expired: 'bg-gray-100 text-gray-800',
    paused: 'bg-yellow-100 text-yellow-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function getStatusLabel(status) {
  const labels = {
    active: 'Actif',
    cancelled: 'Annulé',
    expired: 'Expiré',
    paused: 'En pause'
  }
  return labels[status] || status
}

/**
 * Libellé pour le select : l'API GET /club/students renvoie un objet plat (name, first_name, student_first_name…), sans user imbriqué.
 */
function formatStudentFilterLabel(s) {
  if (!s) return ''
  const top = typeof s.name === 'string' ? s.name.trim() : ''
  if (top) return top
  if (s.user?.name && String(s.user.name).trim()) return String(s.user.name).trim()
  const fromUserCols = [s.first_name, s.last_name].filter(Boolean).join(' ').trim()
  if (fromUserCols) return fromUserCols
  const fromStudentCols = [s.student_first_name, s.student_last_name].filter(Boolean).join(' ').trim()
  if (fromStudentCols) return fromStudentCols
  const fromNestedUser = [s.user?.first_name, s.user?.last_name].filter(Boolean).join(' ').trim()
  if (fromNestedUser) return fromNestedUser
  return `Élève #${s.id ?? '?'}`
}

function getStudentName(slot) {
  const st = slot.student
  if (st) {
    if (st.user?.name) return st.user.name
    const fl = [st.first_name, st.last_name].filter(Boolean).join(' ').trim()
    if (fl) return fl
  }
  if (slot.subscription_instance?.students?.length) {
    const firstStudent = slot.subscription_instance.students[0]
    if (firstStudent?.user?.name) return firstStudent.user.name
    const fl = [firstStudent?.first_name, firstStudent?.last_name].filter(Boolean).join(' ').trim()
    if (fl) return fl
  }
  return 'Non défini'
}

function getSubscriptionName(slot) {
  const subscription = slot.subscription_instance?.subscription
  if (!subscription) {
    return 'N/A'
  }
  if (subscription.subscription_number) {
    return subscription.subscription_number
  }
  if (subscription.template?.model_number) {
    return subscription.template.model_number
  }
  if (subscription.template?.name) {
    return subscription.template.name
  }
  return 'N/A'
}
</script>
