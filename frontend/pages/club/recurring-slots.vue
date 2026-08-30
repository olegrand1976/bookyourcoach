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
          <div class="flex items-center gap-3">
            <NuxtLink
              to="/club/planning"
              class="text-sm text-blue-600 hover:text-blue-800 font-medium"
            >
              Planning
            </NuxtLink>
            <NuxtLink
              to="/club/subscriptions"
              class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors flex items-center space-x-2"
            >
              <span>←</span>
              <span>Abonnements</span>
            </NuxtLink>
          </div>
        </div>

        <!-- Onglets -->
        <div class="mt-6 border-b border-gray-200">
          <nav class="-mb-px flex gap-4" aria-label="Onglets créneaux">
            <button
              type="button"
              class="whitespace-nowrap border-b-2 px-1 py-2 text-sm font-medium"
              :class="activeTab === 'list'
                ? 'border-violet-600 text-violet-700'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              @click="activeTab = 'list'"
            >
              Liste
            </button>
            <button
              type="button"
              class="whitespace-nowrap border-b-2 px-1 py-2 text-sm font-medium"
              :class="activeTab === 'diagnostics'
                ? 'border-violet-600 text-violet-700'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              @click="switchToDiagnostics"
            >
              Diagnostic
              <span
                v-if="diagnosticsSummary.with_issues"
                class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800"
              >
                {{ diagnosticsSummary.with_issues }}
              </span>
            </button>
          </nav>
        </div>
      </div>

      <template v-if="activeTab === 'list'">

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
            <Autocomplete
              :model-value="teacherFilterModel"
              :items="teachers"
              label="Enseignant"
              placeholder="Rechercher un enseignant…"
              class="[&_label]:mb-1 [&_label]:text-xs [&_label]:font-medium [&_label]:text-gray-500 [&_label]:uppercase"
              :max-results="500"
              :get-item-label="formatTeacherFilterLabel"
              :get-item-id="getPersonItemId"
              :filter-function="filterTeacherByQuery"
              @update:model-value="onListTeacherFilterChange"
            />
          </div>
          <div>
            <Autocomplete
              :model-value="studentFilterModel"
              :items="students"
              label="Élève"
              placeholder="Rechercher un élève…"
              class="[&_label]:mb-1 [&_label]:text-xs [&_label]:font-medium [&_label]:text-gray-500 [&_label]:uppercase"
              :max-results="500"
              :get-item-label="formatStudentFilterLabel"
              :get-item-id="getPersonItemId"
              :filter-function="filterStudentByQuery"
              @update:model-value="onListStudentFilterChange"
            />
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
              placeholder="N° abonnement, modèle…"
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
          <table class="min-w-[1080px] w-full text-sm text-left">
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
                <th scope="col" class="px-3 py-3 whitespace-nowrap">
                  Dernière génération
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
                  {{ getTeacherName(slot) }}
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
                <td class="px-3 py-2.5 text-gray-700 whitespace-nowrap tabular-nums text-xs sm:text-sm">
                  <span v-if="slot.last_generated_at">{{ formatDateTime(slot.last_generated_at) }}</span>
                  <span v-else class="text-gray-400">—</span>
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
      </template>

      <!-- Onglet Diagnostic -->
      <template v-else>
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-6">
          <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-4">
            <div>
              <h2 class="text-sm font-semibold text-gray-700">Incohérences série ↔ cours futurs</h2>
              <p class="text-xs text-gray-500 mt-1">
                Détecte les séries sans planification future, écarts moniteur/horaire, et permet de régénérer les cours manquants.
              </p>
            </div>
            <div class="flex items-center gap-3 self-start sm:self-auto">
              <button
                type="button"
                class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                :disabled="diagnosticsLoading"
                @click="resetDiagFilters"
              >
                Réinitialiser
              </button>
              <button
                type="button"
                class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                :disabled="diagnosticsLoading"
                @click="loadDiagnostics"
              >
                Actualiser
              </button>
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Alerte</label>
              <select
                v-model="diagFilters.issue"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                @change="loadDiagnostics"
              >
                <option value="">Toutes</option>
                <option value="no_future_lessons">Sans cours futurs</option>
                <option value="teacher_mismatch">Moniteur désaligné</option>
                <option value="schedule_drift">Horaire désaligné</option>
                <option value="gap_in_series">Trous dans la série</option>
                <option value="orphan_lessons">Cours orphelins</option>
                <option value="cancelled_srs_with_futures">Série annulée + futurs</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Statut série</label>
              <select
                v-model="diagFilters.status"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                @change="loadDiagnostics"
              >
                <option value="">Tous</option>
                <option value="active">Actif</option>
                <option value="cancelled">Annulé</option>
              </select>
            </div>
            <div>
              <Autocomplete
                :model-value="diagTeacherFilterModel"
                :items="teachers"
                label="Enseignant"
                placeholder="Rechercher un enseignant…"
                class="[&_label]:mb-1 [&_label]:text-xs [&_label]:font-medium [&_label]:text-gray-500 [&_label]:uppercase"
                :max-results="500"
                :get-item-label="formatTeacherFilterLabel"
                :get-item-id="getPersonItemId"
                :filter-function="filterTeacherByQuery"
                @update:model-value="onDiagTeacherFilterChange"
              />
            </div>
            <div>
              <Autocomplete
                :model-value="diagStudentFilterModel"
                :items="students"
                label="Élève"
                placeholder="Rechercher un élève…"
                class="[&_label]:mb-1 [&_label]:text-xs [&_label]:font-medium [&_label]:text-gray-500 [&_label]:uppercase"
                :max-results="500"
                :get-item-label="formatStudentFilterLabel"
                :get-item-id="getPersonItemId"
                :filter-function="filterStudentByQuery"
                @update:model-value="onDiagStudentFilterChange"
              />
            </div>
            <div class="sm:col-span-2 lg:col-span-3 xl:col-span-4 flex items-end gap-3 text-sm text-gray-600">
              <span>{{ diagnosticsSummary.total }} série(s)</span>
              <span class="font-medium text-amber-700">{{ diagnosticsSummary.with_issues }} avec alerte(s)</span>
            </div>
          </div>
        </div>

        <div v-if="diagnosticsLoading" class="text-center py-12">
          <p class="text-gray-500">Analyse en cours…</p>
        </div>

        <div
          v-else-if="diagnosticRows.length === 0"
          class="bg-white rounded-lg shadow-sm p-12 text-center"
        >
          <h3 class="text-xl font-semibold text-gray-900 mb-2">Aucune incohérence</h3>
          <p class="text-gray-600">Aucune série ne correspond aux filtres, ou tout est cohérent.</p>
        </div>

        <div
          v-else
          class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
        >
          <div class="overflow-x-auto">
            <table class="min-w-[1100px] w-full text-sm text-left">
              <thead class="text-xs font-semibold text-gray-600 uppercase tracking-wide bg-gray-50 border-b border-gray-200">
                <tr>
                  <th class="px-3 py-3">Élève</th>
                  <th class="px-3 py-3">Moniteur</th>
                  <th class="px-3 py-3 whitespace-nowrap">Jour / horaire</th>
                  <th class="px-3 py-3">Fréq.</th>
                  <th class="px-3 py-3">Statut</th>
                  <th class="px-3 py-3 whitespace-nowrap">Futurs</th>
                  <th class="px-3 py-3 whitespace-nowrap">Prochaine</th>
                  <th class="px-3 py-3">Alertes</th>
                  <th class="px-3 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr
                  v-for="row in diagnosticRows"
                  :key="row.id"
                  class="hover:bg-amber-50/40 align-top"
                >
                  <td class="px-3 py-2.5">{{ getStudentName(row) }}</td>
                  <td class="px-3 py-2.5">{{ getTeacherName(row, '—') }}</td>
                  <td class="px-3 py-2.5 whitespace-nowrap">
                    {{ getDayName(row.day_of_week) }}
                    {{ formatTime(row.start_time) }}–{{ formatTime(row.end_time) }}
                  </td>
                  <td class="px-3 py-2.5">{{ getIntervalLabel(row) }}</td>
                  <td class="px-3 py-2.5">
                    <span :class="getStatusClass(row.status)" class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium">
                      {{ getStatusLabel(row.status) }}
                    </span>
                  </td>
                  <td class="px-3 py-2.5 tabular-nums">{{ row.future_lessons_count }}</td>
                  <td class="px-3 py-2.5 whitespace-nowrap text-xs">
                    <NuxtLink
                      v-if="row.next_expected_date"
                      :to="{ path: '/club/planning', query: { date: row.next_expected_date } }"
                      class="text-blue-600 hover:underline"
                    >
                      {{ formatDate(row.next_expected_date) }}
                    </NuxtLink>
                    <span v-else class="text-gray-400">—</span>
                  </td>
                  <td class="px-3 py-2.5">
                    <ul v-if="row.issues?.length" class="space-y-1">
                      <li
                        v-for="issue in row.issues"
                        :key="issue.code"
                        class="text-xs text-amber-900 bg-amber-50 border border-amber-100 rounded px-2 py-1"
                      >
                        {{ issueLabel(issue.code) }} — {{ issue.message }}
                      </li>
                    </ul>
                    <span v-else class="text-xs text-green-700">OK</span>
                  </td>
                  <td class="px-3 py-2.5 text-right">
                    <button
                      v-if="row.can_regenerate"
                      type="button"
                      :disabled="processing"
                      class="px-2.5 py-1 bg-violet-600 text-white rounded-md hover:bg-violet-700 text-xs font-medium disabled:opacity-50"
                      @click="regenerateRow(row)"
                    >
                      Régénérer futurs
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import Autocomplete from '~/components/Autocomplete.vue'
import { useToast } from '~/composables/useToast'
import { resolveStudentDisplayName } from '~/composables/planning/usePlanningParticipant'

