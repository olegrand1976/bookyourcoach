<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">
            Dashboard Enseignant
          </h1>
          <p class="mt-2 text-gray-600">
            Bonjour {{ authStore.userName }}, gérez vos cours et votre planning
          </p>
        </div>
        <NotificationBell />
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
                  <div class="flex items-center justify-between">
                    <div>
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
                    <div class="flex items-center">
                      <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        ⏳ En attente
                      </span>
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
                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm font-medium"
                      >
                        ✓ Accepter
                      </button>
                      <button
                        @click="respondToReplacement(replacement.id, 'reject')"
                        class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors text-sm font-medium"
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
              <p class="text-sm font-medium text-gray-600">Total cours</p>
              <p class="text-2xl font-bold text-gray-900">{{ lessons.length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-orange-100 rounded-lg">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Remplacements</p>
              <p class="text-2xl font-bold text-gray-900">{{ allReplacements.length }}</p>
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
              <p class="text-2xl font-bold text-gray-900">{{ uniqueClubs.length }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Mes cours -->
      <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-6 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Mes cours</h3>
        </div>
        <div class="p-6">
          <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="text-gray-600 mt-4">Chargement...</p>
          </div>

          <div v-else-if="lessons.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Club</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date/Heure</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type de cours</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Élève</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="lesson in lessons" :key="lesson.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                      {{ lesson.club?.name || 'N/A' }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ formatDate(lesson.start_time) }}</div>
                    <div class="text-xs text-gray-500">{{ formatTime(lesson.start_time) }} - {{ formatTime(lesson.end_time) }}</div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-gray-900">{{ lesson.course_type?.name || 'N/A' }}</div>
                    <div class="text-xs text-gray-500">{{ lesson.duration }}min - {{ lesson.price }}€</div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900">
                      {{ lesson.student?.user?.name || 'Sans élève' }}
                    </div>
                    <div v-if="lesson.student?.age" class="text-xs text-gray-500">
                      {{ lesson.student.age }} ans
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getStatusClass(lesson.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                      {{ getStatusLabel(lesson.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                    <button
                      @click="openLessonDetails(lesson)"
                      class="text-blue-600 hover:text-blue-900"
                    >
                      👁️ Voir
                    </button>
                    <button
                      @click="openReplacementRequest(lesson)"
                      class="text-orange-600 hover:text-orange-900"
                    >
                      🔄 Remplacer
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="text-gray-500">Aucun cours planifié</p>
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
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import LessonDetailsModal from '~/components/teacher/LessonDetailsModal.vue'
import ReplacementRequestModal from '~/components/teacher/ReplacementRequestModal.vue'
import NotificationBell from '~/components/NotificationBell.vue'

definePageMeta({
  middleware: ['auth']
})

const authStore = useAuthStore()
const { $api } = useNuxtApp()

// State
const loading = ref(true)
const lessons = ref<any[]>([])
const allReplacements = ref<any[]>([])
const availableTeachers = ref<any[]>([])
const selectedLesson = ref<any | null>(null)
const showDetailsModal = ref(false)
const showReplacementModal = ref(false)

// Computed
const todayLessons = computed(() => {
  const today = new Date().toISOString().split('T')[0]
  return lessons.value.filter(lesson => {
    const lessonDate = new Date(lesson.start_time).toISOString().split('T')[0]
    return lessonDate === today
  })
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

// Toutes les demandes en attente (pour la statistique)
const pendingReplacements = computed(() => {
  return allReplacements.value.filter(r => r.status === 'pending')
})

const uniqueClubs = computed(() => {
  const clubs = lessons.value.map(l => l.club?.id).filter(Boolean)
  return [...new Set(clubs)]
})

// Methods
onMounted(async () => {
  await loadData()
})

async function loadData() {
  loading.value = true
  try {
    // Charger les cours
    const lessonsResponse = await $api.get('/teacher/lessons')
    lessons.value = lessonsResponse.data.data || []

    // Charger les demandes de remplacement
    const replacementsResponse = await $api.get('/teacher/lesson-replacements')
    allReplacements.value = replacementsResponse.data.data || []

    // Charger les enseignants disponibles
    const teachersResponse = await $api.get('/teacher/teachers')
    availableTeachers.value = teachersResponse.data.data || []

    console.log('✅ Données chargées:', {
      lessons: lessons.value.length,
      replacements: allReplacements.value.length,
      teachers: availableTeachers.value.length
    })
  } catch (error) {
    console.error('❌ Erreur lors du chargement des données:', error)
  } finally {
    loading.value = false
  }
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

    console.log(`✅ Remplacement ${action === 'accept' ? 'accepté' : 'refusé'}`)
    
    // Recharger les données
    await loadData()
  } catch (error) {
    console.error('❌ Erreur:', error)
    alert('Erreur lors de la réponse à la demande')
  }
}

async function handleReplacementSuccess() {
  console.log('✅ Demande de remplacement envoyée avec succès')
  await loadData()
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
</script>
