<template>
  <!-- Modal avec design moderne et responsive -->
  <div class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden">
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
              <h3 class="text-xl font-bold text-white">Ajouter un nouvel enseignant</h3>
              <p class="text-blue-100 text-sm">Remplissez les informations ci-dessous</p>
            </div>
          </div>
          <button @click="$emit('close')" class="text-white hover:text-blue-200 transition-colors p-2 hover:bg-white hover:bg-opacity-20 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- Contenu avec scroll -->
      <div class="overflow-y-auto max-h-[calc(90vh-120px)]">
        <form @submit.prevent="addTeacher" class="p-6 space-y-8">
          
          <!-- Section Informations personnelles -->
          <div class="bg-gray-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-blue-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">Informations personnelles</h4>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Nom complet <span class="text-red-500">*</span>
                </label>
                <input 
                  v-model="form.name" 
                  type="text" 
                  required 
                  placeholder="Ex: Marie Dubois"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Email <span class="text-red-500">*</span>
                </label>
                <input 
                  v-model="form.email" 
                  type="email" 
                  required 
                  placeholder="Ex: marie.dubois@email.com"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                <input 
                  v-model="form.phone" 
                  type="tel" 
                  placeholder="Ex: 06 12 34 56 78"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Années d'expérience</label>
                <input 
                  v-model.number="form.experience_years" 
                  type="number" 
                  min="0" 
                  placeholder="Ex: 5"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Type de contrat <span class="text-red-500">*</span>
                </label>
                <select 
                  v-model="form.contract_type" 
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
                  <option value="freelance">Indépendant</option>
                  <option value="employee">Salarié</option>
                  <option value="volunteer">Bénévole</option>
                  <option value="student">Étudiant</option>
                  <option value="article17">Article 17</option>
                  <option value="intern">Stagiaire</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Section Spécialisations -->
          <div class="bg-purple-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-purple-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                </svg>
              </div>
              <div>
                <h4 class="text-lg font-semibold text-gray-900">Spécialisations</h4>
                <p class="text-sm text-gray-600">
                  <span v-if="clubSpecializations.length > 0">
                    Spécialisations disponibles du club (non sélectionnées par défaut)
                  </span>
                  <span v-else>
                    Sélectionnez les spécialisations de l'enseignant
                  </span>
                </p>
              </div>
            </div>
            
            <!-- Spécialisations du club (si disponibles) -->
            <div v-if="clubSpecializations.length > 0" class="mb-6">
              <h5 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs mr-2">Club</span>
                Spécialisations du club
              </h5>
              <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div 
                  v-for="specialization in clubSpecializations" 
                  :key="specialization.value" 
                  class="flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 hover:shadow-md"
                  :class="form.specializations.includes(specialization.value) 
                    ? 'border-blue-500 bg-blue-50 shadow-md' 
                    : 'border-gray-200 bg-white hover:border-gray-300'"
                  @click="toggleSpecialization(specialization.value)"
                >
                  <input 
                    :id="'club-specialization-' + specialization.value" 
                    v-model="form.specializations" 
                    :value="specialization.value" 
                    type="checkbox" 
                    class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  >
                  <label :for="'club-specialization-' + specialization.value" class="ml-4 flex items-center cursor-pointer flex-1">
                    <span class="text-2xl mr-3">{{ specialization.icon }}</span>
                    <div>
                      <div class="font-medium text-gray-900">{{ specialization.label }}</div>
                      <div class="text-sm text-gray-500">{{ specialization.description }}</div>
                    </div>
                  </label>
                </div>
              </div>
            </div>
            
            <!-- Toutes les spécialisations disponibles -->
            <div>
              <h5 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs mr-2">Toutes</span>
                Autres spécialisations disponibles
              </h5>
              <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div 
                  v-for="specialization in availableSpecializations" 
                  :key="specialization.value" 
                  class="flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 hover:shadow-md"
                  :class="form.specializations.includes(specialization.value) 
                    ? 'border-blue-500 bg-blue-50 shadow-md' 
                    : 'border-gray-200 bg-white hover:border-gray-300'"
                  @click="toggleSpecialization(specialization.value)"
                >
                  <input 
                    :id="'specialization-' + specialization.value" 
                    v-model="form.specializations" 
                    :value="specialization.value" 
                    type="checkbox" 
                    class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  >
                  <label :for="'specialization-' + specialization.value" class="ml-4 flex items-center cursor-pointer flex-1">
                    <span class="text-2xl mr-3">{{ specialization.icon }}</span>
                    <div>
                      <div class="font-medium text-gray-900">{{ specialization.label }}</div>
                      <div class="text-sm text-gray-500">{{ specialization.description }}</div>
                    </div>
                  </label>
                </div>
              </div>
            </div>
            
            <!-- Message si aucune spécialisation sélectionnée -->
            <div v-if="form.specializations.length === 0" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
              <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <p class="text-sm text-yellow-800">
                  Aucune spécialisation sélectionnée. L'enseignant pourra enseigner toutes les disciplines.
                </p>
              </div>
            </div>
          </div>

          <!-- Section Tarifs et Bio -->
          <div class="bg-emerald-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-emerald-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">Tarifs et présentation</h4>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Tarif horaire (€)</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">€</span>
                  </div>
                  <input 
                    v-model.number="form.hourly_rate" 
                    type="number" 
                    min="0" 
                    step="0.01" 
                    placeholder="50.00"
                    class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  >
                </div>
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Bio / Présentation</label>
                <textarea 
                  v-model="form.bio" 
                  rows="4" 
                  placeholder="Décrivez votre expérience et votre approche pédagogique..."
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
                ></textarea>
              </div>
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
              class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg hover:from-blue-600 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-medium flex items-center space-x-2"
            >
              <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ loading ? 'Ajout en cours...' : 'Ajouter l\'enseignant' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const emit = defineEmits(['close', 'success'])

