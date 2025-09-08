<template>
  <div class="bookings-page">
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          Mes Réservations
        </h1>
        <p class="text-gray-600">
          Gérez vos cours réservés et votre planning
        </p>
      </div>

      <!-- Status Filter -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Filtrer par statut</h2>
        <div class="flex flex-wrap gap-2">
          <button 
            v-for="status in statusOptions" 
            :key="status.value"
            @click="selectedStatus = status.value"
            :class="[
              'px-4 py-2 rounded-md text-sm font-medium transition-colors',
              selectedStatus === status.value
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            {{ status.label }}
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Erreur</h3>
            <div class="mt-2 text-sm text-red-700">{{ error }}</div>
          </div>
        </div>
      </div>

      <!-- Bookings List -->
      <div v-else class="space-y-6">
        <div 
          v-for="booking in filteredBookings" 
          :key="booking.id"
          class="bg-white rounded-lg shadow-md border border-gray-200"
        >
          <div class="p-6">
            <!-- Booking Header -->
            <div class="flex items-start justify-between mb-4">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                  {{ booking.lesson?.title || 'Leçon' }}
                </h3>
                <p class="text-sm text-gray-600">
                  {{ booking.lesson?.course_type?.name || 'Type non spécifié' }}
                </p>
              </div>
              <span 
                :class="[
                  'px-3 py-1 text-sm font-medium rounded-full',
                  getStatusClass(booking.status)
                ]"
              >
                {{ getStatusText(booking.status) }}
              </span>
            </div>

            <!-- Teacher Info -->
            <div class="flex items-center mb-4">
              <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                  <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">
                  {{ booking.lesson?.teacher?.user?.name || 'Enseignant' }}
                </p>
                <p class="text-xs text-gray-500">Enseignant</p>
              </div>
            </div>

            <!-- Booking Details -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
              <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <div>
                  <p class="font-medium">{{ formatDate(booking.lesson?.start_time) }}</p>
                  <p class="text-xs">{{ formatTime(booking.lesson?.start_time) }} - {{ formatTime(booking.lesson?.end_time) }}</p>
                </div>
              </div>
              
              <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <div>
                  <p class="font-medium">{{ booking.lesson?.location?.name || 'Lieu non spécifié' }}</p>
                </div>
              </div>

              <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                </svg>
                <div>
                  <p class="font-medium">{{ booking.price ? `${booking.price}€` : 'Prix non spécifié' }}</p>
                </div>
              </div>
            </div>

            <!-- Notes -->
            <div v-if="booking.notes" class="mb-4">
              <h4 class="text-sm font-medium text-gray-900 mb-1">Notes</h4>
              <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-md">
                {{ booking.notes }}
              </p>
            </div>

            <!-- Actions -->
            <div class="flex space-x-2">
              <button 
                v-if="canCancel(booking)"
                @click="cancelBooking(booking.id)"
                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors text-sm font-medium"
              >
                Annuler
              </button>
              
              <button 
                v-if="canRate(booking)"
                @click="rateLesson(booking)"
                class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors text-sm font-medium"
              >
                Noter
              </button>
              
              <button 
                @click="viewBookingDetails(booking.id)"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors text-sm font-medium"
              >
                Détails
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="filteredBookings.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune réservation</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ selectedStatus === 'all' ? 'Vous n\'avez pas encore de réservations.' : 'Aucune réservation avec ce statut.' }}
          </p>
          <div v-if="selectedStatus === 'all'" class="mt-4">
            <NuxtLink 
              to="/student/lessons"
              class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
            >
              Voir les leçons disponibles
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

// Meta
definePageMeta({
  middleware: ['auth', 'student'],
  layout: 'student'
})

// State
const bookings = ref<any[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const selectedStatus = ref('all')

const statusOptions = [
  { value: 'all', label: 'Toutes' },
  { value: 'pending', label: 'En attente' },
  { value: 'confirmed', label: 'Confirmées' },
  { value: 'completed', label: 'Terminées' },
  { value: 'cancelled', label: 'Annulées' }
]

// Computed
const filteredBookings = computed(() => {
  if (selectedStatus.value === 'all') {
    return bookings.value
  }
  return bookings.value.filter(booking => booking.status === selectedStatus.value)
})

// Methods
const loadBookings = async () => {
  try {
    loading.value = true
    error.value = null
    
    const { $api } = useNuxtApp()
    const response = await $api.get('/student/bookings')
    
    if (response.data.success) {
      bookings.value = response.data.data
    } else {
      throw new Error('Erreur lors du chargement des réservations')
    }
  } catch (err: any) {
    error.value = err.message || 'Erreur lors du chargement des réservations'
    console.error('Error loading bookings:', err)
  } finally {
    loading.value = false
  }
}

const cancelBooking = async (bookingId: number) => {
  if (!confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
    return
  }

  try {
    const { $api } = useNuxtApp()
    const response = await $api.put(`/student/bookings/${bookingId}/cancel`)
    
    if (response.data.success) {
      // Recharger les réservations
      await loadBookings()
      
      const { $toast } = useNuxtApp()
      $toast.success('Réservation annulée avec succès!')
    } else {
      throw new Error(response.data.message || 'Erreur lors de l\'annulation')
    }
  } catch (err: any) {
    const { $toast } = useNuxtApp()
    $toast.error(err.message || 'Erreur lors de l\'annulation')
    console.error('Error cancelling booking:', err)
  }
}

const rateLesson = (booking: any) => {
  // Ouvrir un modal de notation (à implémenter)
  console.log('Rate lesson:', booking)
}

const viewBookingDetails = (bookingId: number) => {
  // Navigation vers la page de détails (à implémenter)
  console.log('View booking details:', bookingId)
}

const canCancel = (booking: any) => {
  return ['pending', 'confirmed'].includes(booking.status) && 
         new Date(booking.lesson?.start_time) > new Date()
}

const canRate = (booking: any) => {
  return booking.status === 'completed' && !booking.rating
}

const getStatusClass = (status: string) => {
  switch (status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'confirmed':
      return 'bg-blue-100 text-blue-800'
    case 'completed':
      return 'bg-green-100 text-green-800'
    case 'cancelled':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getStatusText = (status: string) => {
  switch (status) {
    case 'pending':
      return 'En attente'
    case 'confirmed':
      return 'Confirmée'
    case 'completed':
      return 'Terminée'
    case 'cancelled':
      return 'Annulée'
    default:
      return status
  }
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long'
  })
}

const formatTime = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(() => {
  loadBookings()
})
</script>

<style scoped>
.bookings-page {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>
