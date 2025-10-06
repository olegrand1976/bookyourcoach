<template>
  <div class="predictive-analysis">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg">
          <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
          </svg>
        </div>
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Analyse Prédictive IA</h2>
          <p class="text-sm text-gray-500">Powered by Google Gemini</p>
        </div>
      </div>
      
      <button 
        @click="refreshAnalysis"
        :disabled="loading"
        class="px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg hover:from-purple-600 hover:to-pink-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 shadow-md"
      >
        <svg v-if="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <svg v-else class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span>{{ loading ? 'Analyse...' : 'Actualiser' }}</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading && !analysis" class="flex flex-col items-center justify-center py-16">
      <div class="relative">
        <div class="w-20 h-20 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin"></div>
        <div class="absolute inset-0 flex items-center justify-center">
          <svg class="w-10 h-10 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
          </svg>
        </div>
      </div>
      <p class="mt-6 text-lg font-medium text-gray-700">Analyse en cours avec l'IA...</p>
      <p class="mt-2 text-sm text-gray-500">Cela peut prendre quelques secondes</p>
    </div>

    <!-- No Data State -->
    <div v-else-if="!analysis && !loading" class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-12 text-center border-2 border-dashed border-blue-200">
      <svg class="w-20 h-20 mx-auto text-blue-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <h3 class="text-xl font-bold text-gray-800 mb-2">Analyse Prédictive Indisponible</h3>
      <p class="text-gray-600 max-w-md mx-auto mb-4">
        L'analyse prédictive nécessite plus de données historiques ou le service Neo4j.
      </p>
      <div class="bg-white rounded-lg p-4 max-w-md mx-auto text-left">
        <p class="text-sm text-gray-700 mb-2">💡 <strong>Pour activer cette fonctionnalité :</strong></p>
        <ul class="text-sm text-gray-600 space-y-1 ml-4">
          <li>• Enregistrez au moins 20 cours sur 8 semaines</li>
          <li>• Vérifiez que Neo4j est démarré</li>
          <li>• Patientez quelques minutes après l'ajout de cours</li>
        </ul>
      </div>
    </div>

    <!-- Analysis Results -->
    <div v-else class="space-y-6">
      <!-- Summary -->
      <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-2xl p-6 border border-purple-200">
        <div class="flex items-start gap-3">
          <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 24 24">
            <path d="M13 7h-2v4H7v2h4v4h2v-4h4v-2h-4V7zm-1-5C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
          </svg>
          <div class="flex-1">
            <h3 class="font-bold text-lg text-purple-900 mb-2">📊 Résumé de l'Analyse</h3>
            <p class="text-gray-700 leading-relaxed">{{ analysis.summary }}</p>
            <p class="text-xs text-gray-500 mt-3">
              Généré le {{ formatDate(analysis.generated_at) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Insights Cards -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div 
          v-for="(insight, index) in analysis.insights" 
          :key="index"
          class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-shadow overflow-hidden border border-gray-100"
        >
          <!-- Card Header with Priority Badge -->
          <div :class="[
            'p-5 border-b',
            getInsightHeaderClass(insight.priority)
          ]">
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <div :class="[
                  'w-10 h-10 rounded-xl flex items-center justify-center',
                  getInsightIconBg(insight.type)
                ]">
                  <span class="text-2xl">{{ getInsightIcon(insight.type) }}</span>
                </div>
                <div>
                  <h4 class="font-bold text-gray-900">{{ insight.title }}</h4>
                  <span :class="[
                    'text-xs font-semibold px-2 py-1 rounded-full',
                    getPriorityClass(insight.priority)
                  ]">
                    {{ getPriorityLabel(insight.priority) }}
                  </span>
                </div>
              </div>
              
              <!-- Confidence Badge -->
              <div class="flex flex-col items-end" v-if="insight.confidence">
                <div class="text-xs text-gray-500 mb-1">Confiance</div>
                <div :class="[
                  'px-3 py-1 rounded-full text-sm font-bold',
                  insight.confidence >= 80 ? 'bg-green-100 text-green-700' :
                  insight.confidence >= 60 ? 'bg-yellow-100 text-yellow-700' :
                  'bg-orange-100 text-orange-700'
                ]">
                  {{ insight.confidence }}%
                </div>
              </div>
            </div>
          </div>

          <!-- Card Body -->
          <div class="p-5">
            <p class="text-gray-700 leading-relaxed mb-4">
              {{ insight.description }}
            </p>

            <!-- Impact -->
            <div v-if="insight.impact" class="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-lg p-4 mb-4 border border-blue-100">
              <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <div>
                  <p class="text-sm font-semibold text-blue-900 mb-1">Impact attendu</p>
                  <p class="text-sm text-blue-800">{{ insight.impact }}</p>
                </div>
              </div>
            </div>

            <!-- Additional Data -->
            <div v-if="insight.data && Object.keys(insight.data).length > 0" class="bg-gray-50 rounded-lg p-4">
              <details class="cursor-pointer">
                <summary class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                  Données détaillées
                </summary>
                <div class="mt-3 space-y-2">
                  <div v-for="(value, key) in insight.data" :key="key" class="flex justify-between text-sm">
                    <span class="text-gray-600 capitalize">{{ formatKey(key) }}:</span>
                    <span class="font-medium text-gray-900">{{ value }}</span>
                  </div>
                </div>
              </details>
            </div>
          </div>
        </div>
      </div>

      <!-- Next Actions -->
      <div v-if="analysis.next_actions && analysis.next_actions.length > 0" class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-6 border border-green-200">
        <div class="flex items-start gap-3">
          <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
          <div class="flex-1">
            <h3 class="font-bold text-lg text-green-900 mb-3">🎯 Actions Recommandées</h3>
            <ul class="space-y-2">
              <li v-for="(action, index) in analysis.next_actions" :key="index" class="flex items-start gap-2">
                <span class="text-green-600 font-bold text-sm">{{ index + 1 }}.</span>
                <span class="text-gray-700">{{ action }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const { $api } = useNuxtApp()

const loading = ref(false)
const analysis = ref(null)

const loadAnalysis = async () => {
  loading.value = true
  try {
    const response = await $api.get('/club/predictive-analysis')
    if (response.data.success && response.data.data) {
      analysis.value = response.data.data
    } else {
      // Pas assez de données ou service indisponible
      console.log('Analyse prédictive indisponible:', response.data.message)
      analysis.value = null
    }
  } catch (error) {
    // Gérer gracieusement toutes les erreurs (500, network, etc.)
    console.warn('Analyse prédictive temporairement indisponible:', error.message)
    analysis.value = null
  } finally {
    loading.value = false
  }
}

const refreshAnalysis = () => {
  loadAnalysis()
}

// Helpers
const getInsightIcon = (type) => {
  const icons = {
    prediction: '🔮',
    recommendation: '💡',
    alert: '⚠️',
    opportunity: '🎯'
  }
  return icons[type] || '📊'
}

const getInsightIconBg = (type) => {
  const colors = {
    prediction: 'bg-purple-100',
    recommendation: 'bg-blue-100',
    alert: 'bg-red-100',
    opportunity: 'bg-green-100'
  }
  return colors[type] || 'bg-gray-100'
}

const getInsightHeaderClass = (priority) => {
  const classes = {
    high: 'bg-red-50 border-red-200',
    medium: 'bg-yellow-50 border-yellow-200',
    low: 'bg-blue-50 border-blue-200'
  }
  return classes[priority] || 'bg-gray-50 border-gray-200'
}

const getPriorityClass = (priority) => {
  const classes = {
    high: 'bg-red-100 text-red-700',
    medium: 'bg-yellow-100 text-yellow-700',
    low: 'bg-blue-100 text-blue-700'
  }
  return classes[priority] || 'bg-gray-100 text-gray-700'
}

const getPriorityLabel = (priority) => {
  const labels = {
    high: '🔴 Haute',
    medium: '🟡 Moyenne',
    low: '🔵 Basse'
  }
  return labels[priority] || 'Normale'
}

const formatKey = (key) => {
  return key.replace(/_/g, ' ')
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  loadAnalysis()
})
</script>

<style scoped>
.predictive-analysis {
  @apply w-full;
}

details summary::-webkit-details-marker {
  display: none;
}

details[open] summary svg {
  transform: rotate(90deg);
}

details summary svg {
  transition: transform 0.2s;
}
</style>
