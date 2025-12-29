<template>
  <!-- Modal avec design moderne et responsive -->
  <div class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
      <!-- Header avec gradient et icône -->
      <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="bg-white bg-opacity-20 p-2 rounded-lg">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
            </div>
            <div>
              <h3 class="text-xl font-bold text-white">Modifier l'élève</h3>
              <p class="text-emerald-100 text-sm">Mettez à jour les informations</p>
            </div>
          </div>
          <button @click="$emit('close')" class="text-white hover:text-emerald-200 transition-colors p-2 hover:bg-white hover:bg-opacity-20 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- Contenu avec scroll -->
      <div class="overflow-y-auto max-h-[calc(90vh-120px)]">
        <form @submit.prevent="updateStudent" class="p-6 space-y-8">
          
          <!-- Section Informations personnelles -->
          <div class="bg-gray-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-emerald-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">Informations personnelles</h4>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Prénom <span class="text-gray-500 text-xs">(facultatif)</span>
                </label>
                <input 
                  v-model="form.first_name" 
                  type="text" 
                  placeholder="Ex: Jean"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                >
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Nom <span class="text-gray-500 text-xs">(facultatif)</span>
                </label>
                <input 
                  v-model="form.last_name" 
                  type="text" 
                  placeholder="Ex: Dupont"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                >
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Email <span class="text-gray-500 text-xs">(facultatif)</span>
                </label>
                <input 
                  v-model="form.email" 
                  type="email" 
                  placeholder="Ex: jean.dupont@email.com"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                >
                <p class="text-xs text-gray-500 mt-1">
                  Si aucun email n'est fourni, un compte utilisateur ne sera pas créé. Vous pourrez compléter ces informations plus tard.
                </p>
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                <input 
                  v-model="form.phone" 
                  type="tel" 
                  placeholder="Ex: 06 12 34 56 78"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                >
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Date de naissance
                </label>
                <div class="flex items-center gap-3">
                  <input 
                    v-model="form.date_of_birth" 
                    type="date" 
                    :max="maxDate"
                    class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                  >
                  <div v-if="calculatedAge !== null" class="flex items-center bg-emerald-100 px-4 py-3 rounded-lg min-w-[100px] justify-center">
                    <span class="text-lg font-bold text-emerald-700">{{ calculatedAge }} ans</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Section Spécialités -->
          <div v-if="availableDisciplines.length > 0" class="bg-blue-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-blue-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">Spécialités d'intérêt</h4>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div 
                v-for="discipline in availableDisciplines" 
                :key="discipline.id" 
                class="flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 hover:shadow-md"
                :class="selectedDisciplines.includes(discipline.id) 
                  ? 'border-emerald-500 bg-emerald-50 shadow-md' 
                  : 'border-gray-200 bg-white hover:border-gray-300'"
                @click="toggleDiscipline(discipline.id)"
              >
                <input 
                  :id="'discipline-' + discipline.id" 
                  v-model="selectedDisciplines" 
                  :value="discipline.id" 
                  type="checkbox" 
                  class="h-5 w-5 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded"
                >
                <label :for="'discipline-' + discipline.id" class="ml-4 flex items-center cursor-pointer flex-1">
                  <span class="text-2xl mr-3">{{ getActivityIcon(discipline.activity_type_id) }}</span>
                  <div>
                    <div class="font-medium text-gray-900">{{ discipline.name }}</div>
                    <div class="text-sm text-gray-500">{{ discipline.description }}</div>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <!-- Section Objectifs et Infos médicales -->
          <div class="bg-purple-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-purple-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">Objectifs et informations médicales</h4>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Objectifs <span class="text-gray-500 text-xs">(facultatif)</span>
                </label>
                <textarea 
                  v-model="form.goals" 
                  rows="4" 
                  placeholder="Décrivez les objectifs de l'élève..."
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors resize-none"
                ></textarea>
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Informations médicales <span class="text-gray-500 text-xs">(facultatif)</span>
                </label>
                <textarea 
                  v-model="form.medical_info" 
                  rows="4" 
                  placeholder="Allergies, limitations, informations importantes..."
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors resize-none"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Section Documents médicaux améliorée -->
          <div class="bg-amber-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-amber-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
              <div>
                <h4 class="text-lg font-semibold text-gray-900">Documents médicaux</h4>
                <p class="text-sm text-gray-600">Ajoutez les documents nécessaires (certificats, assurances...)</p>
              </div>
            </div>
            
            <!-- Liste des documents avec design amélioré -->
            <div v-if="medicalDocuments.length > 0" class="space-y-4">
              <div 
                v-for="(doc, index) in medicalDocuments" 
                :key="doc.id || index" 
                class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow"
              >
                <div class="flex items-center justify-between mb-4">
                  <div class="flex items-center space-x-3">
                    <div class="bg-blue-100 p-2 rounded-lg">
                      <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                      </svg>
                    </div>
                    <h5 class="font-medium text-gray-900">{{ doc.document_type || `Document ${index + 1}` }}</h5>
                    <span v-if="doc.id" class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">Existant</span>
                  </div>
                  <button 
                    type="button" 
                    @click="removeDocument(index)" 
                    class="text-red-500 hover:text-red-700 p-1 hover:bg-red-50 rounded-lg transition-colors"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                  </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Type de document</label>
                    <select 
                      v-model="doc.document_type" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    >
                      <option value="">Sélectionner un type</option>
                      <option value="certificat_medical">🏥 Certificat médical</option>
                      <option value="assurance">🛡️ Assurance</option>
                      <option value="autorisation_parentale">👨‍👩‍👧‍👦 Autorisation parentale</option>
                      <option value="autre">📄 Autre</option>
                    </select>
                  </div>
                  
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Fréquence de renouvellement</label>
                    <select 
                      v-model="doc.renewal_frequency" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    >
                      <option value="">Aucune</option>
                      <option value="yearly">📅 Annuel</option>
                      <option value="monthly">📆 Mensuel</option>
                      <option value="quarterly">📊 Trimestriel</option>
                    </select>
                  </div>
                  
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Date d'expiration</label>
                    <input 
                      v-model="doc.expiry_date" 
                      type="date" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    >
                  </div>
                  
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Fichier</label>
                    <div class="relative">
                      <input 
                        @change="handleFileUpload($event, index)" 
                        type="file" 
                        accept=".pdf,.jpg,.jpeg,.png" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                      >
                      <div v-if="doc.file_name || doc.file_path" class="mt-2 text-sm text-emerald-600 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ doc.file_name || 'Fichier existant' }}
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="mt-4 space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Notes</label>
                  <textarea 
                    v-model="doc.notes" 
                    rows="2" 
                    placeholder="Informations supplémentaires..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none"
                  ></textarea>
                </div>
              </div>
            </div>
            
            <!-- Message quand aucun document -->
            <div v-else class="text-center py-8 text-gray-500">
              <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              <p>Aucun document médical ajouté</p>
              <p class="text-sm">Cliquez sur "Ajouter" pour commencer</p>
            </div>
            
            <!-- Bouton Ajouter déplacé en bas à droite -->
            <div class="flex justify-end mt-4">
              <button 
                type="button" 
                @click="addDocument" 
                class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>Ajouter</span>
              </button>
            </div>
          </div>
          
          <!-- Boutons d'action -->
          <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
            <button 
              type="button" 
              @click="$emit('close')" 
              class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors font-medium"
            >
              Annuler
            </button>
            <button 
              type="submit" 
              :disabled="loading" 
              class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-lg hover:from-emerald-600 hover:to-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-medium flex items-center space-x-2"
            >
              <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ loading ? 'Mise à jour...' : 'Enregistrer' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const props = defineProps({
  student: { type: Object, required: true }
})

