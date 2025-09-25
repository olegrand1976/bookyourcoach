<template>
  <div class="min-h-screen bg-equestrian-cream">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-equestrian-darkBrown flex items-center">
          <span class="text-4xl mr-3">🏢</span>
          Profil du Club
        </h1>
        <p class="mt-2 text-equestrian-brown">Gérez les informations et activités de votre club</p>
      </div>

      <!-- Profile Form -->
      <div class="bg-white shadow-lg rounded-lg border border-equestrian-gold/20">
        <form @submit.prevent="updateClub" class="space-y-6 p-6">
          <!-- Informations générales -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-lg font-semibold text-equestrian-darkBrown mb-4 flex items-center">
              <span class="text-xl mr-2">📋</span>
              Informations générales
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nom du club</label>
                <input v-model="form.name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent"
                  placeholder="Nom de votre club" required />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email de contact</label>
                <input v-model="form.email" type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent"
                  placeholder="contact@votreclub.com" required />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                <input v-model="form.phone" type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent"
                  placeholder="+33 1 23 45 67 89" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Site web</label>
                <input v-model="form.website" type="url" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent"
                  placeholder="https://votreclub.com" />
              </div>
            </div>

            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
              <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent"
                placeholder="Décrivez votre club, ses valeurs et ses services..."></textarea>
            </div>
          </div>

          <!-- Adresse -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-lg font-semibold text-equestrian-darkBrown mb-4 flex items-center">
              <span class="text-xl mr-2">📍</span>
              Adresse
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Adresse</label>
                <input v-model="form.address" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent"
                  placeholder="123 Rue de l'Équitation" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Code postal</label>
                <input v-model="form.postal_code" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent"
                  placeholder="75001" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
                <input v-model="form.city" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent"
                  placeholder="Paris" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pays</label>
                <input v-model="form.country" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent"
                  placeholder="France" />
              </div>
            </div>
          </div>

          <!-- Activités du club -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-lg font-semibold text-equestrian-darkBrown mb-4 flex items-center">
              <span class="text-xl mr-2">🏃‍♀️</span>
              Activités proposées
            </h2>

            <div class="mb-4">
              <p class="text-sm text-gray-600 mb-4">Sélectionnez les activités que votre club propose :</p>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="activity in availableActivities" :key="activity.id" 
                     class="flex items-center p-4 border rounded-lg hover:bg-gray-50 transition-colors"
                     :class="selectedActivities.includes(activity.id) ? 'border-equestrian-gold bg-equestrian-cream' : 'border-gray-200'">
                  <input :id="'activity-' + activity.id" 
                         v-model="selectedActivities" 
                         :value="activity.id" 
                         type="checkbox" 
                         class="h-4 w-4 text-equestrian-gold focus:ring-equestrian-gold border-gray-300 rounded">
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
              <h3 class="text-md font-medium text-equestrian-darkBrown mb-3">Spécialités par activité</h3>
              
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
                             class="h-3 w-3 text-equestrian-gold focus:ring-equestrian-gold border-gray-300 rounded mr-2">
                      <span class="text-gray-700">{{ discipline.name }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Statut du club -->
          <div class="pb-6">
            <h2 class="text-lg font-semibold text-equestrian-darkBrown mb-4 flex items-center">
              <span class="text-xl mr-2">⚙️</span>
              Paramètres
            </h2>

            <div class="flex items-center">
              <input v-model="form.is_active" type="checkbox" id="is_active" 
                     class="h-4 w-4 text-equestrian-gold focus:ring-equestrian-gold border-gray-300 rounded">
              <label for="is_active" class="ml-2 text-sm text-gray-700">
                Club actif (visible sur la plateforme)
              </label>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
            <button type="button" @click="cancelEdit" 
                    class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-equestrian-gold">
              Annuler
            </button>
            <button type="submit" :disabled="loading"
                    class="px-6 py-2 bg-equestrian-gold text-white rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-equestrian-gold disabled:opacity-50">
              <span v-if="loading">Enregistrement...</span>
              <span v-else>Enregistrer les modifications</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
const authStore = useAuthStore()
const loading = ref(false)

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
const selectedActivities = ref([])
const selectedDisciplines = ref([])

// Charger les données
const loadClubData = async () => {
  try {
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/club/profile`)
    
    if (response.user && response.user.club) {
      const club = response.user.club
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

// Méthodes utilitaires
const getActivityById = (id) => {
  return availableActivities.value.find(activity => activity.id === id)
}

const getDisciplinesByActivity = (activityId) => {
  // Cette fonction devrait charger les disciplines depuis l'API
  // Pour l'instant, retournons des données statiques
  const disciplines = {
    1: [ // Équitation
      { id: 1, name: 'Dressage' },
      { id: 2, name: 'Saut d\'obstacles' },
      { id: 3, name: 'Complet' },
      { id: 4, name: 'Endurance' }
    ],
    2: [ // Natation
      { id: 5, name: 'Crawl' },
      { id: 6, name: 'Brasse' },
      { id: 7, name: 'Papillon' },
      { id: 8, name: 'Aquagym' }
    ],
    3: [ // Salle de sport
      { id: 9, name: 'Musculation' },
      { id: 10, name: 'Cardio' },
      { id: 11, name: 'Yoga' },
      { id: 12, name: 'Pilates' }
    ],
    4: [ // Coaching sportif
      { id: 13, name: 'Perte de poids' },
      { id: 14, name: 'Prise de masse' },
      { id: 15, name: 'Préparation physique' },
      { id: 16, name: 'Rééducation' }
    ]
  }
  return disciplines[activityId] || []
}

// Actions
const updateClub = async () => {
  loading.value = true
  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    
    const updateData = {
      ...form.value,
      activity_types: selectedActivities.value,
      disciplines: selectedDisciplines.value
    }
    
    await $fetch(`${config.public.apiBase}/club/profile`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${tokenCookie.value}`,
        'Content-Type': 'application/json'
      },
      body: updateData
    })
    
    // Rediriger vers le dashboard
    await navigateTo('/club/dashboard')
  } catch (error) {
    console.error('Erreur lors de la mise à jour du club:', error)
  } finally {
    loading.value = false
  }
}

const cancelEdit = () => {
  navigateTo('/club/dashboard')
}

// Initialisation
onMounted(async () => {
  await Promise.all([
    loadClubData(),
    loadActivities()
  ])
})

useHead({
  title: 'Profil du Club | activibe',
  meta: [
    { name: 'description', content: 'Gérez les informations et activités de votre club sur activibe' }
  ]
})
</script>
