<template>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">
                    Dashboard Enseignant
                </h1>
                <p class="mt-2 text-gray-600">
                    Bonjour {{ authStore.userName }}, gérez vos cours et votre planning
                </p>
            </div>

            <!-- Stats cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <EquestrianIcon name="helmet" :size="24" class="text-blue-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Cours aujourd'hui</p>
                            <p class="text-2xl font-bold text-gray-900">{{ stats.today_lessons }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <EquestrianIcon name="trophy" :size="24" class="text-green-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Élèves actifs</p>
                            <p class="text-2xl font-bold text-gray-900">{{ stats.active_students }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <EquestrianIcon name="saddle" :size="24" class="text-yellow-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Revenus ce mois</p>
                            <p class="text-2xl font-bold text-gray-900">{{ stats.monthly_earnings }}€</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <span class="text-2xl">⭐</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Note moyenne</p>
                            <p class="text-2xl font-bold text-gray-900">{{ stats.average_rating }}/5</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Prochains cours -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Prochains cours</h3>
                    </div>
                    <div class="p-6">
                        <div v-if="upcomingLessons.length > 0" class="space-y-4">
                            <div v-for="lesson in upcomingLessons" :key="lesson.id" 
                                 class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900">{{ lesson.student_name }}</p>
                                    <p class="text-sm text-gray-600">{{ lesson.type }} - {{ lesson.duration }}min</p>
                                    <p class="text-sm text-gray-500">{{ formatDate(lesson.scheduled_at) }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full" 
                                          :class="getStatusClass(lesson.status)">
                                        {{ lesson.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <EquestrianIcon name="helmet" :size="48" class="mx-auto text-gray-400 mb-4" />
                            <p class="text-gray-500">Aucun cours planifié</p>
                        </div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Actions rapides</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <NuxtLink to="/teacher/schedule" 
                                  class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <EquestrianIcon name="helmet" :size="20" class="text-blue-600 mr-3" />
                            <div>
                                <p class="font-medium text-gray-900">Gérer mon planning</p>
                                <p class="text-sm text-gray-600">Définir mes disponibilités</p>
                            </div>
                        </NuxtLink>

                        <NuxtLink to="/teacher/students" 
                                  class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                            <EquestrianIcon name="trophy" :size="20" class="text-green-600 mr-3" />
                            <div>
                                <p class="font-medium text-gray-900">Mes élèves</p>
                                <p class="text-sm text-gray-600">Suivi et progression</p>
                            </div>
                        </NuxtLink>

                        <NuxtLink to="/teacher/earnings" 
                                  class="flex items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors">
                            <span class="text-xl mr-3">💰</span>
                            <div>
                                <p class="font-medium text-gray-900">Mes revenus</p>
                                <p class="text-sm text-gray-600">Paiements et statistiques</p>
                            </div>
                        </NuxtLink>

                        <NuxtLink to="/profile" 
                                  class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                            <span class="text-xl mr-3">👤</span>
                            <div>
                                <p class="font-medium text-gray-900">Mon profil</p>
                                <p class="text-sm text-gray-600">Informations personnelles</p>
                            </div>
                        </NuxtLink>
                    </div>
                </div>
            </div>

            <!-- Statistiques détaillées -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Aperçu de la semaine</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-blue-600">{{ stats.week_lessons }}</p>
                            <p class="text-sm text-gray-600">Cours cette semaine</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-600">{{ stats.week_hours }}</p>
                            <p class="text-sm text-gray-600">Heures enseignées</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-600">{{ stats.week_earnings }}€</p>
                            <p class="text-sm text-gray-600">Revenus de la semaine</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-purple-600">{{ stats.new_students }}</p>
                            <p class="text-sm text-gray-600">Nouveaux élèves</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
definePageMeta({
    middleware: ['auth', 'teacher']
})

const authStore = useAuthStore()

// État réactif
const stats = ref({
    today_lessons: 3,
    active_students: 12,
    monthly_earnings: 1450,
    average_rating: 4.8,
    week_lessons: 15,
    week_hours: 22,
    week_earnings: 1100,
    new_students: 2
})

const upcomingLessons = ref([
    {
        id: 1,
        student_name: "Claire Martin",
        type: "Dressage",
        duration: 60,
        scheduled_at: "2025-08-24T10:00:00Z",
        status: "confirmé"
    },
    {
        id: 2,
        student_name: "Thomas Dubois",
        type: "Saut d'obstacles",
        duration: 45,
        scheduled_at: "2025-08-24T14:30:00Z",
        status: "en attente"
    },
    {
        id: 3,
        student_name: "Sophie Laurent",
        type: "Cours débutant",
        duration: 60,
        scheduled_at: "2025-08-24T16:00:00Z",
        status: "confirmé"
    }
])

// Méthodes
const formatDate = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStatusClass = (status) => {
    switch (status) {
        case 'confirmé':
            return 'bg-green-100 text-green-800'
        case 'en attente':
            return 'bg-yellow-100 text-yellow-800'
        case 'annulé':
            return 'bg-red-100 text-red-800'
        default:
            return 'bg-gray-100 text-gray-800'
    }
}

// Chargement des données
const loadTeacherDashboard = async () => {
    try {
        // TODO: Implémenter l'appel API pour charger les données du dashboard enseignant
        // const { $api } = useNuxtApp()
        // const response = await $api.get('/teacher/dashboard')
        // if (response.data) {
        //     stats.value = response.data.stats
        //     upcomingLessons.value = response.data.upcoming_lessons
        // }
    } catch (error) {
        console.error('Erreur lors du chargement du dashboard enseignant:', error)
    }
}

// Initialisation
onMounted(() => {
    loadTeacherDashboard()
})
</script>
