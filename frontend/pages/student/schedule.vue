<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-6 md:mb-8">
        <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
              Mon Planning
            </h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">
              Consultez vos cours réservés et découvrez les leçons disponibles
            </p>
          </div>
          
          <div class="flex items-center space-x-4">
            <NuxtLink 
              to="/student/dashboard" 
              class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm md:text-base"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Retour au dashboard
            </NuxtLink>
          </div>
        </div>
      </div>

      <!-- Intégration Google Calendar -->
      <div class="mb-6 md:mb-8 bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-lg md:text-xl font-semibold text-gray-900 mb-4">Synchronisation Google Calendar</h2>
        <GoogleCalendarIntegration :student-id="authStore.user?.id" />
      </div>

      <!-- Calendrier -->
      <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-lg md:text-xl font-semibold text-gray-900 mb-4">Calendrier des cours</h2>
        <StudentCalendar :student-id="authStore.user?.id" />
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: ['auth'],
  layout: 'default'
})

const authStore = useAuthStore()

// Vérifier que l'utilisateur peut agir comme étudiant
if (!authStore.canActAsStudent) {
  throw createError({
    statusCode: 403,
    statusMessage: 'Accès refusé - Droits étudiant requis'
  })
}
</script>
