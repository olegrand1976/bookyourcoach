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
                    <button @click="showCreateModal = true" class="w-full flex items-center justify-center px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
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
                            <option value="club">Club</option>
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
                                    <div class="flex space-x-2 justify-end">
                                        <!-- Modifier -->
                                        <button 
                                            @click="editUser(user)" 
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="Modifier l'utilisateur"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        
                                        <!-- Créer club (si rôle club sans club) -->
                                        <button 
                                            v-if="user.role === 'club' && (!user.clubs || user.clubs.length === 0)"
                                            @click="createClubForUser(user)" 
                                            class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors"
                                            title="Créer un club pour cet utilisateur"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </button>
                                        
                                        <!-- Mot de passe -->
                                        <button 
                                            @click="resetUserPassword(user)" 
                                            class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg transition-colors"
                                            title="Réinitialiser le mot de passe"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                        </button>
                                        
                                        <!-- Activer/Désactiver -->
                                        <button 
                                            @click="toggleUserStatus(user)"
                                            :class="user.is_active ? 'text-red-600 hover:bg-red-50' : 'text-green-600 hover:bg-green-50'"
                                            class="p-2 rounded-lg transition-colors"
                                            :title="user.is_active ? 'Désactiver l\'utilisateur' : 'Activer l\'utilisateur'"
                                        >
                                            <svg v-if="user.is_active" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
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
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showCreateModal || showEditModal"
                class="fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-start justify-center pt-10 pb-10"
                @click.self="closeModal">
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div class="relative w-full max-w-2xl shadow-xl rounded-xl bg-white my-auto">
                        <!-- Header -->
                        <div class="border-b border-gray-200 px-8 py-6">
                            <h3 class="text-2xl font-semibold text-gray-900">
                                {{ showEditModal ? 'Modifier l\'utilisateur' : 'Créer un nouvel utilisateur' }}
                            </h3>
                        </div>
                        
                        <!-- Body -->
                        <div class="px-8 py-6 max-h-[calc(100vh-200px)] overflow-y-auto">
                            <form @submit.prevent="showEditModal ? updateUser() : createUser()" class="space-y-6">
                        <!-- Informations personnelles -->
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
                                    <input 
                                        v-model="userForm.last_name" 
                                        type="text" 
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" 
                                        required
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Prénom *</label>
                                    <input 
                                        v-model="userForm.first_name" 
                                        type="text" 
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" 
                                        required
                                    >
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input 
                                    v-model="userForm.email" 
                                    type="email" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" 
                                    required
                                >
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                                <input 
                                    v-model="userForm.phone" 
                                    type="tel" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                >
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date de naissance</label>
                                <input 
                                    v-model="userForm.birth_date" 
                                    type="date" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                >
                            </div>
                        </div>
                        
                        <!-- Adresse -->
                        <div class="space-y-4 border-t border-gray-100 pt-6">
                            <label class="block text-base font-semibold text-gray-900">Adresse</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-2">Rue</label>
                                    <input 
                                        v-model="userForm.street" 
                                        type="text" 
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" 
                                        placeholder="Rue de la Résistance"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-2">Numéro</label>
                                    <input 
                                        v-model="userForm.street_number" 
                                        type="text" 
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" 
                                        placeholder="92 / A"
                                    >
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-2">Code postal</label>
                                    <input 
                                        v-model="userForm.postal_code" 
                                        type="text" 
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" 
                                        placeholder="7131"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-2">Ville</label>
                                    <input 
                                        v-model="userForm.city" 
                                        type="text" 
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" 
                                        placeholder="Waudrez"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-2">Pays</label>
                                    <select 
                                        v-model="userForm.country" 
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 bg-white"
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
                        
                        <!-- Rôle -->
                        <div class="border-t border-gray-100 pt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rôle *</label>
                            <select 
                                v-model="userForm.role" 
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 bg-white" 
                                required
                            >
                                <option value="student">Élève</option>
                                <option value="teacher">Enseignant</option>
                                <option value="admin">Administrateur</option>
                                <option value="club">Club</option>
                            </select>
                        </div>
                        
                        <!-- Mot de passe (uniquement création) -->
                        <div v-if="!showEditModal" class="space-y-4 border-t border-gray-100 pt-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe *</label>
                                <input 
                                    v-model="userForm.password" 
                                    type="password" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" 
                                    required
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirmer le mot de passe *</label>
                                <input 
                                    v-model="userForm.password_confirmation" 
                                    type="password" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" 
                                    required
                                >
                            </div>
                        </div>
                                <!-- Footer avec boutons -->
                                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                                    <button 
                                        type="button" 
                                        @click="closeModal" 
                                        class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200"
                                    >
                                        Annuler
                                    </button>
                                    <button 
                                        type="submit" 
                                        class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg hover:from-blue-600 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm hover:shadow-md transition-all duration-200"
                                    >
                                        {{ showEditModal ? 'Modifier' : 'Créer' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
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
        student: 'bg-blue-100 text-blue-800',
        club: 'bg-purple-100 text-purple-800'
    }
    return classes[role] || 'bg-gray-100 text-gray-800'
}

