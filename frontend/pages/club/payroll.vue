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
      <!-- Statistics Cards -->
      <div v-if="selectedReport" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
          <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Enseignants</p>
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
              <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(selectedReport.statistics?.total_commissions_dcl || selectedReport.statistics?.total_commissions_type1 || 0) }}</p>
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
              <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(selectedReport.statistics?.total_commissions_ndcl || selectedReport.statistics?.total_commissions_type2 || 0) }}</p>
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
              <p class="text-sm font-medium text-gray-600">Total à Payer (€)</p>
              <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(selectedReport.statistics?.total_a_payer || 0) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Reports Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">Rapports disponibles</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Période</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enseignants</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DCL (€)</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NDCL (€)</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total (€)</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="report in reports" :key="`${report.year}-${report.month}`" 
                  :class="selectedReport?.year === report.year && selectedReport?.month === report.month ? 'bg-blue-50' : 'hover:bg-gray-50'"
                  @click="loadReportDetails(report.year, report.month)"
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
                  {{ formatCurrency(report.statistics?.total_commissions_dcl || report.statistics?.total_commissions_type1 || 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatCurrency(report.statistics?.total_commissions_ndcl || report.statistics?.total_commissions_type2 || 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                  {{ formatCurrency(report.statistics?.total_a_payer || 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex space-x-2">
                    <button
                      @click.stop="loadReportDetails(report.year, report.month)"
                      class="text-blue-600 hover:text-blue-900"
                      title="Voir les détails"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <a
                      :href="getExportUrl(report.year, report.month)"
                      @click.stop
                      class="text-green-600 hover:text-green-900"
                      title="Exporter en CSV"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </a>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Report Details -->
      <div v-if="selectedReport && selectedReportDetails" class="bg-white rounded-lg shadow overflow-hidden mt-6">
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enseignant</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commissions DCL (€)</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commissions NDCL (€)</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total à Payer (€)</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(data, teacherId) in selectedReportDetails.report" :key="teacherId">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ data.enseignant_id }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ data.nom_enseignant }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatCurrency(data.total_commissions_dcl || data.total_commissions_type1 || 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatCurrency(data.total_commissions_ndcl || data.total_commissions_type2 || 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                  {{ formatCurrency(data.total_a_payer) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

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

<script setup>
console.log('📊 [PAYROLL] Page chargée')

definePageMeta({
  middleware: ['auth']
})

const { $api } = useNuxtApp()
const config = useRuntimeConfig()

console.log('📊 [PAYROLL] $api disponible:', !!$api)
console.log('📊 [PAYROLL] config:', config.public?.apiBase)

// State
const loading = ref(true)
const error = ref(null)
const reports = ref([])
const selectedReport = ref(null)
const selectedReportDetails = ref(null)
const showGenerateModal = ref(false)
const generating = ref(false)

// Generate form
const currentDate = new Date()
const generateYear = ref(currentDate.getFullYear())
const generateMonth = ref(currentDate.getMonth() + 1)

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
  } catch (err) {
    console.error('❌ [PAYROLL] Erreur lors du chargement des rapports:', err)
    console.error('❌ [PAYROLL] Erreur complète:', {
      message: err.message,
      response: err.response?.data,
      status: err.response?.status
    })
    error.value = err.response?.data?.message || err.message || 'Erreur lors du chargement des rapports'
  } finally {
    loading.value = false
    console.log('📊 [PAYROLL] Chargement terminé, loading:', loading.value)
  }
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

const generateReport = async () => {
  generating.value = true
  
  try {
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
    } else {
      error.value = response.data?.message || response.message || 'Erreur lors de la génération'
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

const getExportUrl = (year, month) => {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase || '/api'
  return `${apiBase}/club/payroll/export/${year}/${month}/csv`
}

// Lifecycle
onMounted(() => {
  loadReports()
})
</script>

