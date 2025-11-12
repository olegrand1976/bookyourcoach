<template>
  <div class="min-h-screen bg-gray-50 p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6 md:mb-8 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl shadow-lg p-4 md:p-6 border-2 border-purple-100">
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <h1 class="text-xl md:text-3xl font-bold text-gray-900">Lettre de Volontariat</h1>
            <p class="mt-1 md:mt-2 text-xs md:text-base text-gray-600">Générer les lettres d'information pour vos enseignants</p>
          </div>
          <div class="p-2 md:p-3 bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg shadow-md flex-shrink-0 ml-4">
            <svg class="w-6 h-6 md:w-8 md:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Alerte si informations légales manquantes -->
      <div v-if="!clubInfoComplete" class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
        <div class="flex">
          <svg class="w-5 h-5 text-yellow-400 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <div>
            <h3 class="text-sm font-medium text-yellow-800">Informations légales incomplètes</h3>
            <p class="mt-1 text-sm text-yellow-700">
              Veuillez compléter les informations légales dans votre profil avant de générer des lettres.
              <NuxtLink to="/club/profile" class="font-medium underline hover:text-yellow-900">
                Compléter maintenant
              </NuxtLink>
            </p>
            <div v-if="missingFieldsList.length > 0" class="mt-2 text-xs text-yellow-600">
              <strong>Champs manquants :</strong> {{ missingFieldsList.join(', ') }}
            </div>
          </div>
        </div>
      </div>

      <!-- Liste des enseignants -->
      <div v-if="clubInfoComplete" class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
          <div class="flex flex-col space-y-3 md:flex-row md:items-center md:justify-between md:space-y-0">
            <div class="flex-1">
              <h2 class="text-lg md:text-xl font-semibold text-gray-900">Enseignants bénévoles</h2>
              <p class="text-xs md:text-sm text-gray-600 mt-1">
                <span class="inline-flex items-center">
                  <svg class="w-3 h-3 md:w-4 md:h-4 mr-1 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                  </svg>
                  <span class="hidden sm:inline">Uniquement les enseignants avec contrat de type "Bénévole"</span>
                  <span class="sm:hidden">Contrat "Bénévole"</span>
                </span>
              </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
              <NuxtLink 
                to="/club/volunteer-letter-history"
                class="inline-flex items-center justify-center px-3 md:px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 shadow-sm hover:shadow-md text-sm"
              >
                <svg class="w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Historique
              </NuxtLink>
              <button 
                @click="sendToAll" 
                :disabled="sendingAll || teachers.length === 0"
                class="inline-flex items-center justify-center px-3 md:px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-lg hover:from-emerald-600 hover:to-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-sm hover:shadow-md text-sm"
              >
                <svg v-if="!sendingAll" class="w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76" />
                </svg>
                <svg v-else class="animate-spin w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span class="hidden sm:inline">{{ sendingAll ? 'Envoi en cours...' : 'Envoyer à tous' }}</span>
                <span class="sm:hidden">{{ sendingAll ? 'Envoi...' : 'Tous' }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="p-8 text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto"></div>
          <p class="text-gray-600 mt-4">Chargement des enseignants...</p>
        </div>

        <!-- Liste -->
        <div v-else-if="teachers.length > 0" class="divide-y divide-gray-200">
          <div v-for="teacher in teachers" :key="teacher.id" 
               class="p-4 md:p-6 hover:bg-gray-50 transition-colors cursor-pointer flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0"
               @click="selectTeacher(teacher)">
            <div class="flex items-center space-x-3 md:space-x-4 flex-1 min-w-0">
              <div class="p-2 md:p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex-shrink-0">
                <svg class="w-5 h-5 md:w-6 md:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-sm md:text-base text-gray-900 break-words">{{ teacher.user?.name }}</h3>
                <p class="text-xs md:text-sm text-gray-600 break-all">{{ teacher.user?.email }}</p>
              </div>
            </div>
            <button class="w-full sm:w-auto inline-flex items-center justify-center px-3 md:px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700 transition-all duration-200 shadow-sm hover:shadow-md text-sm">
              <svg class="w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span class="hidden sm:inline">Générer la lettre</span>
              <span class="sm:hidden">Générer</span>
            </button>
          </div>
        </div>

        <!-- Aucun enseignant -->
        <div v-else class="p-8 text-center text-gray-500">
          <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <p class="text-lg font-medium">Aucun enseignant bénévole</p>
          <p class="text-sm mt-1">Assurez-vous que le type de contrat de vos enseignants soit défini sur "Bénévole"</p>
          <NuxtLink to="/club/teachers" class="inline-flex items-center mt-3 text-blue-600 hover:text-blue-800 text-sm font-medium">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            Gérer les enseignants
          </NuxtLink>
        </div>
      </div>

      <!-- Modal de prévisualisation de la lettre -->
      <div v-if="selectedTeacher" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50" @click.self="closeModal">
        <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
          <!-- Header du modal -->
          <div class="sticky top-0 bg-gradient-to-r from-purple-500 to-pink-600 text-white px-6 py-4 flex items-center justify-between rounded-t-xl">
            <div>
              <h2 class="text-2xl font-bold">Note d'Information au Volontaire</h2>
              <p class="text-purple-100 text-sm mt-1">{{ selectedTeacher.user?.name }}</p>
            </div>
            <button @click="closeModal" class="p-2 hover:bg-white hover:bg-opacity-20 rounded-lg transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Contenu de la lettre -->
          <div id="letter-content" class="p-8 prose prose-sm max-w-none">
            <VolunteerLetterTemplate :club="clubData" :teacher="selectedTeacher" />
          </div>

          <!-- Footer avec actions -->
          <div class="sticky bottom-0 bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between rounded-b-xl">
            <button @click="closeModal" class="inline-flex items-center px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors shadow-sm hover:shadow-md">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              Fermer
            </button>
            <div class="space-x-3">
              <button @click="printLetter" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-sm hover:shadow-md">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Imprimer
              </button>
              <button @click="sendEmail" :disabled="sending" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm hover:shadow-md">
                <svg v-if="!sending" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <svg v-else class="animate-spin w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                {{ sending ? 'Envoi...' : 'Envoyer par Email' }}
              </button>
              <button @click="downloadPDF" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700 transition-all duration-200 shadow-sm hover:shadow-md">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Télécharger PDF
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

definePageMeta({
  middleware: ['auth']
})

// Composables
const { $api } = useNuxtApp()
const toast = useToast()

// État
const loading = ref(true)
const clubData = ref(null)
const teachers = ref([])
const selectedTeacher = ref(null)
const sending = ref(false)
const sendingAll = ref(false)

// Computed
const missingFieldsList = computed(() => {
  if (!clubData.value) return []
  
  const required = {
    'name': 'Nom du club',
    'company_number': 'Numéro d\'entreprise',
    'legal_representative_name': 'Nom du représentant légal',
    'legal_representative_role': 'Fonction du représentant',
    'insurance_rc_company': 'Compagnie d\'assurance RC',
    'insurance_rc_policy_number': 'Numéro de police RC',
    'expense_reimbursement_type': 'Type de défraiement'
  }
  
  const missing = []
  Object.entries(required).forEach(([field, label]) => {
    const value = clubData.value[field]
    const isValid = value !== null && value !== undefined && value !== ''
    if (!isValid) {
      missing.push(label)
    }
  })
  
  return missing
})

const clubInfoComplete = computed(() => {
  if (!clubData.value) {
    console.log('❌ clubData.value est null ou undefined')
    return false
  }
  
  const required = [
    'name',
    'company_number',
    'legal_representative_name',
    'legal_representative_role',
    'insurance_rc_company',
    'insurance_rc_policy_number',
    'expense_reimbursement_type'
  ]
  
  console.log('🔍 Vérification des champs obligatoires:')
  const missingFields = []
  required.forEach(field => {
    const value = clubData.value[field]
    const isValid = value !== null && value !== undefined && value !== ''
    console.log(`  - ${field}: "${value}" → ${isValid ? '✅' : '❌'}`)
    if (!isValid) {
      missingFields.push(field)
    }
  })
  
  if (missingFields.length > 0) {
    console.log(`❌ Champs manquants: ${missingFields.join(', ')}`)
  } else {
    console.log('✅ Tous les champs obligatoires sont remplis')
  }
  
  return required.every(field => {
    const value = clubData.value[field]
    return value !== null && value !== undefined && value !== ''
  })
})

// Méthodes
async function loadData() {
  try {
    loading.value = true
    
    // Charger les informations du club
    const clubRes = await $api.get('/club/profile')
    console.log('📊 Réponse API /club/profile:', clubRes.data)
    if (clubRes.data.success && clubRes.data.data) {
      clubData.value = clubRes.data.data
      console.log('📊 Club data loaded:', clubData.value)
    }
    
    // Charger uniquement les enseignants bénévoles (volunteer)
    const teachersRes = await $api.get('/club/teachers', {
      params: { contract_type: 'volunteer' }
    })
    if (teachersRes.data.success) {
      teachers.value = teachersRes.data.teachers || []
      console.log(`📋 ${teachers.value.length} enseignant(s) bénévole(s) chargé(s)`)
    }
  } catch (error) {
    console.error('Erreur chargement:', error)
    toast.error('Erreur lors du chargement des données')
  } finally {
    loading.value = false
  }
}

function selectTeacher(teacher) {
  selectedTeacher.value = teacher
}

function closeModal() {
  selectedTeacher.value = null
}

function printLetter() {
  window.print()
}

async function downloadPDF() {
  toast.info('Génération du PDF en cours...')
  
  // TODO: Implémenter la génération PDF côté serveur
  // Pour l'instant, on utilise l'impression du navigateur
  window.print()
}

async function sendEmail() {
  if (!selectedTeacher.value) return
  
  try {
    sending.value = true
    
    const response = await $api.post(`/club/volunteer-letters/send/${selectedTeacher.value.id}`)
    
    if (response.data.success) {
      toast.success(response.data.message || 'Lettre envoyée avec succès !')
      closeModal()
    } else {
      toast.error(response.data.message || 'Erreur lors de l\'envoi')
    }
  } catch (error) {
    console.error('Erreur envoi email:', error)
    toast.error(error.response?.data?.message || 'Erreur lors de l\'envoi de la lettre')
  } finally {
    sending.value = false
  }
}

async function sendToAll() {
  if (sendingAll.value) return
  
  if (!confirm(`Êtes-vous sûr de vouloir envoyer la lettre à tous les enseignants (${teachers.value.length}) ?`)) {
    return
  }
  
  try {
    sendingAll.value = true
    toast.info('Envoi en cours...')
    
    const response = await $api.post('/club/volunteer-letters/send-all')
    
    if (response.data.success) {
      const results = response.data.results
      
      // Avec les queues, les emails sont "queued" (en file d'attente) au lieu de "sent"
      if (results.queued) {
        toast.success(`${results.queued} lettre(s) en cours d'envoi. Les emails seront envoyés dans quelques instants.`)
      } else if (results.sent) {
        // Rétrocompatibilité avec l'ancienne version
        toast.success(`${results.sent} lettre(s) envoyée(s) avec succès !`)
      } else {
        toast.info(response.data.message)
      }
      
      if (results.skipped > 0) {
        toast.warning(`${results.skipped} enseignant(s) ignoré(s) (pas d'email)`)
      }
      
      // Afficher les détails dans la console
      console.log('Résultats envoi en masse:', results)
    } else {
      toast.error(response.data.message || 'Erreur lors de l\'envoi')
    }
  } catch (error) {
    console.error('Erreur envoi en masse:', error)
    toast.error(error.response?.data?.message || 'Erreur lors de l\'envoi des lettres')
  } finally {
    sendingAll.value = false
  }
}

// Initialisation
onMounted(() => {
  loadData()
})

useHead({
  title: 'Lettre de Volontariat | activibe'
})
</script>

<style scoped>
@media print {
  /* Masquer tout sauf le contenu de la lettre lors de l'impression */
  body * {
    visibility: hidden;
  }
  
  #letter-content,
  #letter-content * {
    visibility: visible;
  }
  
  #letter-content {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
}
</style>

