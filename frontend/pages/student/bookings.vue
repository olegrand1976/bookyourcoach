<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-6 md:mb-8">
        <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
              Réservations
            </h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">
              Gérez vos cours réservés
            </p>
          </div>
          
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

      <!-- Status Filter -->
      <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 mb-6 sm:mb-8">
        <h2 class="text-base md:text-lg font-semibold text-gray-900 mb-4">Filtrer par statut</h2>
        <div class="flex flex-wrap gap-2">
          <button 
            v-for="status in statusOptions" 
            :key="status.value"
            @click="selectedStatus = status.value"
            :class="[
              'min-h-[44px] px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200',
              selectedStatus === status.value
                ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-md'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            {{ status.label }}
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-gray-600">Chargement des réservations...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3 flex-1">
            <h3 class="text-sm font-medium text-red-800">Erreur</h3>
            <div class="mt-2 text-sm text-red-700">{{ error }}</div>
            <button 
              @click="loadBookings"
              class="mt-4 px-4 py-2 bg-red-100 text-red-800 rounded-lg hover:bg-red-200 transition-colors text-sm font-medium"
            >
              Réessayer
            </button>
          </div>
        </div>
      </div>

      <!-- Bookings List -->
      <div v-else class="space-y-4 sm:space-y-6">
        <div 
          v-for="booking in filteredBookings" 
          :key="booking.id"
          class="bg-white rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-all"
        >
          <div class="p-4 sm:p-6">
            <!-- Booking Header -->
            <div class="flex items-start justify-between mb-4">
              <div>
                <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-1">
                  {{ booking.lesson?.course_type?.name || booking.lesson?.title || 'Leçon' }}
                </h3>
                <p class="text-xs md:text-sm text-gray-600">
                  {{ booking.lesson?.teacher?.user?.name || 'Enseignant non spécifié' }}
                </p>
              </div>
              <span 
                :class="[
                  'px-3 py-1 text-xs md:text-sm font-medium rounded-full',
                  getStatusClass(booking.status)
                ]"
              >
                {{ getStatusText(booking.status) }}
              </span>
            </div>

            <!-- Booking Details -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
              <div class="flex items-center text-sm text-gray-600">
                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                  <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ formatDate(booking.lesson?.start_time) }}</p>
                  <p class="text-xs text-gray-500">{{ formatTime(booking.lesson?.start_time) }} - {{ formatTime(booking.lesson?.end_time) }}</p>
                </div>
              </div>
              
              <div class="flex items-center text-sm text-gray-600">
                <div class="bg-emerald-100 p-2 rounded-lg mr-3">
                  <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ booking.lesson?.location?.name || 'Lieu non spécifié' }}</p>
                </div>
              </div>

              <div class="flex items-center text-sm text-gray-600">
                <div class="bg-purple-100 p-2 rounded-lg mr-3">
                  <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ formatPrice(booking.price) }}</p>
                </div>
              </div>
            </div>

            <!-- Notes -->
            <div v-if="booking.notes" class="mb-4 p-3 bg-gray-50 rounded-lg">
              <h4 class="text-sm font-medium text-gray-900 mb-1">Notes</h4>
              <p class="text-xs md:text-sm text-gray-600">{{ booking.notes }}</p>
            </div>

            <!-- Actions : ligne dédiée, boutons avec icônes -->
            <div class="flex flex-wrap gap-2 pt-4 mt-4 border-t border-gray-200">
              <button 
                v-if="canCancel(booking)"
                @click="handleCancelBooking(booking.id)"
                class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:from-red-600 hover:to-red-700 transition-all shadow-sm hover:shadow-md text-sm font-medium"
              >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Annuler
              </button>
              <button 
                v-if="canRate(booking)"
                @click="rateLesson(booking)"
                class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-lg hover:from-yellow-600 hover:to-yellow-700 transition-all shadow-sm hover:shadow-md text-sm font-medium"
              >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
                Noter
              </button>
              <button 
                @click="viewBookingDetails(booking.id)"
                class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium"
              >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Détails
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="filteredBookings.length === 0" class="text-center py-12 bg-white rounded-xl shadow-lg">
          <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <h3 class="mt-2 text-base md:text-lg font-medium text-gray-900">Aucune réservation</h3>
          <p class="mt-1 text-sm md:text-base text-gray-500">
            {{ selectedStatus === 'all' ? 'Vous n\'avez pas encore de réservations.' : 'Aucune réservation avec ce statut.' }}
          </p>
          <div v-if="selectedStatus === 'all'" class="mt-6">
            <NuxtLink 
              to="/student/lessons"
              class="inline-flex items-center justify-center gap-2 min-h-[44px] px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all shadow-sm hover:shadow-md font-medium"
            >
              <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
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
import { useStudentData } from '~/composables/useStudentData'
import { useStudentFormatters } from '~/composables/useStudentFormatters'

definePageMeta({
  middleware: ['auth', 'student'],
  layout: 'student'
})

// Composables
const { loading, loadBookings: loadBookingsData, cancelBooking } = useStudentData()
const { formatDate, formatTime, formatPrice, getStatusClass, getStatusText } = useStudentFormatters()

// State
const bookings = ref<any[]>([])
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
    bookings.value = await loadBookingsData()
  } catch (err) {
    console.error('Error loading bookings:', err)
  }
}

const handleCancelBooking = async (bookingId: number) => {
  if (!confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
    return
  }

  try {
    await cancelBooking(bookingId)
    await loadBookings()
  } catch (err) {
    console.error('Error cancelling booking:', err)
  }
}

const rateLesson = (booking: any) => {
  // TODO: Implémenter le modal de notation
  console.log('Rate lesson:', booking)
}

const viewBookingDetails = (bookingId: number) => {
  // TODO: Navigation vers la page de détails
  console.log('View booking details:', bookingId)
}

const canCancel = (booking: any) => {
  return ['pending', 'confirmed'].includes(booking.status) && 
         new Date(booking.lesson?.start_time) > new Date()
}

const canRate = (booking: any) => {
  return booking.status === 'completed' && !booking.rating
}

// Lifecycle
onMounted(() => {
  loadBookings()
})
</script>
