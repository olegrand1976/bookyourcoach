<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header avec actions -->
      <div class="mb-6 md:mb-8">
        <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Élèves</h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">
              Gérez vos élèves et leurs informations
            </p>
          </div>
          <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
            <button 
              @click="showAddStudentModal = true"
              class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg hover:from-emerald-600 hover:to-teal-700 transition-all duration-200 font-medium flex items-center justify-center space-x-2 text-sm md:text-base"
            >
              <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <span>Nouvel élève</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Stats rapides -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow p-6">
          <div class="flex items-center">
            <div class="p-3 bg-emerald-100 rounded-lg">
              <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats?.total || 0 }}</p>
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
              <p class="text-2xl font-semibold text-gray-900">{{ stats?.active || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
          <div class="flex items-center">
            <div class="p-3 bg-gray-100 rounded-lg">
              <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Inactifs</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats?.inactive || 0 }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtres et recherche -->
      <div class="bg-white rounded-xl shadow p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
            <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
            <select 
              v-model="selectedStatus" 
              @change="loadStudents(1)"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
            >
              <option value="active">Actifs</option>
              <option value="inactive">Inactifs</option>
              <option value="all">Tous</option>
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
              <option value="joined_desc">Date d'inscription (plus récent)</option>
              <option value="joined_asc">Date d'inscription (plus ancien)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Liste des élèves -->
      <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
          <h3 class="text-lg font-medium text-gray-900">
            Liste des élèves ({{ totalStudents }})
          </h3>
          <div v-if="pagination && pagination.total > pagination.per_page" class="text-sm text-gray-600">
            Page {{ pagination.current_page }} sur {{ pagination.last_page }}
          </div>
        </div>
        
        <div v-if="loading" class="text-center py-12">
          <svg class="animate-spin h-8 w-8 text-emerald-600 mx-auto" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="mt-2 text-sm text-gray-600">Chargement...</p>
        </div>
        
        <div v-else-if="filteredStudents.length === 0" class="text-center py-12">
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
            class="p-4 md:p-6 hover:bg-gray-50 transition-colors"
          >
            <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
              <div class="flex items-start space-x-3 md:space-x-4 flex-1">
                <div class="bg-emerald-100 p-2 md:p-3 rounded-full flex-shrink-0">
                  <svg class="w-5 h-5 md:w-6 md:h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                </div>
                
                <div class="flex-1 min-w-0">
                  <div class="flex items-start flex-wrap gap-2 mb-2">
                    <h4 class="text-base md:text-lg font-medium text-gray-900 break-words">{{ getStudentName(student) }}</h4>
                    <span v-if="student.is_active !== false" class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full flex-shrink-0">
                      Actif
                    </span>
                    <span v-else class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full flex-shrink-0">
                      Inactif
                    </span>
                  </div>
                  
                  <div class="mt-1 space-y-1 text-xs md:text-sm text-gray-600">
                    <div class="flex items-center break-all">
                      <svg class="w-3 h-3 md:w-4 md:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                      </svg>
                      <span>{{ getStudentEmail(student) }}</span>
                    </div>
                    
                    <div v-if="student.phone" class="flex items-center">
                      <svg class="w-3 h-3 md:w-4 md:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                      </svg>
                      <span>{{ student.phone }}</span>
                    </div>
                    
                    <div v-if="student.joined_at" class="flex items-center">
                      <svg class="w-3 h-3 md:w-4 md:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                      </svg>
                      <span>Inscrit le {{ formatDate(student.joined_at) }}</span>
                    </div>
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
              
              <div class="flex items-center space-x-1 md:space-x-2 ml-auto md:ml-0">
                <button 
                  @click="viewStudentHistory(student)"
                  class="text-purple-600 hover:text-purple-800 p-1.5 md:p-2 hover:bg-purple-50 rounded-lg transition-colors"
                  title="Historique (abonnements + cours)"
                >
                  <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                </button>
                
                <button 
                  @click="viewStudentSubscriptions(student)"
                  class="text-blue-600 hover:text-blue-800 p-1.5 md:p-2 hover:bg-blue-50 rounded-lg transition-colors"
                  title="Abonnements"
                >
                  <svg class="w-4 h-4 md:w-5 md:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                </button>
                
                <button 
                  @click="resendInvitation(student.id)"
                  :disabled="resending[student.id]"
                  class="text-purple-600 hover:text-purple-800 p-1.5 md:p-2 hover:bg-purple-50 rounded-lg transition-colors disabled:opacity-50"
                  title="Renvoyer l'email"
                >
                  <svg v-if="resending[student.id]" class="animate-spin h-4 w-4 md:h-5 md:w-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <svg v-else class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                  </svg>
                </button>
                
                <button 
                  @click="editStudent(student)"
                  class="text-emerald-600 hover:text-emerald-800 p-1.5 md:p-2 hover:bg-emerald-50 rounded-lg transition-colors"
                  title="Modifier"
                >
                  <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                  </svg>
                </button>
                
                <button 
                  @click="toggleStudentStatus(student)"
                  :class="[
                    'p-1.5 md:p-2 rounded-lg transition-colors',
                    student.is_active 
                      ? 'text-yellow-600 hover:text-yellow-800 hover:bg-yellow-50' 
                      : 'text-green-600 hover:text-green-800 hover:bg-green-50'
                  ]"
                  :title="student.is_active ? 'Désactiver' : 'Activer'"
                >
                  <svg v-if="student.is_active" class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                  </svg>
                  <svg v-else class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                </button>
                
                <button 
                  @click="deleteStudent(student)"
                  class="text-red-600 hover:text-red-800 p-1.5 md:p-2 hover:bg-red-50 rounded-lg transition-colors"
                  title="Supprimer"
                >
                  <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              Affichage de {{ ((pagination.current_page - 1) * pagination.per_page) + 1 }} à {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} sur {{ pagination.total }} élèves
            </div>
            <div class="flex space-x-2">
              <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="pagination.current_page === 1"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Précédent
              </button>
              <template v-for="page in getPageNumbers()" :key="page">
                <button
                  v-if="page !== '...'"
                  @click="changePage(page)"
                  :class="[
                    'px-4 py-2 text-sm font-medium rounded-lg',
                    page === pagination.current_page
                      ? 'bg-emerald-600 text-white'
                      : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
                  ]"
                >
                  {{ page }}
                </button>
                <span v-else class="px-4 py-2 text-sm font-medium text-gray-500">
                  {{ page }}
                </span>
              </template>
              <button
                @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Suivant
              </button>
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

    <!-- Modal de modification d'élève -->
    <EditStudentModal 
      v-if="showEditStudentModal && selectedStudent" 
      :student="selectedStudent"
      @close="closeEditModal" 
      @success="loadStudents" 
    />

    <!-- Modal de gestion des abonnements -->
    <StudentSubscriptionsModal 
      v-if="showSubscriptionsModal && selectedStudent" 
      :student="selectedStudent"
      @close="showSubscriptionsModal = false" 
      @success="loadStudents" 
    />

    <!-- Modal d'historique -->
    <StudentHistoryModal 
      v-if="showHistoryModal && selectedStudent" 
      :student="selectedStudent"
      @close="showHistoryModal = false" 
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import EditStudentModal from '~/components/EditStudentModal.vue'
import StudentSubscriptionsModal from '~/components/StudentSubscriptionsModal.vue'
import StudentHistoryModal from '~/components/StudentHistoryModal.vue'

