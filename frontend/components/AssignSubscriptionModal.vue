<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" @click.self="$emit('close')">
    <div class="flex items-center justify-center min-h-screen px-4 py-12">
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="$emit('close')"></div>
      
      <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">Créer un abonnement</h2>
            <button 
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6">
          <!-- Sélection de l'élève principal (Autocomplete) -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Élève principal *
            </label>
            <div class="relative">
              <input
                v-model="studentSearchQuery"
                @input="filterStudents"
                @focus="showStudentDropdown = true"
                @blur="handleStudentBlur"
                type="text"
                placeholder="Rechercher un élève..."
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                :disabled="loadingStudents"
              />
              <div v-if="showStudentDropdown && filteredStudents.length > 0" 
                   class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                <div
                  v-for="s in filteredStudents"
                  :key="s.id"
                  @mousedown.prevent="selectStudent(s)"
                  class="px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                >
                  <div class="font-medium text-gray-900">{{ s.name }}</div>
                  <div v-if="s.email" class="text-sm text-gray-500">{{ s.email }}</div>
                </div>
              </div>
              <div v-if="selectedMainStudent" class="mt-2 p-2 bg-blue-50 rounded border border-blue-200">
                <span class="text-sm font-medium text-gray-900">Sélectionné: {{ selectedMainStudent.name }}</span>
                <button 
                  @click="clearMainStudent"
                  class="ml-2 text-red-600 hover:text-red-800 text-sm"
                >
                  ✕
                </button>
              </div>
              <p v-if="!loadingStudents && allStudents.length === 0" class="mt-2 text-sm text-amber-600">
                Aucun élève disponible.
              </p>
            </div>
          </div>

          <!-- Sélection du modèle d'abonnement -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Modèle d'abonnement *
            </label>
            <select 
              v-model="form.subscription_template_id"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
              :disabled="loadingTemplates"
              @change="onTemplateSelected"
            >
              <option value="">Sélectionner un modèle</option>
              <option 
                v-for="template in availableTemplates" 
                :key="template.id"
                :value="template.id"
              >
                Modèle {{ template.model_number }} - {{ template.price }}€ ({{ template.total_lessons }} cours, validité: {{ template.validity_months || 12 }} mois)
              </option>
            </select>
            <!-- Affichage du prix -->
            <div v-if="selectedTemplate" class="mt-2 p-3 bg-green-50 rounded-lg border border-green-200">
              <p class="text-sm text-gray-700">
                <span class="font-semibold">Prix total:</span>
                <span class="text-lg font-bold text-green-700 ml-2">{{ selectedTemplate.price }} €</span>
              </p>
            </div>
            <p v-if="!loadingTemplates && availableTemplates.length === 0" class="mt-2 text-sm text-amber-600">
              Aucun modèle d'abonnement disponible. Veuillez d'abord créer un modèle dans la section "Modèles d'Abonnements".
            </p>
          </div>

          <!-- Date de début -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2" for="started_at">
              Date de début *
            </label>
            <div class="relative">
              <input 
                id="started_at"
                v-model="form.started_at"
                type="date"
                required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                @click.stop
                @focus.stop
              />
            </div>
            <p class="mt-1 text-xs text-gray-500">
              Date de début de l'abonnement. Vous pouvez sélectionner n'importe quelle date (passée ou future).
            </p>
          </div>

          <!-- Date d'expiration (calculée automatiquement) -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Date d'expiration (calculée automatiquement)
            </label>
            <input 
              v-model="calculatedExpiresAt"
              type="date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50"
              readonly
            />
            <p class="mt-1 text-xs text-gray-500">
              Calculée automatiquement à partir du premier cours et selon les paramètres d'abonnement du profil club.
            </p>
          </div>

          <!-- Nombre de cours déjà utilisés -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Nombre de cours déjà utilisés
            </label>
            <input 
              v-model.number="form.lessons_used"
              type="number"
              min="0"
              :max="selectedTemplate ? selectedTemplate.total_lessons + (selectedTemplate.free_lessons || 0) : 0"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="0"
            />
            <p class="mt-1 text-xs text-gray-500">
              <span v-if="selectedTemplate">
                Nombre de cours déjà utilisés sur les {{ selectedTemplate.total_lessons + (selectedTemplate.free_lessons || 0) }} cours disponibles.
              </span>
              <span v-else>
                Sélectionnez d'abord un modèle d'abonnement.
              </span>
            </p>
          </div>

          <!-- Classification DCL/NDCL pour les commissions -->
          <div class="mb-6 border-t pt-4">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              Classification pour les commissions
            </label>
            <div class="space-y-3">
              <div class="flex items-center">
                <input
                  id="sub_dcl"
                  v-model="form.est_legacy"
                  :value="false"
                  type="radio"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                />
                <label for="sub_dcl" class="ml-2 block text-sm text-gray-700">
                  <span class="font-medium">DCL</span> (Déclaré) - Commission standard
                </label>
              </div>
              <div class="flex items-center">
                <input
                  id="sub_ndcl"
                  v-model="form.est_legacy"
                  :value="true"
                  type="radio"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                />
                <label for="sub_ndcl" class="ml-2 block text-sm text-gray-700">
                  <span class="font-medium">NDCL</span> (Non Déclaré) - Commission legacy
                </label>
              </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">
              ⓘ Cette classification détermine le type de commission pour l'enseignant dans les rapports de paie.
            </p>
          </div>

          <!-- Date de paiement et montant (optionnel) -->
          <div class="mb-6 grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Date de paiement (optionnel)
              </label>
              <input
                v-model="form.date_paiement"
                type="date"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
              />
              <p class="mt-1 text-xs text-gray-500">
                Détermine le mois de commission dans les rapports de paie
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Montant payé (optionnel)
              </label>
              <input
                v-model.number="form.montant"
                type="number"
                step="0.01"
                min="0"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Montant réellement payé"
              />
              <p class="mt-1 text-xs text-gray-500">
                Montant réellement payé (peut différer du prix du template)
              </p>
            </div>
          </div>

          <!-- Élèves additionnels (Autocomplete multiple) -->
          <div v-if="showFamilyOption && selectedMainStudent" class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Ajouter d'autres élèves (abonnement familial)
            </label>
            <div class="relative">
              <input
                v-model="additionalStudentSearchQuery"
                @input="filterAdditionalStudents"
                @focus="showAdditionalDropdown = true"
                @blur="handleAdditionalBlur"
                type="text"
                placeholder="Rechercher un élève à ajouter..."
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
              />
              <div v-if="showAdditionalDropdown && filteredAdditionalStudents.length > 0" 
                   class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                <div
                  v-for="s in filteredAdditionalStudents"
                  :key="s.id"
                  @mousedown.prevent="addAdditionalStudent(s)"
                  class="px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                >
                  <div class="font-medium text-gray-900">{{ s.name }}</div>
                  <div v-if="s.email" class="text-sm text-gray-500">{{ s.email }}</div>
                </div>
              </div>
            </div>
            <!-- Liste des élèves additionnels sélectionnés -->
            <div v-if="selectedAdditionalStudents.length > 0" class="mt-3 space-y-2">
              <div 
                v-for="s in selectedAdditionalStudents" 
                :key="s.id"
                class="flex items-center justify-between p-2 bg-gray-50 rounded border border-gray-200"
              >
                <span class="text-sm text-gray-900">{{ s.name }}</span>
                <button 
                  @click="removeAdditionalStudent(s.id)"
                  class="text-red-600 hover:text-red-800 text-sm"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-3">
          <button
            @click="$emit('close')"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
          >
            Annuler
          </button>
          <button
            @click="assignSubscription"
            :disabled="!selectedMainStudent || !form.subscription_template_id || submitting"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="!submitting">Assigner</span>
            <span v-else class="flex items-center">
              <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Attribution...
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'

