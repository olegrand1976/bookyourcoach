<template>
  <div class="min-h-screen bg-gray-50 p-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Profil du Club</h1>
        <p class="mt-2 text-gray-600">Gérez les informations et activités de votre club</p>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex items-center justify-center py-20">
        <div class="text-center">
          <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-gray-600">Chargement des données...</p>
        </div>
      </div>

      <!-- Form -->
      <form v-else @submit.prevent="handleSubmit" class="bg-white shadow-lg rounded-lg p-6 space-y-8">
        <!-- Informations générales -->
        <section class="border-b pb-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Informations générales</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nom du club *</label>
              <input v-model="formData.name" type="text" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
              <input v-model="formData.email" type="email" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
              <input v-model="formData.phone" type="tel"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Site web</label>
              <input v-model="formData.website" type="url"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>
          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="formData.description" rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"></textarea>
          </div>
        </section>

        <!-- Adresse -->
        <section class="border-b pb-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Adresse</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
              <input v-model="formData.address" type="text"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
              <input v-model="formData.postal_code" type="text"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
              <input v-model="formData.city" type="text"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
              <input v-model="formData.country" type="text"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>
        </section>

        <!-- Activités et Disciplines -->
        <section class="border-b pb-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Activités et Disciplines</h2>
          
          <!-- Sélection des activités -->
          <div class="mb-6">
            <h3 class="font-medium text-gray-900 mb-3">Activités proposées</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <label v-for="activity in activities" :key="activity.id"
                     class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer"
                     :class="selectedActivityIds.includes(activity.id) ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                <input type="checkbox" :value="activity.id" v-model="selectedActivityIds"
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
                <span class="ml-3 font-medium text-gray-900">{{ activity.name }}</span>
              </label>
            </div>
          </div>

          <!-- Disciplines par activité sélectionnée -->
          <div v-if="selectedActivityIds.length > 0" class="space-y-4">
            <h3 class="font-medium text-gray-900 mb-3">Disciplines proposées</h3>
            <div v-for="activityId in selectedActivityIds" :key="activityId" class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-medium text-gray-900 mb-2">{{ getActivityName(activityId) }}</h4>
              <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                <label v-for="discipline in getDisciplinesByActivityId(activityId)" :key="discipline.id"
                       class="flex items-center p-2 text-sm">
                  <input type="checkbox" :value="discipline.id" v-model="selectedDisciplineIds"
                         class="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mr-2" />
                  <span class="text-gray-700">{{ discipline.name }}</span>
                </label>
              </div>
            </div>
          </div>
        </section>

        <!-- Configuration des cours -->
        <section v-if="selectedDisciplineIds.length > 0" class="border-b pb-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Configuration des cours</h2>
          <p class="text-sm text-gray-600 mb-4">Configurez la durée et le prix pour chaque discipline</p>
          
          <div class="space-y-4">
            <template v-for="disciplineId in selectedDisciplineIds" :key="disciplineId">
            <div v-if="settings[disciplineId]"
                 class="bg-white border border-gray-200 rounded-lg p-4">
              <h4 class="font-medium text-gray-900 mb-3">{{ getDisciplineName(disciplineId) }}</h4>
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Durée (minutes)</label>
                  <select v-model.number="settings[disciplineId].duration"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option :value="15">15 minutes</option>
                    <option :value="30">30 minutes</option>
                    <option :value="45">45 minutes</option>
                    <option :value="60">1 heure</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Prix (€)</label>
                  <input v-model.number="settings[disciplineId].price" type="number" step="0.01" min="0"
                         class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                </div>
                <div class="flex items-end">
                  <div class="w-full text-sm text-gray-600 bg-gray-50 rounded-md p-3">
                    <div class="font-medium">Prix/heure</div>
                    <div class="text-lg font-bold text-blue-600">
                      {{ calculatePricePerHour(disciplineId) }}€/h
                    </div>
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Participants (min - max)</label>
                  <div class="flex space-x-2">
                    <input v-model.number="settings[disciplineId].min_participants" type="number" min="1"
                           class="w-1/2 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                    <input v-model.number="settings[disciplineId].max_participants" type="number" min="1"
                           class="w-1/2 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optionnel)</label>
                  <input v-model="settings[disciplineId].notes" type="text"
                         class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                         placeholder="Matériel fourni, niveau requis..." />
                </div>
              </div>
            </div>
            </template>
          </div>
        </section>

        <!-- Statut -->
        <section class="pb-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Paramètres</h2>
          <label class="flex items-center">
            <input v-model="formData.is_active" type="checkbox"
                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
            <span class="ml-2 text-sm text-gray-700">Club actif (visible sur la plateforme)</span>
          </label>
        </section>

        <!-- Actions -->
        <div class="flex justify-end space-x-4 pt-6 border-t">
          <button type="button" @click="goBack"
                  class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
            Annuler
          </button>
          <button type="submit" :disabled="isSaving"
                  class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 min-w-[150px]">
            <span v-if="!isSaving">Enregistrer</span>
            <span v-else class="flex items-center justify-center">
              <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Sauvegarde...
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, watch, onMounted } from 'vue'
import { useToast } from '~/composables/useToast'

