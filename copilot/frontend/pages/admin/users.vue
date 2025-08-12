<template>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                            <EquestrianIcon icon="helmet" class="mr-3 text-primary-600" :size="32" />
                            Gestion des Utilisateurs
                        </h1>
                        <p class="mt-2 text-gray-600">Gérez tous les utilisateurs de la plateforme</p>
                    </div>
                    <button @click="showCreateModal = true" class="btn-primary flex items-center">
                        <EquestrianIcon icon="horseshoe" class="mr-2" :size="16" />
                        Nouvel utilisateur
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                        <input v-model="filters.search" type="text" placeholder="Nom ou email..." class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rôle</label>
                        <select v-model="filters.role" class="input-field">
                            <option value="">Tous les rôles</option>
                            <option value="admin">Administrateur</option>
                            <option value="teacher">Enseignant</option>
                            <option value="student">Élève</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                        <select v-model="filters.status" class="input-field">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actif</option>
                            <option value="inactive">Inactif</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button @click="loadUsers" class="btn-outline w-full">
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
                                    {{ formatDate(user.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button @click="editUser(user)" class="text-indigo-600 hover:text-indigo-900">
                                            Modifier
                                        </button>
                                        <button @click="toggleUserStatus(user)"
                                            :class="user.is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'">
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
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ showEditModal ? 'Modifier l\'utilisateur' : 'Créer un nouvel utilisateur' }}
                    </h3>
                    <form @submit.prevent="showEditModal ? updateUser() : createUser()" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom</label>
                            <input v-model="userForm.name" type="text" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input v-model="userForm.email" type="email" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Rôle</label>
                            <select v-model="userForm.role" class="input-field" required>
                                <option value="student">Élève</option>
                                <option value="teacher">Enseignant</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                        <div v-if="!showEditModal">
                            <label class="block text-sm font-medium text-gray-700">Mot de passe</label>
                            <input v-model="userForm.password" type="password" class="input-field" required>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" @click="closeModal" class="btn-outline">Annuler</button>
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
    middleware: 'auth-admin'
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
    status: ''
})

// Form
const userForm = ref({
    id: null,
    name: '',
    email: '',
    role: 'student',
    password: ''
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
        const params = new URLSearchParams({
            page: currentPage.value,
            per_page: perPage.value,
            ...filters.value
        })

        const response = await $api.get(`/admin/users?${params}`)
        users.value = response.data.data
        totalUsers.value = response.data.total
        totalPages.value = Math.ceil(totalUsers.value / perPage.value)
    } catch (error) {
        console.error('Erreur lors du chargement des utilisateurs:', error)
        // Données de fallback
        users.value = [
            {
                id: 1,
                name: 'Marie Dubois',
                email: 'admin@bookyourcoach.fr',
                role: 'admin',
                is_active: true,
                created_at: '2025-08-11T19:13:54.000000Z'
            }
        ]
        totalUsers.value = 1
        totalPages.value = 1
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
        await $api.post('/admin/users', {
            ...userForm.value,
            password_confirmation: userForm.value.password
        })

        closeModal()
        await loadUsers()
        alert('Utilisateur créé avec succès!')
    } catch (error) {
        console.error('Erreur lors de la création:', error)
        alert('Erreur lors de la création de l\'utilisateur')
    }
}

const editUser = (user) => {
    userForm.value = {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
        password: ''
    }
    showEditModal.value = true
}

const updateUser = async () => {
    try {
        const { $api } = useNuxtApp()
        await $api.put(`/admin/users/${userForm.value.id}`, userForm.value)

        closeModal()
        await loadUsers()
        alert('Utilisateur modifié avec succès!')
    } catch (error) {
        console.error('Erreur lors de la modification:', error)
        alert('Erreur lors de la modification de l\'utilisateur')
    }
}

const toggleUserStatus = async (user) => {
    try {
        const { $api } = useNuxtApp()
        await $api.patch(`/admin/users/${user.id}/toggle-status`)

        user.is_active = !user.is_active
        alert(`Utilisateur ${user.is_active ? 'activé' : 'désactivé'} avec succès!`)
    } catch (error) {
        console.error('Erreur lors du changement de statut:', error)
        alert('Erreur lors du changement de statut')
    }
}

const closeModal = () => {
    showCreateModal.value = false
    showEditModal.value = false
    userForm.value = {
        id: null,
        name: '',
        email: '',
        role: 'student',
        password: ''
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
