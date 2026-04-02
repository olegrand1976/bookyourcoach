<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-6 md:mb-8 flex items-center justify-between">
        <div class="flex-1 min-w-0 mr-4">
          <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
            Dashboard Enseignant
          </h1>
          <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600 break-words">
            Bonjour {{ authStore.userName }}, gérez vos cours et votre planning
          </p>
        </div>
        <NotificationBell />
      </div>

      <!-- Mes Clubs -->
      <div v-if="clubs.length > 0" class="mb-6 md:mb-8">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
          <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-pink-50">
            <h3 class="text-base md:text-lg font-semibold text-gray-900">Mes Clubs</h3>
            <p class="text-xs md:text-sm text-gray-600 mt-1">Sélectionnez un club pour voir vos cours et statistiques</p>
          </div>
          <div class="p-4 md:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
              <button
                @click="selectClub(null)"
                :class="[
                  'text-left min-h-[44px] p-3 md:p-4 rounded-lg border-2 transition-all duration-200',
                  selectedClubId === null 
                    ? 'border-purple-500 bg-purple-50 shadow-md' 
                    : 'border-gray-200 hover:border-purple-300 hover:bg-gray-50'
                ]"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <h4 class="text-sm md:text-base font-semibold text-gray-900">Tous les clubs</h4>
                    <p class="text-xs md:text-sm text-gray-600 mt-1">Voir tous mes cours</p>
                  </div>
                  <div v-if="selectedClubId === null" class="text-purple-500 flex-shrink-0 ml-2">
                    <svg class="w-5 h-5 md:w-6 md:h-6" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
              </button>
              
              <button
                v-for="club in clubs"
                :key="club.id"
                @click="selectClub(club.id)"
                :class="[
                  'text-left min-h-[44px] p-3 md:p-5 rounded-lg border-2 transition-all duration-200',
                  selectedClubId === club.id 
                    ? 'border-purple-500 bg-purple-50 shadow-md' 
                    : 'border-gray-200 hover:border-purple-300 hover:bg-gray-50'
                ]"
              >
                <div class="flex items-start justify-between">
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center">
                      <div class="p-1.5 md:p-2 bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg mr-2 md:mr-3 flex-shrink-0">
                        <svg class="w-4 h-4 md:w-5 md:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                      </div>
                      <h4 class="font-semibold text-gray-900 text-sm md:text-lg break-words">{{ club.name }}</h4>
                    </div>
                    
                    <!-- Contact info -->
                    <div class="mt-2 md:mt-3 ml-7 md:ml-11">
                      <h5 class="text-xs md:text-sm font-semibold text-gray-700 mb-1 md:mb-2">Nom du représentant légal du club</h5>
                      <div v-if="club.legal_representative_name" class="text-xs md:text-sm font-medium text-gray-900 mb-1 md:mb-2">
                        {{ club.legal_representative_name }}
                        <span v-if="club.legal_representative_role" class="text-gray-600">({{ club.legal_representative_role }})</span>
                      </div>
                      <div class="space-y-1 md:space-y-2">
                      <!-- Email -->
                      <div v-if="club.email" class="flex items-center text-xs md:text-sm text-gray-600">
                        <svg class="w-3 h-3 md:w-4 md:h-4 mr-1 md:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <a :href="'mailto:' + club.email" 
                           class="text-blue-600 hover:text-blue-800 hover:underline break-all"
                           @click.stop>
                          {{ club.email }}
                        </a>
                      </div>
                      
                      <!-- Téléphone -->
                      <div v-if="club.phone" class="flex items-center text-xs md:text-sm text-gray-600">
                        <svg class="w-3 h-3 md:w-4 md:h-4 mr-1 md:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        <span>{{ club.phone }}</span>
                      </div>
                      
                      <!-- Adresse -->
                      <div v-if="getClubAddress(club)" class="flex items-start text-xs md:text-sm text-gray-600">
                        <svg class="w-3 h-3 md:w-4 md:h-4 mr-1 md:mr-2 mt-0.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="break-words">{{ getClubAddress(club) }}</span>
                      </div>
                      </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="flex items-center mt-3 ml-11">
                      <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                        Actif
                      </span>
                      <span class="ml-2 text-xs text-gray-500">
                        {{ getClubLessonsCount(club.id) }} cours
                      </span>
                    </div>
                  </div>
                  
                  <!-- Check icon -->
                  <div v-if="selectedClubId === club.id" class="text-purple-500 ml-3">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
              </button>
            </div>
          </div>
        </div>
      </div>


      <!-- Notifications de demandes envoyées -->
      <div v-if="pendingReplacementsSent.length > 0" class="mb-8">
        <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-6">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
            </div>
            <div class="ml-3 flex-1">
              <h3 class="text-lg font-medium text-blue-800">
                📤 {{ pendingReplacementsSent.length }} demande(s) en attente de réponse
              </h3>
              <div class="mt-4 space-y-3">
                <div
                  v-for="replacement in pendingReplacementsSent"
                  :key="replacement.id"
                  class="bg-white rounded-lg p-4 shadow-sm"
                >
                  <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 flex-1">
                      <p class="font-medium text-gray-900">
                        Vous avez demandé à {{ replacement.replacement_teacher?.user?.name }} de vous remplacer
                      </p>
                      <p class="text-sm text-gray-600">
                        📅 {{ formatDate(replacement.lesson?.start_time) }} à {{ formatTime(replacement.lesson?.start_time) }}
                      </p>
                      <p class="text-sm text-gray-600">
                        👤 Élève: {{ replacement.lesson?.student?.user?.name || 'Non assigné' }}
                        <span v-if="replacement.lesson?.student?.age" class="text-gray-500">
                          ({{ replacement.lesson.student.age }} ans)
                        </span>
                      </p>
                      <p class="text-sm text-gray-500">Raison: {{ replacement.reason }}</p>
                    </div>
                    <div class="flex flex-shrink-0 flex-wrap items-center gap-2">
                      <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        ⏳ En attente
                      </span>
                      <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-red-100 px-3 py-2 text-sm font-medium text-red-800 hover:bg-red-200"
                        @click="cancelReplacementRequest(replacement.id)"
                      >
                        Supprimer la demande
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Notifications de remplacement -->
      <div v-if="pendingReplacementsReceived.length > 0" class="mb-8">
        <div class="bg-orange-50 border-l-4 border-orange-500 rounded-lg p-6">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <div class="ml-3 flex-1">
              <h3 class="text-lg font-medium text-orange-800">
                🔔 {{ pendingReplacementsReceived.length }} demande(s) de remplacement à traiter
              </h3>
              <div class="mt-4 space-y-3">
                <div
                  v-for="replacement in pendingReplacementsReceived"
                  :key="replacement.id"
                  class="bg-white rounded-lg p-4 shadow-sm"
                >
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="font-medium text-gray-900">
                        {{ replacement.original_teacher?.user?.name }} demande un remplacement
                      </p>
                      <p class="text-sm text-gray-600">
                        📅 {{ formatDate(replacement.lesson?.start_time) }} à {{ formatTime(replacement.lesson?.start_time) }}
                      </p>
                      <p class="text-sm text-gray-600">
                        👤 Élève: {{ replacement.lesson?.student?.user?.name || 'Non assigné' }}
                        <span v-if="replacement.lesson?.student?.age" class="text-gray-500">
                          ({{ replacement.lesson.student.age }} ans)
                        </span>
                      </p>
                      <p class="text-sm text-gray-500">Raison: {{ replacement.reason }}</p>
                    </div>
                    <div class="flex gap-2">
                      <button
                        @click="respondToReplacement(replacement.id, 'accept')"
                        class="bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-200 text-sm font-medium"
                      >
                        ✓ Accepter
                      </button>
                      <button
                        @click="respondToReplacement(replacement.id, 'reject')"
                        class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-lg hover:from-red-600 hover:to-red-700 transition-all duration-200 text-sm font-medium"
                      >
                        ✗ Refuser
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Cours aujourd'hui</p>
              <p class="text-2xl font-bold text-gray-900">{{ todayLessons.length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Cours filtrés</p>
              <p class="text-2xl font-bold text-gray-900">{{ filteredLessons.length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-start justify-between">
            <div class="flex items-center flex-1">
              <div class="p-2 bg-yellow-100 rounded-lg">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-4 flex-1">
                <p class="text-sm font-medium text-gray-600 mb-2">Revenus du mois</p>
                <div class="space-y-2">
                  <!-- Mois précédent -->
                  <div class="border-l-2 border-gray-200 pl-3">
                    <p class="text-xs text-gray-500 mb-1">Mois précédent</p>
                    <div class="flex items-center gap-3">
                      <span class="text-sm font-medium text-green-600">
                        Payé: {{ formatCurrency(revenues?.previous_month?.paid || 0) }}
                      </span>
                      <span class="text-sm font-medium text-orange-600">
                        Non payé: {{ formatCurrency(revenues?.previous_month?.unpaid || 0) }}
                      </span>
                    </div>
                  </div>
                  <!-- Mois en cours -->
                  <div class="border-l-2 border-blue-500 pl-3">
                    <p class="text-xs text-gray-500 mb-1">Mois en cours</p>
                    <div class="flex items-center gap-3">
                      <span class="text-sm font-medium text-green-600">
                        Payé: {{ formatCurrency(revenues?.current_month?.paid || 0) }}
                      </span>
                      <span class="text-sm font-medium text-orange-600">
                        Non payé: {{ formatCurrency(revenues?.current_month?.unpaid || 0) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-purple-100 rounded-lg">
              <span class="text-2xl">⭐</span>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Clubs</p>
              <p class="text-2xl font-bold text-gray-900">{{ clubs.length }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Sélecteur de calendrier -->
      <div class="mb-8">
        <div class="bg-white rounded-lg shadow p-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Voir le calendrier</label>
          <div class="flex flex-wrap gap-2">
            <NuxtLink 
              to="/teacher/schedule"
              class="min-h-[44px] inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all duration-200 shadow-md hover:shadow-lg"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Mon Calendrier Personnel
            </NuxtLink>
            <NuxtLink 
              v-for="club in clubs"
              :key="club.id"
              :to="`/teacher/schedule?club=${club.id}`"
              class="min-h-[44px] inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-md hover:shadow-lg"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              {{ club.name }}
            </NuxtLink>
          </div>
        </div>
      </div>

      <!-- Mes cours -->
      <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-4 sm:p-6 border-b border-gray-200">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h3 class="text-lg font-medium text-gray-900">
                {{ selectedClubName ? `Mes cours - ${selectedClubName}` : 'Mes cours' }}
              </h3>
              <span class="text-sm text-gray-500">{{ filteredLessons.length }} cours</span>
            </div>
            
            <!-- Filtres de période -->
            <div class="flex flex-wrap gap-2">
              <button
                @click="changePeriod('7days')"
                :class="[
                  'min-h-[44px] px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                  selectedPeriod === '7days'
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                7 jours
              </button>
              <button
                @click="changePeriod('15days')"
                :class="[
                  'min-h-[44px] px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                  selectedPeriod === '15days'
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                15 jours
              </button>
              <button
                @click="changePeriod('previous_month')"
                :class="[
                  'min-h-[44px] px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                  selectedPeriod === 'previous_month'
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                Mois précédent
              </button>
              <button
                @click="changePeriod('current_month')"
                :class="[
                  'min-h-[44px] px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                  selectedPeriod === 'current_month'
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                Mois en cours
              </button>
              <button
                @click="changePeriod('next_month')"
                :class="[
                  'min-h-[44px] px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                  selectedPeriod === 'next_month'
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                Mois à venir
              </button>
              <button
                type="button"
                @click="toggleBulkReplacementMode"
                :class="[
                  'min-h-[44px] px-3 py-2 text-sm font-medium rounded-lg transition-colors border-2',
                  bulkReplacementMode
                    ? 'border-orange-500 bg-orange-50 text-orange-800'
                    : 'border-transparent bg-amber-100 text-amber-900 hover:bg-amber-200'
                ]"
              >
                {{ bulkReplacementMode ? 'Quitter la sélection' : 'Demande groupée' }}
              </button>
            </div>
          </div>
          <div
            v-if="bulkReplacementMode"
            class="px-4 sm:px-6 pb-4 pt-2 border-t border-gray-100 flex flex-col gap-3"
          >
            <p class="text-sm text-gray-600">
              Cochez des cours ou choisissez une plage de dates (un seul remplaçant pour tous). Les cours sans club ne peuvent pas être inclus.
            </p>
            <div class="flex flex-col sm:flex-row flex-wrap gap-3 items-stretch sm:items-end">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Date début</label>
                <input
                  v-model="bulkDateFrom"
                  type="date"
                  class="min-h-[44px] px-3 py-2 border border-gray-300 rounded-lg text-sm w-full sm:w-auto"
                >
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Date fin</label>
                <input
                  v-model="bulkDateTo"
                  type="date"
                  class="min-h-[44px] px-3 py-2 border border-gray-300 rounded-lg text-sm w-full sm:w-auto"
                >
              </div>
              <button
                type="button"
                class="min-h-[44px] px-4 py-2 text-sm font-medium rounded-lg bg-blue-100 text-blue-800 hover:bg-blue-200"
                @click="selectLessonsInDateRange"
              >
                Sélectionner les cours sur la période
              </button>
              <button
                type="button"
                class="min-h-[44px] px-4 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200"
                @click="clearBulkSelection"
              >
                Effacer la sélection
              </button>
            </div>
            <div class="flex flex-wrap items-center gap-3">
              <span v-if="selectedLessonIds.length" class="text-sm font-medium text-gray-800">
                {{ selectedLessonIds.length }} cours sélectionné(s)
              </span>
              <button
                type="button"
                class="min-h-[44px] px-4 py-2 text-sm font-semibold rounded-lg bg-gradient-to-r from-orange-500 to-red-600 text-white disabled:opacity-50 disabled:cursor-not-allowed hover:from-orange-600 hover:to-red-700"
                :disabled="!selectedLessonIds.length"
                @click="showBulkReplacementModal = true"
              >
                Envoyer la demande groupée
              </button>
            </div>
          </div>
        </div>
        <div class="p-4 sm:p-6">
          <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="text-gray-600 mt-4">Chargement...</p>
          </div>

          <div v-else-if="filteredLessons.length > 0" class="overflow-x-auto -mx-4 sm:mx-0">
            <!-- Groupement par jour/date -->
            <div v-for="(dayLessons, dateKey) in groupedLessonsByDate" :key="dateKey" class="mb-6">
              <!-- En-tête du jour -->
              <div class="bg-gray-100 px-4 py-2 mb-2 rounded-t-lg">
                <h4 class="text-sm font-semibold text-gray-700">
                  {{ formatDayHeader(dateKey) }}
                </h4>
              </div>
              
              <!-- Tableau des cours pour ce jour -->
              <table class="min-w-[700px] sm:min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th
                      v-if="bulkReplacementMode"
                      class="px-2 py-3 w-12 text-left text-xs font-medium text-gray-500 uppercase"
                    >
                      Sel.
                    </th>
                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Club</th>
                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Heure</th>
                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type de cours</th>
                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Élève</th>
                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remplacement</th>
                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="lesson in dayLessons" :key="lesson.id" class="hover:bg-gray-50">
                    <td v-if="bulkReplacementMode" class="px-2 py-3 sm:py-4 align-middle">
                      <input
                        v-if="canSelectForBulkReplacement(lesson)"
                        type="checkbox"
                        class="h-5 w-5 rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                        :checked="selectedLessonIds.includes(lesson.id)"
                        @change="toggleLessonSelection(lesson.id)"
                      >
                      <span
                        v-else
                        class="text-xs text-gray-400"
                        title="Non éligible (passé, sans club ou remplacement en cours)"
                      >—</span>
                    </td>
                    <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">
                        {{ lesson.club?.name || 'N/A' }}
                      </div>
                    </td>
                    <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">{{ formatTime(lesson.start_time) }} - {{ formatTime(lesson.end_time) }}</div>
                    </td>
                    <td class="px-4 sm:px-6 py-3 sm:py-4">
                      <div class="text-sm text-gray-900">{{ lesson.course_type?.name || 'N/A' }}</div>
                      <div class="text-xs text-gray-500">{{ calculateDuration(lesson.start_time, lesson.end_time) }}min - {{ lesson.price }}€</div>
                    </td>
                    <td class="px-4 sm:px-6 py-3 sm:py-4">
                      <div class="text-sm font-medium text-gray-900">
                        {{ getLessonStudentNames(lesson) }}
                      </div>
                      <div v-if="getLessonStudentAges(lesson)" class="text-xs text-gray-500">
                        {{ getLessonStudentAges(lesson) }}
                      </div>
                    </td>
                    <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                      <span :class="getStatusClass(lesson.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                        {{ getStatusLabel(lesson.status) }}
                      </span>
                    </td>
                    <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                      <div v-if="getReplacementForLesson(lesson.id)">
                        <div class="mb-1">
                          <!-- Cas 1: On a demandé un remplacement (on est l'enseignant d'origine) -->
                          <div v-if="isOriginalTeacherForReplacement(getReplacementForLesson(lesson.id))" 
                               class="text-xs">
                            <div class="flex items-center gap-1 text-orange-700 font-medium">
                              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                              </svg>
                              <span>Vous avez demandé un remplacement</span>
                            </div>
                            <div class="text-gray-600 mt-0.5">
                              Demandé à: {{ getReplacementForLesson(lesson.id).replacement_teacher?.user?.name || 'N/A' }}
                            </div>
                            <!-- Boutons d'action pour l'enseignant d'origine -->
                            <div v-if="getReplacementForLesson(lesson.id).status === 'pending'" class="mt-2 flex gap-2">
                              <button
                                @click="cancelReplacementRequest(getReplacementForLesson(lesson.id).id)"
                                class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center px-3 py-2 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors"
                              >
                                ✗ Annuler
                              </button>
                              <button
                                @click="modifyReplacementRequest(lesson, getReplacementForLesson(lesson.id))"
                                class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center px-3 py-2 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors"
                              >
                                ✏️ Modifier
                              </button>
                            </div>
                          </div>
                          <!-- Cas 2: Un remplacement nous est demandé (on est le remplaçant) -->
                          <div v-else 
                               class="text-xs">
                            <div class="flex items-center gap-1 text-blue-700 font-medium">
                              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                              </svg>
                              <span>Remplacement demandé</span>
                            </div>
                            <div class="text-gray-600 mt-0.5">
                              Demandé par: {{ getReplacementForLesson(lesson.id).original_teacher?.user?.name || 'N/A' }}
                            </div>
                          </div>
                        </div>
                        <span :class="getReplacementStatusClass(getReplacementForLesson(lesson.id).status)" 
                              class="px-2 py-1 text-xs font-semibold rounded-full mt-1 inline-block">
                          {{ getReplacementStatusLabel(getReplacementForLesson(lesson.id).status) }}
                        </span>
                      </div>
                      <div v-else class="text-xs text-gray-400">
                        Aucun
                      </div>
                    </td>
                    <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm font-medium">
                      <div class="flex flex-wrap gap-2">
                        <button
                          @click="openLessonDetails(lesson)"
                          class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center px-3 py-2 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded-lg transition-colors"
                        >
                          👁️ Voir
                        </button>
                        <button
                          v-if="!bulkReplacementMode && (!getReplacementForLesson(lesson.id) || (getReplacementForLesson(lesson.id) && !isOriginalTeacherForReplacement(getReplacementForLesson(lesson.id)) && !hasPendingReplacementReceived(lesson.id)))"
                          @click="openReplacementRequest(lesson)"
                          class="min-h-[44px] inline-flex items-center justify-center px-3 py-2 text-orange-600 hover:text-orange-900 hover:bg-orange-50 rounded-lg transition-colors"
                        >
                          🔄 Remplacer
                        </button>
                        <button
                          v-if="hasPendingReplacementReceived(lesson.id)"
                          @click="acceptReplacementForLesson(lesson.id)"
                          class="min-h-[44px] px-3 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-200 text-xs font-medium"
                        >
                          ✓ Accepter
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-else class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="text-gray-500">{{ selectedClubName ? `Aucun cours pour ${selectedClubName}` : 'Aucun cours planifié' }}</p>
          </div>
        </div>
      </div>

      <!-- Modales -->
      <LessonDetailsModal
        :show="showDetailsModal"
        :lesson="selectedLesson"
        @close="showDetailsModal = false"
        @request-replacement="openReplacementFromDetails"
      />

      <ReplacementRequestModal
        :show="showReplacementModal"
        :lesson="selectedLesson"
        :available-teachers="availableTeachers"
        @close="showReplacementModal = false"
        @success="handleReplacementSuccess"
      />

      <BulkReplacementRequestModal
        :show="showBulkReplacementModal"
        :lessons="selectedLessonsForBulk"
        :available-teachers="availableTeachers"
        @close="showBulkReplacementModal = false"
        @success="handleBulkReplacementSuccess"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import LessonDetailsModal from '~/components/teacher/LessonDetailsModal.vue'
import ReplacementRequestModal from '~/components/teacher/ReplacementRequestModal.vue'
import BulkReplacementRequestModal from '~/components/teacher/BulkReplacementRequestModal.vue'
import NotificationBell from '~/components/NotificationBell.vue'

definePageMeta({
  middleware: ['auth']
})

const authStore = useAuthStore()
const { $api } = useNuxtApp()

// State
const loading = ref(true)
const lessons = ref<any[]>([])
const clubs = ref<any[]>([])
const allReplacements = ref<any[]>([])
const availableTeachers = ref<any[]>([])
const selectedLesson = ref<any | null>(null)
const showDetailsModal = ref(false)
const showReplacementModal = ref(false)
const selectedClubId = ref<number | null>(null)
const monthlyEarnings = ref(0)
const dashboardStats = ref<any>(null)
const selectedPeriod = ref<string>('7days') // Par défaut: 7 jours à venir
const revenues = ref<any>(null)

const bulkReplacementMode = ref(false)
const selectedLessonIds = ref<number[]>([])
const bulkDateFrom = ref('')
const bulkDateTo = ref('')
const showBulkReplacementModal = ref(false)

// Computed
const todayLessons = computed(() => {
  const today = new Date().toISOString().split('T')[0]
  return filteredLessons.value.filter(lesson => {
    const lessonDate = new Date(lesson.start_time).toISOString().split('T')[0]
    return lessonDate === today
  })
})

const filteredLessons = computed(() => {
  let filtered = lessons.value
  if (selectedClubId.value !== null) {
    filtered = filtered.filter(lesson => lesson.club_id === selectedClubId.value)
  }
  // Trier par date et heure
  return filtered.sort((a, b) => {
    const dateA = new Date(a.start_time).getTime()
    const dateB = new Date(b.start_time).getTime()
    return dateA - dateB
  })
})

// Grouper les cours par jour/date
const groupedLessonsByDate = computed(() => {
  const groups: Record<string, any[]> = {}
  
  filteredLessons.value.forEach(lesson => {
    const date = new Date(lesson.start_time)
    // Utiliser la date au format YYYY-MM-DD comme clé
    const dateKey = date.toISOString().split('T')[0]
    
    if (!groups[dateKey]) {
      groups[dateKey] = []
    }
    groups[dateKey].push(lesson)
  })
  
  // Trier les groupes par date (du plus ancien au plus récent)
  const sortedKeys = Object.keys(groups).sort()
  const sortedGroups: Record<string, any[]> = {}
  sortedKeys.forEach(key => {
    sortedGroups[key] = groups[key]
  })
  
  return sortedGroups
})

const selectedClubName = computed(() => {
  if (selectedClubId.value === null) return null
  const club = clubs.value.find(c => c.id === selectedClubId.value)
  return club?.name || null
})

// Demandes REÇUES (où je suis le remplaçant potentiel) - en attente de MA réponse
const pendingReplacementsReceived = computed(() => {
  const teacherId = authStore.user?.teacher?.id || authStore.user?.id
  return allReplacements.value.filter(r => 
    r.status === 'pending' && 
    r.replacement_teacher_id === teacherId
  )
})

// Demandes ENVOYÉES (où je suis le demandeur) - en attente de réponse
const pendingReplacementsSent = computed(() => {
  const teacherId = authStore.user?.teacher?.id || authStore.user?.id
  return allReplacements.value.filter(r => 
    r.status === 'pending' && 
    r.original_teacher_id === teacherId
  )
})

const uniqueClubs = computed(() => {
  const clubsSet = lessons.value.map(l => l.club?.id).filter(Boolean)
  return [...new Set(clubsSet)]
})

const selectedLessonsForBulk = computed(() => {
  const idSet = new Set(selectedLessonIds.value)
  return filteredLessons.value.filter(l => idSet.has(l.id))
})

// Methods
onMounted(async () => {
  await loadData()
})

async function loadData() {
  loading.value = true
  try {
    // Essayer de charger via le dashboard complet d'abord
    try {
      const dashboardResponse = await $api.get('/teacher/dashboard', {
        params: {
          period: selectedPeriod.value // Passer la période sélectionnée
        }
      })
      if (dashboardResponse.data.success && dashboardResponse.data.data) {
        const data = dashboardResponse.data.data
        dashboardStats.value = data.stats
        monthlyEarnings.value = data.stats?.monthly_earnings || 0
        revenues.value = data.stats?.revenues || null
        clubs.value = data.clubs || []
        
        // Si un seul club, le sélectionner automatiquement
        if (clubs.value.length === 1) {
          selectedClubId.value = clubs.value[0].id
        }
        
        // Utiliser les cours depuis le dashboard qui sont déjà triés selon la période
        if (data.upcoming_lessons && data.recent_lessons) {
          // Combiner et dédupliquer les cours par ID
          const allLessons = [...data.upcoming_lessons, ...data.recent_lessons]
          const uniqueLessons = Array.from(new Map(allLessons.map(lesson => [lesson.id, lesson])).values())
          lessons.value = uniqueLessons
        } else if (data.upcoming_lessons) {
          // Si seulement upcoming_lessons est présent
          lessons.value = data.upcoming_lessons
        } else {
          // Fallback: charger les cours séparément avec limite (par défaut 50, max 100)
          const lessonsResponse = await $api.get('/teacher/lessons', {
            params: {
              limit: 100,
              period: selectedPeriod.value
            }
          })
          lessons.value = lessonsResponse.data.data || lessonsResponse.data || []
        }
        
        
      }
    } catch (dashboardError) {
      // Charger les cours avec limite pour optimiser les performances
      const lessonsResponse = await $api.get('/teacher/lessons', {
        params: {
          limit: 100,
          period: selectedPeriod.value
        }
      })
      lessons.value = lessonsResponse.data.data || lessonsResponse.data || []
      
      // Charger les clubs
      const clubsResponse = await $api.get('/teacher/clubs')
      clubs.value = clubsResponse.data.clubs || []
      
      // Si un seul club, le sélectionner automatiquement
      if (clubs.value.length === 1) {
        selectedClubId.value = clubs.value[0].id
      }
    }

    // Charger les demandes de remplacement et enseignants en parallèle (plus rapide)
    const [replacementsResponse, teachersResponse] = await Promise.all([
      $api.get('/teacher/lesson-replacements').catch(() => ({ data: { data: [] } })),
      $api.get('/teacher/teachers').catch(() => ({ data: { data: [] } }))
    ])
    
    allReplacements.value = replacementsResponse.data.data || []
    availableTeachers.value = teachersResponse.data.data || []
  } catch (error) {
    console.error('❌ Erreur lors du chargement des données:', error)
  } finally {
    loading.value = false
  }
}

function selectClub(clubId: number | null) {
  selectedClubId.value = clubId
}

async function changePeriod(period: string) {
  if (selectedPeriod.value === period) {
    return // Pas de changement
  }
  
  selectedPeriod.value = period
  await loadData() // Recharger les données avec la nouvelle période
}

function getClubLessonsCount(clubId: number): number {
  return lessons.value.filter(l => l.club_id === clubId).length
}

function getClubAddress(club: any): string {
  const parts = []
  if (club.address) {
    parts.push(club.address)
  }
  if (club.postal_code && club.city) {
    parts.push(`${club.postal_code} ${club.city}`)
  } else if (club.city) {
    parts.push(club.city)
  }
  if (club.country && club.country !== 'France' && club.country !== 'Belgique') {
    parts.push(club.country)
  }
  return parts.join(', ')
}

// Fonction pour vérifier si on est l'enseignant d'origine pour ce remplacement
function isOriginalTeacherForReplacement(replacement: any): boolean {
  if (!replacement) return false
  const teacherId = authStore.user?.teacher?.id
  if (!teacherId) return false
  return replacement.original_teacher_id === teacherId
}

function openLessonDetails(lesson: any) {
  selectedLesson.value = lesson
  showDetailsModal.value = true
}

function openReplacementRequest(lesson: any) {
  selectedLesson.value = lesson
  showReplacementModal.value = true
}

function openReplacementFromDetails() {
  showDetailsModal.value = false
  showReplacementModal.value = true
}

async function respondToReplacement(replacementId: number, action: 'accept' | 'reject') {
  try {
    const response = await $api.post(`/teacher/lesson-replacements/${replacementId}/respond`, {
      action
    })

    
    // Recharger les données
    await loadData()
  } catch (error) {
    console.error('❌ Erreur:', error)
    alert('Erreur lors de la réponse à la demande')
  }
}

async function handleReplacementSuccess() {
  const toast = useToast()
  toast.success('Demande envoyée. Le remplaçant et les responsables du club sont notifiés par e-mail.')
  await loadData()
}

function toggleBulkReplacementMode() {
  bulkReplacementMode.value = !bulkReplacementMode.value
  if (!bulkReplacementMode.value) {
    selectedLessonIds.value = []
    showBulkReplacementModal.value = false
  } else {
    initBulkDateRange()
  }
}

function initBulkDateRange() {
  const list = filteredLessons.value.filter(l => canSelectForBulkReplacement(l))
  if (!list.length) {
    const t = new Date()
    bulkDateFrom.value = t.toISOString().split('T')[0]
    bulkDateTo.value = t.toISOString().split('T')[0]
    return
  }
  const times = list.map(l => new Date(l.start_time).getTime())
  const minD = new Date(Math.min(...times))
  const maxD = new Date(Math.max(...times))
  bulkDateFrom.value = minD.toISOString().split('T')[0]
  bulkDateTo.value = maxD.toISOString().split('T')[0]
}

function selectLessonsInDateRange() {
  if (!bulkDateFrom.value || !bulkDateTo.value) {
    return
  }
  const fromT = new Date(bulkDateFrom.value + 'T00:00:00').getTime()
  const toT = new Date(bulkDateTo.value + 'T23:59:59.999').getTime()
  const ids = filteredLessons.value
    .filter(l => canSelectForBulkReplacement(l))
    .filter(l => {
      const t = new Date(l.start_time).getTime()
      return t >= fromT && t <= toT
    })
    .map(l => l.id)
  selectedLessonIds.value = ids
}

function clearBulkSelection() {
  selectedLessonIds.value = []
}

function toggleLessonSelection(lessonId: number) {
  const idx = selectedLessonIds.value.indexOf(lessonId)
  if (idx >= 0) {
    selectedLessonIds.value.splice(idx, 1)
  } else {
    selectedLessonIds.value.push(lessonId)
  }
}

async function handleBulkReplacementSuccess() {
  selectedLessonIds.value = []
  bulkReplacementMode.value = false
  const toast = useToast()
  toast.success('Demandes de remplacement envoyées')
  await loadData()
}

// Fonction pour annuler une demande de remplacement
async function cancelReplacementRequest(replacementId: number) {
  if (!confirm('Êtes-vous sûr de vouloir annuler cette demande de remplacement ?')) {
    return
  }
  
  try {
    const response = await $api.delete(`/teacher/lesson-replacements/${replacementId}`)
    
    if (response.data.success) {
      const toast = useToast()
      toast.success('Demande de remplacement annulée avec succès')
      await loadData()
    } else {
      alert('❌ ' + (response.data.message || 'Erreur lors de l\'annulation'))
    }
  } catch (error: any) {
    console.error('❌ Erreur lors de l\'annulation:', error)
    const errorMessage = error.response?.data?.message || 'Erreur lors de l\'annulation de la demande'
    alert('❌ ' + errorMessage)
  }
}

// Fonction pour modifier une demande de remplacement
function modifyReplacementRequest(lesson: any, replacement: any) {
  // Fermer la modale de détails si elle est ouverte
  showDetailsModal.value = false
  
  // Pré-remplir le formulaire avec les données actuelles
  selectedLesson.value = lesson
  
  // Pré-sélectionner le remplaçant actuel et la raison
  // On ouvrira la modale avec ces valeurs pré-remplies
  // Pour modifier, on devra d'abord annuler l'ancienne demande, puis créer une nouvelle
  if (confirm('Pour modifier la demande, vous devrez d\'abord annuler l\'ancienne demande, puis en créer une nouvelle. Continuer ?')) {
    // Annuler l'ancienne demande
    cancelReplacementRequest(replacement.id).then(() => {
      // Attendre un peu pour que l'annulation soit traitée
      setTimeout(() => {
        // Ouvrir la modale de création avec le cours pré-sélectionné
        openReplacementRequest(lesson)
      }, 500)
    })
  }
}

// Fonction pour obtenir les noms des élèves d'un cours
function getLessonStudentNames(lesson: any): string {
  // Vérifier d'abord la relation many-to-many (students)
  if (lesson.students && Array.isArray(lesson.students) && lesson.students.length > 0) {
    const names = lesson.students
      .map((s: any) => {
        // Essayer plusieurs chemins pour obtenir le nom
        return s.user?.name || s.name || (s.user && typeof s.user === 'string' ? s.user : null) || null
      })
      .filter((name: string) => name !== null && name !== undefined && name !== '' && name !== 'Sans nom')
    
    if (names.length > 0) {
      return names.join(', ')
    }
  }
  
  // Sinon, vérifier la relation one-to-many (student)
  if (lesson.student) {
    const studentName = lesson.student.user?.name || lesson.student.name || null
    if (studentName && studentName !== '' && studentName !== 'Sans nom') {
      return studentName
    }
  }
  
  return 'Sans élève'
}

// Fonction pour obtenir les âges des élèves d'un cours
function getLessonStudentAges(lesson: any): string {
  const ages: string[] = []
  
  // Vérifier d'abord la relation many-to-many (students)
  if (lesson.students && Array.isArray(lesson.students) && lesson.students.length > 0) {
    lesson.students.forEach((s: any) => {
      if (s.age) {
        ages.push(`${s.age} ans`)
      }
    })
  }
  
  // Sinon, vérifier la relation one-to-many (student)
  if (ages.length === 0 && lesson.student?.age) {
    ages.push(`${lesson.student.age} ans`)
  }
  
  return ages.length > 0 ? ages.join(', ') : ''
}

function formatDate(datetime: string): string {
  if (!datetime) return ''
  const date = new Date(datetime)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

// Formater l'en-tête du jour (ex: "Lundi 15 janvier 2024")
function formatDayHeader(dateKey: string): string {
  if (!dateKey) return ''
  const date = new Date(dateKey + 'T00:00:00')
  return date.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

function formatTime(datetime: string): string {
  if (!datetime) return ''
  const date = new Date(datetime)
  return date.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    'confirmed': '✓ Confirmé',
    'pending': '⏳ Attente',
    'cancelled': '✗ Annulé',
    'completed': '✓ Terminé'
  }
  return labels[status] || status
}

function getStatusClass(status: string): string {
  const classes: Record<string, string> = {
    'confirmed': 'bg-green-100 text-green-800',
    'pending': 'bg-yellow-100 text-yellow-800',
    'cancelled': 'bg-red-100 text-red-800',
    'completed': 'bg-blue-100 text-blue-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function calculateDuration(startTime: string, endTime: string): number {
  if (!startTime || !endTime) return 0
  const start = new Date(startTime)
  const end = new Date(endTime)
  const diffMs = end.getTime() - start.getTime()
  return Math.round(diffMs / (1000 * 60)) // Retourner la durée en minutes
}

// Fonction pour obtenir le remplacement d'un cours
function getReplacementForLesson(lessonId: number) {
  return allReplacements.value.find(r => r.lesson_id === lessonId)
}

function canSelectForBulkReplacement(lesson: any): boolean {
  const clubId = lesson.club_id ?? lesson.club?.id
  if (clubId == null) {
    return false
  }
  const startMs = lesson.start_time ? new Date(lesson.start_time).getTime() : 0
  if (startMs < Date.now() - 60 * 1000) {
    return false
  }
  if (hasPendingReplacementReceived(lesson.id)) {
    return false
  }
  const r = getReplacementForLesson(lesson.id)
  if (r && r.status === 'pending' && isOriginalTeacherForReplacement(r)) {
    return false
  }
  return true
}

// Fonction pour vérifier si on a reçu une demande de remplacement en attente pour ce cours
function hasPendingReplacementReceived(lessonId: number): boolean {
  const teacherId = authStore.user?.teacher?.id || authStore.user?.id
  const replacement = allReplacements.value.find(r => 
    r.lesson_id === lessonId && 
    r.status === 'pending' && 
    r.replacement_teacher_id === teacherId
  )
  return !!replacement
}

// Fonction pour accepter un remplacement directement depuis la table
async function acceptReplacementForLesson(lessonId: number) {
  const teacherId = authStore.user?.teacher?.id || authStore.user?.id
  const replacement = allReplacements.value.find(r => 
    r.lesson_id === lessonId && 
    r.status === 'pending' && 
    r.replacement_teacher_id === teacherId
  )
  
  if (replacement) {
    await respondToReplacement(replacement.id, 'accept')
  }
}

function getReplacementStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    'pending': '⏳ En attente',
    'accepted': '✓ Accepté',
    'rejected': '✗ Refusé',
    'cancelled': '✗ Annulé'
  }
  return labels[status] || status
}

function getReplacementStatusClass(status: string): string {
  const classes: Record<string, string> = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'accepted': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800',
    'cancelled': 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function formatCurrency(amount: number): string {
  if (!amount && amount !== 0) return '0,00 €'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}
</script>
