<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Indicateur de chargement -->
    <div v-if="isLoading" class="fixed inset-0 bg-white bg-opacity-75 flex items-center justify-center z-50">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <p class="text-gray-600">Chargement des données du dashboard...</p>
      </div>
    </div>

    <!-- Message d'erreur -->
    <div v-if="hasError && !isLoading" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="bg-red-50 border border-red-200 rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Erreur de chargement</h3>
            <div class="mt-2 text-sm text-red-700">
              <p>{{ errorMessage }}</p>
            </div>
            <div class="mt-4">
              <button 
                @click="loadDashboardData"
                class="bg-cyan-100 text-cyan-800 px-4 py-2 rounded-md text-sm font-medium hover:bg-cyan-200 transition-colors"
              >
                Réessayer
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contenu principal -->
    <div v-if="!hasError && !isLoading" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header avec navigation -->
      <div class="mb-6 md:mb-8">
        <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
              Tableau de bord Club
            </h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">
              Bienvenue {{ club?.name }}, gérez votre club en un seul endroit
            </p>
          </div>
          
          <!-- Boutons desktop -->
          <div class="hidden lg:flex items-center space-x-2 xl:space-x-4">
            <ClubNotificationBell />
            
            <button @click="navigateTo('/club/qr-code')" class="btn-qr-code">
              <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
              </svg>
              <span class="hidden xl:inline">QR Code</span>
            </button>
            
            <button @click="navigateTo('/club/planning')" class="btn-planning">
              <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span class="hidden xl:inline">Planning</span>
            </button>
            
            <button @click="navigateTo('/club/teachers/add')" class="btn-teacher">
              <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <span class="hidden xl:inline">Enseignant</span>
            </button>
            
            <button @click="navigateTo('/club/students/add')" class="btn-student">
              <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <span class="hidden xl:inline">Élève</span>
            </button>
            
            <button @click="navigateTo('/club/volunteer-letter')" class="inline-flex items-center px-3 xl:px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700 transition-all duration-200 shadow-sm hover:shadow-md text-sm">
              <svg class="w-5 h-5 xl:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span class="hidden xl:inline">Lettres</span>
            </button>
          </div>

          <!-- Boutons mobile/tablette -->
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 lg:hidden">
            <button @click="navigateTo('/club/qr-code')" class="btn-qr-code text-xs sm:text-sm">
              <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
              </svg>
              <span>QR Code</span>
            </button>
            
            <button @click="navigateTo('/club/planning')" class="btn-planning text-xs sm:text-sm">
              <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span>Planning</span>
            </button>
            
            <button @click="navigateTo('/club/teachers/add')" class="btn-teacher text-xs sm:text-sm">
              <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <span>Enseignant</span>
            </button>
            
            <button @click="navigateTo('/club/students/add')" class="btn-student text-xs sm:text-sm">
              <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <span>Élève</span>
            </button>
            
            <button @click="navigateTo('/club/volunteer-letter')" class="inline-flex items-center justify-center px-2 sm:px-3 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700 transition-all duration-200 shadow-sm hover:shadow-md text-xs sm:text-sm col-span-2 sm:col-span-1">
              <svg class="w-4 h-4 sm:w-5 sm:h-5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span class="hidden sm:inline">Lettres</span>
              <span class="sm:hidden ml-2">Lettres</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Lien vers les plafonds légaux -->
      <div class="mb-6 md:mb-8">
        <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-4 md:p-6">
          <div class="flex flex-col sm:flex-row sm:items-start space-y-3 sm:space-y-0 sm:space-x-3">
            <div class="flex-shrink-0">
              <svg class="w-5 h-5 md:w-6 md:h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="text-sm md:text-base font-medium text-blue-800">Plafonds légaux de défraiement des volontaires</h3>
              <p class="mt-1 md:mt-2 text-xs md:text-sm text-blue-700">
                Consultez les montants officiels indexés pour le défraiement des volontaires en Belgique ({{ new Date().getFullYear() }}).
              </p>
              <div class="mt-3 md:mt-4">
                <a 
                  href="https://conseilsuperieurvolontaires.belgium.be/fr/defraiements/plafonds-limites-indexes.htm" 
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex items-center px-3 md:px-4 py-2 bg-blue-600 text-white text-xs md:text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm hover:shadow-md"
                >
                  <svg class="w-3 h-3 md:w-4 md:h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
                  <span>Voir les plafonds officiels</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats principales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Enseignants -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="p-3 bg-blue-100 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Enseignants</p>
                <p class="text-2xl font-semibold text-gray-900">{{ stats?.total_teachers || 0 }}</p>
              </div>
            </div>
            <NuxtLink to="/club/teachers" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
              Voir tout →
            </NuxtLink>
          </div>
        </div>

        <!-- Élèves -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="p-3 bg-emerald-100 rounded-lg">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Élèves</p>
                <p class="text-2xl font-semibold text-gray-900">{{ stats?.total_students || 0 }}</p>
              </div>
            </div>
            <NuxtLink to="/club/students" class="text-emerald-600 hover:text-emerald-800 text-sm font-medium">
              Voir tout →
            </NuxtLink>
          </div>
        </div>

        <!-- Cours totaux -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="p-3 bg-purple-100 rounded-lg">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Cours totaux</p>
                <p class="text-2xl font-semibold text-gray-900">{{ stats?.total_lessons || 0 }}</p>
              </div>
            </div>
            <div class="text-sm text-gray-500">
              {{ stats?.completed_lessons || 0 }} terminés
            </div>
          </div>
        </div>

        <!-- Revenus -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="p-3 bg-yellow-100 rounded-lg">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Revenus totaux</p>
                <p class="text-2xl font-semibold text-gray-900">{{ stats?.total_revenue || 0 }}€</p>
              </div>
            </div>
            <div class="text-sm text-gray-500">
              {{ stats?.monthly_revenue || 0 }}€ ce mois
            </div>
          </div>
        </div>
      </div>

      <!-- Élèves avec données incomplètes -->
      <div v-if="incompleteStudents && incompleteStudents.length > 0" class="mb-8">
        <div class="bg-amber-50 border-l-4 border-amber-500 rounded-lg p-6">
          <div class="flex items-start justify-between">
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
              <div class="ml-3 flex-1">
                <h3 class="text-lg font-semibold text-amber-800">
                  Élèves avec données incomplètes ({{ incompleteStudents.length }})
                </h3>
                <p class="mt-2 text-sm text-amber-700">
                  Certains élèves n'ont pas de nom ou d'email. Complétez ces informations pour leur permettre de se connecter.
                </p>
                <div class="mt-4 space-y-3">
                  <div 
                    v-for="student in incompleteStudents.slice(0, 5)" 
                    :key="student.student_id"
                    class="bg-white rounded-lg p-4 border border-amber-200"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex-1">
                        <div class="flex items-center space-x-2">
                          <span class="font-medium text-gray-900">
                            {{ getStudentDisplayName(student) }}
                          </span>
                          <span 
                            v-for="field in student.missing_fields" 
                            :key="field"
                            class="px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800"
                          >
                            {{ getMissingFieldLabel(field) }}
                          </span>
                        </div>
                        <p v-if="student.email" class="text-sm text-gray-600 mt-1">
                          {{ student.email }}
                        </p>
                      </div>
                      <button 
                        @click="openCompleteStudentModal(student)"
                        class="ml-4 bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors"
                      >
                        Compléter
                      </button>
                    </div>
                  </div>
                  <button 
                    v-if="incompleteStudents.length > 5"
                    @click="navigateTo('/club/students?incomplete=true')"
                    class="w-full text-center text-amber-700 hover:text-amber-800 text-sm font-medium py-2"
                  >
                    Voir tous les élèves incomplets ({{ incompleteStudents.length }}) →
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Analyse Prédictive IA -->
      <div class="mb-8">
        <PredictiveAnalysis />
      </div>

      <!-- Métriques avancées -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Taux d'occupation -->
        <div class="bg-white rounded-xl shadow-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Taux d'occupation</h3>
            <div class="p-2 bg-indigo-100 rounded-lg">
              <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
          </div>
          <div class="text-3xl font-bold text-indigo-600 mb-2">
            {{ stats?.occupancy_rate || 0 }}%
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div 
              class="bg-indigo-600 h-2 rounded-full transition-all duration-300" 
              :style="{ width: `${stats?.occupancy_rate || 0}%` }"
            ></div>
          </div>
          <p class="text-sm text-gray-600 mt-2">
            Cours occupés sur le total
          </p>
        </div>

        <!-- Prix moyen -->
        <div class="bg-white rounded-xl shadow-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Prix moyen</h3>
            <div class="p-2 bg-green-100 rounded-lg">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
          </div>
          <div class="text-3xl font-bold text-green-600 mb-2">
            {{ stats?.average_lesson_price || 0 }}€
          </div>
          <p class="text-sm text-gray-600">
            Par cours
          </p>
        </div>

      </div>

      <!-- Sections récentes -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Enseignants récents -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">Enseignants récents</h3>
              <NuxtLink to="/club/teachers" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Voir tout →
              </NuxtLink>
            </div>
          </div>
          <div class="p-6">
            <div v-if="recentTeachers?.length === 0" class="text-center text-gray-500 py-8">
              <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
              </svg>
              <p>Aucun enseignant pour le moment</p>
              <button 
                @click="navigateTo('/club/teachers/add')"
                class="mt-4 bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition-colors"
              >
                Ajouter le premier
              </button>
            </div>
            <div v-else class="space-y-4">
              <div 
                v-for="teacher in recentTeachers.slice(0, 5)" 
                :key="teacher.id" 
                class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
              >
                <div class="flex items-center space-x-3">
                  <div class="bg-blue-100 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                  </div>
                  <div>
                    <p class="font-medium text-gray-900">{{ teacher.name }}</p>
                    <p class="text-sm text-gray-600">{{ teacher.email }}</p>
                  </div>
                </div>
                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                  {{ teacher.hourly_rate }}€/h
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Élèves récents -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-emerald-50 to-teal-50">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">Élèves récents</h3>
              <NuxtLink to="/club/students" class="text-emerald-600 hover:text-emerald-800 text-sm font-medium">
                Voir tout →
              </NuxtLink>
            </div>
          </div>
          <div class="p-6">
            <div v-if="recentStudents?.length === 0" class="text-center text-gray-500 py-8">
              <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
              </svg>
              <p>Aucun élève pour le moment</p>
              <button 
                @click="navigateTo('/club/students/add')"
                class="mt-4 bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition-colors"
              >
                Ajouter le premier
              </button>
            </div>
            <div v-else class="space-y-4">
              <div 
                v-for="student in recentStudents.slice(0, 5)" 
                :key="student.id" 
                class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
              >
                <div class="flex items-center space-x-3">
                  <div class="bg-emerald-100 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                  </div>
                  <div>
                    <p class="font-medium text-gray-900">{{ student.name }}</p>
                    <p class="text-sm text-gray-600">{{ student.email }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Cours récents -->
      <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-pink-50">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Cours récents</h3>
            <div class="flex items-center space-x-2">
              <button @click="navigateTo('/club/planning')" class="btn-planning">
                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span>Planning</span>
              </button>
              <button @click="navigateTo('/club/lessons/new')" class="btn-success">
                Nouveau cours
              </button>
            </div>
          </div>
        </div>
        <div class="p-6">
          <div v-if="recentLessons?.length === 0" class="text-center text-gray-500 py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <p>Aucun cours programmé</p>
            <button 
              @click="navigateTo('/club/lessons/new')"
              class="mt-4 bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition-colors"
            >
              Programmer le premier
            </button>
          </div>
          <div v-else class="space-y-4">
            <div 
              v-for="lesson in recentLessons.slice(0, 5)" 
              :key="lesson.id" 
              class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <div class="flex items-center space-x-3">
                <div class="bg-purple-100 p-2 rounded-lg">
                  <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ lesson.title || 'Cours' }}</p>
                  <p class="text-sm text-gray-600">{{ formatDate(lesson.start_time) }}</p>
                </div>
              </div>
              <span 
                class="px-2 py-1 text-xs font-medium rounded-full"
                :class="getStatusClass(lesson.status)"
              >
                {{ getStatusLabel(lesson.status) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal pour compléter les données d'un élève -->
    <div 
      v-if="showCompleteModal && selectedStudentForComplete"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="closeCompleteModal"
    >
      <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-semibold text-gray-900">Compléter les données de l'élève</h3>
            <button 
              @click="closeCompleteModal"
              class="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="completeStudentData">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Prénom <span class="text-gray-500 text-xs">(facultatif)</span>
                </label>
                <input 
                  v-model="completeForm.first_name"
                  type="text"
                  placeholder="Prénom"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Nom <span class="text-gray-500 text-xs">(facultatif)</span>
                </label>
                <input 
                  v-model="completeForm.last_name"
                  type="text"
                  placeholder="Nom"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Email <span class="text-gray-500 text-xs">(facultatif - créera un compte utilisateur si fourni)</span>
                </label>
                <input 
                  v-model="completeForm.email"
                  type="email"
                  placeholder="email@exemple.com"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Téléphone <span class="text-gray-500 text-xs">(facultatif)</span>
                </label>
                <input 
                  v-model="completeForm.phone"
                  type="tel"
                  placeholder="06 12 34 56 78"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                >
              </div>
            </div>
            
            <div class="mt-6 flex justify-end space-x-3">
              <button 
                type="button"
                @click="closeCompleteModal"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
              >
                Annuler
              </button>
              <button 
                type="submit"
                :disabled="completing"
                class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ completing ? 'Enregistrement...' : 'Enregistrer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PredictiveAnalysis from '~/components/AI/PredictiveAnalysis.vue'
import ClubNotificationBell from '~/components/club/ClubNotificationBell.vue'

// Le middleware global 'auth.global.ts' gère déjà la protection de cette route.
// definePageMeta({
//   middleware: ['auth']
// })

const club = ref(null)
const stats = ref(null)
const recentTeachers = ref([])
const recentStudents = ref([])
const recentLessons = ref([])
const incompleteStudents = ref([])
const isLoading = ref(true)
const hasError = ref(false)
const errorMessage = ref('')
const showCompleteModal = ref(false)
const selectedStudentForComplete = ref(null)
const completing = ref(false)
const completeForm = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: ''
})

