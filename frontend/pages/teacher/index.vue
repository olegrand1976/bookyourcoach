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

      <!-- Navigation principale avec gradients du Design System -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Dashboard - Bleu/Indigo (Enseignant) -->
        <NuxtLink to="/teacher/dashboard" 
          class="group bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-transparent hover:border-blue-300 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
          <div class="flex items-center mb-4">
            <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-md">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-gray-900">Dashboard</h3>
              <p class="text-sm text-gray-600">Vue d'ensemble</p>
            </div>
          </div>
          <p class="text-gray-700">Consultez vos statistiques, prochains cours et revenus</p>
        </NuxtLink>

        <!-- Planning - Orange/Rouge (Planning) -->
        <NuxtLink to="/teacher/schedule" 
          class="group bg-gradient-to-br from-orange-50 to-red-50 border-2 border-transparent hover:border-orange-300 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
          <div class="flex items-center mb-4">
            <div class="p-3 bg-gradient-to-r from-orange-500 to-red-600 rounded-lg shadow-md">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-gray-900">Mon Planning</h3>
              <p class="text-sm text-gray-600">Calendrier</p>
            </div>
          </div>
          <p class="text-gray-700">Gérez vos cours et disponibilités avec Google Calendar</p>
        </NuxtLink>

        <!-- Élèves - Vert/Teal (Élèves) -->
        <NuxtLink to="/teacher/students" 
          class="group bg-gradient-to-br from-emerald-50 to-teal-50 border-2 border-transparent hover:border-emerald-300 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
          <div class="flex items-center mb-4">
            <div class="p-3 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-lg shadow-md">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-gray-900">Mes Élèves</h3>
              <p class="text-sm text-gray-600">Suivi</p>
            </div>
          </div>
          <p class="text-gray-700">Gérez vos élèves et suivez leur progression</p>
        </NuxtLink>

        <!-- Revenus - Violet/Rose (Premium/Analyse) -->
        <NuxtLink to="/teacher/earnings" 
          class="group bg-gradient-to-br from-purple-50 to-pink-50 border-2 border-transparent hover:border-purple-300 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
          <div class="flex items-center mb-4">
            <div class="p-3 bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg shadow-md">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-gray-900">Mes Revenus</h3>
              <p class="text-sm text-gray-600">Paiements</p>
            </div>
          </div>
          <p class="text-gray-700">Consultez vos revenus et statistiques financières</p>
        </NuxtLink>

        <!-- QR Code - Violet/Rose (Premium) -->
        <NuxtLink to="/teacher/qr-code" 
          class="group bg-gradient-to-br from-purple-50 to-pink-50 border-2 border-transparent hover:border-purple-300 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
          <div class="flex items-center mb-4">
            <div class="p-3 bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg shadow-md">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-gray-900">QR Code</h3>
              <p class="text-sm text-gray-600">Affiliation clubs</p>
            </div>
          </div>
          <p class="text-gray-700">Scannez pour rejoindre un club facilement</p>
        </NuxtLink>

        <!-- Profil - Bleu/Indigo (Enseignant) -->
        <NuxtLink to="/teacher/profile" 
          class="group bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-transparent hover:border-blue-300 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
          <div class="flex items-center mb-4">
            <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-md">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-gray-900">Mon Profil</h3>
              <p class="text-sm text-gray-600">Informations</p>
            </div>
          </div>
          <p class="text-gray-700">Gérez vos informations personnelles et professionnelles</p>
        </NuxtLink>
      </div>

      <!-- Aperçu rapide -->
      <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-semibold text-gray-900">Aperçu rapide</h3>
          <NuxtLink to="/teacher/dashboard" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
            Voir le dashboard complet →
          </NuxtLink>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-6 text-center">
            <div class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
              {{ quickStats.todayLessons }}
            </div>
            <p class="text-sm font-medium text-gray-700 mt-2">Cours aujourd'hui</p>
          </div>
          <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-lg p-6 text-center">
            <div class="text-4xl font-bold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">
              {{ quickStats.totalStudents }}
            </div>
            <p class="text-sm font-medium text-gray-700 mt-2">Élèves actifs</p>
          </div>
          <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-6 text-center">
            <div class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
              {{ quickStats.weeklyEarnings }}€
            </div>
            <p class="text-sm font-medium text-gray-700 mt-2">Revenus cette semaine</p>
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
