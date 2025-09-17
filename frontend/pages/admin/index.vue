<template>
  <div class="p-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Dashboard Administrateur</h1>
    <p class="text-gray-600 mb-8">Vue d'ensemble et gestion de la plateforme Acti'Vibe.</p>

    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-4 text-gray-500">Chargement des données...</p>
    </div>

    <div v-else class="space-y-8">
      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200 flex items-center space-x-4">
          <div class="bg-blue-100 p-3 rounded-xl">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21a6 6 0 00-9-5.197m0 0A5.995 5.995 0 0112 13a5.995 5.995 0 013 5.197M15 21a6 6 0 00-9-5.197"></path></svg>
          </div>
          <div>
            <p class="text-sm text-gray-500">Utilisateurs</p>
            <p class="text-2xl font-bold text-gray-800">{{ stats.users }}</p>
          </div>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200 flex items-center space-x-4">
          <div class="bg-green-100 p-3 rounded-xl">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
          </div>
          <div>
            <p class="text-sm text-gray-500">Enseignants</p>
            <p class="text-2xl font-bold text-gray-800">{{ stats.teachers }}</p>
          </div>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200 flex items-center space-x-4">
          <div class="bg-indigo-100 p-3 rounded-xl">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 14l9-5-9-5-9 5 9 5z"></path><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-5.998 12.078 12.078 0 01.665-6.479L12 14z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-5.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222 4 2.222V20M1 14.55v-7.5l4-2.222m0 16.444v-7.5l-4-2.222"></path></svg>
          </div>
          <div>
            <p class="text-sm text-gray-500">Élèves</p>
            <p class="text-2xl font-bold text-gray-800">{{ stats.students }}</p>
          </div>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200 flex items-center space-x-4">
          <div class="bg-orange-100 p-3 rounded-xl">
           <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
          </div>
          <div>
            <p class="text-sm text-gray-500">Clubs</p>
            <p class="text-2xl font-bold text-gray-800">{{ stats.clubs }}</p>
          </div>
        </div>
      </div>

      <!-- Main Content Area -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Users -->
        <div class="lg:col-span-2 bg-white p-8 rounded-2xl shadow-lg border border-gray-200">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-800">Derniers utilisateurs inscrits</h2>
            <NuxtLink to="/admin/users" class="text-sm font-medium text-blue-600 hover:text-blue-800">
              Voir tout →
            </NuxtLink>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full">
              <thead class="border-b border-gray-200">
                <tr>
                  <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                  <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase">Rôle</th>
                  <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in recentUsers" :key="user.id" class="border-b border-gray-100">
                  <td class="py-4 text-sm font-medium text-gray-900">{{ user.name }}</td>
                  <td class="py-4 text-sm">
                    <span :class="getRoleClass(user.role)" class="px-2 py-1 text-xs font-semibold rounded-full">
                      {{ getRoleLabel(user.role) }}
                    </span>
                  </td>
                  <td class="py-4 text-sm">
                    <span :class="user.is_active ? 'text-green-600' : 'text-red-600'" class="flex items-center">
                      <div :class="user.is_active ? 'bg-green-400' : 'bg-red-400'" class="w-2 h-2 rounded-full mr-2"></div>
                      {{ user.is_active ? 'Actif' : 'Inactif' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- System Status & Actions -->
        <div class="space-y-8">
          <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-200">
            <h2 class="text-xl font-bold text-gray-800 mb-6">État du système</h2>
            <div class="space-y-4">
              <div v-for="status in systemStatus" :key="status.name" class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">{{ status.name }}</span>
                <span :class="status.status === 'online' ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100'" class="px-2.5 py-1 text-xs font-semibold rounded-full">
                  {{ status.status === 'online' ? 'En ligne' : 'Hors ligne' }}
                </span>
              </div>
            </div>
          </div>
          <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-200">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Actions rapides</h2>
            <div class="space-y-3">
              <button @click="showCreateUserModal = true" class="w-full flex items-center justify-center px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Créer un utilisateur
              </button>
              <button @click="showCreateClubModal = true" class="w-full flex items-center justify-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                Créer un club
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create User Modal -->
    <div v-if="showCreateUserModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-8">
          <h3 class="text-xl font-bold text-gray-800 mb-6">Créer un nouvel utilisateur</h3>
          <form @submit.prevent="createUser" class="space-y-6">
            <!-- Informations personnelles -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Prénom *</label>
                <input v-model="newUser.first_name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Nom *</label>
                <input v-model="newUser.last_name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Email *</label>
                <input v-model="newUser.email" type="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                <input v-model="newUser.phone" type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Date de naissance</label>
              <input v-model="newUser.birth_date" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <!-- Adresse -->
            <div>
              <label class="block text-sm font-medium text-gray-700">Adresse</label>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Rue</label>
                  <input v-model="newUser.street" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Nom de la rue">
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Numéro</label>
                  <input v-model="newUser.street_number" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="92, 92/A, 92B...">
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Code postal</label>
                  <input v-model="newUser.postal_code" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="1000">
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Ville</label>
                  <input v-model="newUser.city" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Bruxelles">
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Pays</label>
                  <select v-model="newUser.country" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Rôle *</label>
              <select v-model="newUser.role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                <option value="student">Élève</option>
                <option value="teacher">Enseignant</option>
                <option value="admin">Administrateur</option>
              </select>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Mot de passe *</label>
                <input v-model="newUser.password" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Confirmer le mot de passe *</label>
                <input v-model="newUser.password_confirmation" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
              </div>
            </div>
            
            <div class="form-button-group">
              <button type="button" @click="showCreateUserModal = false" class="btn-secondary">Annuler</button>
              <button type="submit" class="btn-primary">Créer l'utilisateur</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Create Club Modal -->
     <div v-if="showCreateClubModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50">
      <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Créer un nouveau club</h3>
        <form @submit.prevent="createClub" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Nom du club</label>
            <input v-model="newClub.name" type="text" class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input v-model="newClub.email" type="email" class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Téléphone</label>
            <input v-model="newClub.phone" type="tel" class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Adresse</label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Rue</label>
                <input v-model="newClub.street" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Nom de la rue">
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">Numéro</label>
                <input v-model="newClub.street_number" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="92, 92/A, 92B...">
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
              <div>
                <label class="block text-xs text-gray-500 mb-1">Code postal</label>
                <input v-model="newClub.postal_code" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="1000">
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">Ville</label>
                <input v-model="newClub.city" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Bruxelles">
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">Pays</label>
                <select v-model="newClub.country" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                  <option value="Belgium">Belgique</option>
                  <option value="France">France</option>
                  <option value="Netherlands">Pays-Bas</option>
                  <option value="Germany">Allemagne</option>
                </select>
              </div>
            </div>
          </div>
          <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
            <button type="button" @click="showCreateClubModal = false" class="btn-secondary">Annuler</button>
            <button type="submit" class="btn-primary">Créer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

definePageMeta({
    layout: 'admin',
    middleware: 'admin'
})

const { $api } = useNuxtApp()
const { showToast } = useToast()

// State
const loading = ref(true)
const stats = ref({
    users: 0,
    teachers: 0,
    students: 0,
    clubs: 0,
})
const recentUsers = ref([])
const systemStatus = ref([
    { name: 'API Backend', status: 'online' },
    { name: 'Base de données', status: 'online' },
    { name: 'Serveur Frontend', status: 'online' },
])
const showCreateUserModal = ref(false)
const showCreateClubModal = ref(false)
const newUser = ref({ 
    first_name: '', 
    last_name: '', 
    email: '', 
    phone: '', 
    birth_date: '', 
    street: '', 
    street_number: '', 
    postal_code: '', 
    city: '', 
    country: 'Belgium', 
    role: 'student', 
    password: '', 
    password_confirmation: '' 
})
const newClub = ref({ 
    name: '', 
    email: '', 
    phone: '', 
    street: '', 
    street_number: '', 
    postal_code: '', 
    city: '', 
    country: 'Belgium',
    description: '',
    website: ''
})

// Fetch data
onMounted(async () => {
    loading.value = true
    try {
        const response = await $api.get('/admin/stats')
        
        // Mapper les données de l'API vers le format attendu par le frontend
        if (response.data.stats) {
            stats.value = {
                users: response.data.stats.total_users || 0,
                teachers: response.data.stats.total_teachers || 0,
                students: response.data.stats.total_students || 0,
                clubs: response.data.stats.total_clubs || 0,
            }
        }
        
        recentUsers.value = response.data.recentUsers || []
    } catch (error) {
        console.error("Erreur lors de la récupération des données du dashboard:", error)
        showToast("Impossible de charger les données du dashboard.", 'error')
    } finally {
        loading.value = false
    }
})

// Methods
function getRoleClass(role) {
    const classes = {
        admin: 'bg-red-100 text-red-800',
        teacher: 'bg-green-100 text-green-800',
        student: 'bg-blue-100 text-blue-800',
    }
    return classes[role] || 'bg-gray-100 text-gray-800'
}

function getRoleLabel(role) {
    const labels = {
        admin: 'Admin',
        teacher: 'Enseignant',
        student: 'Élève',
    }
    return labels[role] || 'N/A'
}

async function createUser() {
    try {
        await $api.post('/admin/users', {
            ...newUser.value,
            password_confirmation: newUser.value.password
        })
        showToast('Utilisateur créé avec succès', 'success')
        showCreateUserModal.value = false
        // Reset form
        newUser.value = { 
            first_name: '', 
            last_name: '', 
            email: '', 
            phone: '', 
            birth_date: '', 
            street: '', 
            street_number: '', 
            postal_code: '', 
            city: '', 
            country: 'Belgium', 
            role: 'student', 
            password: '', 
            password_confirmation: '' 
        }
        // Re-fetch users
        const response = await $api.get('/admin/stats')
        recentUsers.value = response.data.recentUsers || []
    } catch (error) {
        showToast(error.data?.message || "Erreur lors de la création de l'utilisateur.", 'error')
    }
}

async function createClub() {
    try {
        console.log('🔍 Données envoyées:', newClub.value)
        const response = await $api.post('/admin/clubs', newClub.value)
        console.log('✅ Réponse API:', response)
        showToast('Club créé avec succès', 'success')
        showCreateClubModal.value = false
        // Reset form
        newClub.value = { 
            name: '', 
            email: '', 
            phone: '', 
            street: '', 
            street_number: '', 
            postal_code: '', 
            city: '', 
            country: 'Belgium',
            description: '',
            website: ''
        }
        // Re-fetch stats
        const statsResponse = await $api.get('/admin/stats')
        if (statsResponse.data.stats) {
            stats.value = {
                users: statsResponse.data.stats.total_users || 0,
                teachers: statsResponse.data.stats.total_teachers || 0,
                students: statsResponse.data.stats.total_students || 0,
                clubs: statsResponse.data.stats.total_clubs || 0,
            }
        }
    } catch (error) {
        console.error('❌ Erreur création club:', error)
        console.error('❌ Détails erreur:', error.response?.data)
        
        // Gestion des erreurs de validation détaillées
        if (error.response?.status === 422 && error.response?.data?.errors) {
            const errors = error.response.data.errors
            let errorMessages = []
            
            // Collecter tous les messages d'erreur
            for (const [field, messages] of Object.entries(errors)) {
                if (Array.isArray(messages)) {
                    errorMessages.push(...messages)
                } else {
                    errorMessages.push(messages)
                }
            }
            
            // Afficher le premier message d'erreur
            if (errorMessages.length > 0) {
                showToast(errorMessages[0], 'error')
            } else {
                showToast('Erreur de validation', 'error')
            }
        } else {
            // Erreur générale
            showToast(error.response?.data?.message || error.message || "Erreur lors de la création du club.", 'error')
        }
    }
}
</script>
