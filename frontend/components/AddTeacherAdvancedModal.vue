<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
      <!-- Header avec gradient et icône -->
      <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="bg-white bg-opacity-20 p-2 rounded-lg">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
              </svg>
            </div>
            <div>
              <h3 class="text-xl font-bold text-white">Ajouter un enseignant</h3>
              <p class="text-blue-100 text-sm">QR code ou recherche manuelle</p>
            </div>
          </div>
          <button @click="$emit('close')" class="text-white hover:text-blue-200 transition-colors p-2 hover:bg-white hover:bg-opacity-20 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- Contenu avec onglets -->
      <div class="overflow-y-auto max-h-[calc(90vh-120px)]">
        <div class="p-6">
          <!-- Onglets -->
          <div class="flex space-x-1 mb-6 bg-gray-100 p-1 rounded-lg">
            <!-- Onglet Nouveau (premier) -->
            <button 
              @click="activeTab = 'new'"
              :class="activeTab === 'new' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-600 hover:text-gray-800'"
              class="flex-1 py-2 px-4 text-sm font-medium rounded-md transition-colors"
            >
              <div class="flex items-center justify-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>Nouveau</span>
              </div>
            </button>
            <!-- Onglet Recherche (milieu) -->
            <button 
              @click="activeTab = 'search'"
              :class="activeTab === 'search' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-600 hover:text-gray-800'"
              class="flex-1 py-2 px-4 text-sm font-medium rounded-md transition-colors"
            >
              <div class="flex items-center justify-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span>Recherche</span>
              </div>
            </button>
            <!-- Onglet QR Code (dernier) -->
            <button 
              @click="activeTab = 'qr'"
              :class="activeTab === 'qr' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-600 hover:text-gray-800'"
              class="flex-1 py-2 px-4 text-sm font-medium rounded-md transition-colors"
            >
              <div class="flex items-center justify-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                </svg>
                <span>QR Code</span>
              </div>
            </button>
          </div>

          <!-- Contenu des onglets -->
          
          <!-- Onglet QR Code -->
          <div v-if="activeTab === 'qr'" class="space-y-6">
            <div class="bg-blue-50 rounded-xl p-6">
              <div class="flex items-center mb-4">
                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                  </svg>
                </div>
                <div>
                  <h4 class="text-lg font-semibold text-gray-900">Scanner QR Code</h4>
                  <p class="text-sm text-gray-600">Demandez à l'enseignant de présenter son QR code</p>
                </div>
              </div>
              
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Code QR</label>
                  <input 
                    v-model="qrCodeInput" 
                    type="text" 
                    placeholder="Scannez ou saisissez le code QR"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    @input="handleQrCodeInput"
                  >
                </div>
                
                <div v-if="scannedUser" class="bg-white border border-gray-200 rounded-lg p-4">
                  <div class="flex items-center space-x-3">
                    <div class="bg-blue-100 p-2 rounded-lg">
                      <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                      </svg>
                    </div>
                    <div class="flex-1">
                      <h5 class="font-medium text-gray-900">{{ scannedUser.name }}</h5>
                      <p class="text-sm text-gray-600">{{ scannedUser.email }}</p>
                      <p class="text-xs text-blue-600">{{ scannedUser.role }}</p>
                    </div>
                    <button 
                      @click="addScannedTeacher"
                      :disabled="loading"
                      class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"
                    >
                      Ajouter
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Onglet Recherche -->
          <div v-if="activeTab === 'search'" class="space-y-6">
            <div class="bg-purple-50 rounded-xl p-6">
              <div class="flex items-center mb-4">
                <div class="bg-purple-100 p-2 rounded-lg mr-3">
                  <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                  </svg>
                </div>
                <div>
                  <h4 class="text-lg font-semibold text-gray-900">Recherche manuelle</h4>
                  <p class="text-sm text-gray-600">Recherchez un enseignant existant</p>
                </div>
              </div>
              
              <div class="space-y-4">
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
                      placeholder="Nom ou email de l'enseignant"
                      class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                      @input="searchUsers"
                    >
                  </div>
                </div>
                
                <div v-if="searchResults.length > 0" class="space-y-2">
                  <h5 class="text-sm font-medium text-gray-700">Résultats</h5>
                  <div class="space-y-2">
                    <div 
                      v-for="user in searchResults" 
                      :key="user.id" 
                      class="bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors"
                    >
                      <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                          <div class="bg-blue-100 p-2 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                          </div>
                          <div>
                            <h6 class="font-medium text-gray-900">{{ user.name }}</h6>
                            <p class="text-sm text-gray-600">{{ user.email }}</p>
                            <p class="text-xs text-blue-600">{{ user.role }}</p>
                          </div>
                        </div>
                        <button 
                          @click="addSearchedTeacher(user)"
                          :disabled="loading"
                          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"
                        >
                          Ajouter
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div v-if="searchQuery && searchResults.length === 0 && !isSearching" class="text-center py-8 text-gray-500">
                  <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                  </svg>
                  <p>Aucun enseignant trouvé</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Onglet Nouveau -->
          <div v-if="activeTab === 'new'" class="space-y-6">
            <form @submit.prevent="createNewTeacher" class="space-y-6">
              <!-- Informations personnelles -->
              <div class="bg-emerald-50 rounded-xl p-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Informations personnelles</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <!-- Prénom -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Prénom *</label>
                    <input 
                      v-model="newTeacherForm.first_name" 
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
                      v-model="newTeacherForm.last_name" 
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
                      v-model="newTeacherForm.email" 
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
                      v-model="newTeacherForm.phone" 
                      type="tel" 
                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                      placeholder="0470123456"
                    >
                  </div>
                  
                  <!-- Numéro NISS -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Numéro NISS</label>
                    <input 
                      v-model="newTeacherForm.niss" 
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
                        v-model="newTeacherForm.street" 
                        type="text" 
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        placeholder="Rue de la Paix"
                      >
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Numéro</label>
                      <input 
                        v-model="newTeacherForm.street_number" 
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
                        v-model="newTeacherForm.postal_code" 
                        type="text" 
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        placeholder="1000"
                      >
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
                      <input 
                        v-model="newTeacherForm.city" 
                        type="text" 
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        placeholder="Bruxelles"
                      >
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Pays</label>
                      <select 
                        v-model="newTeacherForm.country" 
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
                      v-model="newTeacherForm.bank_account_number" 
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
                      v-model="newTeacherForm.contract_type" 
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
                      v-model.number="newTeacherForm.hourly_rate" 
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
                      v-model="newTeacherForm.experience_start_date" 
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
                    v-model="newTeacherForm.notes" 
                    rows="4"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                    placeholder="Informations complémentaires, qualifications, expériences..."
                  ></textarea>
                </div>
              </div>

              <!-- Boutons -->
              <div class="flex justify-end space-x-3 pt-4">
                <button 
                  type="button"
                  @click="resetNewTeacherForm"
                  class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                >
                  Réinitialiser
                </button>
                <button 
                  type="submit"
                  :disabled="loading"
                  class="px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-lg hover:from-emerald-600 hover:to-teal-700 disabled:opacity-50 transition-colors font-medium flex items-center space-x-2"
                >
                  <svg v-if="loading" class="animate-spin h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                  </svg>
                  <span>{{ loading ? 'Création...' : 'Créer l\'enseignant' }}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const emit = defineEmits(['close', 'success'])