definePageMeta({
  middleware: ['auth']
})

const students = ref([])
const stats = ref({ total: 0, active: 0, inactive: 0 })
const availableDisciplines = ref([])
const showAddStudentModal = ref(false)
const showEditStudentModal = ref(false)
const showSubscriptionsModal = ref(false)
const showHistoryModal = ref(false)
const selectedStudent = ref(null)
const searchQuery = ref('')
const selectedStatus = ref('active')
const sortBy = ref('name')
const resending = ref({})
const loading = ref(false)
const pagination = ref(null)
const currentPage = ref(1)
const perPage = 20

// Les statistiques sont maintenant fournies par le backend via stats.value
const totalStudents = computed(() => {
  // Utiliser le filtre en cours pour afficher le bon total
  if (selectedStatus.value === 'active') {
    return stats.value.active
  } else if (selectedStatus.value === 'inactive') {
    return stats.value.inactive
  } else {
    return stats.value.total
  }
})

// Helper pour obtenir le nom complet d'un élève de manière sécurisée
const getStudentName = (student) => {
  if (student?.name) return student.name
  if (student?.first_name || student?.last_name) {
    const name = ((student.first_name || '') + ' ' + (student.last_name || '')).trim()
    return name || 'Élève sans nom'
  }
  if (student?.student_first_name || student?.student_last_name) {
    const name = ((student.student_first_name || '') + ' ' + (student.student_last_name || '')).trim()
    return name || 'Élève sans nom'
  }
  return 'Élève sans nom'
}

