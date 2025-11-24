<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
    <div class="bg-white rounded-lg max-w-6xl w-full max-h-[90vh] overflow-y-auto my-8">
      <div class="p-6">
        <!-- En-tête -->
        <div class="flex items-center justify-between mb-6 pb-4 border-b">
          <div>
            <h3 class="text-2xl font-bold text-gray-900">
              Historique complet des cours
            </h3>
            <p class="text-sm text-gray-600 mt-1">
              Tous les cours passés et à venir
            </p>
          </div>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Filtres -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
            <input
              v-model="filters.dateFrom"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
            <input
              v-model="filters.dateTo"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
            <select
              v-model="filters.status"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Tous</option>
              <option value="pending">En attente</option>
              <option value="confirmed">Confirmé</option>
              <option value="completed">Terminé</option>
              <option value="cancelled">Annulé</option>
            </select>
          </div>
          <div class="flex items-end">
            <button
              @click="applyFilters"
              :disabled="loading"
              class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
            >
              Filtrer
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-4 text-gray-500">Chargement de l'historique...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
          <p class="text-red-800">{{ error }}</p>
        </div>

        <!-- Liste des cours -->
        <div v-else class="space-y-4">
          <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-600">
              {{ filteredLessons.length }} cours trouvé(s)
            </p>
            <button
              @click="exportHistory"
              class="px-3 py-1.5 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Exporter
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Heure</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enseignant</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Élève(s)</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="lesson in filteredLessons" :key="lesson.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                    {{ formatDate(lesson.start_time) }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                    {{ formatTime(lesson.start_time) }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                    {{ lesson.course_type?.name || 'Non défini' }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                    {{ lesson.teacher?.user?.name || 'Non assigné' }}
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-900">
                    {{ getLessonStudents(lesson) }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                    {{ formatPrice(lesson.price) }} €
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap text-sm">
                    <span :class="getStatusClass(lesson.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                      {{ getStatusLabel(lesson.status) }}
                    </span>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap text-sm">
                    <button
                      @click="viewLesson(lesson)"
                      class="text-blue-600 hover:text-blue-900"
                    >
                      Voir détails
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="flex items-center justify-between mt-6 pt-4 border-t">
            <div class="text-sm text-gray-600">
              Page {{ currentPage }} sur {{ totalPages }}
            </div>
            <div class="flex gap-2">
              <button
                @click="previousPage"
                :disabled="currentPage === 1 || loading"
                class="px-3 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Précédent
              </button>
              <button
                @click="nextPage"
                :disabled="currentPage === totalPages || loading"
                class="px-3 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Suivant
              </button>
            </div>
          </div>

          <!-- Message si aucun cours -->
          <div v-if="!loading && filteredLessons.length === 0" class="text-center py-12 text-gray-500">
            <p>Aucun cours trouvé pour cette période</p>
          </div>
        </div>

        <!-- Bouton fermer -->
        <div class="flex justify-end mt-6 pt-4 border-t">
          <button
            @click="$emit('close')"
            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

interface Lesson {
  id: number
  start_time: string
  end_time: string
  course_type?: { name: string }
  teacher?: { user?: { name: string } }
  student?: { user?: { name: string } }
  students?: Array<{ user?: { name: string } }>
  price: number
  status: string
  [key: string]: any
}

interface Props {
  show: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'close': []
  'view-lesson': [lesson: Lesson]
}>()

const { $api } = useNuxtApp()

const loading = ref(false)
const error = ref<string | null>(null)
const lessons = ref<Lesson[]>([])
const currentPage = ref(1)
const perPage = ref(50)
const totalLessons = ref(0)

const filters = ref({
  dateFrom: '',
  dateTo: '',
  status: ''
})

// Initialiser les dates par défaut (1 an en arrière jusqu'à 1 an en avant)
watch(() => props.show, (newValue) => {
  if (newValue) {
    const today = new Date()
    const oneYearAgo = new Date(today)
    oneYearAgo.setFullYear(today.getFullYear() - 1)
    const oneYearLater = new Date(today)
    oneYearLater.setFullYear(today.getFullYear() + 1)
    
    filters.value.dateFrom = oneYearAgo.toISOString().split('T')[0]
    filters.value.dateTo = oneYearLater.toISOString().split('T')[0]
    currentPage.value = 1
    loadHistory()
  } else {
    lessons.value = []
    error.value = null
  }
})

const totalPages = computed(() => Math.ceil(totalLessons.value / perPage.value))

const filteredLessons = computed(() => {
  return lessons.value
})

async function loadHistory() {
  loading.value = true
  error.value = null
  
  try {
    const params: any = {
      limit: perPage.value,
      offset: (currentPage.value - 1) * perPage.value
    }
    
    if (filters.value.dateFrom) {
      params.date_from = filters.value.dateFrom
    }
    if (filters.value.dateTo) {
      params.date_to = filters.value.dateTo
    }
    if (filters.value.status) {
      params.status = filters.value.status
    }
    
    const paramsWithOrder = {
      ...params,
      order: 'desc' // Du plus récent au plus ancien pour l'historique
    }
    
    const response = await $api.get('/lessons', { params: paramsWithOrder })
    
    if (response.data.success) {
      lessons.value = response.data.data || []
      // Si le backend retourne le total, l'utiliser, sinon estimer
      totalLessons.value = response.data.pagination?.total || lessons.value.length
    } else {
      error.value = response.data.message || 'Erreur lors du chargement'
    }
  } catch (err: any) {
    console.error('Erreur chargement historique:', err)
    error.value = err.response?.data?.message || err.message || 'Erreur lors du chargement'
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  currentPage.value = 1
  loadHistory()
}

function previousPage() {
  if (currentPage.value > 1) {
    currentPage.value--
    loadHistory()
  }
}

function nextPage() {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
    loadHistory()
  }
}

function formatDate(dateString: string): string {
  if (!dateString) return ''
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  }).format(date)
}

