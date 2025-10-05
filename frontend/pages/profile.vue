<template>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                    <span class="text-4xl mr-3">👤</span>
                    Mon Profil
                </h1>
                <p class="mt-2 text-gray-600">Gérez vos informations personnelles et préférences</p>
            </div>

            <!-- Profile Form -->
            <div class="bg-white shadow-lg rounded-lg border border-gray-200">
                <form @submit.prevent="updateProfile" class="space-y-6 p-6">
                    <!-- Personal Information -->
                    <div class="border-b border-gray-200 pb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <span class="text-xl mr-2">🏇</span>
                            Informations personnelles
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nom complet</label>
                                <input v-model="form.name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Votre nom complet" required />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input v-model="form.email" type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="votre@email.com" required />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                                <input v-model="form.phone" type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="+33 6 12 34 56 78" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date de naissance</label>
                                <input v-model="form.birth_date" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                        </div>
                    </div>

                    <!-- Role-specific Information -->
                    <div v-if="authStore.isAdmin" class="border-b border-gray-200 pb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <EquestrianIcon icon="trophy" class="mr-2 text-primary-600" :size="20" />
                            Administration
                        </h2>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-blue-800">
                                <strong>Rôle :</strong> Administrateur système
                            </p>
                            <p class="text-blue-700 text-sm mt-1">
                                Accès complet aux fonctionnalités de gestion de la plateforme
                            </p>
                        </div>
                    </div>

                    <div v-if="authStore.isTeacher" class="border-b border-gray-200 pb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <EquestrianIcon icon="saddle" class="mr-2 text-primary-600" :size="20" />
                            Informations d'enseignant
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Spécialités</label>
                                <textarea v-model="form.specialties" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="3"
                                    placeholder="Dressage, saut d'obstacles, équitation western..."></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Expérience (années)</label>
                                <input v-model="form.experience_years" type="number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" min="0"
                                    placeholder="10" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Certifications</label>
                                <textarea v-model="form.certifications" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="2"
                                    placeholder="BPJEPS, Galop 7, FFE..."></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tarif horaire (€)</label>
                                <input v-model="form.hourly_rate" type="number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" min="0" step="5"
                                    placeholder="45" />
                            </div>
                        </div>
                    </div>

                    <div v-if="authStore.isStudent" class="border-b border-gray-200 pb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <EquestrianIcon icon="horseshoe" class="mr-2 text-primary-600" :size="20" />
                            Informations d'élève
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Niveau équestre</label>
                                <select v-model="form.riding_level" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Sélectionnez votre niveau</option>
                                    <option value="debutant">Débutant</option>
                                    <option value="galop1">Galop 1</option>
                                    <option value="galop2">Galop 2</option>
                                    <option value="galop3">Galop 3</option>
                                    <option value="galop4">Galop 4</option>
                                    <option value="galop5">Galop 5</option>
                                    <option value="galop6">Galop 6</option>
                                    <option value="galop7">Galop 7</option>
                                    <option value="expert">Expert</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Préférences de cours</label>
                                <textarea v-model="form.course_preferences" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="2"
                                    placeholder="Dressage, saut, balade..."></textarea>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Contact d'urgence</label>
                                <input v-model="form.emergency_contact" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Nom et téléphone du contact d'urgence" />
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-4 pt-6">
                        <button type="button" @click="resetForm" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" :disabled="loading">
                            Annuler
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent flex items-center" :disabled="loading">
                            <span v-if="loading" class="mr-2">
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4" fill="none" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                            </span>
                            Sauvegarder
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
definePageMeta({
    middleware: 'auth'
})

const authStore = useAuthStore()
const toast = useToast()

const loading = ref(false)

// Form data
const form = reactive({
    name: '',
    email: '',
    phone: '',
    birth_date: '',
    // Teacher specific
    specialties: '',
    experience_years: 0,
    certifications: '',
    hourly_rate: 0,
    // Student specific
    riding_level: '',
    course_preferences: '',
    emergency_contact: ''
})

