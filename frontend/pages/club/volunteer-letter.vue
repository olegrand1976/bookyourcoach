<template>
  <div class="min-h-screen bg-gray-50 p-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-8 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl shadow-lg p-6 border-2 border-purple-100">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Lettre de Volontariat</h1>
            <p class="mt-2 text-gray-600">Générer les lettres d'information pour vos enseignants</p>
          </div>
          <div class="p-3 bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg shadow-md">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
          </div>
        </div>
      </div>

      <!-- Liste des enseignants -->
      <div v-if="clubInfoComplete" class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
          <h2 class="text-xl font-semibold text-gray-900">Enseignants affiliés</h2>
          <p class="text-sm text-gray-600 mt-1">Cliquez sur un enseignant pour générer sa lettre</p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="p-8 text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto"></div>
          <p class="text-gray-600 mt-4">Chargement des enseignants...</p>
        </div>

        <!-- Liste -->
        <div v-else-if="teachers.length > 0" class="divide-y divide-gray-200">
          <div v-for="teacher in teachers" :key="teacher.id" 
               class="p-6 hover:bg-gray-50 transition-colors cursor-pointer flex items-center justify-between"
               @click="selectTeacher(teacher)">
            <div class="flex items-center space-x-4">
              <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">{{ teacher.user?.name }}</h3>
                <p class="text-sm text-gray-600">{{ teacher.user?.email }}</p>
              </div>
            </div>
            <button class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700 transition-all duration-200 shadow-sm hover:shadow-md">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Générer la lettre
            </button>
          </div>
        </div>

        <!-- Aucun enseignant -->
        <div v-else class="p-8 text-center text-gray-500">
          <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <p class="text-lg font-medium">Aucun enseignant affilié</p>
          <p class="text-sm mt-1">Ajoutez des enseignants à votre club pour générer leurs lettres</p>
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
  middleware: ['auth', 'club'],
  layout: 'club'
})

// Composables
const { $api } = useNuxtApp()
const toast = useToast()

// État
const loading = ref(true)
const clubData = ref(null)
const teachers = ref([])
const selectedTeacher = ref(null)

// Computed
const clubInfoComplete = computed(() => {
  if (!clubData.value) return false
  
  const required = [
    'name',
    'company_number',
    'legal_representative_name',
    'legal_representative_role',
    'insurance_rc_company',
    'insurance_rc_policy_number',
    'expense_reimbursement_type'
  ]
  
  return required.every(field => clubData.value[field])
})

// Méthodes
async function loadData() {
  try {
    loading.value = true
    
    // Charger les informations du club
    const clubRes = await $api.get('/club/profile')
    if (clubRes.data.success && clubRes.data.data) {
      clubData.value = clubRes.data.data
    }
    
    // Charger les enseignants affiliés
    const teachersRes = await $api.get('/club/teachers')
    if (teachersRes.data.success) {
      teachers.value = teachersRes.data.teachers || []
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