const getRoleLabel = (role) => {
    const labels = {
        admin: 'Admin',
        teacher: 'Enseignant',
        student: 'Élève',
        club: 'Club'
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

const editUser = async (user) => {
    try {
        const { $api } = useNuxtApp()
        
        // Récupérer les détails de l'utilisateur via l'API
        // Cela créera automatiquement un club si l'utilisateur a le rôle "club" mais n'a pas de club
        const response = await $api.get(`/admin/users/${user.id}`)
        
        const userData = response.data.user || response.user || user
        
        // Afficher un message si un club a été créé automatiquement
        if (response.data.club_auto_created || response.club_auto_created) {
            console.log('✅ Club créé automatiquement pour l\'utilisateur:', userData.name)
            // Optionnel: afficher une notification
            // showToast('Club créé automatiquement pour cet utilisateur', 'success')
        }
        
        userForm.value = {
            id: userData.id,
            first_name: userData.first_name || '',
            last_name: userData.last_name || '',
            email: userData.email,
            phone: userData.phone || '',
            birth_date: userData.birth_date || '',
            street: userData.street || '',
            street_number: userData.street_number || '',
            postal_code: userData.postal_code || '',
            city: userData.city || '',
            country: userData.country || 'Belgium',
            role: userData.role,
            password: '',
            password_confirmation: ''
        }
        showEditModal.value = true
    } catch (error) {
        console.error('Erreur lors de la récupération des détails de l\'utilisateur:', error)
        // En cas d'erreur, utiliser les données de la liste
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

const createClubForUser = async (user) => {
    if (!confirm(`Créer un club pour ${user.name} ?`)) {
        return
    }
    
    try {
        const { $api } = useNuxtApp()
        const response = await $api.post(`/admin/users/${user.id}/create-club`)
        console.log('Réponse de création de club:', response)

        // Recharger la liste des utilisateurs pour afficher les changements
        await loadUsers()
        
        alert('Club créé avec succès! L\'utilisateur peut maintenant se connecter à son dashboard club.')
    } catch (error) {
        console.error('Erreur lors de la création du club:', error)
        console.error('Détails de l\'erreur:', error.response?.data)
        
        if (error.response?.data?.message) {
            alert(error.response.data.message)
        } else {
            alert('Erreur lors de la création du club: ' + error.message)
        }
    }
}

const resetUserPassword = async (user) => {
    if (!confirm(`Êtes-vous sûr de vouloir réinitialiser le mot de passe de ${user.name} (${user.email}) ?`)) {
        return
    }

    try {
        const { $api } = useNuxtApp()
        const response = await $api.post(`/admin/users/${user.id}/reset-password`)
        console.log('Réponse de la réinitialisation:', response)

        if (response.data && response.data.temporary_password) {
            const tempPassword = response.data.temporary_password
            alert(`Mot de passe réinitialisé avec succès !\n\nNouveau mot de passe temporaire : ${tempPassword}\n\nVeuillez le communiquer à l'utilisateur.`)
        } else {
            alert('Mot de passe réinitialisé avec succès !')
        }
    } catch (error) {
        console.error('Erreur lors de la réinitialisation du mot de passe:', error)
        console.error('Détails de l\'erreur:', error.response?.data)
        alert('Erreur lors de la réinitialisation: ' + (error.response?.data?.message || error.message))
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