// Load user profile data
const loadProfileData = async () => {
    console.log('loadProfileData called, user:', authStore.user)

    if (!authStore.user) {
        console.log('No user found in authStore, trying to fetch from API')

        // Essayer de récupérer l'utilisateur depuis l'API si on a un token
        const tokenCookie = useCookie('auth-token')
        if (tokenCookie.value) {
            try {
                const { $api } = useNuxtApp()
                const response = await $api.get('/auth/user')

                // Mettre à jour l'authStore avec les données utilisateur
                authStore.user = response.data
                authStore.isAuthenticated = true

                console.log('User fetched from API:', response.data)
            } catch (error) {
                console.error('Erreur lors de la récupération de l\'utilisateur:', error)
                // Ne pas afficher d'erreur toast ici, juste retourner
                return
            }
        } else {
            return
        }
    }

    // Précharger les données utilisateur de base
    console.log('Preloading user data:', authStore.user.name, authStore.user.email)
    form.name = authStore.user.name || ''
    form.email = authStore.user.email || ''

    // Load additional profile data from API
    try {
        const { $api } = useNuxtApp()
        const response = await $api.get('/profile')

        if (response.data.profile) {
            const profile = response.data.profile
            form.phone = profile.phone || ''

            // Format birth_date for input[type="date"] (YYYY-MM-DD)
            if (profile.date_of_birth) {
                const date = new Date(profile.date_of_birth)
                form.birth_date = date.toISOString().split('T')[0]
            } else {
                form.birth_date = ''
            }

            // Teacher specific data
            if (authStore.isTeacher && response.data.teacher) {
                const teacher = response.data.teacher
                form.specialties = teacher.specialties || ''
                form.experience_years = teacher.experience_years || 0
                form.certifications = teacher.certifications || ''
                form.hourly_rate = teacher.hourly_rate || 0
            }

            // Student specific data
            if (authStore.isStudent && response.data.student) {
                const student = response.data.student
                form.riding_level = student.level || ''
                form.course_preferences = student.course_preferences || ''
                form.emergency_contact = student.emergency_contact || ''
            }
        }
    } catch (error) {
        console.error('Erreur lors du chargement du profil:', error)
        // Ne pas afficher d'erreur toast pour éviter les messages répétitifs
        // toast.error('Erreur lors du chargement du profil')
    }
}

// Charger les données au montage
onMounted(async () => {
    console.log('onMounted - authStore.user:', authStore.user)
    console.log('onMounted - authStore.isAuthenticated:', authStore.isAuthenticated)

    // Rediriger les enseignants vers leur page de profil dédiée
    if (authStore.isTeacher) {
        await navigateTo('/teacher/profile')
        return
    }

    // Toujours essayer de charger les données
    await loadProfileData()
})

// Observer les changements de l'utilisateur pour précharger les données
watch(() => authStore.user, (newUser, oldUser) => {
    console.log('Watch triggered - newUser:', newUser, 'oldUser:', oldUser)
    if (newUser && newUser !== oldUser) {
        loadProfileData()
    }
}, { immediate: true })

const updateProfile = async () => {
    loading.value = true

    try {
        const { $api } = useNuxtApp()
        await $api.put('/profile', form)

        toast.success('Profil mis à jour avec succès')

        // Update auth store with new user data
        authStore.user.name = form.name
        authStore.user.email = form.email

    } catch (error) {
        console.error('Erreur lors de la mise à jour:', error)
        toast.error('Erreur lors de la mise à jour du profil')
    } finally {
        loading.value = false
    }
}

const resetForm = () => {
    // Reset form to initial values
    if (authStore.user) {
        form.name = authStore.user.name || ''
        form.email = authStore.user.email || ''
        // Recharger toutes les données du profil
        loadProfileData()
    }
}
</script>
