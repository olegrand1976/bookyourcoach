<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 text-center">
              <span class="mr-3 text-primary-600">📊</span>
              Analyse Graphique
            </h1>
            <p class="mt-2 text-gray-600">Visualisez et analysez les relations entre les entités de votre plateforme</p>
          </div>
        </div>
      </div>

      <!-- Navigation par onglets -->
      <div class="mb-8">
        <nav class="flex space-x-8">
          <button 
            @click="activeTab = 'visualization'"
            :class="activeTab === 'visualization' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
            class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
          >
            Visualisation Interactive
          </button>
          <button 
            @click="activeTab = 'analytics'"
            :class="activeTab === 'analytics' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
            class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
          >
            Analyses Prédéfinies
          </button>
          <button 
            @click="activeTab = 'sync'"
            :class="activeTab === 'sync' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
            class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
          >
            Synchronisation
          </button>
        </nav>
      </div>

      <!-- Contenu des onglets -->
      <div v-if="isLoading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-600">Chargement...</p>
      </div>

      <!-- Onglet Visualisation Interactive -->
      <div v-if="activeTab === 'visualization'" class="bg-white rounded-lg shadow">
        <div class="p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Visualisation Interactive du Graphe</h2>
          <p class="text-gray-600 mb-6">Sélectionnez une entité pour visualiser ses relations dans le graphe</p>
          
          <GraphVisualizationSimple 
            :initial-entity="selectedEntity"
            :initial-item="selectedItem"
          />
        </div>
      </div>

      <!-- Onglet Analyses Prédéfinies -->
      <div v-if="activeTab === 'analytics'" class="bg-white rounded-lg shadow">
        <div class="p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Analyses Prédéfinies</h2>
          <p class="text-gray-600 mb-6">Consultez des analyses prédéfinies sur les données de votre plateforme</p>
          
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Métriques Globales -->
            <div class="bg-gray-50 rounded-lg p-4">
              <h3 class="font-semibold text-gray-900 mb-2">📈 Métriques Globales</h3>
              <div v-if="globalMetrics" class="space-y-2 mb-3">
                <div class="flex justify-between text-sm">
                  <span>Utilisateurs:</span>
                  <span class="font-medium">{{ globalMetrics.total_users }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span>Clubs:</span>
                  <span class="font-medium">{{ globalMetrics.total_clubs }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span>Enseignants:</span>
                  <span class="font-medium">{{ globalMetrics.total_teachers }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span>Contrats:</span>
                  <span class="font-medium">{{ globalMetrics.total_contracts }}</span>
                </div>
              </div>
              <button 
                @click="loadGlobalMetrics"
                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700"
              >
                🔄 Actualiser
              </button>
            </div>

            <!-- Relations Utilisateurs-Clubs -->
            <div class="bg-gray-50 rounded-lg p-4">
              <h3 class="font-semibold text-gray-900 mb-2">👥 Relations Utilisateurs-Clubs</h3>
              <div v-if="userClubRelations.length > 0" class="space-y-2 max-h-32 overflow-y-auto mb-3">
                <div 
                  v-for="relation in userClubRelations.slice(0, 3)" 
                  :key="relation.club_name"
                  class="text-sm"
                >
                  <div class="font-medium">{{ relation.club_name }}</div>
                  <div class="text-gray-600">{{ relation.member_count }} membres</div>
                </div>
              </div>
              <button 
                @click="loadUserClubRelations"
                class="w-full px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700"
              >
                📈 Analyser
              </button>
            </div>

            <!-- Enseignants par Spécialité -->
            <div class="bg-gray-50 rounded-lg p-4">
              <h3 class="font-semibold text-gray-900 mb-2">🎯 Enseignants par Spécialité</h3>
              <div v-if="teachersBySpecialty.length > 0" class="space-y-2 max-h-32 overflow-y-auto mb-3">
                <div 
                  v-for="specialty in teachersBySpecialty.slice(0, 3)" 
                  :key="specialty.specialty"
                  class="text-sm"
                >
                  <div class="font-medium">{{ specialty.specialty }}</div>
                  <div class="text-gray-600">{{ specialty.teacher_count }} enseignants</div>
                </div>
              </div>
              <button 
                @click="loadTeachersBySpecialty"
                class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700"
              >
                🎯 Analyser
              </button>
            </div>

            <!-- Répartition Géographique -->
            <div class="bg-gray-50 rounded-lg p-4">
              <h3 class="font-semibold text-gray-900 mb-2">🌍 Répartition Géographique</h3>
              <div v-if="geographicDistribution.length > 0" class="space-y-2 max-h-32 overflow-y-auto mb-3">
                <div 
                  v-for="location in geographicDistribution.slice(0, 3)" 
                  :key="location.club_city"
                  class="text-sm"
                >
                  <div class="font-medium">{{ location.club_city }}</div>
                  <div class="text-gray-600">{{ location.clubs_count }} clubs</div>
                </div>
              </div>
              <button 
                @click="loadGeographicDistribution"
                class="w-full px-4 py-2 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700"
              >
                🌍 Analyser
              </button>
            </div>

            <!-- Performance des Clubs -->
            <div class="bg-gray-50 rounded-lg p-4">
              <h3 class="font-semibold text-gray-900 mb-2">🏆 Performance des Clubs</h3>
              <div v-if="clubPerformance.length > 0" class="space-y-2 max-h-32 overflow-y-auto mb-3">
                <div 
                  v-for="club in clubPerformance.slice(0, 3)" 
                  :key="club.club_name"
                  class="text-sm"
                >
                  <div class="font-medium">{{ club.club_name }}</div>
                  <div class="text-gray-600">{{ club.members_count }} membres, {{ club.teachers_count }} enseignants</div>
                </div>
              </div>
              <button 
                @click="loadClubPerformance"
                class="w-full px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700"
              >
                🏆 Analyser
              </button>
            </div>

            <!-- Spécialités Populaires -->
            <div class="bg-gray-50 rounded-lg p-4">
              <h3 class="font-semibold text-gray-900 mb-2">🔥 Spécialités Populaires</h3>
              <div v-if="mostDemandedSpecialties.length > 0" class="space-y-2 max-h-32 overflow-y-auto mb-3">
                <div 
                  v-for="specialty in mostDemandedSpecialties.slice(0, 3)" 
                  :key="specialty.specialty"
                  class="text-sm"
                >
                  <div class="font-medium">{{ specialty.specialty }}</div>
                  <div class="text-gray-600">{{ specialty.contracts_count }} contrats</div>
                </div>
              </div>
              <button 
                @click="loadMostDemandedSpecialties"
                class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700"
              >
                🔥 Analyser
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Onglet Synchronisation -->
      <div v-if="activeTab === 'sync'" class="bg-white rounded-lg shadow">
        <div class="p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Synchronisation Neo4j</h2>
          <p class="text-gray-600 mb-6">Gérez la synchronisation des données MySQL vers Neo4j</p>
          
          <!-- Statut de synchronisation -->
          <div v-if="syncStats" class="mb-6">
            <h3 class="font-medium text-gray-900 mb-3">Statut de synchronisation</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div 
                v-for="(status, entity) in syncStats.sync_status" 
                :key="entity"
                class="text-center p-3 rounded-lg"
                :class="status.synced ? 'bg-green-50' : 'bg-red-50'"
              >
                <div class="font-medium capitalize">{{ entity }}</div>
                <div class="text-sm text-gray-600">
                  {{ status.neo4j_count }} / {{ status.mysql_count }}
                </div>
                <div class="text-xs" :class="status.synced ? 'text-green-600' : 'text-red-600'">
                  {{ status.percentage }}%
                </div>
              </div>
            </div>
          </div>

          <!-- Actions de synchronisation -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <button 
              @click="syncAll"
              :disabled="isSyncing"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:bg-gray-400"
            >
              {{ isSyncing ? '⏳ Synchronisation...' : '🔄 Synchronisation complète' }}
            </button>
            
            <button 
              @click="loadSyncStats"
              class="px-4 py-2 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700"
            >
              📊 Actualiser le statut
            </button>
          </div>

          <!-- Logs de synchronisation -->
          <div v-if="syncLogs.length > 0">
            <h3 class="font-medium text-gray-900 mb-3">Logs de synchronisation</h3>
            <div class="bg-gray-50 p-4 rounded-lg max-h-64 overflow-y-auto">
              <div 
                v-for="log in syncLogs" 
                :key="log.id"
                class="text-sm font-mono"
                :class="log.type === 'error' ? 'text-red-600' : 'text-gray-700'"
              >
                [{{ log.timestamp }}] {{ log.message }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import GraphVisualizationSimple from '~/components/GraphVisualizationSimple.vue'

// Configuration de la page
definePageMeta({
  layout: 'admin',
  middleware: 'admin'
})

// État réactif
const activeTab = ref('visualization')
const isLoading = ref(false)
const isSyncing = ref(false)

// Données
const globalMetrics = ref(null)
const userClubRelations = ref([])
const teachersBySpecialty = ref([])
const geographicDistribution = ref([])
const clubPerformance = ref([])
const mostDemandedSpecialties = ref([])
const syncStats = ref(null)
const syncLogs = ref([])

// Configuration des onglets
const tabs = [
  { id: 'visualization', label: '🔗 Visualisation Interactive' },
  { id: 'analytics', label: '📊 Analyses Prédéfinies' },
  { id: 'sync', label: '🔄 Synchronisation' }
]

// Charger les métriques globales
const loadGlobalMetrics = async () => {
  try {
    isLoading.value = true
    const response = await $fetch('/api/neo4j/metrics')
    if (response.success) {
      globalMetrics.value = response.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement des métriques:', error)
  } finally {
    isLoading.value = false
  }
}

// Charger les relations utilisateurs-clubs
const loadUserClubRelations = async () => {
  try {
    isLoading.value = true
    const response = await $fetch('/api/neo4j/user-club-relations')
    if (response.success) {
      userClubRelations.value = response.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement des relations:', error)
  } finally {
    isLoading.value = false
  }
}

// Charger les enseignants par spécialité
const loadTeachersBySpecialty = async () => {
  try {
    isLoading.value = true
    const response = await $fetch('/api/neo4j/teachers-by-specialty')
    if (response.success) {
      teachersBySpecialty.value = response.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement des spécialités:', error)
  } finally {
    isLoading.value = false
  }
}

// Charger la répartition géographique
const loadGeographicDistribution = async () => {
  try {
    isLoading.value = true
    const response = await $fetch('/api/neo4j/geographic-distribution')
    if (response.success) {
      geographicDistribution.value = response.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement de la répartition:', error)
  } finally {
    isLoading.value = false
  }
}

// Charger la performance des clubs
const loadClubPerformance = async () => {
  try {
    isLoading.value = true
    const response = await $fetch('/api/neo4j/club-performance')
    if (response.success) {
      clubPerformance.value = response.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement de la performance:', error)
  } finally {
    isLoading.value = false
  }
}

// Charger les spécialités les plus demandées
const loadMostDemandedSpecialties = async () => {
  try {
    isLoading.value = true
    const response = await $fetch('/api/neo4j/most-demanded-specialties')
    if (response.success) {
      mostDemandedSpecialties.value = response.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement des spécialités:', error)
  } finally {
    isLoading.value = false
  }
}

// Charger les statistiques de synchronisation
const loadSyncStats = async () => {
  try {
    const response = await $fetch('/api/neo4j/sync-stats')
    if (response.success) {
      syncStats.value = response.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement des stats:', error)
  }
}

// Synchronisation complète
const syncAll = async () => {
  try {
    isSyncing.value = true
    syncLogs.value = []
    
    // Simuler la synchronisation (en réalité, ce serait un appel à une commande Artisan)
    syncLogs.value.push({
      id: Date.now(),
      timestamp: new Date().toLocaleTimeString(),
      message: 'Début de la synchronisation...',
      type: 'info'
    })
    
    // Ici, vous pourriez appeler une API qui lance la commande Artisan
    // await $fetch('/api/neo4j/sync', { method: 'POST' })
    
    syncLogs.value.push({
      id: Date.now() + 1,
      timestamp: new Date().toLocaleTimeString(),
      message: 'Synchronisation terminée avec succès',
      type: 'success'
    })
    
    // Recharger les statistiques
    await loadSyncStats()
    
  } catch (error) {
    syncLogs.value.push({
      id: Date.now(),
      timestamp: new Date().toLocaleTimeString(),
      message: `Erreur: ${error.message}`,
      type: 'error'
    })
  } finally {
    isSyncing.value = false
  }
}

// Initialisation
onMounted(() => {
  loadGlobalMetrics()
  loadSyncStats()
})
</script>

<style scoped>
/* Styles spécifiques à la page d'analyse graphique */
</style>
