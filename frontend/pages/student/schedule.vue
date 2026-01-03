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
              Consultez votre calendrier de cours
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

      <!-- Calendrier -->
      <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <StudentCalendar :student-id="authStore.user?.student?.id || authStore.user?.id" />
      </div>
    </div>

    <!-- Modal de détails de cours -->
    <div v-if="selectedLesson" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="selectedLesson = null">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Détails du cours</h3>
          <button @click="selectedLesson = null" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <div class="space-y-4">
          <div>
            <span class="text-sm font-medium text-gray-500">Type de cours:</span>
            <p class="mt-1 text-sm text-gray-900">{{ selectedLesson.course_type?.name || selectedLesson.courseType?.name || 'N/A' }}</p>
          </div>
          
          <div>
            <span class="text-sm font-medium text-gray-500">Date:</span>
            <p class="mt-1 text-sm text-gray-900">{{ formatFullDate(selectedLesson.start_time) }}</p>
          </div>
          
          <div>
            <span class="text-sm font-medium text-gray-500">Heure:</span>
            <p class="mt-1 text-sm text-gray-900">
              {{ formatTime(selectedLesson.start_time) }} - {{ formatTime(selectedLesson.end_time) }}
            </p>
          </div>
          
          <div v-if="selectedLesson.teacher">
            <span class="text-sm font-medium text-gray-500">Enseignant:</span>
            <p class="mt-1 text-sm text-gray-900">{{ selectedLesson.teacher?.user?.name || selectedLesson.teacher?.name || 'N/A' }}</p>
          </div>
          
          <div v-if="selectedLesson.location">
            <span class="text-sm font-medium text-gray-500">Lieu:</span>
            <p class="mt-1 text-sm text-gray-900">{{ selectedLesson.location?.name || selectedLesson.location || 'N/A' }}</p>
          </div>
          
          <div v-if="selectedLesson.price">
            <span class="text-sm font-medium text-gray-500">Prix:</span>
            <p class="mt-1 text-sm text-gray-900">{{ formatPrice(selectedLesson.price) }}</p>
          </div>
          
          <div v-if="selectedLesson.status">
            <span class="text-sm font-medium text-gray-500">Statut:</span>
            <span 
              :class="{
                'bg-green-100 text-green-800': selectedLesson.status === 'confirmed',
                'bg-yellow-100 text-yellow-800': selectedLesson.status === 'pending',
                'bg-gray-100 text-gray-800': selectedLesson.status === 'completed',
                'bg-red-100 text-red-800': selectedLesson.status === 'cancelled'
              }"
              class="mt-1 inline-block px-2 py-1 text-xs font-medium rounded-full"
            >
              {{ getStatusLabel(selectedLesson.status) }}
            </span>
          </div>
          
          <div v-if="selectedLesson.notes">
            <span class="text-sm font-medium text-gray-500">Notes:</span>
            <p class="mt-1 text-sm text-gray-900">{{ selectedLesson.notes }}</p>
          </div>
        </div>
        
        <div class="mt-6 flex justify-end">
          <button @click="selectedLesson = null" 
            class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors">
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: ['auth', 'student'],
  layout: 'student'
})

const authStore = useAuthStore()
const selectedLesson = ref(null)

// Vérifier que l'utilisateur peut agir comme étudiant
if (!authStore.canActAsStudent) {
  throw createError({
    statusCode: 403,
    statusMessage: 'Accès refusé - Droits étudiant requis'
  })
}

// Écouter les événements du calendrier
const handleLessonClick = (lesson) => {
  selectedLesson.value = lesson
}

// Exposer la fonction pour le composant enfant
provide('onLessonClick', handleLessonClick)

const formatFullDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

const formatTime = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(price)
}

const getStatusLabel = (status) => {
  const labels = {
    'confirmed': 'Confirmé',
    'pending': 'En attente',
    'completed': 'Terminé',
    'cancelled': 'Annulé',
    'available': 'Disponible'
  }
  return labels[status] || status
}
</script>
