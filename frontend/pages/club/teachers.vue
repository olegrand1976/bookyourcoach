<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header avec actions -->
      <div class="mb-6 md:mb-8">
        <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Enseignants</h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">
              Gérez vos enseignants et leurs informations
            </p>
          </div>
          <div>
            <button 
              @click="showNewTeacherModal = true"
              class="w-full sm:w-auto bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 font-medium flex items-center justify-center space-x-2 shadow-md hover:shadow-lg text-sm md:text-base"
            >
              <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <span>Ajouter un enseignant</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Stats rapides -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow p-6">
          <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total</p>
              <p class="text-2xl font-semibold text-gray-900">{{ teachers.length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
          <div class="flex items-center">
            <div class="p-3 bg-green-100 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Actifs</p>
              <p class="text-2xl font-semibold text-gray-900">{{ activeTeachers }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
          <div class="flex items-center">
            <div class="p-3 bg-yellow-100 rounded-lg">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Tarif moyen</p>
              <p class="text-2xl font-semibold text-gray-900">{{ averageRate }}€</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
          <div class="flex items-center">
            <div class="p-3 bg-purple-100 rounded-lg">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Expérience moy.</p>
              <p class="text-2xl font-semibold text-gray-900">{{ averageExperience }} ans</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtres et recherche -->
      <div class="bg-white rounded-xl shadow p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tri</label>
            <select 
              v-model="sortBy" 
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="name">Nom (A-Z)</option>
              <option value="name_desc">Nom (Z-A)</option>
              <option value="experience">Expérience (croissant)</option>
              <option value="experience_desc">Expérience (décroissant)</option>
              <option value="rate">Tarif (croissant)</option>
              <option value="rate_desc">Tarif (décroissant)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Liste des enseignants -->
      <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">
            Liste des enseignants ({{ filteredTeachers.length }})
          </h3>
        </div>
        
        <div v-if="filteredTeachers.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun enseignant</h3>
          <p class="mt-1 text-sm text-gray-500">Commencez par ajouter votre premier enseignant.</p>
          <div class="mt-6">
            <button 
              @click="showAddTeacherModal = true"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
            >
              Ajouter un enseignant
            </button>
          </div>
        </div>
        
        <div v-else class="divide-y divide-gray-200">
          <div 
            v-for="teacher in filteredTeachers" 
            :key="teacher.id" 
            class="p-4 md:p-6 hover:bg-gray-50 transition-colors"
          >
            <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
              <div class="flex items-start space-x-3 md:space-x-4 flex-1">
                <div class="bg-blue-100 p-2 md:p-3 rounded-full flex-shrink-0">
                  <svg class="w-5 h-5 md:w-6 md:h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                </div>
                
                <div class="flex-1 min-w-0">
                  <div class="flex items-start flex-wrap gap-2 mb-2">
                    <h4 class="text-base md:text-lg font-medium text-gray-900 break-words">{{ teacher.name }}</h4>
                    <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full flex-shrink-0">
                      Actif
                    </span>
                  </div>
                  
                  <div class="mt-1 space-y-1 text-xs md:text-sm text-gray-600">
                    <div class="flex items-center break-all">
                      <svg class="w-3 h-3 md:w-4 md:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                      </svg>
                      <span>{{ teacher.email }}</span>
                    </div>
                    
                    <div v-if="teacher.phone" class="flex items-center">
                      <svg class="w-3 h-3 md:w-4 md:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                      </svg>
                      <span>{{ teacher.phone }}</span>
                    </div>
                  </div>
                  
                  <div class="mt-2 flex items-center flex-wrap gap-2 text-xs md:text-sm">
                    <span class="flex items-center text-blue-600">
                      <svg class="w-3 h-3 md:w-4 md:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                      </svg>
                      {{ teacher.hourly_rate }}€/h
                    </span>
                    
                    <span class="flex items-center text-purple-600">
                      <svg class="w-3 h-3 md:w-4 md:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                      </svg>
                      {{ teacher.experience_years }} ans
                    </span>
                    
                    <span v-if="teacher.contract_type" class="px-2 py-1 text-xs font-medium bg-indigo-100 text-indigo-800 rounded-full">
                      {{ getContractTypeLabel(teacher.contract_type) }}
                    </span>
                  </div>
                  
                  <!-- Spécialisations masquées (informatives uniquement, non obligatoires) -->
                  
                  <div v-if="teacher.bio" class="mt-2 text-sm text-gray-600">
                    {{ teacher.bio.substring(0, 150) }}{{ teacher.bio.length > 150 ? '...' : '' }}
                  </div>
                </div>
              </div>
              
              <div class="flex items-center space-x-1 md:space-x-2 ml-auto md:ml-0">
                <button 
                  @click="resendInvitation(teacher.id)"
                  :disabled="resending[teacher.id]"
                  class="text-green-600 hover:text-green-800 p-1.5 md:p-2 hover:bg-green-50 rounded-lg transition-colors disabled:opacity-50"
                  title="Renvoyer l'email"
                >
                  <svg v-if="resending[teacher.id]" class="animate-spin h-4 w-4 md:h-5 md:w-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <svg v-else class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                  </svg>
                </button>
                
                <button 
                  @click="editTeacher(teacher)"
                  class="text-blue-600 hover:text-blue-800 p-1.5 md:p-2 hover:bg-blue-50 rounded-lg transition-colors"
                  title="Modifier"
                >
                  <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                  </svg>
                </button>
                
                <button 
                  @click="deleteTeacher(teacher)"
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
      </div>
    </div>

    <!-- Modal d'ajout d'enseignant -->
    <AddTeacherModal 
      v-if="showNewTeacherModal" 
      @close="showNewTeacherModal = false" 
      @success="loadTeachers" 
    />
    
    <!-- Modal de modification d'enseignant -->
    <EditTeacherModal 
      v-if="showEditTeacherModal && selectedTeacher" 
      :teacher="selectedTeacher"
      @close="closeEditModal" 
      @success="loadTeachers" 
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import EditTeacherModal from '~/components/EditTeacherModal.vue'

definePageMeta({
  middleware: ['auth']
})

const teachers = ref([])
const showNewTeacherModal = ref(false)
const showEditTeacherModal = ref(false)
const selectedTeacher = ref(null)
const searchQuery = ref('')
const sortBy = ref('name')
const resending = ref({})

// Computed properties pour les statistiques
const activeTeachers = computed(() => teachers.value.length)
const averageRate = computed(() => {
  if (teachers.value.length === 0) return 0
  
  // Filtrer les enseignants avec un tarif valide
  const teachersWithRate = teachers.value.filter(t => t.hourly_rate && t.hourly_rate > 0)
  if (teachersWithRate.length === 0) return 0
  
  const total = teachersWithRate.reduce((sum, teacher) => sum + teacher.hourly_rate, 0)
  return Math.round(total / teachersWithRate.length)
})
const averageExperience = computed(() => {
  if (teachers.value.length === 0) return 0
  
  // Filtrer les enseignants avec une expérience valide
  const teachersWithExp = teachers.value.filter(t => t.experience_years && t.experience_years > 0)
  if (teachersWithExp.length === 0) return 0
  
  const total = teachersWithExp.reduce((sum, teacher) => sum + teacher.experience_years, 0)
  return Math.round(total / teachersWithExp.length)
})

// Filtrage et tri des enseignants
const filteredTeachers = computed(() => {
  let filtered = teachers.value

  // Filtrage par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(teacher => 
      teacher.name.toLowerCase().includes(query) ||
      teacher.email.toLowerCase().includes(query)
    )
  }

  // Tri
  filtered.sort((a, b) => {
    switch (sortBy.value) {
      case 'name':
        return a.name.localeCompare(b.name)
      case 'name_desc':
        return b.name.localeCompare(a.name)
      case 'experience':
        return (a.experience_years || 0) - (b.experience_years || 0)
      case 'experience_desc':
        return (b.experience_years || 0) - (a.experience_years || 0)
      case 'rate':
        return (a.hourly_rate || 0) - (b.hourly_rate || 0)
      case 'rate_desc':
        return (b.hourly_rate || 0) - (a.hourly_rate || 0)
      default:
        return 0
    }
  })

  return filtered
})

// Helper pour les labels de type de contrat
const getContractTypeLabel = (contractType) => {
  const labels = {
    'freelance': 'Indépendant',
    'employee': 'Salarié',
    'volunteer': 'Bénévole',
    'article17': 'Article 17',
    'student': 'Étudiant',
    'intern': 'Stagiaire'
  }
  return labels[contractType] || contractType
}

// Charger les enseignants
const loadTeachers = async () => {
  try {
    console.log('🔄 Chargement des enseignants...')
    
    // Utiliser $api qui inclut automatiquement le token via l'intercepteur
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/teachers')
    
    console.log('✅ Enseignants reçus:', response)
    
    if (response.data.success && response.data.teachers) {
      // Mapper les données pour inclure les informations de l'utilisateur
      teachers.value = response.data.teachers.map(teacher => {
        return {
          id: teacher.id,
          name: teacher.user?.name || 'N/A',
          email: teacher.user?.email || 'N/A',
          phone: teacher.user?.phone || null,
          hourly_rate: parseFloat(teacher.hourly_rate) || 0,
          experience_years: parseInt(teacher.experience_years) || 0,
          bio: teacher.bio || '',
          contract_type: teacher.contract_type || 'freelance'
        }
      })
      
      console.log('✅ Enseignants mappés:', teachers.value)
    }
  } catch (error) {
    console.error('❌ Erreur lors du chargement des enseignants:', error)
    const toast = useToast()
    toast.error('Erreur lors du chargement des enseignants')
  }
}

// Actions sur les enseignants
const editTeacher = (teacher) => {
  console.log('📝 Modifier enseignant:', teacher)
  console.log('📝 Teacher object:', JSON.stringify(teacher, null, 2))
  selectedTeacher.value = { ...teacher } // Créer une copie pour éviter les problèmes de réactivité
  showEditTeacherModal.value = true
  console.log('📝 Modal ouvert:', showEditTeacherModal.value)
  console.log('📝 Selected teacher:', selectedTeacher.value)
}

const closeEditModal = () => {
  showEditTeacherModal.value = false
  selectedTeacher.value = null
}

const deleteTeacher = async (teacher) => {
  if (!confirm(`Êtes-vous sûr de vouloir retirer l'enseignant ${teacher.name} de votre club ?`)) {
    return
  }
  
  try {
    const { $api } = useNuxtApp()
    const toast = useToast()
    const response = await $api.delete(`/club/teachers/${teacher.id}`)
    
    console.log('✅ Enseignant supprimé:', response)
    
    if (response.data.success) {
      toast.success(response.data.message || 'Enseignant retiré du club avec succès')
      await loadTeachers()
    } else {
      toast.error('Erreur lors de la suppression de l\'enseignant')
    }
  } catch (error) {
    console.error('❌ Erreur lors de la suppression de l\'enseignant:', error)
    const toast = useToast()
    toast.error(error.response?.data?.message || 'Erreur lors de la suppression de l\'enseignant. Veuillez réessayer.')
  }
}

// Renvoyer l'invitation à un enseignant
const resendInvitation = async (teacherId) => {
  resending.value[teacherId] = true
  const toast = useToast()
  
  try {
    const { $api } = useNuxtApp()
    const response = await $api.post(`/club/teachers/${teacherId}/resend-invitation`)
    
    console.log('✅ Email renvoyé:', response)
    
    if (response.data.success) {
      toast.success(response.data.message || 'Email d\'invitation renvoyé avec succès')
    } else {
      toast.error(response.data.message || 'Erreur lors du renvoi de l\'invitation')
    }
    
  } catch (error) {
    console.error('❌ Erreur lors du renvoi de l\'invitation:', error)
    
    // Afficher le message d'erreur du serveur s'il existe
    const errorMessage = error.response?.data?.message || error.message || 'Erreur lors du renvoi de l\'invitation. Veuillez réessayer.'
    toast.error(errorMessage)
  } finally {
    resending.value[teacherId] = false
  }
}

onMounted(() => {
  loadTeachers()
})
</script>
