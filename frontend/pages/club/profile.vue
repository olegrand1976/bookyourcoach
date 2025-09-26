<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 flex items-center">
          <span class="text-4xl mr-3">🏢</span>
          Profil du Club
        </h1>
        <p class="mt-2 text-gray-600">Gérez les informations et activités de votre club</p>
      </div>

      <!-- Profile Form -->
      <div class="bg-white shadow-lg rounded-lg border border-gray-200">
        <form @submit.prevent="updateClub" class="space-y-6 p-6">
          <!-- Informations générales -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <span class="text-xl mr-2">📋</span>
              Informations générales
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nom du club</label>
                <input v-model="form.name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Nom de votre club" required />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email de contact</label>
                <input v-model="form.email" type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="contact@votreclub.com" required />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                <input v-model="form.phone" type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="+33 1 23 45 67 89" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Site web</label>
                <input v-model="form.website" type="url" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="https://votreclub.com" />
              </div>
            </div>

            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
              <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Décrivez votre club, ses valeurs et ses services..."></textarea>
            </div>
          </div>

          <!-- Adresse -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <span class="text-xl mr-2">📍</span>
              Adresse
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Adresse</label>
                <input v-model="form.address" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="123 Rue de l'Équitation" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Code postal</label>
                <input v-model="form.postal_code" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="75001" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
                <input v-model="form.city" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Paris" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pays</label>
                <input v-model="form.country" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="France" />
              </div>
            </div>
          </div>

          <!-- Activités du club -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <span class="text-xl mr-2">🏃‍♀️</span>
              Activités proposées
            </h2>

            <div class="mb-4">
              <p class="text-sm text-gray-600 mb-4">Sélectionnez les activités que votre club propose :</p>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="activity in availableActivities" :key="activity.id" 
                     class="flex items-center p-4 border rounded-lg hover:bg-gray-50 transition-colors"
                     :class="selectedActivities.includes(activity.id) ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                  <input :id="'activity-' + activity.id" 
                         v-model="selectedActivities" 
                         :value="activity.id" 
                         type="checkbox" 
                         class="h-4 w-4 text-blue-500 focus:ring-blue-500 border-gray-300 rounded">
                  <label :for="'activity-' + activity.id" class="ml-3 flex items-center cursor-pointer">
                    <span class="text-2xl mr-2">{{ activity.icon }}</span>
                    <div>
                      <div class="font-medium text-gray-900">{{ activity.name }}</div>
                      <div class="text-sm text-gray-500">{{ activity.description }}</div>
                    </div>
                  </label>
                </div>
              </div>
            </div>

            <!-- Spécialités par activité -->
            <div v-if="selectedActivities.length > 0" class="mt-6">
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-md font-medium text-gray-900">Spécialités par activité</h3>
                <button
                  v-if="!showAddSpecialtyForm"
                  @click="showAddSpecialtyForm = true"
                  class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2 text-sm"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                  <span>Ajouter une spécialité</span>
                </button>
              </div>
              
              <!-- Formulaire d'ajout de spécialité -->
              <AddCustomSpecialtyForm
                v-if="showAddSpecialtyForm"
                :available-activities="availableActivities"
                @cancel="showAddSpecialtyForm = false"
                @success="handleAddSpecialtySuccess"
              />

              <div v-for="activityId in selectedActivities" :key="activityId" class="mb-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                  <h4 class="font-medium text-gray-900 mb-2 flex items-center">
                    <span class="text-lg mr-2">{{ getActivityById(activityId)?.icon }}</span>
                    {{ getActivityById(activityId)?.name }}
                  </h4>
                  
                  <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    <label v-for="discipline in getDisciplinesByActivity(activityId)" :key="discipline.id"
                           class="flex items-center p-2 text-sm">
                      <input :id="'discipline-' + discipline.id" 
                             v-model="selectedDisciplines" 
                             :value="discipline.id" 
                             type="checkbox" 
                             class="h-3 w-3 text-blue-500 focus:ring-blue-500 border-gray-300 rounded mr-2">
                      <span class="text-gray-700">{{ discipline.name }}</span>
                    </label>
                    
                    <!-- Spécialités personnalisées pour cette activité -->
                    <template v-for="customSpecialty in getCustomSpecialtiesByActivity(activityId)" :key="'custom-' + customSpecialty.id">
                      <!-- Affiche le formulaire de modification si on est en mode édition pour CETTE spécialité -->
                      <div v-if="editingSpecialtyId === customSpecialty.id" class="col-span-2 md:col-span-3">
                        <EditCustomSpecialtyForm
                          :specialty="customSpecialty"
                          :available-activities="availableActivities"
                          @cancel="editingSpecialtyId = null"
                          @success="handleEditSpecialtySuccess"
                        />
                      </div>

                      <!-- Affiche la spécialité normalement sinon -->
                      <div v-else
                           :class="[
                             'flex items-center justify-between p-2 text-sm rounded border',
                             customSpecialty.is_active ? 'bg-blue-50 border-blue-200' : 'bg-gray-100 border-gray-200 opacity-60'
                           ]">
                        <div class="flex items-center">
                          <input :id="'custom-specialty-' + customSpecialty.id" 
                                 v-model="selectedCustomSpecialties" 
                                 :value="customSpecialty.id" 
                                 type="checkbox" 
                                 class="h-3 w-3 text-blue-500 focus:ring-blue-500 border-gray-300 rounded mr-2">
                          <span class="font-medium" :class="[customSpecialty.is_active ? 'text-gray-700' : 'text-gray-500 line-through']">
                            {{ customSpecialty.name }}
                          </span>
                          <span class="text-xs text-blue-600 ml-1">(personnalisée)</span>
                        </div>
                        <div class="flex items-center space-x-2">
                          <button
                            type="button"
                            @click="editingSpecialtyId = customSpecialty.id"
                            class="p-1 text-gray-500 hover:text-blue-700 hover:bg-blue-100 rounded"
                            title="Modifier"
                          >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                          </button>
                          <button
                            type="button"
                            @click="toggleCustomSpecialty(customSpecialty.id)"
                            :class="[
                              'px-2 py-1 text-xs rounded transition-colors',
                              customSpecialty.is_active 
                                ? 'bg-red-100 text-red-700 hover:bg-red-200' 
                                : 'bg-green-100 text-green-700 hover:bg-green-200'
                            ]"
                            :title="customSpecialty.is_active ? 'Désactiver' : 'Activer'"
                          >
                            {{ customSpecialty.is_active ? 'Désactiver' : 'Activer' }}
                          </button>
                        </div>
                      </div>
                    </template>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Statut du club -->
          <div class="pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <span class="text-xl mr-2">⚙️</span>
              Paramètres
            </h2>

            <div class="flex items-center">
              <input v-model="form.is_active" type="checkbox" id="is_active" 
                     class="h-4 w-4 text-blue-500 focus:ring-blue-500 border-gray-300 rounded">
              <label for="is_active" class="ml-2 text-sm text-gray-700">
                Club actif (visible sur la plateforme)
              </label>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
            <button type="button" @click="cancelEdit" 
                    class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
              Annuler
            </button>
            <button type="submit" :disabled="loading"
                    class="px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 flex items-center justify-center w-48">
              <svg v-if="loading" class="animate-spin h-4 w-4 text-white mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span class="text-center">Enregistrer les modifications</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useToast } from '~/composables/useToast'
