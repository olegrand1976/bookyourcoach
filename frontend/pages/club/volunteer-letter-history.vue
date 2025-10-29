<template>
  <div class="min-h-screen bg-gray-50 p-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Historique des Lettres de Volontariat</h1>
            <p class="mt-2 text-gray-600">Consultez l'historique complet des lettres envoyées</p>
          </div>
          <NuxtLink 
            to="/club/volunteer-letter"
            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700 transition-all duration-200 shadow-sm hover:shadow-md"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Retour
          </NuxtLink>
        </div>
      </div>

      <!-- Filtres -->
      <div class="bg-white rounded-xl shadow p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="Nom de l'enseignant..."
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
            >
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
            <select 
              v-model="statusFilter" 
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
            >
              <option value="">Tous</option>
              <option value="sent">Envoyé</option>
              <option value="failed">Échoué</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Période</label>
            <select 
              v-model="periodFilter" 
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
            >
              <option value="">Toutes</option>
              <option value="today">Aujourd'hui</option>
              <option value="week">Cette semaine</option>
              <option value="month">Ce mois</option>
              <option value="year">Cette année</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Statistiques -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total envoyé</p>
              <p class="text-3xl font-bold text-blue-600">{{ stats.total }}</p>
            </div>
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Réussis</p>
              <p class="text-3xl font-bold text-green-600">{{ stats.sent }}</p>
            </div>
            <div class="p-3 bg-green-100 rounded-lg">
              <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Échoués</p>
              <p class="text-3xl font-bold text-red-600">{{ stats.failed }}</p>
            </div>
            <div class="p-3 bg-red-100 rounded-lg">
              <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Taux de succès</p>
              <p class="text-3xl font-bold text-purple-600">{{ successRate }}%</p>
            </div>
            <div class="p-3 bg-purple-100 rounded-lg">
              <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Tableau historique -->
      <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-pink-50">
          <h3 class="text-lg font-medium text-gray-900">
            Historique ({{ filteredHistory.length }} enregistrement{{ filteredHistory.length > 1 ? 's' : '' }})
          </h3>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="p-12 text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto"></div>
          <p class="text-gray-600 mt-4">Chargement de l'historique...</p>
        </div>

        <!-- Tableau -->
        <div v-else-if="filteredHistory.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enseignant</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d'envoi</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Envoyé par</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="item in filteredHistory" :key="item.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ item.teacher_name }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-600">{{ item.teacher_email }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-600">{{ formatDate(item.sent_at) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span 
                    :class="[
                      'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full',
                      item.status === 'sent' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    ]"
                  >
                    {{ item.status === 'sent' ? '✓ Envoyé' : '✗ Échoué' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                  {{ item.sent_by_name }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Aucun résultat -->
        <div v-else class="p-12 text-center text-gray-500">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun historique</h3>
          <p class="mt-1 text-sm text-gray-500">Aucune lettre de volontariat n'a encore été envoyée.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: ['auth'],
  layout: 'default'
})

const { $api } = useNuxtApp()
const toast = useToast()

// State
const history = ref([])
const loading = ref(false)
const searchQuery = ref('')
const statusFilter = ref('')
const periodFilter = ref('')

// Computed - Statistiques
const stats = computed(() => {
  const sent = history.value.filter(h => h.status === 'sent').length
  const failed = history.value.filter(h => h.status === 'failed').length
  return {
    total: history.value.length,
    sent,
    failed
  }
})

const successRate = computed(() => {
  if (stats.value.total === 0) return 0
  return Math.round((stats.value.sent / stats.value.total) * 100)
})

// Computed - Filtrage
const filteredHistory = computed(() => {
  let filtered = [...history.value]

  // Filtrer par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(item =>
      item.teacher_name?.toLowerCase().includes(query) ||
      item.teacher_email?.toLowerCase().includes(query)
    )
  }

  // Filtrer par statut
  if (statusFilter.value) {
    filtered = filtered.filter(item => item.status === statusFilter.value)
  }

  // Filtrer par période
  if (periodFilter.value) {
    const now = new Date()
    filtered = filtered.filter(item => {
      const sentDate = new Date(item.sent_at)
      switch (periodFilter.value) {
        case 'today':
          return sentDate.toDateString() === now.toDateString()
        case 'week':
          const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000)
          return sentDate >= weekAgo
        case 'month':
          return sentDate.getMonth() === now.getMonth() && sentDate.getFullYear() === now.getFullYear()
        case 'year':
          return sentDate.getFullYear() === now.getFullYear()
        default:
          return true
      }
    })
  }

  // Trier par date décroissante (plus récent en premier)
  filtered.sort((a, b) => new Date(b.sent_at) - new Date(a.sent_at))

  return filtered
})

// Méthodes
const loadHistory = async () => {
  try {
    loading.value = true
    const response = await $api.get('/club/volunteer-letters/history')
    
    if (response.data.success) {
      history.value = response.data.history || []
    }
  } catch (error) {
    console.error('Erreur chargement historique:', error)
    toast.error('Erreur lors du chargement de l\'historique')
  } finally {
    loading.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

// Lifecycle
onMounted(() => {
  loadHistory()
})
</script>

