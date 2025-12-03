<template>
  <div v-if="isOpen" class="fixed inset-0 z-[70] overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-12">
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="close"></div>
      
      <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-red-500 to-orange-500">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Créneau complet
              </h2>
              <p class="text-sm text-red-100 mt-1">
                {{ slotInfo?.current_count || 0 }}/{{ slotInfo?.max_slots || 1 }} cours sur ce créneau
              </p>
            </div>
            <button 
              @click="close"
              class="text-white hover:text-gray-200 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="overflow-y-auto max-h-[calc(90vh-200px)] p-6">
          <!-- Info créneau -->
          <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
            <div class="flex items-center gap-2 text-amber-800 mb-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="font-semibold">Ce créneau est complet</span>
            </div>
            <p class="text-amber-700 text-sm">
              Pour ajouter un nouveau cours à <strong>{{ formatTime(slotInfo?.time) }}</strong> le <strong>{{ formatDate(slotInfo?.date) }}</strong>, 
              vous devez libérer un emplacement en annulant un cours existant.
            </p>
          </div>

          <!-- Liste des cours -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
              Cours sur ce créneau ({{ lessons.length }})
            </h3>

            <div v-if="loading" class="flex justify-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>

            <div v-else-if="lessons.length === 0" class="bg-gray-50 rounded-lg p-6 text-center">
              <p class="text-gray-500">Aucun cours trouvé sur ce créneau</p>
            </div>

            <div v-else class="space-y-3">
              <div 
                v-for="lesson in lessons" 
                :key="lesson.id"
                class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow"
              >
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <!-- En-tête du cours -->
                    <div class="flex items-center gap-2 mb-2">
                      <span class="font-semibold text-gray-900">{{ lesson.course_type_name }}</span>
                      <span 
                        v-if="lesson.has_subscription"
                        class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full"
                      >
                        Abonnement
                      </span>
                      <span 
                        v-else
                        class="px-2 py-0.5 text-xs font-medium bg-orange-100 text-orange-800 rounded-full"
                      >
                        Séance libre
                      </span>
                    </div>

                    <!-- Détails -->
                    <div class="grid grid-cols-2 gap-2 text-sm text-gray-600">
                      <div class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ formatTime(lesson.start_time) }} - {{ formatTime(lesson.end_time) }}
                      </div>
                      <div class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ lesson.student_name }}
                      </div>
                      <div class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        {{ lesson.teacher_name }}
                      </div>
                      <div v-if="lesson.price" class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ lesson.price }} €
                      </div>
                    </div>

                    <!-- Info abonnement -->
                    <div v-if="lesson.subscription_name" class="mt-2 text-xs text-green-700 bg-green-50 rounded px-2 py-1 inline-block">
                      {{ lesson.subscription_name }}
                    </div>
                  </div>

                  <!-- Actions -->
                  <div class="ml-4 flex flex-col gap-2">
                    <button
                      @click="openCancelModal(lesson, 'single')"
                      class="px-3 py-2 text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors flex items-center gap-1"
                      :disabled="cancelling"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                      Annuler
                    </button>
                    
                    <button
                      v-if="lesson.has_subscription"
                      @click="openCancelModal(lesson, 'all_future')"
                      class="px-3 py-2 text-sm bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200 transition-colors flex items-center gap-1"
                      :disabled="cancelling"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                      + Futurs
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end">
          <button 
            @click="close"
            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>

    <!-- Modale de confirmation d'annulation -->
    <div v-if="showCancelConfirm" class="fixed inset-0 z-[80] overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4 py-12">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeCancelConfirm"></div>
        
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Confirmer l'annulation</h3>
          </div>
          
          <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
              <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
              <div>
                <p class="text-gray-900 font-medium">
                  {{ cancelScope === 'single' ? 'Annuler ce cours uniquement' : 'Annuler ce cours et tous les cours futurs' }}
                </p>
                <p class="text-sm text-gray-500">
                  {{ lessonToCancel?.course_type_name }} - {{ lessonToCancel?.student_name }}
                </p>
              </div>
            </div>

            <div v-if="cancelScope === 'all_future'" class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-4">
              <p class="text-sm text-orange-800">
                <strong>Attention :</strong> Tous les cours futurs de cet abonnement seront également annulés.
              </p>
            </div>

            <!-- Raison -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Raison de l'annulation (optionnel)
              </label>
              <textarea 
                v-model="cancelReason"
                rows="2"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500"
                placeholder="Ex: Libération du créneau pour un autre cours"
              ></textarea>
            </div>
          </div>
          
          <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button
              @click="closeCancelConfirm"
              class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
              :disabled="cancelling"
            >
              Annuler
            </button>
            <button
              @click="confirmCancel"
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2"
              :disabled="cancelling"
            >
              <span v-if="cancelling" class="animate-spin">⏳</span>
              <span>{{ cancelling ? 'Annulation...' : 'Confirmer l\'annulation' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useNuxtApp } from '#app'

interface Lesson {
  id: number
  start_time: string
  end_time: string
  duration: number
  status: string
  teacher_name: string
  teacher_id: number
  student_name: string
  student_id: number | null
  course_type_name: string
  course_type_id: number
  has_subscription: boolean
  subscription_name: string | null
  subscription_instance_id: number | null
  price: number | null
}

interface SlotInfo {
  date: string
  time: string
  day_of_week: number
  max_slots: number
  max_capacity: number | null
  current_count: number
  is_full: boolean
  available_slots: number
}

const props = defineProps<{
  isOpen: boolean
  date?: string
  time?: string
  duration?: number
  teacherId?: number
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'lesson-cancelled', lessonIds: number[]): void
}>()