import AddCustomSpecialtyForm from '~/components/AddCustomSpecialtyForm.vue'
import EditCustomSpecialtyForm from '~/components/EditCustomSpecialtyForm.vue'

const authStore = useAuthStore()
const loading = ref(false)
const toast = useToast()

// Données du formulaire
const form = ref({
  name: '',
  email: '',
  phone: '',
  website: '',
  description: '',
  address: '',
  city: '',
  postal_code: '',
  country: '',
  is_active: true
})

// Activités et spécialités
const availableActivities = ref([])
const availableDisciplines = ref([])
const selectedActivities = ref([])
const selectedDisciplines = ref([])
const customSpecialties = ref([])
const selectedCustomSpecialties = ref([])
const showAddSpecialtyForm = ref(false)
const editingSpecialtyId = ref(null)

// Charger les données
const loadClubData = async () => {
  try {
    console.log('🔄 Chargement du profil club...')
    
    // Utiliser $api qui inclut automatiquement le token via l'intercepteur
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/profile')
    
    console.log('✅ Profil club reçu:', response)
    
    if (response.data.success && response.data.data) {
      const club = response.data.data
      form.value = {
        name: club.name || '',
        email: club.email || '',
        phone: club.phone || '',
        website: club.website || '',
        description: club.description || '',
        address: club.address || '',
        city: club.city || '',
        postal_code: club.postal_code || '',
        country: club.country || '',
        is_active: club.is_active !== false
      }
      
      // Charger les activités sélectionnées
      if (club.activity_types) {
        selectedActivities.value = club.activity_types.map(activity => activity.id)
      }
      
      // Charger les disciplines sélectionnées
      if (club.disciplines) {
        selectedDisciplines.value = club.disciplines.map(discipline => discipline.id)
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement du profil:', error)
  }
}

// Charger les activités disponibles
const loadActivities = async () => {
  try {
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/activity-types`)
    availableActivities.value = response.data || []
  } catch (error) {
    console.error('Erreur lors du chargement des activités:', error)
  }
}

// Charger les disciplines disponibles
const loadDisciplines = async () => {
  try {
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/disciplines`)
    availableDisciplines.value = response.data || []
  } catch (error) {
    console.error('Erreur lors du chargement des disciplines:', error)
  }
}

// Méthodes utilitaires
const getActivityById = (id) => {
  return availableActivities.value.find(activity => activity.id === id)
}

const getDisciplinesByActivity = (activityId) => {
  return availableDisciplines.value.filter(discipline => discipline.activity_type_id === activityId)
}

const getCustomSpecialtiesByActivity = (activityId) => {
  // Affiche toutes les spécialités, actives ou non
  return customSpecialties.value.filter(specialty => specialty.activity_type_id === activityId)
}

// Charger les spécialités personnalisées
const loadCustomSpecialties = async () => {
  try {
    console.log('🔄 Chargement des spécialités personnalisées...')
    
    // Utiliser $api qui inclut automatiquement le token via l'intercepteur
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/custom-specialties')
    
    console.log('✅ Spécialités reçues:', response)
    
    if (response.data.success) {
      customSpecialties.value = response.data.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement des spécialités personnalisées:', error)
  }
}

// Désactiver une spécialité personnalisée
const toggleCustomSpecialty = async (specialtyId) => {
  try {
    const specialty = customSpecialties.value.find(s => s.id === specialtyId)
    if (!specialty) return

    console.log('🔄 Basculement spécialité:', specialtyId)
    
    // Utiliser $api qui inclut automatiquement le token via l'intercepteur
    const { $api } = useNuxtApp()
    await $api.patch(`/club/custom-specialty/${specialtyId}/toggle`)

    // Mettre à jour localement
    specialty.is_active = !specialty.is_active
    
    toast.success(
      specialty.is_active ? 'Spécialité activée' : 'Spécialité désactivée', 
      'Modification réussie'
    )
  } catch (error) {
    console.error('Erreur lors de la modification de la spécialité:', error)
    toast.error('Erreur lors de la modification', 'Échec')
  }
}

// Actions
const updateClub = async () => {
  loading.value = true
  try {
    console.log('🔄 Mise à jour du profil club...')
    
    const updateData = {
      ...form.value,
      activity_types: selectedActivities.value,
      disciplines: selectedDisciplines.value
    }
    
    // Utiliser $api qui inclut automatiquement le token via l'intercepteur
    const { $api } = useNuxtApp()
    await $api.put('/club/profile', updateData)
    
    console.log('✅ Profil club mis à jour avec succès')
    
    // Afficher le message de succès
    toast.success('Profil du club mis à jour avec succès', 'Sauvegarde réussie')
    
    // Rediriger vers le dashboard après un court délai
    setTimeout(async () => {
      await navigateTo('/club/dashboard')
    }, 1500)
  } catch (error) {
    console.error('Erreur lors de la mise à jour du club:', error)
    toast.error('Erreur lors de la mise à jour du profil', 'Échec de la sauvegarde')
  } finally {
    loading.value = false
  }
}

const cancelEdit = () => {
  navigateTo('/club/dashboard')
}

const handleAddSpecialtySuccess = (newSpecialty) => {
  loadCustomSpecialties()
  showAddSpecialtyForm.value = false
}

const handleEditSpecialtySuccess = (updatedSpecialty) => {
  loadCustomSpecialties()
  editingSpecialtyId.value = null
}

// Initialisation
onMounted(async () => {
  await Promise.all([
    loadClubData(),
    loadActivities(),
    loadDisciplines(),
    loadCustomSpecialties()
  ])
})

useHead({
  title: 'Profil du Club | activibe',
  meta: [
    { name: 'description', content: 'Gérez les informations et activités de votre club sur activibe' }
  ]
})
</script>