const loading = ref(false)

const form = ref({
  name: '',
  email: '',
  phone: '',
  specializations: [],
  experience_years: 0,
  hourly_rate: 50,
  bio: '',
  contract_type: 'freelance', // Valeur par défaut
});

// Spécialisations disponibles avec icônes
const availableSpecializations = ref([
  { value: 'dressage', label: 'Dressage', description: 'Équitation classique', icon: '🏇' },
  { value: 'obstacle', label: 'Obstacle', description: 'Saut d\'obstacles', icon: '🏆' },
  { value: 'cross', label: 'Cross', description: 'Cross-country', icon: '🌲' },
  { value: 'complet', label: 'Complet', description: 'Concours complet', icon: '🎯' },
  { value: 'voltige', label: 'Voltige', description: 'Voltige équestre', icon: '🤸' },
  { value: 'pony', label: 'Poney', description: 'Cours poney', icon: '🐴' }
])

// Spécialisations du club (sera chargé dynamiquement)
const clubSpecializations = ref([])
const isLoadingSpecializations = ref(false)

// Charger les spécialisations du club
const loadClubSpecializations = async () => {
  isLoadingSpecializations.value = true
  try {
    const { $api } = useNuxtApp()
    
    const response = await $api.get('/club/profile')
    if (response.data.club && response.data.club.disciplines) {
      // Convertir les disciplines du club en spécialisations
      clubSpecializations.value = response.data.club.disciplines.map(discipline => {
        // Mapper les disciplines aux spécialisations équestres
        const mapping = {
          'Dressage': 'dressage',
          'Saut d\'obstacles': 'obstacle', 
          'Complet': 'complet',
          'Endurance': 'cross',
          'Voltige': 'voltige',
          'Poney': 'pony'
        }
        
        return {
          value: mapping[discipline.name] || discipline.name.toLowerCase(),
          label: discipline.name,
          description: discipline.description,
          icon: getActivityIcon(discipline.activity_type_id)
        }
      })
      
      // Les spécialisations du club sont disponibles mais non sélectionnées par défaut
      // L'utilisateur peut les sélectionner manuellement s'il le souhaite
    }
  } catch (error) {
    console.error('Erreur lors du chargement des spécialisations du club:', error)
  } finally {
    isLoadingSpecializations.value = false
  }
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

// Toggle spécialisation
const toggleSpecialization = (specializationValue) => {
  const index = form.value.specializations.indexOf(specializationValue)
  if (index > -1) {
    form.value.specializations.splice(index, 1)
  } else {
    form.value.specializations.push(specializationValue)
  }
}

const addTeacher = async () => {
  loading.value = true
  try {
    const { $api } = useNuxtApp()
    
    // Séparer le nom en prénom et nom de famille
    const nameParts = form.value.name.trim().split(' ')
    const firstName = nameParts[0]
    const lastName = nameParts.slice(1).join(' ') || nameParts[0]
    
    const response = await $api.post('/club/teachers', {
      first_name: firstName,
      last_name: lastName,
      email: form.value.email,
      phone: form.value.phone,
      experience_years: form.value.experience_years,
      hourly_rate: form.value.hourly_rate,
      bio: form.value.bio,
      contract_type: form.value.contract_type
    })
    
    console.log('✅ Enseignant créé avec succès:', response)
    
    // Afficher le toast de succès
    const { showToast } = useToast()
    showToast('Enseignant créé avec succès !', 'success')
    
    // Émettre les événements
    emit('success')
    emit('close')
    
  } catch (error) {
    console.error('❌ Erreur lors de l\'ajout de l\'enseignant:', error)
    
    // Afficher le toast d'erreur
    const { showToast } = useToast()
    showToast('Erreur lors de la création de l\'enseignant', 'error')
    
  } finally {
    loading.value = false
  }
}

// Charger les spécialisations du club au montage du composant
onMounted(() => {
  loadClubSpecializations()
})
</script>