const loading = ref(false)
const lessons = ref<Lesson[]>([])
const slotInfo = ref<SlotInfo | null>(null)

const showCancelConfirm = ref(false)
const lessonToCancel = ref<Lesson | null>(null)
const cancelScope = ref<'single' | 'all_future'>('single')
const cancelReason = ref('')
const cancelling = ref(false)

// Charger les cours du créneau
const loadSlotOccupants = async () => {
  if (!props.date || !props.time) return
  
  loading.value = true
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/lessons/slot-occupants', {
      params: {
        date: props.date,
        time: props.time,
        duration: props.duration || 60,
        teacher_id: props.teacherId
      }
    })
    
    if (response.data.success) {
      lessons.value = response.data.data.lessons
      slotInfo.value = response.data.data.slot_info
    }
  } catch (error) {
    console.error('Erreur chargement cours du créneau:', error)
  } finally {
    loading.value = false
  }
}

// Watcher pour recharger quand la modale s'ouvre
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    loadSlotOccupants()
  }
})

// Fermer la modale
const close = () => {
  emit('close')
}

// Ouvrir la confirmation d'annulation
const openCancelModal = (lesson: Lesson, scope: 'single' | 'all_future') => {
  lessonToCancel.value = lesson
  cancelScope.value = scope
  cancelReason.value = ''
  showCancelConfirm.value = true
}

// Fermer la confirmation
const closeCancelConfirm = () => {
  showCancelConfirm.value = false
  lessonToCancel.value = null
  cancelReason.value = ''
}

// Confirmer l'annulation
const confirmCancel = async () => {
  if (!lessonToCancel.value) return
  
  cancelling.value = true
  try {
    const { $api } = useNuxtApp()
    const response = await $api.post(`/lessons/${lessonToCancel.value.id}/cancel-with-future`, {
      cancel_scope: cancelScope.value,
      reason: cancelReason.value || 'Libération du créneau'
    })
    
    if (response.data.success) {
      // Émettre l'événement avec les IDs des cours annulés
      emit('lesson-cancelled', response.data.data.cancelled_lesson_ids)
      
      // Fermer la confirmation et recharger la liste
      closeCancelConfirm()
      await loadSlotOccupants()
      
      // Si plus de cours, fermer la modale principale
      if (lessons.value.length === 0) {
        close()
      }
    }
  } catch (error: any) {
    console.error('Erreur annulation cours:', error)
    alert(error.response?.data?.message || 'Erreur lors de l\'annulation du cours')
  } finally {
    cancelling.value = false
  }
}

// Formatters
const formatDate = (dateStr: string | undefined) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString('fr-FR', { 
    weekday: 'long', 
    day: 'numeric', 
    month: 'long' 
  })
}

const formatTime = (timeStr: string | undefined) => {
  if (!timeStr) return ''
  // Si c'est une datetime complète, extraire l'heure
  if (timeStr.includes('T') || timeStr.includes(' ')) {
    const date = new Date(timeStr)
    return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
  }
  // Si c'est juste une heure
  return timeStr.substring(0, 5)
}
</script>

