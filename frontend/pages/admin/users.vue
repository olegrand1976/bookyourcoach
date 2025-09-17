<template>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 text-center">
                            <span class="mr-3 text-primary-600">👥</span>
                            Gestion des Utilisateurs
                        </h1>
                        <p class="mt-2 text-gray-600">Gérez tous les utilisateurs de la plateforme</p>
                    </div>
                    <button @click="showCreateModal = true" class="btn-primary btn-lg flex items-center">
                        <span class="mr-2">➕</span>
                        Nouvel utilisateur
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                        <input v-model="filters.search" type="text" placeholder="Nom ou email..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rôle</label>
                        <select v-model="filters.role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Tous les rôles</option>
                            <option value="admin">Administrateur</option>
                            <option value="teacher">Enseignant</option>
                            <option value="student">Élève</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                        <select v-model="filters.status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actif</option>
                            <option value="inactive">Inactif</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
                        <input v-model="filters.postal_code" type="text" placeholder="Ex: 1000" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="flex items-end">
                        <button @click="loadUsers" class="btn-secondary btn-full">
                            Filtrer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Liste des utilisateurs ({{ users.length }})
                    </h2>
                </div>

                <div v-if="loading" class="p-8 text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
                    <p class="mt-2 text-gray-500">Chargement...</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Utilisateur
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rôle
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Code postal
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Inscription
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div
                                                class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                                <span class="text-sm font-medium text-primary-600">
                                                    {{ user.name.charAt(0).toUpperCase() }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                            <div class="text-sm text-gray-500">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getRoleClass(user.role)"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ getRoleLabel(user.role) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="user.is_active ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100'"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ user.is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ user.postal_code || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(user.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button @click="editUser(user)" class="table-action-btn table-action-btn-edit">
                                            Modifier
                                        </button>
                                        <button @click="toggleUserStatus(user)"
                                            :class="user.is_active ? 'table-action-btn table-action-btn-delete' : 'table-action-btn table-action-btn-view'">
                                            {{ user.is_active ? 'Désactiver' : 'Activer' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button :disabled="currentPage <= 1" @click="changePage(currentPage - 1)"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Précédent
                        </button>
                        <button :disabled="currentPage >= totalPages" @click="changePage(currentPage + 1)"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Suivant
                        </button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Affichage de <span class="font-medium">{{ (currentPage - 1) * perPage + 1 }}</span>
                                à <span class="font-medium">{{ Math.min(currentPage * perPage, totalUsers) }}</span>
                                sur <span class="font-medium">{{ totalUsers }}</span> résultats
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <button v-for="page in visiblePages" :key="page" @click="changePage(page)"
                                    :class="page === currentPage ? 'bg-primary-50 border-primary-500 text-primary-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
                                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    {{ page }}
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit User Modal -->
        <div v-if="showCreateModal || showEditModal"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ showEditModal ? 'Modifier l\'utilisateur' : 'Créer un nouvel utilisateur' }}
                    </h3>
                    <form @submit.prevent="showEditModal ? updateUser() : createUser()" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nom *</label>
                                <input v-model="userForm.last_name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Prénom *</label>
                                <input v-model="userForm.first_name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email *</label>
                            <input v-model="userForm.email" type="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                            <input v-model="userForm.phone" type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date de naissance</label>
                            <input v-model="userForm.birth_date" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Adresse</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Rue</label>
                                    <input v-model="userForm.street" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Nom de la rue">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Numéro</label>
                                    <input v-model="userForm.street_number" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="92, 92/A, 92B...">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Code postal</label>
                                    <input v-model="userForm.postal_code" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="1000">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Ville</label>
                                    <input v-model="userForm.city" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Bruxelles">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Pays</label>
                                    <select v-model="userForm.country" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
                            <select v-model="userForm.role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="student">Élève</option>
                                <option value="teacher">Enseignant</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                        
                        <div v-if="!showEditModal">
                            <label class="block text-sm font-medium text-gray-700">Mot de passe *</label>
                            <input v-model="userForm.password" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div v-if="!showEditModal">
                            <label class="block text-sm font-medium text-gray-700">Confirmer le mot de passe *</label>
                            <input v-model="userForm.password_confirmation" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div class="form-button-group">
                            <button type="button" @click="closeModal" class="btn-secondary">Annuler</button>
                            <button type="submit" class="btn-primary">
                                {{ showEditModal ? 'Modifier' : 'Créer' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'

definePageMeta({
    layout: 'admin',
    middleware: 'admin'
})

// Reactive data
const loading = ref(true)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const users = ref([])
const currentPage = ref(1)
const perPage = ref(10)
const totalUsers = ref(0)
const totalPages = ref(0)

// Filters
const filters = ref({
    search: '',
    role: '',
    status: '',
    postal_code: ''
})

// Form
const userForm = ref({
    id: null,
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

// Computed
const visiblePages = computed(() => {
    const pages = []
    const start = Math.max(1, currentPage.value - 2)
    const end = Math.min(totalPages.value, currentPage.value + 2)

    for (let i = start; i <= end; i++) {
        pages.push(i)
    }
    return pages
})

// Methods
const getRoleClass = (role) => {
    const classes = {
        admin: 'bg-red-100 text-red-800',
        teacher: 'bg-green-100 text-green-800',
        student: 'bg-blue-100 text-blue-800'
    }
    return classes[role] || 'bg-gray-100 text-gray-800'
}

const getRoleLabel = (role) => {
    const labels = {
        admin: 'Admin',
        teacher: 'Enseignant',
        student: 'Élève'
    }
    return labels[role] || role
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR')
}

const loadUsers = async () => {
    loading.value = true
    try {
        const { $api } = useNuxtApp()

        // Construire les paramètres en filtrant les valeurs vides
        const params = new URLSearchParams({
            page: currentPage.value,
            per_page: perPage.value
        })

        // Ajouter seulement les filtres non vides
        if (filters.value.search && filters.value.search.trim()) {
            params.append('search', filters.value.search.trim())
        }
        if (filters.value.role && filters.value.role.trim()) {
            params.append('role', filters.value.role.trim())
        }
        if (filters.value.status && filters.value.status.trim()) {
            params.append('status', filters.value.status.trim())
        }
        if (filters.value.postal_code && filters.value.postal_code.trim()) {
            params.append('postal_code', filters.value.postal_code.trim())
        }

        console.log('Paramètres envoyés:', params.toString())
        const response = await $api.get(`/admin/users?${params}`)

        // Debug: afficher la réponse complète
        console.log('Response complète:', response)
        console.log('Response.data:', response.data)

        // Accès aux données selon la structure de pagination Laravel
        const responseData = response.data || response
        if (responseData.data && Array.isArray(responseData.data)) {
            // Structure de pagination Laravel
            users.value = responseData.data
            totalUsers.value = responseData.total || responseData.data.length
            totalPages.value = responseData.last_page || Math.ceil(totalUsers.value / perPage.value)
            currentPage.value = responseData.current_page || 1
            console.log('Utilisateurs chargés (pagination Laravel):', users.value.length)
        } else if (Array.isArray(responseData)) {
            // Cas où la réponse est directement un tableau
            users.value = responseData
            totalUsers.value = responseData.length
            totalPages.value = Math.ceil(totalUsers.value / perPage.value)
            console.log('Utilisateurs chargés (tableau direct):', users.value.length)
        } else {
            console.warn('Structure de réponse inattendue:', responseData)
            users.value = []
            totalUsers.value = 0
            totalPages.value = 0
        }
    } catch (error) {
        console.error('Erreur lors du chargement des utilisateurs:', error)
        console.error('Détails de l\'erreur:', error.response?.data)
        // Données de fallback en cas d'erreur
        users.value = []
        totalUsers.value = 0
        totalPages.value = 0
    } finally {
        loading.value = false
    }
}

const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
        loadUsers()
    }
}

const createUser = async () => {
    try {
        const { $api } = useNuxtApp()
        
        // Préparer les données pour l'API
        const userData = {
            first_name: userForm.value.first_name,
            last_name: userForm.value.last_name,
            email: userForm.value.email,
            phone: userForm.value.phone,
            birth_date: userForm.value.birth_date,
            street: userForm.value.street,
            street_number: userForm.value.street_number,
            postal_code: userForm.value.postal_code,
            city: userForm.value.city,
            country: userForm.value.country,
            role: userForm.value.role,
            password: userForm.value.password,
            password_confirmation: userForm.value.password_confirmation
        }
        
        console.log('Données envoyées:', userData)
        
        const response = await $api.post('/admin/users', userData)
        console.log('Réponse de création:', response)

        closeModal()
        await loadUsers()
        alert('Utilisateur créé avec succès!')
    } catch (error) {
        console.error('Erreur lors de la création:', error)
        console.error('Détails de l\'erreur:', error.response?.data)
        
        // Afficher les erreurs de validation si disponibles
        if (error.response?.data?.errors) {
            const errors = error.response.data.errors
            let errorMessage = 'Erreurs de validation:\n'
            for (const field in errors) {
                errorMessage += `- ${field}: ${errors[field].join(', ')}\n`
            }
            alert(errorMessage)
        } else {
            alert('Erreur lors de la création de l\'utilisateur: ' + (error.response?.data?.message || error.message))
        }
    }
}

const editUser = (user) => {
    userForm.value = {
        id: user.id,
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        email: user.email,
        phone: user.phone || '',
        birth_date: user.birth_date || '',
        street: user.street || '',
        street_number: user.street_number || '',
        postal_code: user.postal_code || '',
        city: user.city || '',
        country: user.country || 'Belgium',
        role: user.role,
        password: '',
        password_confirmation: ''
    }
    showEditModal.value = true
}

const updateUser = async () => {
    try {
        const { $api } = useNuxtApp()
        
        // Préparer les données pour l'API (sans les mots de passe vides)
        const userData = {
            first_name: userForm.value.first_name,
            last_name: userForm.value.last_name,
            email: userForm.value.email,
            phone: userForm.value.phone,
            birth_date: userForm.value.birth_date,
            street: userForm.value.street,
            street_number: userForm.value.street_number,
            postal_code: userForm.value.postal_code,
            city: userForm.value.city,
            country: userForm.value.country,
            role: userForm.value.role
        }
        
        // Ajouter le mot de passe seulement s'il est fourni
        if (userForm.value.password && userForm.value.password.trim()) {
            userData.password = userForm.value.password
            userData.password_confirmation = userForm.value.password_confirmation
        }
        
        console.log('Données de mise à jour:', userData)
        
        const response = await $api.put(`/admin/users/${userForm.value.id}`, userData)
        console.log('Réponse de mise à jour:', response)

        closeModal()
        await loadUsers()
        alert('Utilisateur modifié avec succès!')
    } catch (error) {
        console.error('Erreur lors de la modification:', error)
        console.error('Détails de l\'erreur:', error.response?.data)
        
        // Afficher les erreurs de validation si disponibles
        if (error.response?.data?.errors) {
            const errors = error.response.data.errors
            let errorMessage = 'Erreurs de validation:\n'
            for (const field in errors) {
                errorMessage += `- ${field}: ${errors[field].join(', ')}\n`
            }
            alert(errorMessage)
        } else {
            alert('Erreur lors de la modification de l\'utilisateur: ' + (error.response?.data?.message || error.message))
        }
    }
}

const toggleUserStatus = async (user) => {
    try {
        const { $api } = useNuxtApp()
        const response = await $api.patch(`/admin/users/${user.id}/toggle-status`)
        console.log('Réponse du changement de statut:', response)

        // Mettre à jour l'utilisateur local avec la réponse du serveur
        const updatedUser = response.data || response
        user.is_active = updatedUser.is_active
        user.status = updatedUser.status
        
        alert(`Utilisateur ${user.is_active ? 'activé' : 'désactivé'} avec succès!`)
    } catch (error) {
        console.error('Erreur lors du changement de statut:', error)
        console.error('Détails de l\'erreur:', error.response?.data)
        alert('Erreur lors du changement de statut: ' + (error.response?.data?.message || error.message))
    }
}

const closeModal = () => {
    showCreateModal.value = false
    showEditModal.value = false
    userForm.value = {
        id: null,
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
}

// Watch filters to reload data
watch(filters, () => {
    currentPage.value = 1
    loadUsers()
}, { deep: true })

// Lifecycle
onMounted(() => {
    loadUsers()
})
</script>