const emit = defineEmits(['close', 'success'])

const loading = ref(false)
const availableDisciplines = ref([])
const selectedDisciplines = ref([])
const medicalDocuments = ref([])

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  date_of_birth: '',
  goals: '',
  medical_info: ''
})

// Date maximale (aujourd'hui)
const maxDate = computed(() => {
  return new Date().toISOString().split('T')[0]
})

// Calculer l'âge à partir de la date de naissance
const calculatedAge = computed(() => {
  if (!form.value.date_of_birth) return null
  
  const birthDate = new Date(form.value.date_of_birth)
  const today = new Date()
  let age = today.getFullYear() - birthDate.getFullYear()
  const monthDiff = today.getMonth() - birthDate.getMonth()
  
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
    age--
  }
  
  return age
})

// Helper pour obtenir le nom et prénom depuis student
const extractNameFromStudent = (student) => {
  if (student.first_name || student.last_name) {
    return {
      first_name: student.first_name || '',
      last_name: student.last_name || ''
    }
  }
  // Si on a seulement un nom complet, essayer de le split
  if (student.name) {
    const parts = student.name.trim().split(' ')
    return {
      first_name: parts[0] || '',
      last_name: parts.slice(1).join(' ') || ''
    }
  }
  // Si on a student_first_name et student_last_name
  if (student.student_first_name || student.student_last_name) {
    return {
      first_name: student.student_first_name || '',
      last_name: student.student_last_name || ''
    }
  }
  return { first_name: '', last_name: '' }
}