// Récupérer l'instance $api injectée par le plugin
const { $api } = useNuxtApp()

const loadDashboardData = async () => {
  try {
    isLoading.value = true
    hasError.value = false
    errorMessage.value = ''
    
    console.log('🔄 Chargement des données du dashboard club...')
    
    if (process.server) {
      console.log('🔴 Côté serveur - pas de chargement des données')
      return
    }
    
    // Utilisation de $api qui inclut automatiquement le token via l'intercepteur
    const response = await $api.get('/club/dashboard')
    
    console.log('✅ Données reçues:', response)
    
    if (response.data.success && response.data.data) {
      club.value = response.data.data.club
      stats.value = response.data.data.stats
      recentTeachers.value = response.data.data.recentTeachers
      recentStudents.value = response.data.data.recentStudents
      recentLessons.value = response.data.data.recentLessons || []
      incompleteStudents.value = response.data.data.incompleteStudents || []
      
      console.log('📊 Stats chargées:', stats.value)
      console.log('⚠️ Élèves incomplets:', incompleteStudents.value)
    } else {
      console.error('❌ Format de réponse invalide:', response)
      const { error } = useToast()
      error('Format de réponse invalide du serveur', 'Erreur de données')
      hasError.value = true
      errorMessage.value = 'Format de réponse invalide du serveur'
    }
  } catch (error) {
    console.error('❌ Erreur lors du chargement des données:', error)
    
    hasError.value = true
    const { error: showError, warning } = useToast()
    
    // La structure de l'erreur avec Axios est dans error.response
    const statusCode = error.response?.status
    
    if (statusCode === 401) {
      errorMessage.value = 'Votre session a expiré. Veuillez vous reconnecter.'
      showError('Votre session a expiré. Veuillez vous reconnecter.', 'Session expirée')
      await navigateTo('/login')
    } else if (statusCode === 403) {
      errorMessage.value = 'Vous n\'avez pas les permissions pour accéder à cette page.'
      showError('Vous n\'avez pas les permissions pour accéder à cette page.', 'Accès refusé')
    } else if (statusCode === 404) {
      errorMessage.value = 'Aucun club n\'est associé à votre compte. Contactez l\'administrateur.'
      warning('Aucun club n\'est associé à votre compte. Contactez l\'administrateur.', 'Club non trouvé')
    } else if (statusCode === 500) {
      errorMessage.value = 'Une erreur serveur s\'est produite. Veuillez réessayer plus tard.'
      showError('Une erreur serveur s\'est produite. Veuillez réessayer plus tard.', 'Erreur serveur')
    } else if (statusCode >= 400) {
      errorMessage.value = `Erreur ${statusCode}: ${error.response?.data?.message || 'Erreur inconnue'}`
      showError(`Erreur ${statusCode}: ${error.response?.data?.message || 'Erreur inconnue'}`, 'Erreur de communication')
    } else {
      errorMessage.value = 'Impossible de charger les données du dashboard. Vérifiez votre connexion.'
      showError('Impossible de charger les données du dashboard. Vérifiez votre connexion.', 'Erreur de connexion')
    }
  } finally {
    isLoading.value = false
  }
}