const activeTab = ref('new') // Onglet "Nouveau" par défaut
const loading = ref(false)
const qrCodeInput = ref('')
const scannedUser = ref(null)
const searchQuery = ref('')
const searchResults = ref([])
const isSearching = ref(false)

// Formulaire de création d'enseignant
const newTeacherForm = ref({
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
  contract_type: 'volunteer', // Valeur par défaut : Bénévole
  hourly_rate: 24,
  experience_start_date: '', // Date de début d'expérience (pour calcul automatique)
  notes: ''
})

const calculatedBirthDate = ref('')

// Calculer la date de naissance depuis le NISS belge
// Format NISS belge : YYMMDD-XXX.XX ou YYMMDDXXX.XX (11 chiffres)
// Les 6 premiers chiffres : YYMMDD (année, mois, jour)
// Pour déterminer le siècle : si YY >= 00 et < 50, c'est 20YY, sinon 19YY
const calculateBirthDateFromNiss = () => {
  calculatedBirthDate.value = ''
  const niss = newTeacherForm.value.niss?.replace(/[^0-9]/g, '') // Enlever les caractères non numériques
  
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

// Gestion du QR code
const handleQrCodeInput = async () => {
  if (qrCodeInput.value.length > 10) {
    await scanQrCode(qrCodeInput.value)
  }
}

const scanQrCode = async (qrCode) => {
  try {
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/qr-code/scan`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: { qr_code: qrCode }
    })
    
    if (response.success && response.data.role === 'teacher') {
      scannedUser.value = response.data
    } else {
      scannedUser.value = null
    }
  } catch (error) {
    console.error('Erreur lors du scan:', error)
    scannedUser.value = null
  }
}

// Recherche d'utilisateurs
const searchUsers = async () => {
  if (searchQuery.value.length < 2) {
    searchResults.value = []
    return
  }
  
  isSearching.value = true
  try {
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/search-users`, {
      params: {
        query: searchQuery.value,
        role: 'teacher'
      }
    })
    
    if (response.success) {
      searchResults.value = response.data
    }
  } catch (error) {
    console.error('Erreur lors de la recherche:', error)
    searchResults.value = []
  } finally {
    isSearching.value = false
  }
}

