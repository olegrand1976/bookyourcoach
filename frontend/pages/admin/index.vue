<template>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                    <EquestrianIcon icon="trophy" class="mr-3 text-primary-600" :size="32" />
                    Dashboard Administrateur
                </h1>
                <p class="mt-2 text-gray-600">Vue d'ensemble et gestion de la plateforme BookYourCoach</p>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
            </div>

            <!-- Statistics Cards -->
            <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-blue-500">
                    <div class="flex items-center">
                        <EquestrianIcon icon="helmet" class="text-blue-500 mr-3" :size="24" />
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Utilisateurs</h3>
                            <p class="text-3xl font-bold text-blue-600">{{ stats.users }}</p>
                            <p class="text-sm text-gray-500">Total inscrits</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-green-500">
                    <div class="flex items-center">
                        <EquestrianIcon icon="saddle" class="text-green-500 mr-3" :size="24" />
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Enseignants</h3>
                            <p class="text-3xl font-bold text-green-600">{{ stats.teachers }}</p>
                            <p class="text-sm text-gray-500">Coaches actifs</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-purple-500">
                    <div class="flex items-center">
                        <EquestrianIcon icon="horseshoe" class="text-purple-500 mr-3" :size="24" />
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Élèves</h3>
                            <p class="text-3xl font-bold text-purple-600">{{ stats.students }}</p>
                            <p class="text-sm text-gray-500">Apprenants</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-orange-500">
                    <div class="flex items-center">
                        <EquestrianIcon icon="horse" class="text-orange-500 mr-3" :size="24" />
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Clubs</h3>
                            <p class="text-3xl font-bold text-orange-600">{{ stats.clubs }}</p>
                            <p class="text-sm text-gray-500">Centres équestres</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                        <EquestrianIcon icon="trophy" class="mr-2 text-primary-600" :size="20" />
                        Actions rapides
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button @click="showCreateUserModal = true"
                            class="btn-primary flex items-center justify-center">
                            <EquestrianIcon icon="helmet" class="mr-2" :size="16" />
                            Créer un utilisateur
                        </button>
                        <button @click="showCreateClubModal = true"
                            class="btn-secondary flex items-center justify-center">
                            <EquestrianIcon icon="horse" class="mr-2" :size="16" />
                            Créer un club
                        </button>
                        <NuxtLink to="/admin/users" class="btn-outline flex items-center justify-center">
                            <EquestrianIcon icon="saddle" class="mr-2" :size="16" />
                            Gérer les utilisateurs
                        </NuxtLink>
                        <NuxtLink to="/admin/settings" class="btn-outline flex items-center justify-center">
                            <EquestrianIcon icon="horseshoe" class="mr-2" :size="16" />
                            Paramètres système
                        </NuxtLink>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                        <EquestrianIcon icon="horseshoe" class="mr-2 text-primary-600" :size="20" />
                        Activité récente
                    </h2>
                    <div class="space-y-3">
                        <div v-for="activity in recentActivities" :key="activity.id"
                            class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <div class="flex-shrink-0">
                                <EquestrianIcon :icon="activity.icon" class="text-primary-600" :size="16" />
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ activity.message }}</p>
                                <p class="text-xs text-gray-500">{{ activity.time }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Users -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                        <EquestrianIcon icon="helmet" class="mr-2 text-primary-600" :size="20" />
                        Derniers utilisateurs
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nom</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Rôle</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Statut
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="user in recentUsers" :key="user.id">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ user.name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <span :class="getRoleClass(user.role)"
                                            class="px-2 py-1 text-xs font-semibold rounded-full">
                                            {{ getRoleLabel(user.role) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <span :class="user.is_active ? 'text-green-600' : 'text-red-600'"
                                            class="flex items-center">
                                            <div :class="user.is_active ? 'bg-green-400' : 'bg-red-400'"
                                                class="w-2 h-2 rounded-full mr-2"></div>
                                            {{ user.is_active ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <NuxtLink to="/admin/users" class="text-primary-600 hover:text-primary-500 text-sm font-medium">
                            Voir tous les utilisateurs →
                        </NuxtLink>
                    </div>
                </div>

                <!-- System Status -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                        <EquestrianIcon icon="trophy" class="mr-2 text-primary-600" :size="20" />
                        État du système
                    </h2>
                    <div class="space-y-4">
                        <div v-for="status in systemStatus" :key="status.name"
                            class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900">{{ status.name }}</span>
                            <span
                                :class="status.status === 'online' ? 'text-green-600 bg-green-100' : 'text-red-600 bg-red-100'"
                                class="px-2 py-1 text-xs font-semibold rounded-full">
                                {{ status.status === 'online' ? 'En ligne' : 'Hors ligne' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create User Modal -->
        <div v-if="showCreateUserModal"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Créer un nouvel utilisateur</h3>
                    <form @submit.prevent="createUser" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom</label>
                            <input v-model="newUser.name" type="text" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input v-model="newUser.email" type="email" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Rôle</label>
                            <select v-model="newUser.role" class="input-field" required>
                                <option value="student">Élève</option>
                                <option value="teacher">Enseignant</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mot de passe</label>
                            <input v-model="newUser.password" type="password" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Confirmer le mot de passe</label>
                            <input v-model="newUser.password_confirmation" type="password" class="input-field" required>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" @click="showCreateUserModal = false"
                                class="btn-outline">Annuler</button>
                            <button type="submit" class="btn-primary">Créer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Create Club Modal -->
        <div v-if="showCreateClubModal"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Créer un nouveau club</h3>
                    <form @submit.prevent="createClub" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom du club</label>
                            <input v-model="newClub.name" type="text" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input v-model="newClub.email" type="email" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                            <input v-model="newClub.phone" type="tel" class="input-field">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Adresse</label>
                            <textarea v-model="newClub.address" class="input-field" rows="3"></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" @click="showCreateClubModal = false"
                                class="btn-outline">Annuler</button>
                            <button type="submit" class="btn-primary">Créer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

definePageMeta({
    layout: 'admin',
    middleware: ['auth', 'admin']
})

const { $api } = useNuxtApp()
const toast = useToast()

// State
const loading = ref(true)
const stats = ref({
    users: 0,
    teachers: 0,
    students: 0,
    clubs: 0,
    lessons_today: 0,
    revenue_month: 0
})
const recentUsers = ref([])
const recentActivities = ref([])
const systemStatus = ref([
    { name: 'API Backend', status: 'online' },
    { name: 'Base de données', status: 'online' },
    { name: 'Serveur Frontend', status: 'online' },
    { name: 'Service de paiement', status: 'online' }
])
const showCreateUserModal = ref(false)
const showCreateClubModal = ref(false)
const newUser = ref({ name: '', email: '', password: '', role: 'student' })
const newClub = ref({ name: '', address: '', city: '', zip_code: '', country: 'France' })

// Fetch data
onMounted(async () => {
    loading.value = true
    try {
        const response = await $api.get('/admin/stats')
        stats.value = response.data.stats
        recentUsers.value = response.data.recentUsers
        recentActivities.value = response.data.recentActivities.map(activity => ({
            ...activity,
            icon: getActivityIcon(activity.action),
            time: formatTimeAgo(activity.created_at)
        }))
    } catch (error) {
        console.error("Erreur lors de la récupération des données du dashboard:", error)
        toast.error("Impossible de charger les données du dashboard.")
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

function getActivityIcon(action) {
    if (action.includes('create')) return 'plus'
    if (action.includes('update')) return 'pencil'
    if (action.includes('delete')) return 'trash'
    if (action.includes('login')) return 'login'
    return 'info'
}

function formatTimeAgo(dateString) {
    const date = new Date(dateString)
    const seconds = Math.floor((new Date() - date) / 1000)
    let interval = seconds / 31536000
    if (interval > 1) return Math.floor(interval) + " ans"
    interval = seconds / 2592000
    if (interval > 1) return Math.floor(interval) + " mois"
    interval = seconds / 86400
    if (interval > 1) return Math.floor(interval) + " jours"
    interval = seconds / 3600
    if (interval > 1) return Math.floor(interval) + " heures"
    interval = seconds / 60
    if (interval > 1) return Math.floor(interval) + " minutes"
    return Math.floor(seconds) + " secondes"
}

async function createUser() {
    try {
        await $api.post('/admin/users', newUser.value)
        toast.success('Utilisateur créé avec succès')
        showCreateUserModal.value = false
        // Re-fetch data or update list locally
    } catch (error) {
        toast.error("Erreur lors de la création de l'utilisateur.")
    }
}

async function createClub() {
    // Implement club creation logic
    toast.info('La création de club n\'est pas encore implémentée.')
    showCreateClubModal.value = false
}
</script>
