<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">
              Ajouter un enseignant
            </h1>
            <p class="mt-2 text-gray-600">
              Inviter un enseignant à rejoindre votre club
            </p>
          </div>
          <button 
            @click="navigateTo('/club/dashboard')"
            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors"
          >
            Retour au dashboard
          </button>
        </div>
      </div>

      <!-- Formulaire -->
      <div class="bg-white rounded-xl shadow-lg p-8">
        <form @submit.prevent="addTeacher" class="space-y-6">
          <!-- Informations personnelles -->
          <div class="bg-emerald-50 rounded-xl p-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Informations personnelles</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Prénom -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Prénom *</label>
                <input 
                  v-model="form.first_name" 
                  type="text" 
                  required
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                  placeholder="Jean"
                >
              </div>
              
              <!-- Nom -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
                <input 
                  v-model="form.last_name" 
                  type="text" 
                  required
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                  placeholder="Dupont"
                >
              </div>
              
              <!-- Email -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                <input 
                  v-model="form.email" 
                  type="email" 
                  required
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                  placeholder="jean.dupont@example.com"
                >
              </div>
              
              <!-- Téléphone -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                <input 
                  v-model="form.phone" 
                  type="tel" 
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                  placeholder="0470123456"
                >
              </div>
              
              <!-- Numéro NISS -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Numéro NISS</label>
                <input 
                  v-model="form.niss" 
                  type="text" 
                  maxlength="11"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                  placeholder="76011042703"
                  @input="calculateBirthDateFromNiss"
                >
                <p v-if="calculatedBirthDate" class="mt-1 text-sm text-green-600">
                  Date de naissance calculée : {{ calculatedBirthDate }}
                </p>
              </div>
            </div>
          </div>

          <!-- Adresse -->
          <div class="bg-blue-50 rounded-xl p-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Adresse</h4>
            
            <div class="grid grid-cols-1 gap-4">
              <!-- Rue et numéro -->
              <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Rue</label>
                  <input 
                    v-model="form.street" 
                    type="text" 
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    placeholder="Rue de la Paix"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Numéro</label>
                  <input 
                    v-model="form.street_number" 
                    type="text" 
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    placeholder="12"
                  >
                </div>
              </div>
              
              <!-- Code postal, Ville, Pays -->
              <div class="grid grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Code postal</label>
                  <input 
                    v-model="form.postal_code" 
                    type="text" 
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    placeholder="1000"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
                  <input 
                    v-model="form.city" 
                    type="text" 
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    placeholder="Bruxelles"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Pays</label>
                  <select 
                    v-model="form.country" 
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
                  >
                    <option value="Belgium">Belgique</option>
                    <option value="France">France</option>
                    <option value="Netherlands">Pays-Bas</option>
                    <option value="Germany">Allemagne</option>
                    <option value="Luxembourg">Luxembourg</option>
                    <option value="Switzerland">Suisse</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Informations bancaires -->
          <div class="bg-yellow-50 rounded-xl p-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Informations bancaires</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
              <!-- Numéro de compte bancaire -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Numéro de compte bancaire</label>
                <input 
                  v-model="form.bank_account_number" 
                  type="text" 
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors"
                  placeholder="BE12 3456 7890 1234"
                >
              </div>
            </div>
          </div>

          <!-- Informations professionnelles -->
          <div class="bg-purple-50 rounded-xl p-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Informations professionnelles</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Type de contrat -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type de contrat</label>
                <select 
                  v-model="form.contract_type" 
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors bg-white"
                >
                  <option value="">Sélectionner...</option>
                  <option value="volunteer">Bénévole</option>
                  <option value="student">Étudiant</option>
                  <option value="employee">Employé</option>
                  <option value="freelance">Indépendant</option>
                  <option value="intern">Stagiaire</option>
                </select>
              </div>
              
              <!-- Tarif horaire -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tarif horaire (€)</label>
                <input 
                  v-model.number="form.hourly_rate" 
                  type="number" 
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                  placeholder="24.00"
                >
              </div>
              
              <!-- Date de début d'expérience -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date de début d'expérience</label>
                <input 
                  v-model="form.experience_start_date" 
                  type="date" 
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                >
                <p class="mt-1 text-xs text-gray-500">
                  Les années d'expérience seront calculées automatiquement à partir de cette date
                </p>
              </div>
            </div>
            
            <!-- Notes / Description -->
            <div class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Notes / Description</label>
              <textarea 
                v-model="form.notes" 
                rows="4"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                placeholder="Informations complémentaires, qualifications, expériences..."
              ></textarea>
            </div>
          </div>

          <!-- Boutons -->
          <div class="flex items-center justify-end space-x-4 pt-6">
            <button
              type="button"
              @click="navigateTo('/club/dashboard')"
              class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading">Création en cours...</span>
              <span v-else>Créer l'enseignant</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Liste des enseignants existants -->
      <div class="mt-8 bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">
          Enseignants du club
        </h2>
        <div v-if="existingTeachers.length === 0" class="text-center text-gray-500 py-8">
          <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
          </svg>
          <p>Aucun enseignant dans le club pour le moment</p>
        </div>
        <div v-else class="space-y-4">
          <div 
            v-for="teacher in existingTeachers" 
            :key="teacher.id" 
            class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
          >
            <div class="flex items-center space-x-3">
              <div class="bg-blue-100 p-2 rounded-lg">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <div>
                <p class="font-medium text-gray-900">{{ teacher.name }}</p>
                <p class="text-sm text-gray-600">{{ teacher.email }}</p>
              </div>
            </div>
            <div class="flex items-center space-x-3">
              <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                {{ teacher.hourly_rate }}€/h
              </span>
              <button
                @click="resendInvitation(teacher.id)"
                :disabled="resending[teacher.id]"
                class="px-3 py-1 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors disabled:opacity-50"
                title="Renvoyer l'email d'invitation"
              >
                <svg v-if="resending[teacher.id]" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from '~/composables/useToast'