// Ajouter un enseignant scanné
const addScannedTeacher = async () => {
  if (!scannedUser.value) return
  
  loading.value = true
  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    
    const response = await $fetch(`${config.public.apiBase}/club/add-existing-teacher`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${tokenCookie.value}`,
        'Content-Type': 'application/json'
      },
      body: {
        teacher_id: scannedUser.value.user_id
      }
    })
    
    console.log('✅ Enseignant ajouté avec succès:', response)
    
    // Afficher le toast de succès
    const { showToast } = useToast()
    showToast('Enseignant ajouté au club avec succès !', 'success')
    
    // Émettre les événements
    emit('success')
    emit('close')
    
  } catch (error) {
    console.error('❌ Erreur lors de l\'ajout de l\'enseignant:', error)
    
    // Afficher le toast d'erreur
    const { showToast } = useToast()
    showToast('Erreur lors de l\'ajout de l\'enseignant', 'error')
    
  } finally {
    loading.value = false
  }
}

// Ajouter un enseignant recherché
const addSearchedTeacher = async (user) => {
  loading.value = true
  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    
    const response = await $fetch(`${config.public.apiBase}/club/add-existing-teacher`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${tokenCookie.value}`,
        'Content-Type': 'application/json'
      },
      body: {
        teacher_id: user.id
      }
    })
    
    console.log('✅ Enseignant ajouté avec succès:', response)
    
    // Afficher le toast de succès
    const { showToast } = useToast()
    showToast('Enseignant ajouté au club avec succès !', 'success')
    
    // Émettre les événements
    emit('success')
    emit('close')
    
  } catch (error) {
    console.error('❌ Erreur lors de l\'ajout de l\'enseignant:', error)
    
    // Afficher le toast d'erreur
    const { showToast } = useToast()
    showToast('Erreur lors de l\'ajout de l\'enseignant', 'error')
    
  } finally {
    loading.value = false
  }
}

// Créer un nouvel enseignant
const createNewTeacher = async () => {
  loading.value = true
  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    
    // Construire le nom complet
    const fullName = `${newTeacherForm.value.first_name} ${newTeacherForm.value.last_name}`.trim()
    
    // Préparer les données
    const teacherData = {
      name: fullName,
      first_name: newTeacherForm.value.first_name,
      last_name: newTeacherForm.value.last_name,
      email: newTeacherForm.value.email,
      phone: newTeacherForm.value.phone || null,
      street: newTeacherForm.value.street || null,
      street_number: newTeacherForm.value.street_number || null,
      postal_code: newTeacherForm.value.postal_code || null,
      city: newTeacherForm.value.city || null,
      country: newTeacherForm.value.country,
      niss: newTeacherForm.value.niss || null,
      bank_account_number: newTeacherForm.value.bank_account_number || null,
      experience_start_date: newTeacherForm.value.experience_start_date || null,
      role: 'teacher',
      contract_type: newTeacherForm.value.contract_type || null,
      hourly_rate: newTeacherForm.value.hourly_rate || null,
      bio: newTeacherForm.value.notes || null
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
    
    // Afficher le toast de succès
    const { showToast } = useToast()
    showToast(`Enseignant ${fullName} créé avec succès !`, 'success')
    
    // Réinitialiser le formulaire
    resetNewTeacherForm()
    
    // Émettre les événements
    emit('success')
    emit('close')
    
  } catch (error) {
    console.error('❌ Erreur lors de la création de l\'enseignant:', error)
    
    // Afficher le toast d'erreur
    const { showToast } = useToast()
    const errorMessage = error.data?.message || 'Erreur lors de la création de l\'enseignant'
    showToast(errorMessage, 'error')
    
  } finally {
    loading.value = false
  }
}

// Réinitialiser le formulaire
const resetNewTeacherForm = () => {
  newTeacherForm.value = {
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
    contract_type: 'volunteer', // Valeur par défaut : Bénévole
    hourly_rate: 24,
    experience_start_date: '',
    notes: ''
  }
  calculatedBirthDate.value = ''
}
</script>