const props = defineProps({
  student: {
    type: Object,
    default: () => ({ id: null, name: 'Nouvel abonnement' })
  },
  showFamilyOption: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['close', 'success'])

const form = ref({
  subscription_template_id: '',
  started_at: '',
  expires_at: '',
  lessons_used: 0,
  // Champs pour les commissions
  est_legacy: false, // Par défaut DCL (false)
  date_paiement: null,
  montant: null
})

const availableTemplates = ref([])
const selectedTemplate = ref(null)
const allStudents = ref([])
const loadingTemplates = ref(false)
const loadingStudents = ref(false)
const submitting = ref(false)
const clubDefaults = ref(null)

// Autocomplete pour élève principal
const studentSearchQuery = ref('')
const showStudentDropdown = ref(false)
const selectedMainStudent = ref(null)
const filteredStudents = ref([])

// Autocomplete pour élèves additionnels
const additionalStudentSearchQuery = ref('')
const showAdditionalDropdown = ref(false)
const selectedAdditionalStudents = ref([])
const filteredAdditionalStudents = ref([])

// Date d'expiration calculée
const calculatedExpiresAt = ref('')

// Charger les modèles d'abonnements disponibles
const loadTemplates = async () => {
  try {
    loadingTemplates.value = true
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/subscription-templates')
    
    if (response.data.success) {
      availableTemplates.value = response.data.data.filter(t => t.is_active)
      
      // Auto-sélectionner si un seul modèle existe
      if (availableTemplates.value.length === 1) {
        form.value.subscription_template_id = availableTemplates.value[0].id.toString()
        onTemplateSelected()
        // Recalculer après que la date de début soit initialisée
        setTimeout(() => {
          calculateExpirationDate()
        }, 100)
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement des modèles:', error)
  } finally {
    loadingTemplates.value = false
  }
}

// Charger les paramètres par défaut du club
const loadClubDefaults = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/profile')
    
    if (response.data.success && response.data.club) {
      clubDefaults.value = response.data.club
    }
  } catch (error) {
    console.error('Erreur lors du chargement des paramètres du club:', error)
  }
}

// Lorsqu'un modèle est sélectionné
const onTemplateSelected = () => {
  if (form.value.subscription_template_id) {
    selectedTemplate.value = availableTemplates.value.find(
      t => t.id === parseInt(form.value.subscription_template_id)
    )
    // Recalculer la date d'expiration
    calculateExpirationDate()
    // Réinitialiser lessons_used si nécessaire (si la valeur dépasse le nouveau max)
    if (selectedTemplate.value && form.value.lessons_used > (selectedTemplate.value.total_lessons + (selectedTemplate.value.free_lessons || 0))) {
      form.value.lessons_used = 0
    }
  } else {
    selectedTemplate.value = null
    calculatedExpiresAt.value = ''
  }
}

// Calculer la date d'expiration à partir du premier cours et des paramètres du club
const calculateExpirationDate = () => {
  if (!selectedTemplate.value || !form.value.started_at) {
    calculatedExpiresAt.value = ''
    return
  }
  
  const startDate = new Date(form.value.started_at)
  
  // Utiliser validity_months du template si disponible
  let monthsToAdd = selectedTemplate.value.validity_months || 12
  
  // Sinon utiliser les paramètres par défaut du club
  if (!monthsToAdd && clubDefaults.value) {
    const validityValue = clubDefaults.value.default_subscription_validity_value || 12
    const validityUnit = clubDefaults.value.default_subscription_validity_unit || 'months'
    
    if (validityUnit === 'weeks') {
      monthsToAdd = Math.ceil(validityValue / 4.33) // Approximation : 4.33 semaines = 1 mois
    } else {
      monthsToAdd = validityValue
    }
  }
  
  // Ajouter les mois à la date de début
  const expirationDate = new Date(startDate)
  expirationDate.setMonth(expirationDate.getMonth() + monthsToAdd)
  
  // Formater en YYYY-MM-DD
  calculatedExpiresAt.value = expirationDate.toISOString().split('T')[0]
  form.value.expires_at = calculatedExpiresAt.value
}

// Charger tous les élèves du club
const loadStudents = async () => {
  try {
    loadingStudents.value = true
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/students')
    
    if (response.data.success) {
      allStudents.value = response.data.data || []
      filteredStudents.value = allStudents.value
      filteredAdditionalStudents.value = allStudents.value
      
      // Si un élève est fourni en prop, le présélectionner
      if (props.student?.id) {
        const found = allStudents.value.find(s => s.id === props.student.id)
        if (found) {
          selectStudent(found)
        }
      }
      
      // Initialiser la date de début avec aujourd'hui
      form.value.started_at = new Date().toISOString().split('T')[0]
      // Réinitialiser lessons_used
      form.value.lessons_used = 0
    }
  } catch (error) {
    console.error('Erreur lors du chargement des élèves:', error)
    allStudents.value = []
    filteredStudents.value = []
    filteredAdditionalStudents.value = []
  } finally {
    loadingStudents.value = false
  }
}

// Filtrer les élèves pour l'autocomplete principal
const filterStudents = () => {
  const query = studentSearchQuery.value.toLowerCase()
  if (!query) {
    filteredStudents.value = allStudents.value.filter(s => 
      !selectedMainStudent.value || s.id !== selectedMainStudent.value.id
    )
  } else {
    filteredStudents.value = allStudents.value.filter(s => {
      const matches = s.name.toLowerCase().includes(query) || 
                      (s.email && s.email.toLowerCase().includes(query))
      return matches && (!selectedMainStudent.value || s.id !== selectedMainStudent.value.id)
    })
  }
}

// Sélectionner un élève principal
const selectStudent = (student) => {
  selectedMainStudent.value = student
  studentSearchQuery.value = student.name
  showStudentDropdown.value = false
  
  // Mettre à jour les filtres des élèves additionnels
  filterAdditionalStudents()
  calculateExpirationDate()
}

// Effacer la sélection de l'élève principal
const clearMainStudent = () => {
  selectedMainStudent.value = null
  studentSearchQuery.value = ''
  selectedAdditionalStudents.value = []
  filterStudents()
  filterAdditionalStudents()
}

// Gestion du blur pour l'autocomplete principal
const handleStudentBlur = () => {
  setTimeout(() => {
    showStudentDropdown.value = false
  }, 200)
}

// Filtrer les élèves pour l'autocomplete additionnel
const filterAdditionalStudents = () => {
  const query = additionalStudentSearchQuery.value.toLowerCase()
  const excludedIds = [
    selectedMainStudent.value?.id,
    ...selectedAdditionalStudents.value.map(s => s.id)
  ].filter(Boolean)
  
  if (!query) {
    filteredAdditionalStudents.value = allStudents.value.filter(s => 
      !excludedIds.includes(s.id)
    )
  } else {
    filteredAdditionalStudents.value = allStudents.value.filter(s => {
      const matches = s.name.toLowerCase().includes(query) || 
                      (s.email && s.email.toLowerCase().includes(query))
      return matches && !excludedIds.includes(s.id)
    })
  }
}

// Ajouter un élève additionnel
const addAdditionalStudent = (student) => {
  if (!selectedAdditionalStudents.value.find(s => s.id === student.id)) {
    selectedAdditionalStudents.value.push(student)
  }
  additionalStudentSearchQuery.value = ''
  showAdditionalDropdown.value = false
  filterAdditionalStudents()
}

// Retirer un élève additionnel
const removeAdditionalStudent = (studentId) => {
  selectedAdditionalStudents.value = selectedAdditionalStudents.value.filter(
    s => s.id !== studentId
  )
  filterAdditionalStudents()
}

// Gestion du blur pour l'autocomplete additionnel
const handleAdditionalBlur = () => {
  setTimeout(() => {
    showAdditionalDropdown.value = false
  }, 200)
}

// Watch pour recalculer la date d'expiration quand la date de début change
watch(() => form.value.started_at, (newValue) => {
  if (newValue) {
    calculateExpirationDate()
  }
}, { immediate: false })

// Assigner l'abonnement
const assignSubscription = async () => {
  if (!selectedMainStudent.value) {
    alert('Veuillez sélectionner un élève principal')
    return
  }
  
  if (!form.value.subscription_template_id) {
    alert('Veuillez sélectionner un modèle d\'abonnement')
    return
  }
  
  try {
    submitting.value = true
    const { $api } = useNuxtApp()
    
    // Préparer les IDs des élèves (l'élève principal + éventuellement d'autres)
    const studentIds = [
      selectedMainStudent.value.id,
      ...selectedAdditionalStudents.value.map(s => s.id)
    ]
    
    // Préparer les données à envoyer
    const payload = {
      subscription_template_id: form.value.subscription_template_id,
      student_ids: studentIds,
      started_at: form.value.started_at,
      expires_at: calculatedExpiresAt.value || null,
      lessons_used: form.value.lessons_used || 0,
      // Champs pour les commissions
      est_legacy: form.value.est_legacy === true || form.value.est_legacy === 'true',
      date_paiement: form.value.date_paiement || null,
      montant: form.value.montant ? parseFloat(form.value.montant) : null
    }
    
    console.log('📤 [AssignSubscription] Payload envoyé:', payload)
    console.log('📤 [AssignSubscription] lessons_used value:', form.value.lessons_used, 'type:', typeof form.value.lessons_used)
    
    const response = await $api.post('/club/subscriptions/assign', payload)
    
    if (response.data.success) {
      alert(response.data.message || 'Abonnement assigné avec succès')
      emit('success')
      emit('close')
    } else {
      alert(response.data.message || 'Erreur lors de l\'assignation')
    }
  } catch (error) {
    console.error('Erreur lors de l\'assignation:', error)
    alert(error.response?.data?.message || 'Erreur lors de l\'assignation de l\'abonnement')
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    loadTemplates(),
    loadStudents(),
    loadClubDefaults()
  ])
})
</script>