// Charger les spécialités du club
const loadClubDisciplines = async () => {
  try {
    if (process.env.NODE_ENV === 'test' || typeof window === 'undefined') {
      return
    }
    
    const { $api } = useNuxtApp()
    
    try {
      const authStore = useAuthStore()
      if (authStore && typeof authStore.initializeAuth === 'function' && !authStore.token) {
        await authStore.initializeAuth()
      }
    } catch (piniaError) {
      if (process.env.NODE_ENV !== 'test') {
        console.warn('⚠️ [EditStudentModal] Pinia non disponible, utilisation du token depuis cookies')
      }
    }
    
    const response = await $api.get('/club/profile')
    if (response.data.club && response.data.club.disciplines) {
      availableDisciplines.value = response.data.club.disciplines
    }
  } catch (error) {
    if (process.env.NODE_ENV !== 'test') {
      console.error('❌ [EditStudentModal] Erreur lors du chargement des spécialités:', error)
    }
  }
}

// Charger les disciplines de l'élève
const loadStudentDisciplines = async () => {
  // Utiliser les disciplines déjà présentes dans student.prop si disponibles
  if (props.student.disciplines && Array.isArray(props.student.disciplines)) {
    selectedDisciplines.value = props.student.disciplines.map(d => typeof d === 'object' ? d.id : d)
    return
  }
  
  // Les données de l'élève sont déjà chargées dans props.student depuis la liste
  // Pas besoin d'appeler l'API GET qui n'existe pas pour cette route
  // Si les disciplines ne sont pas dans props.student, elles seront vides
  selectedDisciplines.value = []
}

