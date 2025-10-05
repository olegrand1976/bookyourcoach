<template>
  <div class="graph-visualization">
    <!-- Contrôles de filtrage -->
    <div class="graph-controls mb-6 p-4 bg-white rounded-lg shadow">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Sélection d'entité -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Entité de départ
          </label>
          <select 
            v-model="selectedEntity" 
            @change="onEntityChange"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Sélectionner une entité</option>
            <option value="club">Club</option>
            <option value="teacher">Enseignant</option>
            <option value="user">Utilisateur</option>
            <option value="contract">Contrat</option>
          </select>
        </div>

        <!-- Sélection d'élément -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ entityLabel }}
          </label>
          <select 
            v-model="selectedItem" 
            @change="onItemChange"
            :disabled="!selectedEntity || isLoading"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
          >
            <option value="">
              {{ isLoading ? 'Chargement...' : `Sélectionner ${entityLabel.toLowerCase()}` }}
            </option>
            <option 
              v-for="item in entityItems" 
              :key="item.id" 
              :value="item.id"
              :disabled="item.id === 'error'"
            >
              {{ item.name }}
            </option>
          </select>
          <div v-if="entityItems.length === 0 && selectedEntity && !isLoading" class="text-sm text-red-600 mt-1">
            Aucun {{ entityLabel.toLowerCase() }} trouvé
          </div>
        </div>

        <!-- Profondeur de recherche -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Profondeur
          </label>
          <select 
            v-model="searchDepth" 
            @change="loadGraphData"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="1">1 niveau</option>
            <option value="2">2 niveaux</option>
            <option value="3">3 niveaux</option>
          </select>
        </div>

        <!-- Actions -->
        <div class="flex items-end">
          <button 
            @click="loadGraphData"
            :disabled="!selectedItem"
            class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
          >
            🔍 Analyser
          </button>
        </div>
      </div>

      <!-- Filtres avancés -->
      <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Filtrer par statut
            </label>
            <select 
              v-model="statusFilter" 
              @change="loadGraphData"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Tous les statuts</option>
              <option value="active">Actif</option>
              <option value="inactive">Inactif</option>
              <option value="pending">En attente</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Filtrer par ville
            </label>
            <select 
              v-model="cityFilter" 
              @change="loadGraphData"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Toutes les villes</option>
              <option 
                v-for="city in cities" 
                :key="city" 
                :value="city"
              >
                {{ city }}
              </option>
            </select>
          </div>

          <div class="flex items-end">
            <button 
              @click="resetFilters"
              class="w-full px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
            >
              🔄 Réinitialiser
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistiques -->
    <div v-if="graphStats" class="mb-6 p-4 bg-blue-50 rounded-lg">
      <h3 class="text-lg font-semibold text-blue-900 mb-2">📊 Statistiques du graphe</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div>
          <span class="font-medium">Nœuds:</span> {{ graphStats.nodes }}
        </div>
        <div>
          <span class="font-medium">Relations:</span> {{ graphStats.edges }}
        </div>
        <div>
          <span class="font-medium">Clubs:</span> {{ graphStats.clubs }}
        </div>
        <div>
          <span class="font-medium">Enseignants:</span> {{ graphStats.teachers }}
        </div>
      </div>
    </div>

    <!-- Conteneur du graphe -->
    <div class="graph-container">
      <div 
        ref="cyContainer" 
        class="w-full h-96 border border-gray-300 rounded-lg bg-gray-50"
        :class="{ 'loading': isLoading }"
      >
        <div v-if="isLoading" class="flex items-center justify-center h-full">
          <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600">Chargement du graphe...</p>
          </div>
        </div>
        <div v-else-if="!graphData" class="flex items-center justify-center h-full">
          <div class="text-center text-gray-500">
            <div class="text-6xl mb-4">🔍</div>
            <p>Sélectionnez une entité pour visualiser ses relations</p>
          </div>
        </div>
        <div v-else-if="graphData && !cytoscapeLoaded" class="flex items-center justify-center h-full">
          <div class="text-center text-gray-500">
            <div class="text-6xl mb-4">⏳</div>
            <p>Chargement de la bibliothèque de visualisation...</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Légende -->
    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
      <h4 class="font-semibold text-gray-800 mb-2">🎨 Légende</h4>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div class="flex items-center">
          <div class="w-4 h-4 bg-blue-500 rounded-full mr-2"></div>
          <span>Clubs</span>
        </div>
        <div class="flex items-center">
          <div class="w-4 h-4 bg-green-500 rounded-full mr-2"></div>
          <span>Enseignants</span>
        </div>
        <div class="flex items-center">
          <div class="w-4 h-4 bg-purple-500 rounded-full mr-2"></div>
          <span>Utilisateurs</span>
        </div>
        <div class="flex items-center">
          <div class="w-4 h-4 bg-orange-500 rounded-full mr-2"></div>
          <span>Contrats</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, computed } from 'vue'