definePageMeta({
  middleware: ['auth']
})

const { showToast } = useToast()
const loading = ref(false)
const existingTeachers = ref([])
const resending = ref({})
const calculatedBirthDate = ref('')

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  niss: '',
  street: '',
  street_number: '',
  postal_code: '',
  city: '',
  country: 'Belgium',
  bank_account_number: '',
  contract_type: 'volunteer',
  hourly_rate: 24,
  experience_start_date: '',
  notes: ''
})

// Calculer la date de naissance depuis le NISS belge
// Format NISS belge : YYMMDD-XXX.XX ou YYMMDDXXX.XX (11 chiffres)
// Les 6 premiers chiffres : YYMMDD (année, mois, jour)
// Pour déterminer le siècle : si YY >= 00 et < 50, c'est 20YY, sinon 19YY
const calculateBirthDateFromNiss = () => {
  calculatedBirthDate.value = ''
  const niss = form.value.niss?.replace(/[^0-9]/g, '') // Enlever les caractères non numériques
  
  if (!niss || niss.length < 6) {
    return
  }
  
  try {
    // Extraire YY, MM, DD
    const yearStr = niss.substring(0, 2)
    const monthStr = niss.substring(2, 4)
    const dayStr = niss.substring(4, 6)
    
    const month = parseInt(monthStr, 10)
    const day = parseInt(dayStr, 10)
    let year = parseInt(yearStr, 10)
    
    // Déterminer le siècle
    // Si YY < 50, c'est 20YY, sinon 19YY
    if (year < 50) {
      year = 2000 + year
    } else {
      year = 1900 + year
    }
    
    // Valider la date
    const date = new Date(year, month - 1, day)
    if (date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day) {
      // Formater la date au format DD/MM/YYYY
      calculatedBirthDate.value = `${dayStr}/${monthStr}/${year}`
    }
  } catch (error) {
    console.error('Erreur lors du calcul de la date de naissance:', error)
  }
}

// Créer l'enseignant
const addTeacher = async () => {
  loading.value = true
  
  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    
    // Construire le nom complet
    const fullName = `${form.value.first_name} ${form.value.last_name}`.trim()
    
    // Préparer les données
    const teacherData = {
      name: fullName,
      first_name: form.value.first_name,
      last_name: form.value.last_name,
      email: form.value.email,
      phone: form.value.phone || null,
      niss: form.value.niss || null,
      street: form.value.street || null,
      street_number: form.value.street_number || null,
      postal_code: form.value.postal_code || null,
      city: form.value.city || null,
      country: form.value.country,
      bank_account_number: form.value.bank_account_number || null,
      experience_start_date: form.value.experience_start_date || null,
      role: 'teacher',
      contract_type: form.value.contract_type || null,
      hourly_rate: form.value.hourly_rate || null,
      bio: form.value.notes || null
    }
    
    const response = await $fetch(`${config.public.apiBase}/club/teachers`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${tokenCookie.value}`,
        'Content-Type': 'application/json'
      },
      body: teacherData
    })
    
    console.log('✅ Enseignant créé avec succès:', response)
    
    showToast(`Enseignant ${fullName} créé avec succès !`, 'success')
    
    // Rediriger vers le dashboard avec un message de succès
    await navigateTo('/club/dashboard')
    
  } catch (error) {
    console.error('Erreur lors de la création de l\'enseignant:', error)
    const errorMessage = error.data?.message || 'Erreur lors de la création de l\'enseignant'
    showToast(errorMessage, 'error')
  } finally {
    loading.value = false
  }
}

// Charger les enseignants existants
const loadExistingTeachers = async () => {
  try {
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/club/teachers`)
    existingTeachers.value = response.data || []
  } catch (error) {
    console.error('Erreur lors du chargement des enseignants:', error)
    existingTeachers.value = []
  }
}

// Renvoyer l'invitation à un enseignant
const resendInvitation = async (teacherId) => {
  resending.value[teacherId] = true
  
  try {
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/club/teachers/${teacherId}/resend-invitation`, {
      method: 'POST'
    })
    
    console.log('✅ Email renvoyé:', response)
    alert(response.message || 'Email d\'invitation renvoyé avec succès')
    
  } catch (error) {
    console.error('Erreur lors du renvoi de l\'invitation:', error)
    alert('Erreur lors du renvoi de l\'invitation. Veuillez réessayer.')
  } finally {
    resending.value[teacherId] = false
  }
}

// Watcher pour vérifier l'enseignant quand l'email change
watch(() => form.value.email, checkTeacher)

onMounted(() => {
  loadExistingTeachers()
})
</script>
