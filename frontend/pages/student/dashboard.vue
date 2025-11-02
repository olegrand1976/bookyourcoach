<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Indicateur de chargement -->
    <div v-if="isLoading" class="fixed inset-0 bg-white bg-opacity-75 flex items-center justify-center z-50">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <p class="text-gray-600">Chargement de votre dashboard...</p>
      </div>
    </div>

    <!-- Contenu principal -->
    <div v-if="!isLoading" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header avec navigation -->
      <div class="mb-6 md:mb-8">
        <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
              Tableau de bord Étudiant
            </h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">
              Bienvenue, gérez vos cours et réservations en un seul endroit
            </p>
          </div>
          
          <!-- Boutons desktop -->
          <div class="hidden lg:flex items-center space-x-2 xl:space-x-4">
            <button @click="navigateTo('/student/lessons')" class="btn-lessons">
              <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
              <span class="hidden xl:inline">Cours</span>
            </button>
            
            <button @click="navigateTo('/student/bookings')" class="btn-bookings">
              <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="hidden xl:inline">Réservations</span>
            </button>
            
            <button @click="navigateTo('/student/schedule')" class="btn-schedule">
              <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span class="hidden xl:inline">Planning</span>
            </button>
            
            <button @click="navigateTo('/student/preferences')" class="btn-preferences">
              <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span class="hidden xl:inline">Préférences</span>
            </button>
          </div>

          <!-- Boutons mobile/tablette -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 lg:hidden">
            <button @click="navigateTo('/student/lessons')" class="btn-lessons text-xs sm:text-sm">
              <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
              <span>Cours</span>
            </button>
            
            <button @click="navigateTo('/student/bookings')" class="btn-bookings text-xs sm:text-sm">
              <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>Réservations</span>
            </button>
            
            <button @click="navigateTo('/student/schedule')" class="btn-schedule text-xs sm:text-sm">
              <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span>Planning</span>
            </button>
            
            <button @click="navigateTo('/student/preferences')" class="btn-preferences text-xs sm:text-sm">
              <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span class="hidden sm:inline">Préférences</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Stats principales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Cours disponibles -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="p-3 bg-blue-100 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Cours disponibles</p>
                <p class="text-2xl font-semibold text-gray-900">{{ stats.availableLessons || 0 }}</p>
              </div>
            </div>
            <NuxtLink to="/student/lessons" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
              Voir →
            </NuxtLink>
          </div>
        </div>

        <!-- Réservations actives -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="p-3 bg-emerald-100 rounded-lg">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Réservations</p>
                <p class="text-2xl font-semibold text-gray-900">{{ stats.activeBookings || 0 }}</p>
              </div>
            </div>
            <NuxtLink to="/student/bookings" class="text-emerald-600 hover:text-emerald-800 text-sm font-medium">
              Voir →
            </NuxtLink>
          </div>
        </div>

        <!-- Cours terminés -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="p-3 bg-purple-100 rounded-lg">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Cours terminés</p>
                <p class="text-2xl font-semibold text-gray-900">{{ stats.completedLessons || 0 }}</p>
              </div>
            </div>
            <div class="text-sm text-gray-500">
              Total
            </div>
          </div>
        </div>

        <!-- Enseignants favoris -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="p-3 bg-orange-100 rounded-lg">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Favoris</p>
                <p class="text-2xl font-semibold text-gray-900">{{ stats.favoriteTeachers || 0 }}</p>
              </div>
            </div>
            <div class="text-sm text-gray-500">
              Enseignants
            </div>
          </div>
        </div>
      </div>

      <!-- Actions Rapides avec gradients -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <NuxtLink 
          to="/student/lessons" 
          class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl shadow-lg p-6 hover:shadow-xl hover:from-blue-600 hover:to-blue-700 transition-all"
        >
          <div class="flex items-center">
            <div class="p-3 bg-white bg-opacity-20 rounded-lg">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-white text-opacity-90">Cours disponibles</p>
              <p class="text-lg font-semibold text-white">Découvrir →</p>
            </div>
          </div>
        </NuxtLink>

        <NuxtLink 
          to="/student/bookings" 
          class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg p-6 hover:shadow-xl hover:from-emerald-600 hover:to-emerald-700 transition-all"
        >
          <div class="flex items-center">
            <div class="p-3 bg-white bg-opacity-20 rounded-lg">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-white text-opacity-90">Mes réservations</p>
              <p class="text-lg font-semibold text-white">Gérer →</p>
            </div>
          </div>
        </NuxtLink>

        <NuxtLink 
          to="/student/schedule" 
          class="bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-xl shadow-lg p-6 hover:shadow-xl hover:from-orange-600 hover:to-orange-700 transition-all"
        >
          <div class="flex items-center">
            <div class="p-3 bg-white bg-opacity-20 rounded-lg">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-white text-opacity-90">Mon planning</p>
              <p class="text-lg font-semibold text-white">Consulter →</p>
            </div>
          </div>
        </NuxtLink>

        <NuxtLink 
          to="/student/preferences" 
          class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl shadow-lg p-6 hover:shadow-xl hover:from-purple-600 hover:to-purple-700 transition-all"
        >
          <div class="flex items-center">
            <div class="p-3 bg-white bg-opacity-20 rounded-lg">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-white text-opacity-90">Préférences</p>
              <p class="text-lg font-semibold text-white">Modifier →</p>
            </div>
          </div>
        </NuxtLink>
      </div>

      <!-- Mes Abonnements -->
      <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Mes Abonnements</h3>
            <NuxtLink to="/student/subscriptions" class="text-green-600 hover:text-green-800 text-sm font-medium">
              Voir tous →
            </NuxtLink>
          </div>
        </div>
        <div class="p-6">
          <div v-if="loadingSubscriptions" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto"></div>
            <p class="text-gray-600 mt-4 text-sm">Chargement des abonnements...</p>
          </div>
          
          <div v-else-if="activeSubscriptions.length === 0" class="text-center text-gray-500 py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-lg mb-2">Aucun abonnement actif</p>
            <NuxtLink 
              to="/student/subscriptions/subscribe"
              class="mt-4 inline-block bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-green-700 transition-all shadow-sm hover:shadow-md"
            >
              Découvrir les abonnements
            </NuxtLink>
          </div>
          
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="subscription in activeSubscriptions.slice(0, 3)"
              :key="subscription.id"
              class="border-2 rounded-lg p-4 hover:shadow-md transition-shadow"
              :class="getSubscriptionStatusClass(subscription.status)"
            >
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                  <h4 class="font-semibold text-gray-900 text-sm mb-1">
                    {{ subscription.subscription?.template?.model_number || subscription.subscription?.template?.name || 'Abonnement' }}
                  </h4>
                  <p class="text-xs text-gray-600 mb-2" v-if="subscription.subscription?.club?.name">
                    🏢 {{ subscription.subscription.club.name }}
                  </p>
                </div>
                <span :class="getSubscriptionStatusBadgeClass(subscription.status)" 
                      class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ getSubscriptionStatusLabel(subscription.status) }}
                </span>
              </div>
              
              <div class="space-y-2 text-xs">
                <div class="flex justify-between">
                  <span class="text-gray-600">Cours utilisés :</span>
                  <span class="font-medium text-gray-900">
                    {{ subscription.lessons_used || 0 }} / {{ subscription.subscription?.template?.total_lessons || 0 }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Prix :</span>
                  <span class="font-medium text-gray-900">
                    {{ formatPrice(subscription.subscription?.template?.price || 0) }} €
                  </span>
                </div>
                <div v-if="subscription.expires_at" class="flex justify-between">
                  <span class="text-gray-600">Expire le :</span>
                  <span class="font-medium text-gray-900">
                    {{ formatSubscriptionDate(subscription.expires_at) }}
                  </span>
                </div>
              </div>
              
              <div class="mt-3 pt-3 border-t border-gray-200">
                <NuxtLink 
                  :to="`/student/subscriptions`"
                  class="text-green-600 hover:text-green-800 text-xs font-medium"
                >
                  Voir détails →
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Activité récente -->
      <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Activité récente</h3>
            <button @click="loadRecentActivity" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
              Actualiser
            </button>
          </div>
        </div>
        <div class="p-6">
          <div v-if="recentActivity.length === 0" class="text-center text-gray-500 py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <p class="text-lg mb-2">Aucune activité récente</p>
            <button 
              @click="navigateTo('/student/lessons')"
              class="mt-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2 rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all shadow-sm hover:shadow-md"
            >
              Découvrir les cours
            </button>
          </div>
          <div v-else class="space-y-4">
            <div 
              v-for="activity in recentActivity.slice(0, 5)" 
              :key="activity.id"
              class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <div class="flex items-center space-x-3">
                <div class="bg-blue-100 p-2 rounded-lg">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ activity.title }}</p>
                  <p class="text-sm text-gray-600">{{ activity.description }}</p>
                </div>
              </div>
              <span class="text-xs text-gray-400">
                {{ formatRelativeDate(activity.date) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useStudentData } from '~/composables/useStudentData'
import { useStudentFormatters } from '~/composables/useStudentFormatters'

definePageMeta({
  middleware: ['auth', 'student'],
  layout: 'default'
})

// Composables
const { loading: dataLoading, loadStats, loadLessonHistory } = useStudentData()
const { formatRelativeDate } = useStudentFormatters()

// State
const stats = ref({
  availableLessons: 0,
  activeBookings: 0,
  completedLessons: 0,
  favoriteTeachers: 0
})

const recentActivity = ref<any[]>([])
const isLoading = ref(true)
const activeSubscriptions = ref<any[]>([])
const loadingSubscriptions = ref(false)

// Methods
const loadRecentActivity = async () => {
  try {
    const history = await loadLessonHistory(5)
    recentActivity.value = history.map((lesson: any) => ({
      id: lesson.id,
      title: `Cours ${lesson.course_type?.name || 'de cours'}`,
      description: `${lesson.teacher?.user?.name || 'Enseignant'} - ${lesson.location?.name || 'Lieu'}`,
      date: lesson.start_time || lesson.created_at
    }))
  } catch (err) {
    console.error('Error loading recent activity:', err)
  }
}

// Load subscriptions
const loadSubscriptions = async () => {
  try {
    loadingSubscriptions.value = true
    const { $api } = useNuxtApp()
    const response = await $api.get('/student/subscriptions')
    
    if (response.data.success) {
      // Filtrer pour ne garder que les abonnements actifs ou expirés récemment
      activeSubscriptions.value = (response.data.data || []).filter((sub: any) => 
        ['active', 'expired'].includes(sub.status)
      )
    }
  } catch (error) {
    console.error('Error loading subscriptions:', error)
  } finally {
    loadingSubscriptions.value = false
  }
}

// Format price
const formatPrice = (price: number | string): string => {
  const numPrice = typeof price === 'string' ? parseFloat(price) : price
  return isNaN(numPrice) ? '0.00' : numPrice.toFixed(2)
}

// Subscription status helpers
const getSubscriptionStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    'active': '✓ Actif',
    'expired': '⏰ Expiré',
    'cancelled': '✗ Annulé',
    'pending': '⏳ En attente'
  }
  return labels[status] || status
}

