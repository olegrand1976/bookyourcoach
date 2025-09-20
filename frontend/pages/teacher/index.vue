<template>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">
                    Espace Enseignant
                </h1>
                <p class="mt-2 text-gray-600">
                    Bienvenue dans votre espace de travail, {{ authStore.userName }}
                </p>
            </div>

            <!-- Navigation principale -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Dashboard -->
                <NuxtLink to="/teacher/dashboard" 
                    class="group bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center mb-4">
                        <div class="p-3 bg-blue-100 rounded-lg group-hover:bg-blue-200 transition-colors">
                            <EquestrianIcon name="helmet" :size="24" class="text-blue-600" />
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">Dashboard</h3>
                            <p class="text-sm text-gray-600">Vue d'ensemble de votre activité</p>
                        </div>
                    </div>
                    <p class="text-gray-700">Consultez vos statistiques, prochains cours et revenus</p>
                </NuxtLink>

                <!-- Planning -->
                <NuxtLink to="/teacher/schedule" 
                    class="group bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center mb-4">
                        <div class="p-3 bg-green-100 rounded-lg group-hover:bg-green-200 transition-colors">
                            <span class="text-2xl">📅</span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">Mon Planning</h3>
                            <p class="text-sm text-gray-600">Gérez vos cours et disponibilités</p>
                        </div>
                    </div>
                    <p class="text-gray-700">Calendrier interactif avec intégration Google Calendar</p>
                </NuxtLink>

                <!-- Élèves -->
                <NuxtLink to="/teacher/students" 
                    class="group bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center mb-4">
                        <div class="p-3 bg-purple-100 rounded-lg group-hover:bg-purple-200 transition-colors">
                            <EquestrianIcon name="trophy" :size="24" class="text-purple-600" />
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">Mes Élèves</h3>
                            <p class="text-sm text-gray-600">Suivi et progression</p>
                        </div>
                    </div>
                    <p class="text-gray-700">Gérez vos élèves et suivez leur progression</p>
                </NuxtLink>

                <!-- Revenus -->
                <NuxtLink to="/teacher/earnings" 
                    class="group bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center mb-4">
                        <div class="p-3 bg-yellow-100 rounded-lg group-hover:bg-yellow-200 transition-colors">
                            <span class="text-2xl">💰</span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">Mes Revenus</h3>
                            <p class="text-sm text-gray-600">Paiements et statistiques</p>
                        </div>
                    </div>
                    <p class="text-gray-700">Consultez vos revenus et statistiques financières</p>
                </NuxtLink>

                <!-- Profil -->
                <NuxtLink to="/teacher/profile" 
                    class="group bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center mb-4">
                        <div class="p-3 bg-indigo-100 rounded-lg group-hover:bg-indigo-200 transition-colors">
                            <span class="text-2xl">👤</span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">Mon Profil</h3>
                            <p class="text-sm text-gray-600">Informations personnelles</p>
                        </div>
                    </div>
                    <p class="text-gray-700">Gérez vos informations personnelles et professionnelles</p>
                </NuxtLink>

                <!-- Paramètres -->
                <NuxtLink to="/teacher/settings" 
                    class="group bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center mb-4">
                        <div class="p-3 bg-gray-100 rounded-lg group-hover:bg-gray-200 transition-colors">
                            <span class="text-2xl">⚙️</span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">Paramètres</h3>
                            <p class="text-sm text-gray-600">Configuration et préférences</p>
                        </div>
                    </div>
                    <p class="text-gray-700">Configurez vos préférences et paramètres</p>
                </NuxtLink>
            </div>

            <!-- Aperçu rapide -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Aperçu rapide</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600">{{ quickStats.todayLessons }}</div>
                        <p class="text-sm text-gray-600">Cours aujourd'hui</p>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600">{{ quickStats.totalStudents }}</div>
                        <p class="text-sm text-gray-600">Élèves actifs</p>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-yellow-600">{{ quickStats.weeklyEarnings }}€</div>
                        <p class="text-sm text-gray-600">Revenus cette semaine</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

definePageMeta({
    middleware: ['auth']
})

const authStore = useAuthStore()
const { $api } = useNuxtApp()

// Vérifier que l'utilisateur peut agir comme enseignant
if (!authStore.canActAsTeacher) {
    throw createError({
        statusCode: 403,
        statusMessage: 'Accès refusé - Droits enseignant requis'
    })
}

// État réactif
const quickStats = ref({
    todayLessons: 0,
    totalStudents: 0,
    weeklyEarnings: 0
})

// Charger les statistiques rapides
onMounted(async () => {
    try {
        const response = await $api.get('/teacher/dashboard-simple')
        if (response.data.stats) {
            quickStats.value = {
                todayLessons: response.data.stats.today_lessons || 0,
                totalStudents: response.data.stats.active_students || 0,
                weeklyEarnings: response.data.stats.week_earnings || 0
            }
        }
    } catch (error) {
        console.error('Erreur lors du chargement des statistiques rapides:', error)
    }
})
</script>