// Props
const props = defineProps({
  initialEntity: {
    type: String,
    default: ''
  },
  initialItem: {
    type: [String, Number],
    default: ''
  }
})

// État réactif
const cyContainer = ref(null)
const cy = ref(null)
const isLoading = ref(false)
const graphData = ref(null)
const graphStats = ref(null)
const cytoscapeLoaded = ref(false)

// Filtres
const selectedEntity = ref(props.initialEntity)
const selectedItem = ref(props.initialItem)
const searchDepth = ref(2)
const statusFilter = ref('')
const cityFilter = ref('')

// Données
const entityItems = ref([])
const cities = ref([])

// Labels des entités
const entityLabels = {
  club: 'Club',
  teacher: 'Enseignant', 
  user: 'Utilisateur',
  contract: 'Contrat'
}

const entityLabel = computed(() => entityLabels[selectedEntity.value] || 'Élément')

// Charger les éléments selon l'entité sélectionnée
const onEntityChange = async () => {
  selectedItem.value = ''
  entityItems.value = []
  
  if (!selectedEntity.value) return
  
  try {
    isLoading.value = true
    console.log('Chargement des éléments pour:', selectedEntity.value)
    
    // Utiliser le bon endpoint selon l'entité
    const endpoint = `/api/admin/${selectedEntity.value}s`
    console.log('Endpoint:', endpoint)
    
    const response = await $fetch(endpoint)
    console.log('Réponse API:', response)
    
    if (response.success) {
      entityItems.value = response.data.map(item => ({
        id: item.id,
        name: item.name || `${item.first_name || ''} ${item.last_name || ''}`.trim() || item.email || `Élément #${item.id}`
      }))
      console.log('Éléments chargés:', entityItems.value)
    } else {
      console.error('Erreur dans la réponse API:', response)
    }
  } catch (error) {
    console.error('Erreur lors du chargement des éléments:', error)
    // Afficher un message d'erreur à l'utilisateur
    entityItems.value = [{
      id: 'error',
      name: 'Erreur de chargement - Vérifiez la connexion'
    }]
  } finally {
    isLoading.value = false
  }
}

// Charger les données du graphe
const onItemChange = () => {
  if (selectedItem.value) {
    loadGraphData()
  }
}

// Charger les données du graphe depuis Neo4j
const loadGraphData = async () => {
  if (!selectedItem.value || !selectedEntity.value) return
  
  try {
    isLoading.value = true
    
    const params = new URLSearchParams({
      entity: selectedEntity.value,
      id: selectedItem.value,
      depth: searchDepth.value,
      status: statusFilter.value,
      city: cityFilter.value
    })
    
    const response = await $fetch(`/api/neo4j/graph-visualization?${params}`)
    
    if (response.success) {
      graphData.value = response.data
      graphStats.value = response.stats
      
      await nextTick()
      await renderGraph()
    }
  } catch (error) {
    console.error('Erreur lors du chargement du graphe:', error)
  } finally {
    isLoading.value = false
  }
}