// Charger les documents médicaux de l'élève
const loadStudentMedicalDocuments = async () => {
  // Utiliser les documents déjà présents dans student.prop si disponibles
  if (props.student.medical_documents && Array.isArray(props.student.medical_documents)) {
    medicalDocuments.value = props.student.medical_documents.map(doc => ({
      id: doc.id,
      document_type: doc.document_type || '',
      file_name: doc.file_name || '',
      file_path: doc.file_path || '',
      expiry_date: doc.expiry_date || '',
      renewal_frequency: doc.renewal_frequency || '',
      notes: doc.notes || '',
      file: null
    }))
    return
  }
  
  // Les données de l'élève sont déjà chargées dans props.student depuis la liste
  // Pas besoin d'appeler l'API GET qui n'existe pas pour cette route
  // Si les documents ne sont pas dans props.student, ils seront vides
  medicalDocuments.value = []
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

// Toggle discipline selection
const toggleDiscipline = (disciplineId) => {
  const index = selectedDisciplines.value.indexOf(disciplineId)
  if (index > -1) {
    selectedDisciplines.value.splice(index, 1)
  } else {
    selectedDisciplines.value.push(disciplineId)
  }
}

// Gestion des documents médicaux
const addDocument = () => {
  medicalDocuments.value.push({
    document_type: '',
    file_path: '',
    file_name: '',
    expiry_date: '',
    renewal_frequency: '',
    notes: '',
    file: null
  })
}

const removeDocument = (index) => {
  medicalDocuments.value.splice(index, 1)
}

const handleFileUpload = (event, index) => {
  const file = event.target.files[0]
  if (file) {
    medicalDocuments.value[index].file = file
    medicalDocuments.value[index].file_name = file.name
  }
}

// Initialiser les données du formulaire
watch(() => props.student, (newStudent) => {
  if (newStudent) {
    const names = extractNameFromStudent(newStudent)
    form.value = {
      first_name: names.first_name,
      last_name: names.last_name,
      email: newStudent.email || '',
      phone: newStudent.phone || '',
      date_of_birth: newStudent.date_of_birth || '',
      goals: newStudent.goals || '',
      medical_info: newStudent.medical_info || ''
    }
    
    // Charger les disciplines et documents si l'élève existe déjà
    if (newStudent.id) {
      loadStudentDisciplines()
      loadStudentMedicalDocuments()
    }
  }
}, { immediate: true })

const updateStudent = async () => {
  loading.value = true
  try {
    const { $api } = useNuxtApp()
    
    // Préparer les données de l'étudiant
    const studentData: any = {
      first_name: form.value.first_name?.trim() || null,
      last_name: form.value.last_name?.trim() || null,
      date_of_birth: form.value.date_of_birth || null,
      goals: form.value.goals?.trim() || null,
      medical_info: form.value.medical_info?.trim() || null,
      disciplines: selectedDisciplines.value.length > 0 ? selectedDisciplines.value : null
    }
    
    // Email : envoyer seulement si défini (pour permettre la mise à jour ou l'effacement)
    // Envoyer une chaîne vide pour effacer, ou la valeur si non vide
    if (form.value.email !== undefined && form.value.email !== null) {
      const emailTrimmed = form.value.email.trim()
      // Envoyer la chaîne vide pour permettre l'effacement dans le backend
      studentData.email = emailTrimmed
    }
    
    // Téléphone : toujours envoyer si défini (pour permettre la mise à jour ou l'effacement)
    // Envoyer une chaîne vide pour effacer, ou la valeur si non vide
    if (form.value.phone !== undefined && form.value.phone !== null) {
      const phoneTrimmed = form.value.phone.trim()
      // Envoyer la chaîne vide pour permettre l'effacement dans le backend
      studentData.phone = phoneTrimmed
    }
    
    // Mettre à jour l'étudiant
    const response = await $api.put(`/club/students/${props.student.id}`, studentData)
    
    // Gérer les documents médicaux - nouveaux fichiers à uploader
    const newDocuments = medicalDocuments.value.filter(doc => doc.file && !doc.id)
    if (newDocuments.length > 0) {
      const formData = new FormData()
      newDocuments.forEach((doc, index) => {
        if (doc.file) {
          formData.append(`documents[${index}][file]`, doc.file)
          formData.append(`documents[${index}][document_type]`, doc.document_type)
          formData.append(`documents[${index}][expiry_date]`, doc.expiry_date || '')
          formData.append(`documents[${index}][renewal_frequency]`, doc.renewal_frequency || '')
          formData.append(`documents[${index}][notes]`, doc.notes || '')
        }
      })
      
      await $api.post(`/club/students/${props.student.id}/medical-documents`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
    }
    
    // Mettre à jour les documents existants modifiés
    const existingDocuments = medicalDocuments.value.filter(doc => doc.id)
    for (const doc of existingDocuments) {
      try {
        await $api.put(`/club/students/${props.student.id}/medical-documents/${doc.id}`, {
          document_type: doc.document_type,
          expiry_date: doc.expiry_date || null,
          renewal_frequency: doc.renewal_frequency || null,
          notes: doc.notes || null
        })
      } catch (error) {
        console.warn('⚠️ Erreur lors de la mise à jour du document:', error)
      }
    }
    
    // Afficher le toast de succès
    const { success } = useToast()
    success(response.data.message || 'Élève mis à jour avec succès !', 'Succès')
    
    emit('success', response.data)
    emit('close')
  } catch (error) {
    console.error('❌ [EditStudentModal] Erreur lors de la mise à jour:', error)
    const { error: showError } = useToast()
    const errorMessage = error?.response?.data?.message || 'Erreur lors de la mise à jour de l\'élève'
    showError(errorMessage, 'Erreur')
  } finally {
    loading.value = false
  }
}

// Charger les données au montage
onMounted(() => {
  loadClubDisciplines()
  if (props.student?.id) {
    loadStudentDisciplines()
    loadStudentMedicalDocuments()
  }
})
</script>
