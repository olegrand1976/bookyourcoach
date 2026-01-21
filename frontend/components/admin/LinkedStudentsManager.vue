<template>
  <div class="border-t border-gray-100 pt-6 mt-6">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center space-x-3">
        <div class="bg-purple-100 p-2 rounded-lg">
          <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
          </svg>
        </div>
        <div>
          <h4 class="text-base font-semibold text-gray-900">Comptes liés (Famille)</h4>
          <p class="text-sm text-gray-500">Lier cet étudiant à d'autres comptes étudiants</p>
        </div>
      </div>
    </div>

    <!-- Liste des comptes liés -->
    <div v-if="linkedStudents.length > 0" class="mb-4">
      <div class="space-y-2">
        <div
          v-for="linkedStudent in linkedStudents"
          :key="linkedStudent.id"
          class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200"
        >
          <div class="flex-1">
            <div class="font-medium text-gray-900">{{ linkedStudent.name }}</div>
            <div class="text-sm text-gray-600">{{ linkedStudent.email }}</div>
          </div>
          <button
            @click="removeLink(linkedStudent.id)"
            :disabled="loading"
            class="ml-4 text-red-600 hover:text-red-800 p-2 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-50"
            title="Retirer le lien"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Message si aucun compte lié -->
    <div v-else class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200 text-center text-sm text-gray-600">
      Aucun compte lié pour le moment
    </div>

    <!-- Recherche et sélection d'un étudiant à lier -->
    <div class="border-t border-gray-200 pt-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Lier à un autre compte étudiant
      </label>
      <div class="flex gap-2">
        <div class="flex-1">
          <input
            v-model="searchQuery"
            @input="searchStudents"
            type="text"
            placeholder="Rechercher par nom ou email..."
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
          />
        </div>
        <button
          @click="searchStudents"
          :disabled="loading"
          class="px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors disabled:opacity-50"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </button>
      </div>

      <!-- Résultats de recherche -->
      <div v-if="searchResults.length > 0" class="mt-3 max-h-48 overflow-y-auto border border-gray-200 rounded-lg">
        <div
          v-for="student in searchResults"
          :key="student.id"
          @click="linkStudent(student)"
          class="p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors"
        >
          <div class="font-medium text-gray-900">{{ student.name }}</div>
          <div class="text-sm text-gray-600">{{ student.email }}</div>
        </div>
      </div>

      <!-- Message si aucun résultat -->
      <div v-if="searchQuery && searchResults.length === 0 && !loading" class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
        Aucun étudiant trouvé. Assurez-vous que l'étudiant a un compte avec email.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'

const props = defineProps({
  studentId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['link-created', 'link-removed'])

const loading = ref(false)
const linkedStudents = ref([])
const searchQuery = ref('')
const searchResults = ref([])

// Charger les comptes liés
const loadLinkedStudents = async () => {
  if (!props.studentId) return
  
  loading.value = true
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get(`/admin/students/${props.studentId}/linked`)
    
    if (response.data.success) {
      linkedStudents.value = response.data.data || []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des comptes liés:', error)
  } finally {
    loading.value = false
  }
}

// Rechercher des étudiants disponibles
const searchStudents = async () => {
  if (!searchQuery.value.trim() || !props.studentId) {
    searchResults.value = []
    return
  }
  
  loading.value = true
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/admin/students/available-for-linking', {
      params: {
        exclude_student_id: props.studentId
      }
    })
    
    if (response.data.success) {
      // Filtrer les résultats selon la recherche
      const query = searchQuery.value.toLowerCase().trim()
      const allStudents = response.data.data || []
      
      searchResults.value = allStudents.filter(student => {
        const name = (student.name || '').toLowerCase()
        const email = (student.email || '').toLowerCase()
        return name.includes(query) || email.includes(query)
      })
    }
  } catch (error) {
    console.error('Erreur lors de la recherche:', error)
    searchResults.value = []
  } finally {
    loading.value = false
  }
}

// Lier un étudiant
const linkStudent = async (student) => {
  if (!props.studentId) return
  
  loading.value = true
  try {
    const { $api } = useNuxtApp()
    const response = await $api.post(`/admin/students/${props.studentId}/link`, {
      linked_student_id: student.id
    })
    
    if (response.data.success) {
      // Recharger la liste des comptes liés
      await loadLinkedStudents()
      
      // Réinitialiser la recherche
      searchQuery.value = ''
      searchResults.value = []
      
      emit('link-created', student)
    }
  } catch (error) {
    console.error('Erreur lors de la liaison:', error)
    alert(error.response?.data?.message || 'Erreur lors de la liaison des comptes')
  } finally {
    loading.value = false
  }
}

// Retirer un lien
const removeLink = async (linkedStudentId) => {
  if (!props.studentId || !confirm('Êtes-vous sûr de vouloir retirer ce lien ?')) {
    return
  }
  
  loading.value = true
  try {
    const { $api } = useNuxtApp()
    const response = await $api.delete(`/admin/students/${props.studentId}/unlink/${linkedStudentId}`)
    
    if (response.data.success) {
      // Recharger la liste des comptes liés
      await loadLinkedStudents()
      
      emit('link-removed', linkedStudentId)
    }
  } catch (error) {
    console.error('Erreur lors de la suppression du lien:', error)
    alert(error.response?.data?.message || 'Erreur lors de la suppression du lien')
  } finally {
    loading.value = false
  }
}

// Watcher pour recharger quand studentId change
watch(() => props.studentId, (newId) => {
  if (newId) {
    loadLinkedStudents()
  } else {
    linkedStudents.value = []
  }
}, { immediate: true })

onMounted(() => {
  if (props.studentId) {
    loadLinkedStudents()
  }
})
</script>