// ============================================================================
// STATE
// ============================================================================
const toast = useToast()
const isLoading = ref(true)
const isSaving = ref(false)

// Données brutes de l'API
const activities = ref([])
const disciplines = ref([])

// Données du formulaire
const formData = reactive({
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

// IDs sélectionnés (la source de vérité)
const selectedActivityIds = ref([])
const selectedDisciplineIds = ref([])

// Settings par discipline (clé = ID numérique)
const settings = ref({})

// ============================================================================
// COMPUTED
// ============================================================================
const getActivityName = (id) => {
  return activities.value.find(a => a.id === id)?.name || ''
}

const getDisciplineName = (id) => {
  return disciplines.value.find(d => d.id === id)?.name || ''
}

const getDisciplinesByActivityId = (activityId) => {
  return disciplines.value.filter(d => d.activity_type_id === activityId)
}

const calculatePricePerHour = (disciplineId) => {
  const s = settings.value[disciplineId]
  if (!s || !s.duration || !s.price) return '0.00'
  return ((s.price / s.duration) * 60).toFixed(2)
}

// ============================================================================
// WATCHERS - Gestion automatique des settings
// ============================================================================
watch(selectedDisciplineIds, (newIds, oldIds) => {
  // Ajouter les settings pour les nouvelles disciplines
  newIds.forEach(id => {
    if (!settings.value[id]) {
      settings.value[id] = {
        duration: 45,
        price: 25.00,
        min_participants: 1,
        max_participants: 8,
        notes: ''
      }
    }
  })
  
  // Supprimer les settings des disciplines désélectionnées
  if (oldIds) {
    oldIds.forEach(id => {
      if (!newIds.includes(id)) {
        delete settings.value[id]
      }
    })
  }
}, { deep: true })

// ============================================================================
// FONCTIONS DE CHARGEMENT
// ============================================================================
async function loadData() {
  try {
    isLoading.value = true
    const { $api } = useNuxtApp()
    const config = useRuntimeConfig()
    
    // 1. Charger les référentiels (pas de token nécessaire)
    const [activitiesRes, disciplinesRes] = await Promise.all([
      $fetch(`${config.public.apiBase}/activity-types`),
      $fetch(`${config.public.apiBase}/disciplines`)
    ])
    
    activities.value = activitiesRes.data || []
    disciplines.value = disciplinesRes.data || []
    
    console.log('✅ Référentiels chargés:', activities.value.length, 'activités,', disciplines.value.length, 'disciplines')
    
    // 2. Charger le profil du club
    const profileRes = await $api.get('/club/profile')
    
    if (profileRes.data.success && profileRes.data.data) {
      const club = profileRes.data.data
      console.log('✅ Profil club reçu:', club)
      
      // Remplir le formulaire
      formData.name = club.name || ''
      formData.email = club.email || ''
      formData.phone = club.phone || ''
      formData.website = club.website || ''
      formData.description = club.description || ''
      formData.address = club.address || ''
      formData.city = club.city || ''
      formData.postal_code = club.postal_code || ''
      formData.country = club.country || ''
      formData.is_active = club.is_active !== false
      
      // Traiter les activités
      if (club.activity_types) {
        const activityData = typeof club.activity_types === 'string' 
          ? JSON.parse(club.activity_types) 
          : club.activity_types
        selectedActivityIds.value = Array.isArray(activityData) 
          ? activityData.map(a => typeof a === 'object' ? a.id : a)
          : []
      }
      
<<<<<<< HEAD
      // Si c'est un nouveau profil (needs_setup), afficher un message informatif
      if (club.needs_setup) {
        console.log('🆕 Nouveau profil club détecté - configuration initiale requise')
        toast.info('Bienvenue ! Configurez votre profil club ci-dessous.', 'Configuration initiale')
      }
      
      // Charger les disciplines sélectionnées (avec parsing JSON si nécessaire)
=======
      // Traiter les disciplines - CONVERSION NOM → ID
>>>>>>> 38415038 (add midification on profile)
      if (club.disciplines) {
        const disciplineData = typeof club.disciplines === 'string' 
          ? JSON.parse(club.disciplines) 
          : club.disciplines
        
        console.log('📋 Disciplines brutes:', disciplineData)
        
        if (Array.isArray(disciplineData)) {
          selectedDisciplineIds.value = disciplineData
            .map(item => {
              if (typeof item === 'number') return item // Déjà un ID
              if (typeof item === 'object' && item.id) return item.id // Objet avec ID
              
              // Nom de discipline → chercher l'ID
              if (typeof item === 'string') {
                const found = disciplines.value.find(d => 
                  d.name.toLowerCase().trim() === item.toLowerCase().trim()
                )
                if (found) {
                  console.log(`  ✓ "${item}" → ID ${found.id}`)
                  return found.id
                }
                console.warn(`  ⚠️ Discipline "${item}" introuvable`)
              }
              return null
            })
            .filter(id => id !== null)
        }
        
        console.log('✅ Disciplines converties:', selectedDisciplineIds.value)
      }
      
      // Traiter les settings - CONVERSION NOM → ID
      if (club.discipline_settings) {
        const settingsData = typeof club.discipline_settings === 'string' 
          ? JSON.parse(club.discipline_settings) 
          : club.discipline_settings
        
        console.log('📋 Settings bruts:', settingsData)
        
        if (typeof settingsData === 'object') {
          // Convertir les clés (noms) en IDs
          Object.entries(settingsData).forEach(([key, value]) => {
            // Chercher l'ID de la discipline par son nom
            const found = disciplines.value.find(d => 
              d.name.toLowerCase().trim() === key.toLowerCase().trim()
            )
            
            if (found && selectedDisciplineIds.value.includes(found.id)) {
              settings.value[found.id] = {
                duration: value.duration || 45,
                price: value.price || 25.00,
                min_participants: value.min_participants || 1,
                max_participants: value.max_participants || 8,
                notes: value.notes || ''
              }
              console.log(`  ✓ Settings "${key}" → ID ${found.id}`)
            }
          })
        }
        
        console.log('✅ Settings convertis:', settings.value)
      }
    }
  } catch (error) {
    console.error('❌ Erreur chargement:', error)
    toast.error('Erreur lors du chargement des données')
  } finally {
    isLoading.value = false
  }
}

// ============================================================================
// SOUMISSION
// ============================================================================
async function handleSubmit() {
  try {
    isSaving.value = true
    const { $api } = useNuxtApp()
    
    const payload = {
      ...formData,
      activity_types: selectedActivityIds.value,
      disciplines: selectedDisciplineIds.value,
      discipline_settings: settings.value
    }
    
    console.log('📤 Envoi:', payload)
    
    await $api.put('/club/profile', payload)
    
    toast.success('Profil mis à jour avec succès')
    
    setTimeout(() => {
      navigateTo('/club/dashboard')
    }, 1000)
  } catch (error) {
    console.error('❌ Erreur sauvegarde:', error)
    toast.error('Erreur lors de la sauvegarde')
  } finally {
    isSaving.value = false
  }
}

function goBack() {
  navigateTo('/club/dashboard')
}

// ============================================================================
// INITIALISATION
// ============================================================================
onMounted(() => {
  loadData()
})

useHead({
  title: 'Profil du Club | activibe'
})
</script>