// Rendre le graphe avec Cytoscape
const renderGraph = async () => {
  if (!graphData.value || !cyContainer.value) return
  
  try {
    // Charger Cytoscape dynamiquement
    const cytoscapeModule = await import('cytoscape')
    const cytoscape = cytoscapeModule.default
    
    // Charger l'extension cose-bilkent
    const coseBilkentModule = await import('cytoscape-cose-bilkent')
    const coseBilkent = coseBilkentModule.default
    
    // Enregistrer l'extension
    cytoscape.use(coseBilkent)
    
    cytoscapeLoaded.value = true
    
    // Détruire l'instance précédente
    if (cy.value) {
      cy.value.destroy()
    }
    
    // Configuration du style
    const style = [
      {
        selector: 'node',
        style: {
          'background-color': 'data(color)',
          'label': 'data(label)',
          'text-valign': 'center',
          'text-halign': 'center',
          'font-size': '12px',
          'font-weight': 'bold',
          'color': '#fff',
          'text-outline-width': 2,
          'text-outline-color': '#000',
          'width': 'data(size)',
          'height': 'data(size)',
          'border-width': 2,
          'border-color': '#fff'
        }
      },
      {
        selector: 'edge',
        style: {
          'width': 3,
          'line-color': 'data(color)',
          'target-arrow-color': 'data(color)',
          'target-arrow-shape': 'triangle',
          'curve-style': 'bezier',
          'label': 'data(label)',
          'font-size': '10px',
          'text-rotation': 'autorotate',
          'text-margin-y': -10
        }
      },
      {
        selector: 'node:selected',
        style: {
          'border-width': 4,
          'border-color': '#ff6b6b'
        }
      },
      {
        selector: 'edge:selected',
        style: {
          'line-color': '#ff6b6b',
          'target-arrow-color': '#ff6b6b'
        }
      }
    ]
    
    // Configuration du layout
    const layout = {
      name: 'cose-bilkent',
      idealEdgeLength: 100,
      nodeRepulsion: 4500,
      edgeElasticity: 0.45,
      nestingFactor: 0.1,
      gravity: 0.25,
      numIter: 2500,
      tile: true,
      animate: true,
      animationDuration: 1000,
      tilingPaddingVertical: 10,
      tilingPaddingHorizontal: 10
    }
    
    // Créer l'instance Cytoscape
    cy.value = cytoscape({
      container: cyContainer.value,
      elements: graphData.value,
      style: style,
      layout: layout,
      minZoom: 0.1,
      maxZoom: 3,
      wheelSensitivity: 0.1
    })
    
    // Ajouter les événements
    cy.value.on('tap', 'node', (event) => {
      const node = event.target
      console.log('Nœud sélectionné:', node.data())
    })
    
    cy.value.on('tap', 'edge', (event) => {
      const edge = event.target
      console.log('Relation sélectionnée:', edge.data())
    })
    
    // Ajuster la vue
    cy.value.fit()
    
  } catch (error) {
    console.error('Erreur lors du chargement de Cytoscape:', error)
    // Fallback: afficher les données sous forme de liste
    showFallbackVisualization()
  }
}

// Visualisation de fallback si Cytoscape ne charge pas
const showFallbackVisualization = () => {
  console.log('Utilisation de la visualisation de fallback')
  // Ici on pourrait afficher les données sous forme de liste ou de tableau
}

// Réinitialiser les filtres
const resetFilters = () => {
  selectedEntity.value = ''
  selectedItem.value = ''
  searchDepth.value = 2
  statusFilter.value = ''
  cityFilter.value = ''
  entityItems.value = []
  graphData.value = null
  graphStats.value = null
  cytoscapeLoaded.value = false
  
  if (cy.value) {
    cy.value.destroy()
    cy.value = null
  }
}

// Charger les villes
const loadCities = async () => {
  try {
    const response = await $fetch('/api/admin/clubs')
    if (response.success) {
      cities.value = [...new Set(response.data.map(club => club.city).filter(Boolean))]
      console.log('Villes chargées:', cities.value)
    }
  } catch (error) {
    console.error('Erreur lors du chargement des villes:', error)
  }
}

// Initialisation
onMounted(() => {
  loadCities()
  
  if (props.initialEntity && props.initialItem) {
    onEntityChange()
  }
})
</script>

<style scoped>
.graph-visualization {
  @apply p-6;
}

.graph-container {
  position: relative;
}

.loading {
  @apply opacity-50 pointer-events-none;
}

/* Styles pour Cytoscape */
:deep(.cytoscape-container) {
  @apply rounded-lg;
}

:deep(.cy-node) {
  cursor: pointer;
}

:deep(.cy-edge) {
  cursor: pointer;
}
</style>