function formatTime(dateString: string): string {
  if (!dateString) return ''
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

function formatPrice(price: number | null | undefined): string {
  if (price === null || price === undefined) return '0,00'
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price)
}

function getLessonStudents(lesson: Lesson): string {
  const students: string[] = []
  
  // Vérifier d'abord la relation many-to-many (students) - priorité pour les cours de groupe
  if (lesson.students && Array.isArray(lesson.students) && lesson.students.length > 0) {
    lesson.students.forEach((student: any) => {
      const name = student.user?.name || student.name
      if (name && !students.includes(name)) {
        students.push(name)
      }
    })
  }
  
  // Ensuite vérifier l'élève principal (student) - pour les cours individuels
  if (lesson.student?.user?.name) {
    const name = lesson.student.user.name
    if (!students.includes(name)) {
      students.push(name)
    }
  } else if (lesson.student?.name) {
    // Fallback si user n'est pas chargé mais que student.name existe
    const name = lesson.student.name
    if (!students.includes(name)) {
      students.push(name)
    }
  }
  
  // Debug si aucun élève trouvé mais qu'il y a un student_id
  if (students.length === 0 && lesson.student_id) {
    console.warn('⚠️ [LessonsHistoryModal] Aucun élève trouvé mais student_id existe:', {
      lesson_id: lesson.id,
      student_id: lesson.student_id,
      student: lesson.student,
      students: lesson.students
    })
  }
  
  return students.length > 0 ? students.join(', ') : 'Aucun élève'
}

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    pending: 'En attente',
    confirmed: 'Confirmé',
    completed: 'Terminé',
    cancelled: 'Annulé'
  }
  return labels[status] || status
}

function getStatusClass(status: string): string {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-green-100 text-green-800',
    completed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function viewLesson(lesson: Lesson) {
  // Émettre un événement pour que le parent puisse ouvrir la modale de détails
  emit('view-lesson', lesson)
  emit('close')
}

function exportHistory() {
  // Créer un CSV avec les données actuelles
  const headers = ['Date', 'Heure', 'Type', 'Enseignant', 'Élève(s)', 'Prix', 'Statut']
  const rows = filteredLessons.value.map(lesson => [
    formatDate(lesson.start_time),
    formatTime(lesson.start_time),
    lesson.course_type?.name || 'Non défini',
    lesson.teacher?.user?.name || 'Non assigné',
    getLessonStudents(lesson),
    `${formatPrice(lesson.price)} €`,
    getStatusLabel(lesson.status)
  ])
  
  const csv = [
    headers.join(','),
    ...rows.map(row => row.map(cell => `"${cell}"`).join(','))
  ].join('\n')
  
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  const url = URL.createObjectURL(blob)
  link.setAttribute('href', url)
  link.setAttribute('download', `historique-cours-${new Date().toISOString().split('T')[0]}.csv`)
  link.style.visibility = 'hidden'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}
</script>