const { $api } = useNuxtApp()
const { success, error: showError } = useToast()

const recurringSlots = ref([])
const teachers = ref([])
const students = ref([])
const loading = ref(true)
const processing = ref(false)
const activeTab = ref('list')
const diagnosticsLoading = ref(false)
const diagnosticRows = ref([])
const diagnosticsSummary = ref({
  total: 0,
  with_issues: 0
})
const diagFilters = reactive({
  issue: '',
  status: 'active',
  teacherId: '',
  studentId: ''
})

const filters = reactive({
  status: '',
  dayOfWeek: '',
  teacherId: '',
  studentId: '',
  dateFrom: '',
  dateTo: '',
  search: ''
})

const teacherFilterModel = computed(() => parseFilterId(filters.teacherId))
const studentFilterModel = computed(() => parseFilterId(filters.studentId))
const diagTeacherFilterModel = computed(() => parseFilterId(diagFilters.teacherId))
const diagStudentFilterModel = computed(() => parseFilterId(diagFilters.studentId))

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

function parseFilterId(value) {
  if (value === '' || value == null) return null
  const n = Number.parseInt(String(value), 10)
  return Number.isFinite(n) ? n : null
}

function getPersonItemId(item) {
  return item.id
}

function formatTeacherFilterLabel(t) {
  if (!t) return ''
  if (t.user?.name && String(t.user.name).trim()) return String(t.user.name).trim()
  if (t.name && String(t.name).trim()) return String(t.name).trim()
  return `Enseignant #${t.id ?? '?'}`
}