// Helper pour obtenir l'email d'un élève de manière sécurisée
const getStudentEmail = (student) => {
  return student.email || 'Pas d\'email'
}

// Formater une date en français
const formatDate = (dateString) => {
  if (!dateString) return 'Date inconnue'
  
  const date = new Date(dateString)
  if (isNaN(date.getTime())) return 'Date invalide'
  
  const options = { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric',
    timeZone: 'UTC'
  }
  
  return date.toLocaleDateString('fr-FR', options)
}

// Filtrage et tri des élèves
const filteredStudents = computed(() => {
  let filtered = students.value

  // Filtrage par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(student => {
      const name = getStudentName(student).toLowerCase()
      const email = getStudentEmail(student).toLowerCase()
      return name.includes(query) || email.includes(query)
    })
  }

  // Tri
  filtered.sort((a, b) => {
    switch (sortBy.value) {
      case 'name':
        const nameA = getStudentName(a)
        const nameB = getStudentName(b)
        return nameA.localeCompare(nameB)
      case 'name_desc':
        const nameADesc = getStudentName(a)
        const nameBDesc = getStudentName(b)
        return nameBDesc.localeCompare(nameADesc)
      case 'joined_desc':
        // Plus récent en premier
        const dateA = a.joined_at ? new Date(a.joined_at) : new Date(0)
        const dateB = b.joined_at ? new Date(b.joined_at) : new Date(0)
        return dateB - dateA
      case 'joined_asc':
        // Plus ancien en premier
        const dateAAsc = a.joined_at ? new Date(a.joined_at) : new Date(0)
        const dateBAsc = b.joined_at ? new Date(b.joined_at) : new Date(0)
        return dateAAsc - dateBAsc
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

// Charger les élèves avec pagination
const loadStudents = async (page = 1) => {
  try {
    loading.value = true
    console.log('🔄 Chargement des élèves...', { 
      page, 
      status: selectedStatus.value,
      selectedStatusType: typeof selectedStatus.value
    })
    
    // Utiliser $api qui inclut automatiquement le token via l'intercepteur
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/students', {
      params: {
        page: page,
        per_page: perPage,
        status: selectedStatus.value || 'active' // S'assurer qu'une valeur est toujours envoyée
      }
    })
    
    console.log('✅ Élèves reçus:', {
      count: response.data.data?.length,
      status: selectedStatus.value,
      stats: response.data.stats,
      pagination: response.data.pagination
    })
    
    if (response.data.success && response.data.data) {
      students.value = response.data.data
      pagination.value = response.data.pagination || null
      stats.value = response.data.stats || { total: 0, active: 0, inactive: 0 }
      currentPage.value = page
      
      // Charger aussi les spécialités disponibles (pour les afficher dans les cards)
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
  } finally {
    loading.value = false
  }
}

// Changer de page
const changePage = (page) => {
  if (page >= 1 && page <= (pagination.value?.last_page || 1)) {
    loadStudents(page)
    // Scroll en haut de la page
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

// Obtenir les numéros de page à afficher
const getPageNumbers = () => {
  if (!pagination.value) return []
  
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages = []
  
  if (last <= 7) {
    // Si moins de 7 pages, afficher toutes
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    // Sinon, afficher intelligemment
    if (current <= 4) {
      // Au début
      for (let i = 1; i <= 5; i++) {
        pages.push(i)
      }
      pages.push('...')
      pages.push(last)
    } else if (current >= last - 3) {
      // À la fin
      pages.push(1)
      pages.push('...')
      for (let i = last - 4; i <= last; i++) {
        pages.push(i)
      }
    } else {
      // Au milieu
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) {
        pages.push(i)
      }
      pages.push('...')
      pages.push(last)
    }
  }
  
  return pages.filter((p, i, arr) => {
    if (p === '...') return arr[i - 1] !== '...'
    return true
  })
}

// Actions sur les élèves
const viewStudent = (student) => {
  console.log('Voir élève:', student)
  // TODO: Implémenter la vue détaillée
}

const editStudent = (student) => {
  console.log('📝 Modifier élève:', student)
  selectedStudent.value = { ...student }
  showEditStudentModal.value = true
}

const viewStudentHistory = (student) => {
  console.log('👁️ Voir historique de l\'élève:', student)
  selectedStudent.value = { ...student }
  showHistoryModal.value = true
}

const viewStudentSubscriptions = (student) => {
  console.log('📋 Voir abonnements de l\'élève:', student)
  selectedStudent.value = { ...student }
  showSubscriptionsModal.value = true
}

const closeEditModal = () => {
  showEditStudentModal.value = false
  selectedStudent.value = null
}

const toggleStudentStatus = async (student) => {
  const action = student.is_active ? 'désactiver' : 'activer'
  if (!confirm(`Êtes-vous sûr de vouloir ${action} l'élève ${getStudentName(student)} ?`)) {
    return
  }
  
  try {
    const { $api } = useNuxtApp()
    const response = await $api.patch(`/club/students/${student.id}/toggle-status`)
    
    console.log('✅ Statut de l\'élève modifié:', response)
    
    if (response.data.success) {
      alert(response.data.message || `Élève ${action} avec succès`)
      loadStudents(currentPage.value)
    } else {
      alert('Erreur lors de la modification du statut de l\'élève')
    }
  } catch (error) {
    console.error('❌ Erreur lors de la modification du statut:', error)
    alert('Erreur lors de la modification du statut. Veuillez réessayer.')
  }
}

const deleteStudent = async (student) => {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer définitivement l'élève ${getStudentName(student)} de votre club ? Cette action est irréversible.`)) {
    return
  }
  
  try {
    const { $api } = useNuxtApp()
    const response = await $api.delete(`/club/students/${student.id}`)
    
    console.log('✅ Élève supprimé:', response)
    
    if (response.data.success) {
      alert(response.data.message || 'Élève retiré du club avec succès')
      loadStudents(currentPage.value)
    } else {
      alert('Erreur lors de la suppression de l\'élève')
    }
  } catch (error) {
    console.error('❌ Erreur lors de la suppression de l\'élève:', error)
    alert('Erreur lors de la suppression de l\'élève. Veuillez réessayer.')
  }
}

const resendInvitation = async (studentId) => {
  resending.value[studentId] = true
  
  try {
    const { $api } = useNuxtApp()
    const response = await $api.post(`/club/students/${studentId}/resend-invitation`)
    
    console.log('✅ Email renvoyé:', response)
    
    if (response.data.success) {
      alert(response.data.message || 'Email d\'invitation renvoyé avec succès')
    } else {
      alert(response.data.message || 'Erreur lors du renvoi de l\'invitation')
    }
    
  } catch (error) {
    console.error('❌ Erreur lors du renvoi de l\'invitation:', error)
    const errorMessage = error.response?.data?.message || error.message || 'Erreur lors du renvoi de l\'invitation. Veuillez réessayer.'
    alert(errorMessage)
  } finally {
    resending.value[studentId] = false
  }
}

onMounted(() => {
  loadStudents()
})
</script>
