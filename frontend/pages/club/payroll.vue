<template>
  <div class="p-8">
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Rapports de Paie</h1>
        <p class="text-gray-600">Gestion des commissions enseignants par période</p>
      </div>
      <button
        @click="showGenerateModal = true"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-md"
      >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Générer un rapport
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-4 text-gray-500">Chargement des rapports...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <div class="flex items-center">
        <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-red-800">{{ error }}</p>
      </div>
    </div>

    <!-- Reports List -->
    <div v-else class="space-y-6">
      <!-- 1. Rapports disponibles -->
      <!-- Reports Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900">Rapports disponibles</h2>
          <div class="flex items-center space-x-3">
            <label class="text-sm font-medium text-gray-700">Filtrer par année:</label>
            <select 
              v-model="filterYear" 
              @change="filterReportsByYear"
              class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
            >
              <option :value="null">Toutes les années</option>
              <option v-for="year in availableReportYears" :key="year" :value="year">{{ year }}</option>
            </select>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Période</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enseignants</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DCL (€)</th>
                <th v-if="hasNdclInReports" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NDCL (€)</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total (€)</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="report in filteredReports" :key="`${report.year}-${report.month}`" 
                  :class="selectedReport?.year === report.year && selectedReport?.month === report.month ? 'bg-blue-50' : 'hover:bg-gray-50'"
                  @click="selectReport(report.year, report.month)"
                  class="cursor-pointer">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ report.month_name }} {{ report.year }}</div>
                  <div v-if="report.generated_at" class="text-xs text-gray-500">
                    Généré le {{ formatDate(report.generated_at) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ report.teachers_count }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatCurrency(report.statistics?.total_commissions_dcl || 0) }}
                </td>
                <td v-if="hasNdclInReports" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatCurrency(report.statistics?.total_commissions_ndcl || 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                  {{ formatCurrency(report.statistics?.total_a_payer || 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex space-x-2">
                    <button
                      @click.stop="selectReport(report.year, report.month)"
                      class="text-blue-600 hover:text-blue-900"
                      title="Voir la synthèse"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button
                      @click.stop="reloadReportForPeriod(report.year, report.month)"
                      :disabled="reloading && reloadingYear === report.year && reloadingMonth === report.month"
                      class="text-orange-600 hover:text-orange-900 disabled:opacity-50 disabled:cursor-not-allowed"
                      title="Réinitialiser le rapport de cette période"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                      </svg>
                    </button>
                    <button
                      @click.stop="exportCSV(report.year, report.month)"
                      class="text-green-600 hover:text-green-900"
                      title="Exporter en CSV"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 2. Indicateur de synthèse -->
      <div v-if="selectedReport" class="space-y-6">
        <!-- Période -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-indigo-500">
          <div class="flex items-center">
            <div class="p-2 bg-indigo-100 rounded-lg">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Période</p>
              <p class="text-2xl font-bold text-gray-900">
                {{ selectedReport.month_name || selectedReport.period?.month_name || '' }} {{ selectedReport.year || selectedReport.period?.year || '' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Statistiques principales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
          <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
              <div class="p-2 bg-blue-100 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Nb Enseignants</p>
                <p class="text-2xl font-bold text-gray-900">{{ selectedReport.statistics?.nombre_enseignants || 0 }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <div class="flex items-center">
              <div class="p-2 bg-green-100 rounded-lg">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">DCL (€)</p>
                <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(selectedReport.statistics?.total_commissions_dcl || 0) }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
            <div class="flex items-center">
              <div class="p-2 bg-purple-100 rounded-lg">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">NDCL (€)</p>
                <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(selectedReport.statistics?.total_commissions_ndcl || 0) }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
            <div class="flex items-center">
              <div class="p-2 bg-red-100 rounded-lg">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Report Mois Suivant (€)</p>
                <p class="text-2xl font-bold" :class="(selectedReport.statistics?.report_mois_suivant || 0) < 0 ? 'text-red-600' : 'text-gray-900'">
                  {{ formatCurrency(selectedReport.statistics?.report_mois_suivant || 0) }}
                </p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500">
            <div class="flex items-center">
              <div class="p-2 bg-orange-100 rounded-lg">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Payé (€)</p>
                <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(selectedReport.statistics?.total_paye || selectedReport.statistics?.total_a_payer || 0) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Rapport détaillé -->
      <div v-if="selectedReport && selectedReportDetails" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
          <h2 class="text-lg font-semibold text-gray-900">
            Détails du rapport - {{ selectedReport.month_name }} {{ selectedReport.year }}
          </h2>
          <button
            @click="selectedReport = null; selectedReportDetails = null"
            class="text-gray-400 hover:text-gray-600"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enseignant</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commissions DCL (€)</th>
                <th v-if="hasNdclInReportDetails" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commissions NDCL (€)</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total à Payer (€)</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(data, teacherId) in selectedReportDetails.report" :key="teacherId">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ data.nom_enseignant }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatCurrency(data.total_commissions_dcl || 0) }}
                </td>
                <td v-if="hasNdclInReportDetails" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatCurrency(data.total_commissions_ndcl || 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                  {{ formatCurrency(data.total_a_payer) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <button
                    @click="openEditModal(parseInt(teacherId), data.nom_enseignant)"
                    class="text-blue-600 hover:text-blue-900 inline-flex items-center gap-1"
                    title="Modifier les paiements"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Modifier
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Edit Payments Modal -->
    <EditPaymentsModal
      v-if="selectedReport && selectedTeacherId"
      :show="showEditModal"
      :year="selectedReport.year"
      :month="selectedReport.month"
      :teacher-id="selectedTeacherId"
      :teacher-name="selectedTeacherName"
      @close="showEditModal = false"
      @reload="handlePaymentsReload"
    />

    <!-- Generate Modal -->
    <div v-if="showGenerateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showGenerateModal = false">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Générer un rapport de paie</h3>
        </div>
        <div class="px-6 py-4">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Année</label>
              <select v-model="generateYear" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Mois</label>
              <select v-model="generateMonth" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option v-for="(month, index) in months" :key="index" :value="index + 1">{{ month }}</option>
              </select>
            </div>
          </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
          <button
            @click="showGenerateModal = false"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
          >
            Annuler
          </button>
          <button
            @click="generateReport"
            :disabled="generating"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="generating">Génération...</span>
            <span v-else>Générer</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useToast } from '@/composables/useToast'
import EditPaymentsModal from '@/components/payroll/EditPaymentsModal.vue'

console.log('📊 [PAYROLL] Page chargée')

definePageMeta({
  middleware: ['auth']
})

const { $api } = useNuxtApp()
const config = useRuntimeConfig()
const { success: showSuccess, error: showError } = useToast()

console.log('📊 [PAYROLL] $api disponible:', !!$api)
console.log('📊 [PAYROLL] config:', config.public?.apiBase)

// State
const loading = ref(true)
const error = ref<string | null>(null)
const reports = ref<any[]>([])
const selectedReport = ref<any>(null)
const selectedReportDetails = ref<any>(null)
const showGenerateModal = ref(false)
const generating = ref(false)
const showEditModal = ref(false)
const selectedTeacherId = ref<number | null>(null)
const selectedTeacherName = ref<string>('')
const reloading = ref(false)
const reloadingYear = ref<number | null>(null)
const reloadingMonth = ref<number | null>(null)

// Generate form
const currentDate = new Date()
const generateYear = ref(currentDate.getFullYear())
const generateMonth = ref(currentDate.getMonth() + 1)
const filterYear = ref<number | null>(currentDate.getFullYear())
const filteredReports = ref<typeof reports.value>([])

const availableYears = Array.from({ length: 5 }, (_, i) => currentDate.getFullYear() - i)
const months = [
  'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
  'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
]

// Methods
const loadReports = async () => {
  loading.value = true
  error.value = null
  
  try {
    console.log('📊 [PAYROLL] Chargement des rapports...')
    const response = await $api.get('/club/payroll/reports')
    console.log('📊 [PAYROLL] Réponse complète:', response)
    console.log('📊 [PAYROLL] response.data:', response.data)
    console.log('📊 [PAYROLL] response.data?.success:', response.data?.success)
    console.log('📊 [PAYROLL] response.data?.data:', response.data?.data)
    
    // Gérer différentes structures de réponse
    if (response.data?.success && response.data?.data) {
      reports.value = response.data.data || []
      console.log('📊 [PAYROLL] Rapports chargés (via data.data):', reports.value.length)
    } else if (response.data?.success && Array.isArray(response.data)) {
      reports.value = response.data
      console.log('📊 [PAYROLL] Rapports chargés (via data array):', reports.value.length)
    } else if (response.success && response.data) {
      reports.value = Array.isArray(response.data) ? response.data : []
      console.log('📊 [PAYROLL] Rapports chargés (via response.data):', reports.value.length)
    } else {
      console.warn('📊 [PAYROLL] Structure de réponse inattendue:', response)
      reports.value = []
      error.value = response.data?.message || response.message || 'Erreur lors du chargement des rapports'
    }
    
    // Appliquer le filtre après le chargement
    filterReportsByYear()
  } catch (err) {
    console.error('❌ [PAYROLL] Erreur lors du chargement des rapports:', err)
    console.error('❌ [PAYROLL] Erreur complète:', {
      message: err.message,
      response: err.response?.data,
      status: err.response?.status
    })
    error.value = err.response?.data?.message || err.message || 'Erreur lors du chargement des rapports'
    filteredReports.value = []
  } finally {
    loading.value = false
    console.log('📊 [PAYROLL] Chargement terminé, loading:', loading.value)
  }
}

const selectReport = async (year, month) => {
  await loadReportDetails(year, month)
}

const loadReportDetails = async (year, month) => {
  try {
    console.log(`📊 [PAYROLL] Chargement détails pour ${year}/${month}`)
    const response = await $api.get(`/club/payroll/reports/${year}/${month}`)
    console.log('📊 [PAYROLL] Détails reçus:', response)
    
    const data = response.data?.data || response.data || response
    if (data.report) {
      selectedReport.value = {
        year,
        month,
        month_name: data.period?.month_name || `${month}/${year}`,
        period: data.period,
        statistics: data.statistics,
        teachers_count: Object.keys(data.report).length
      }
      selectedReportDetails.value = data
    }
  } catch (err) {
    console.error('❌ [PAYROLL] Erreur lors du chargement des détails:', err)
    error.value = err.response?.data?.message || err.message || 'Erreur lors du chargement des détails'
  }
}

const reloadReportForPeriod = async (year: number, month: number) => {
  reloading.value = true
  reloadingYear.value = year
  reloadingMonth.value = month
  
  try {
    const response = await $api.post(`/club/payroll/reports/${year}/${month}/reload`, {
      reset_manual_changes: true
    })
    
    if (response.data?.success) {
      // Recharger la liste des rapports
      await loadReports()
      // Si c'est le rapport actuellement sélectionné, recharger aussi les détails
      if (selectedReport.value && selectedReport.value.year === year && selectedReport.value.month === month) {
        await loadReportDetails(year, month)
      }
      showSuccess('Rapport réinitialisé avec succès', 'Succès')
    } else {
      error.value = response.data?.message || 'Erreur lors de la réinitialisation'
      showError(error.value, 'Erreur')
    }
  } catch (err) {
    console.error('❌ [PAYROLL] Erreur lors de la réinitialisation:', err)
    error.value = err.response?.data?.message || err.message || 'Erreur lors de la réinitialisation'
    showError(error.value, 'Erreur')
  } finally {
    reloading.value = false
    reloadingYear.value = null
    reloadingMonth.value = null
  }
}

const generateReport = async () => {
  generating.value = true
  
  try {
    // Vérifier que la période n'est pas dans le futur
    const currentYear = new Date().getFullYear()
    const currentMonth = new Date().getMonth() + 1
    
    if (generateYear.value > currentYear || (generateYear.value === currentYear && generateMonth.value > currentMonth)) {
      error.value = 'Impossible de générer un rapport pour une période future'
      showError('Impossible de générer un rapport pour une période future', 'Erreur')
      generating.value = false
      return
    }
    
    console.log(`📊 [PAYROLL] Génération rapport pour ${generateYear.value}/${generateMonth.value}`)
    const response = await $api.post('/club/payroll/generate', {
      year: generateYear.value,
      month: generateMonth.value
    })
    console.log('📊 [PAYROLL] Réponse génération:', response)
    
    const success = response.data?.success || response.success
    if (success) {
      showGenerateModal.value = false
      await loadReports()
      await loadReportDetails(generateYear.value, generateMonth.value)
      showSuccess('Rapport généré avec succès', 'Succès')
    } else {
      error.value = response.data?.message || response.message || 'Erreur lors de la génération'
      showError(error.value, 'Erreur')
    }
  } catch (err) {
    console.error('❌ [PAYROLL] Erreur lors de la génération:', err)
    error.value = err.response?.data?.message || err.message || 'Erreur lors de la génération'
  } finally {
    generating.value = false
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2
  }).format(amount)
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

const exportCSV = async (year: number, month: number) => {
  try {
    const config = useRuntimeConfig()
    const apiBase = config.public.apiBase || '/api'
    const token = useCookie('token')
    
    // Créer une URL avec le token d'authentification
    const url = `${apiBase}/club/payroll/export/${year}/${month}/csv`
    
    console.log('📥 [PAYROLL] Export CSV:', { url, year, month })
    
    // Utiliser fetch pour télécharger le fichier avec authentification
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token.value}`,
        'Accept': 'text/csv',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include'
    })
    
    if (!response.ok) {
      const errorText = await response.text()
      console.error('❌ [PAYROLL] Erreur réponse:', { status: response.status, errorText })
      throw new Error(`Erreur ${response.status}: ${errorText || 'Erreur lors du téléchargement du CSV'}`)
    }
    
    // Récupérer le blob
    const blob = await response.blob()
    
    // Vérifier que c'est bien un CSV
    if (!blob.type.includes('csv') && !blob.type.includes('text')) {
      console.warn('⚠️ [PAYROLL] Type de fichier inattendu:', blob.type)
    }
    
    // Créer un lien temporaire pour télécharger le fichier
    const downloadUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = downloadUrl
    
    // Nom du fichier depuis les headers ou générer un nom par défaut
    const contentDisposition = response.headers.get('Content-Disposition')
    let filename = `rapport_paie_${month}_${year}.csv`
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/i)
      if (filenameMatch && filenameMatch[1]) {
        filename = filenameMatch[1].replace(/['"]/g, '')
      }
    }
    
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(downloadUrl)
    
    console.log('✅ [PAYROLL] CSV téléchargé:', filename)
    showSuccess('Export CSV téléchargé avec succès', 'Succès')
  } catch (err: any) {
    console.error('❌ [PAYROLL] Erreur lors de l\'export CSV:', err)
    showError(err.message || 'Erreur lors de l\'export CSV', 'Erreur')
  }
}

// Computed properties pour vérifier la présence de valeurs NDCL
const hasNdclInReports = computed(() => {
  return filteredReports.value.some(report => {
    const ndcl = report.statistics?.total_commissions_ndcl || 0
    return ndcl > 0
  })
})

// Computed pour obtenir les années disponibles dans les rapports
const availableReportYears = computed(() => {
  const years = new Set<number>()
  reports.value.forEach(report => {
    years.add(report.year)
  })
  return Array.from(years).sort((a, b) => b - a) // Tri décroissant
})

// Fonction pour filtrer les rapports par année
const filterReportsByYear = () => {
  if (filterYear.value === null) {
    filteredReports.value = reports.value
  } else {
    filteredReports.value = reports.value.filter(report => report.year === filterYear.value)
  }
}

const hasNdclInSelectedReport = computed(() => {
  if (!selectedReport.value) return false
  const ndcl = selectedReport.value.statistics?.total_commissions_ndcl || 0
  return ndcl > 0
})

const hasNdclInReportDetails = computed(() => {
  if (!selectedReportDetails.value?.report) return false
  return Object.values(selectedReportDetails.value.report).some(data => {
    const ndcl = data.total_commissions_ndcl || 0
    return ndcl > 0
  })
})

function openEditModal(teacherId: number, teacherName: string) {
  console.log('🔵 [PAYROLL] openEditModal appelé:', { teacherId, teacherName, selectedReport: selectedReport.value })
  
  if (!selectedReport.value) {
    console.error('❌ [PAYROLL] selectedReport n\'est pas défini')
    showError('Veuillez d\'abord sélectionner un rapport', 'Erreur')
    return
  }
  
  selectedTeacherId.value = teacherId
  selectedTeacherName.value = teacherName
  showEditModal.value = true
  
  console.log('✅ [PAYROLL] Modale ouverte:', {
    showEditModal: showEditModal.value,
    teacherId: selectedTeacherId.value,
    teacherName: selectedTeacherName.value,
    year: selectedReport.value.year,
    month: selectedReport.value.month
  })
}

async function handlePaymentsReload() {
  // Recharger les détails du rapport après modification
  if (selectedReport.value) {
    await loadReportDetails(selectedReport.value.year, selectedReport.value.month)
  }
}

// Lifecycle
onMounted(async () => {
  await loadReports()
  // Charger automatiquement le premier rapport ou le rapport du mois en cours
  if (reports.value.length > 0) {
    // Chercher le rapport du mois en cours, sinon prendre le premier
    const currentDate = new Date()
    const currentYear = currentDate.getFullYear()
    const currentMonth = currentDate.getMonth() + 1
    
    const currentReport = reports.value.find(r => r.year === currentYear && r.month === currentMonth)
    const reportToLoad = currentReport || reports.value[0]
    
    if (reportToLoad) {
      await loadReportDetails(reportToLoad.year, reportToLoad.month)
    }
  }
})
</script>

