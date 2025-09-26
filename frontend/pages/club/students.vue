<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header avec actions -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Élèves</h1>
            <p class="mt-2 text-gray-600">
              Gérez vos élèves et leurs informations
            </p>
          </div>
          <div class="flex space-x-3">
            <button 
              @click="showAddStudentModal = true"
              class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-6 py-3 rounded-lg hover:from-emerald-600 hover:to-teal-700 transition-all duration-200 font-medium flex items-center space-x-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <span>Nouvel élève</span>
            </button>
            <button 
              @click="showAddExistingStudentModal = true"
              class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-6 py-3 rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 font-medium flex items-center space-x-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
              <span>Élève existant</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Stats rapides -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow p-6">
          <div class="flex items-center">
            <div class="p-3 bg-emerald-100 rounded-lg">
              <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total</p>
              <p class="text-2xl font-semibold text-gray-900">{{ students.length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
          <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Actifs</p>
              <p class="text-2xl font-semibold text-gray-900">{{ activeStudents }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
          <div class="flex items-center">
            <div class="p-3 bg-yellow-100 rounded-lg">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Débutants</p>
              <p class="text-2xl font-semibold text-gray-900">{{ beginnerStudents }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
          <div class="flex items-center">
            <div class="p-3 bg-purple-100 rounded-lg">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Documents</p>
              <p class="text-2xl font-semibold text-gray-900">{{ studentsWithDocuments }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtres et recherche -->
      <div class="bg-white rounded-xl shadow p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
              </div>
              <input 
                v-model="searchQuery" 
                type="text" 
                placeholder="Nom, email..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              >
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Niveau</label>
            <select 
              v-model="selectedLevel" 
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
            >
              <option value="">Tous les niveaux</option>
              <option value="debutant">🌱 Débutant</option>
              <option value="intermediaire">📈 Intermédiaire</option>
              <option value="avance">⭐ Avancé</option>
              <option value="expert">🏆 Expert</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Spécialité</label>
            <select 
              v-model="selectedDiscipline" 
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
            >
              <option value="">Toutes les spécialités</option>
              <option v-for="discipline in availableDisciplines" :key="discipline.id" :value="discipline.id">
                {{ getActivityIcon(discipline.activity_type_id) }} {{ discipline.name }}
              </option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tri</label>
            <select 
              v-model="sortBy" 
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
            >
              <option value="name">Nom (A-Z)</option>
              <option value="name_desc">Nom (Z-A)</option>
              <option value="level">Niveau (croissant)</option>
              <option value="level_desc">Niveau (décroissant)</option>
              <option value="created">Date d'inscription</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Liste des élèves -->
      <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">
            Liste des élèves ({{ filteredStudents.length }})
          </h3>
        </div>
        
        <div v-if="filteredStudents.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun élève</h3>
          <p class="mt-1 text-sm text-gray-500">Commencez par ajouter votre premier élève.</p>
          <div class="mt-6">
            <button 
              @click="showAddStudentModal = true"
              class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition-colors"
            >
              Ajouter un élève
            </button>
          </div>
        </div>
        
        <div v-else class="divide-y divide-gray-200">
          <div 
            v-for="student in filteredStudents" 
            :key="student.id" 
            class="p-6 hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <div class="bg-emerald-100 p-3 rounded-full">
                  <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                </div>
                
                <div class="flex-1">
                  <div class="flex items-center space-x-3">
                    <h4 class="text-lg font-medium text-gray-900">{{ student.name }}</h4>
                    <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                      Actif
                    </span>
                    <span v-if="student.level" class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                      {{ getLevelLabel(student.level) }}
                    </span>
                  </div>
                  
                  <div class="mt-1 flex items-center space-x-4 text-sm text-gray-600">
                    <span class="flex items-center">
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                      </svg>
                      {{ student.email }}
                    </span>
                    
                    <span v-if="student.phone" class="flex items-center">
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                      </svg>
                      {{ student.phone }}
                    </span>
                  </div>
                  
                  <div v-if="student.disciplines && student.disciplines.length > 0" class="mt-2">
                    <div class="flex flex-wrap gap-2">
                      <span 
                        v-for="discipline in student.disciplines" 
                        :key="discipline.id" 
                        class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full"
                      >
                        {{ getActivityIcon(discipline.activity_type_id) }} {{ discipline.name }}
                      </span>
                    </div>
                  </div>
                  
                  <div v-if="student.goals" class="mt-2 text-sm text-gray-600">
                    <span class="font-medium">Objectifs:</span> {{ student.goals.substring(0, 100) }}{{ student.goals.length > 100 ? '...' : '' }}
                  </div>
                  
                  <div v-if="student.medical_info" class="mt-1 text-sm text-amber-600">
                    <span class="font-medium">⚠️ Infos médicales:</span> {{ student.medical_info.substring(0, 80) }}{{ student.medical_info.length > 80 ? '...' : '' }}
                  </div>
                </div>
              </div>
              
              <div class="flex items-center space-x-2">
                <button 
                  @click="viewStudent(student)"
                  class="text-blue-600 hover:text-blue-800 p-2 hover:bg-blue-50 rounded-lg transition-colors"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                </button>
                
                <button 
                  @click="editStudent(student)"
                  class="text-emerald-600 hover:text-emerald-800 p-2 hover:bg-emerald-50 rounded-lg transition-colors"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                  </svg>
                </button>
                
                <button 
                  @click="deleteStudent(student)"
                  class="text-red-600 hover:text-red-800 p-2 hover:bg-red-50 rounded-lg transition-colors"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal d'ajout d'élève -->
    <AddStudentModal 
      v-if="showAddStudentModal" 
      @close="showAddStudentModal = false" 
      @success="loadStudents" 
    />

    <!-- Modal d'ajout d'élève existant -->
    <AddStudentAdvancedModal 
      v-if="showAddExistingStudentModal" 
      :club-id="1"
      @close="showAddExistingStudentModal = false" 
      @success="loadStudents" 
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

definePageMeta({
  middleware: ['auth']
})

const students = ref([])
const availableDisciplines = ref([])
const showAddStudentModal = ref(false)
const showAddExistingStudentModal = ref(false)
const searchQuery = ref('')
const selectedLevel = ref('')
const selectedDiscipline = ref('')
const sortBy = ref('name')

// Computed properties pour les statistiques
const activeStudents = computed(() => students.value.length)
const beginnerStudents = computed(() => 
  students.value.filter(student => student.level === 'debutant').length
)
const studentsWithDocuments = computed(() => 
  students.value.filter(student => student.medical_documents && student.medical_documents.length > 0).length
)

// Filtrage et tri des élèves
const filteredStudents = computed(() => {
  let filtered = students.value

  // Filtrage par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(student => 
      student.name.toLowerCase().includes(query) ||
      student.email.toLowerCase().includes(query)
    )
  }

  // Filtrage par niveau
  if (selectedLevel.value) {
    filtered = filtered.filter(student => student.level === selectedLevel.value)
  }

  // Filtrage par spécialité
  if (selectedDiscipline.value) {
    filtered = filtered.filter(student => 
      student.disciplines && student.disciplines.some(d => d.id == selectedDiscipline.value)
    )
  }

  // Tri
  filtered.sort((a, b) => {
    switch (sortBy.value) {
      case 'name':
        return a.name.localeCompare(b.name)
      case 'name_desc':
        return b.name.localeCompare(a.name)
      case 'level':
        const levelOrder = { 'debutant': 1, 'intermediaire': 2, 'avance': 3, 'expert': 4 }
        return (levelOrder[a.level] || 0) - (levelOrder[b.level] || 0)
      case 'level_desc':
        const levelOrderDesc = { 'debutant': 1, 'intermediaire': 2, 'avance': 3, 'expert': 4 }
        return (levelOrderDesc[b.level] || 0) - (levelOrderDesc[a.level] || 0)
      case 'created':
        return new Date(b.created_at) - new Date(a.created_at)
      default:
        return 0
    }
  })

  return filtered
})

// Obtenir le label d'un niveau
const getLevelLabel = (level) => {
  const labels = {
    debutant: '🌱 Débutant',
    intermediaire: '📈 Intermédiaire',
    avance: '⭐ Avancé',
    expert: '🏆 Expert'
  }
  return labels[level] || level
}

// Obtenir l'icône de l'activité
const getActivityIcon = (activityTypeId) => {
  const icons = {
    1: '🏇', // Équitation
    2: '🏊‍♀️', // Natation
    3: '💪', // Salle de sport
    4: '🏃‍♂️' // Coaching sportif
  }
  return icons[activityTypeId] || '🎯'
}

// Charger les élèves et spécialités
const loadStudents = async () => {
  try {
    console.log('🔄 Chargement des élèves...')
    
    // Utiliser $api qui inclut automatiquement le token via l'intercepteur
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/students')
    
    console.log('✅ Élèves reçus:', response)
    
    if (response.data.success && response.data.data) {
      students.value = response.data.data
      
      // Charger aussi les spécialités disponibles
      try {
        const disciplinesResponse = await $api.get('/disciplines')
        if (disciplinesResponse.data.success && disciplinesResponse.data.data) {
          availableDisciplines.value = disciplinesResponse.data.data
        }
      } catch (disciplineError) {
        console.warn('⚠️ Impossible de charger les disciplines:', disciplineError)
      }
    }
  } catch (error) {
    console.error('❌ Erreur lors du chargement des élèves:', error)
  }
}

// Actions sur les élèves
const viewStudent = (student) => {
  console.log('Voir élève:', student)
  // TODO: Implémenter la vue détaillée
}

const editStudent = (student) => {
  console.log('Modifier élève:', student)
  // TODO: Implémenter l'édition
}

const deleteStudent = (student) => {
  if (confirm(`Êtes-vous sûr de vouloir supprimer l'élève ${student.name} ?`)) {
    console.log('Supprimer élève:', student)
    // TODO: Implémenter la suppression
  }
}

onMounted(() => {
  loadStudents()
})
</script>