function filterTeacherByQuery(t, query) {
  const q = query.trim().toLowerCase()
  if (!q) return true
  if (formatTeacherFilterLabel(t).toLowerCase().includes(q)) return true
  return String(t.id) === q
}

function filterStudentByQuery(s, query) {
  const q = query.trim().toLowerCase()
  if (!q) return true
  if (formatStudentFilterLabel(s).toLowerCase().includes(q)) return true
  if (s.email && String(s.email).toLowerCase().includes(q)) return true
  for (const part of [s.first_name, s.last_name, s.student_first_name, s.student_last_name]) {
    if (part && String(part).toLowerCase().includes(q)) return true
  }
  return String(s.id) === q
}

function onListTeacherFilterChange(id) {
  filters.teacherId = id == null ? '' : String(id)
  loadRecurringSlots()
}

function onListStudentFilterChange(id) {
  filters.studentId = id == null ? '' : String(id)
  loadRecurringSlots()
}

function onDiagTeacherFilterChange(id) {
  diagFilters.teacherId = id == null ? '' : String(id)
  loadDiagnostics()
}

function onDiagStudentFilterChange(id) {
  diagFilters.studentId = id == null ? '' : String(id)
  loadDiagnostics()
}

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

function resetDiagFilters() {
  diagFilters.issue = ''
  diagFilters.status = 'active'
  diagFilters.teacherId = ''
  diagFilters.studentId = ''
  loadDiagnostics()
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

async function switchToDiagnostics() {
  activeTab.value = 'diagnostics'
  if (diagnosticRows.value.length === 0 && !diagnosticsLoading.value) {
    await loadDiagnostics()
  }
}

async function loadDiagnostics() {
  try {
    diagnosticsLoading.value = true
    const params = {}
    if (diagFilters.issue) params.issue = diagFilters.issue
    if (diagFilters.status) params.status = diagFilters.status
    if (diagFilters.teacherId) params.teacher_id = parseInt(diagFilters.teacherId, 10)
    if (diagFilters.studentId) params.student_id = parseInt(diagFilters.studentId, 10)
    const response = await $api.get('/club/recurring-slots/diagnostics', { params })
    if (response.data.success) {
      const payload = response.data.data || {}
      diagnosticRows.value = Array.isArray(payload) ? payload : (payload.items || [])
      diagnosticsSummary.value = (Array.isArray(payload) ? response.data.summary : payload.summary)
        || { total: 0, with_issues: 0 }
    } else {
      showError(response.data.message || 'Erreur diagnostic')
    }
  } catch (err) {
    console.error('Erreur diagnostic:', err)
    showError('Erreur lors du diagnostic des créneaux')
  } finally {
    diagnosticsLoading.value = false
  }
}

async function regenerateRow(row) {
  if (!confirm(`Régénérer les cours futurs manquants pour la série #${row.id} ? Les cours existants ne seront pas supprimés.`)) {
    return
  }
  try {
    processing.value = true
    const response = await $api.post(`/club/recurring-slots/${row.id}/regenerate-future-lessons`, {})
    if (response.data.success) {
      success(response.data.message || 'Régénération terminée')
      await loadDiagnostics()
    } else {
      showError(response.data.message || 'Échec de la régénération')
    }
  } catch (err) {
    console.error('Erreur régénération:', err)
    showError(err.response?.data?.message || 'Erreur lors de la régénération')
  } finally {
    processing.value = false
  }
}

function issueLabel(code) {
  const labels = {
    no_future_lessons: 'Sans futurs',
    teacher_mismatch: 'Moniteur',
    orphan_lessons: 'Orphelins',
    cancelled_srs_with_futures: 'Série annulée',
    schedule_drift: 'Horaire',
    gap_in_series: 'Trous'
  }
  return labels[code] || code
}

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

function formatDateTime(date) {
  if (!date) return '—'
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
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
 * Libellé pour le filtre : l'API GET /club/students renvoie un objet plat (name, first_name, student_first_name…), sans user imbriqué.
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
  if (!slot) return 'Non défini'
  const st = slot.student
  if (st) {
    const resolved = resolveStudentDisplayName(st)
    if (resolved) return resolved
  }
  if (slot.subscription_instance?.students?.length) {
    for (const s of slot.subscription_instance.students) {
      const resolved = resolveStudentDisplayName(s)
      if (resolved) return resolved
    }
  }
  const sid = slot.student_id ?? st?.id
  if (sid != null) return `Élève #${sid}`
  return 'Non défini'
}

function getTeacherName(slot, emptyLabel = 'Non défini') {
  if (!slot) return emptyLabel
  const t = slot.teacher
  if (t) {
    if (t.name && String(t.name).trim()) return String(t.name).trim()
    if (t.user?.name && String(t.user.name).trim()) return String(t.user.name).trim()
    const fl = [t.first_name, t.last_name].filter(Boolean).join(' ').trim()
    if (fl) return fl
  }
  const tid = slot.teacher_id ?? t?.id
  if (tid != null) return `Enseignant #${tid}`
  return emptyLabel
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
  return 'N/A'
}
</script>