const getSubscriptionStatusBadgeClass = (status: string): string => {
  const classes: Record<string, string> = {
    'active': 'bg-green-100 text-green-800',
    'expired': 'bg-yellow-100 text-yellow-800',
    'cancelled': 'bg-red-100 text-red-800',
    'pending': 'bg-blue-100 text-blue-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getSubscriptionStatusClass = (status: string): string => {
  const classes: Record<string, string> = {
    'active': 'border-green-300 bg-green-50',
    'expired': 'border-yellow-300 bg-yellow-50',
    'cancelled': 'border-red-300 bg-red-50',
    'pending': 'border-blue-300 bg-blue-50'
  }
  return classes[status] || 'border-gray-300 bg-gray-50'
}

const formatSubscriptionDate = (dateString: string | null): string => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

// Lifecycle
onMounted(async () => {
  try {
    isLoading.value = true
    const [statsData] = await Promise.all([
      loadStats(),
      loadRecentActivity(),
      loadSubscriptions()
    ])
    stats.value = statsData || stats.value
  } catch (error) {
    console.error('Error loading dashboard data:', error)
  } finally {
    isLoading.value = false
  }
})
</script>

<style scoped>
/* Styles pour les boutons avec classes personnalisées */
.btn-lessons {
  @apply inline-flex items-center px-3 xl:px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-200 shadow-sm hover:shadow-md text-sm;
}

.btn-bookings {
  @apply inline-flex items-center px-3 xl:px-4 py-2 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-lg hover:from-emerald-600 hover:to-emerald-700 transition-all duration-200 shadow-sm hover:shadow-md text-sm;
}

.btn-schedule {
  @apply inline-flex items-center px-3 xl:px-4 py-2 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg hover:from-orange-600 hover:to-orange-700 transition-all duration-200 shadow-sm hover:shadow-md text-sm;
}

.btn-preferences {
  @apply inline-flex items-center px-3 xl:px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700 transition-all duration-200 shadow-sm hover:shadow-md text-sm;
}

.btn-icon {
  @apply w-5 h-5 xl:mr-2;
}
</style>