// Méthodes utilitaires
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
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-green-100 text-green-800',
    completed: 'bg-blue-100 text-blue-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'En attente',
    confirmed: 'Confirmé',
    completed: 'Terminé',
    cancelled: 'Annulé'
  }
  return labels[status] || status
}

const getLevelLabel = (level) => {
  const labels = {
    debutant: '🌱 Débutant',
    intermediaire: '📈 Intermédiaire',
    avance: '⭐ Avancé',
    expert: '🏆 Expert'
  }
  return labels[level] || level
}

const getStudentDisplayName = (student) => {
  if (student.first_name || student.last_name) {
    return `${student.first_name || ''} ${student.last_name || ''}`.trim() || 'Élève sans nom'
  }
  if (student.name) {
    return student.name
  }
  return 'Élève sans nom'
}

const getMissingFieldLabel = (field) => {
  const labels = {
    'email': 'Pas d\'email',
    'name': 'Pas de nom',
    'first_name': 'Pas de prénom',
    'last_name': 'Pas de nom',
    'phone': 'Pas de téléphone'
  }
  return labels[field] || field
}

const openCompleteStudentModal = (student) => {
  selectedStudentForComplete.value = student
  // Pré-remplir le formulaire avec les données existantes
  completeForm.value = {
    first_name: student.first_name || '',
    last_name: student.last_name || '',
    email: student.email || '',
    phone: ''
  }
  showCompleteModal.value = true
}

const closeCompleteModal = () => {
  showCompleteModal.value = false
  selectedStudentForComplete.value = null
  completeForm.value = {
    first_name: '',
    last_name: '',
    email: '',
    phone: ''
  }
}

const completeStudentData = async () => {
  if (!selectedStudentForComplete.value) return
  
  completing.value = true
  try {
    const { success } = useToast()
    
    const response = await $api.put(
      `/club/students/${selectedStudentForComplete.value.student_id}`,
      completeForm.value
    )
    
    if (response.data.success) {
      success(response.data.message || 'Données complétées avec succès')
      onStudentCompleted()
    }
  } catch (error) {
    console.error('Erreur lors de la complétion des données:', error)
    const { error: showError } = useToast()
    showError(
      error.response?.data?.message || 'Erreur lors de la mise à jour des données',
      'Erreur'
    )
  } finally {
    completing.value = false
  }
}

const onStudentCompleted = () => {
  closeCompleteModal()
  // Recharger les données pour mettre à jour la liste
  loadDashboardData()
}

onMounted(() => {
  loadDashboardData()
})
</script>