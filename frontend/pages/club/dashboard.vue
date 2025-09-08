<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
          Dashboard Club
        </h1>
        <p class="mt-2 text-gray-600">
          Bienvenue {{ club?.name }}, gérez vos enseignants et élèves
        </p>
      </div>

      <!-- Stats cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Enseignants -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
              <EquestrianIcon icon="helmet" :size="24" class="text-blue-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Enseignants</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats?.total_teachers || 0 }}</p>
            </div>
          </div>
        </div>

        <!-- Élèves -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
              <EquestrianIcon icon="horse" :size="24" class="text-green-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Élèves</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats?.total_students || 0 }}</p>
            </div>
          </div>
        </div>

        <!-- Cours totaux -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-purple-100 rounded-lg">
              <EquestrianIcon icon="trophy" :size="24" class="text-purple-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Cours totaux</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats?.total_lessons || 0 }}</p>
            </div>
          </div>
        </div>

        <!-- Cours terminés -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-emerald-100 rounded-lg">
              <EquestrianIcon icon="medal" :size="24" class="text-emerald-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Cours terminés</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats?.completed_lessons || 0 }}</p>
            </div>
          </div>
        </div>

        <!-- Revenus totaux -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-yellow-100 rounded-lg">
              <EquestrianIcon icon="saddle" :size="24" class="text-yellow-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Revenus totaux</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats?.total_revenue || 0 }}€</p>
            </div>
          </div>
        </div>

        <!-- Revenus mensuels -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-orange-100 rounded-lg">
              <EquestrianIcon icon="calendar" :size="24" class="text-orange-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Revenus ce mois</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats?.monthly_revenue || 0 }}€</p>
            </div>
          </div>
        </div>

        <!-- Taux d'occupation -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-indigo-100 rounded-lg">
              <EquestrianIcon icon="chart" :size="24" class="text-indigo-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Taux d'occupation</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats?.occupancy_rate || 0 }}%</p>
            </div>
          </div>
        </div>

        <!-- Prix moyen -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-pink-100 rounded-lg">
              <EquestrianIcon icon="star" :size="24" class="text-pink-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Prix moyen</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats?.average_lesson_price || 0 }}€</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions rapides -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Actions rapides</h3>
          <div class="space-y-3">
            <button @click="showAddTeacherModal = true" class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
              <EquestrianIcon icon="helmet" :size="16" class="mr-2" />
              Ajouter un enseignant
            </button>
            <button @click="showAddStudentModal = true" class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
              <EquestrianIcon icon="horse" :size="16" class="mr-2" />
              Ajouter un élève
            </button>
            <button @click="navigateTo('/club/profile')" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
              <EquestrianIcon icon="settings" :size="16" class="mr-2" />
              Modifier le profil du club
            </button>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Informations du club</h3>
          <div class="space-y-2">
            <p><span class="font-medium">Nom:</span> {{ club?.name }}</p>
            <p><span class="font-medium">Email:</span> {{ club?.email }}</p>
            <p><span class="font-medium">Téléphone:</span> {{ club?.phone }}</p>
            <p><span class="font-medium">Ville:</span> {{ club?.city }}</p>
            <p><span class="font-medium">Disciplines:</span> {{ club?.disciplines?.join(', ') }}</p>
          </div>
        </div>
      </div>

      <!-- Cours récents -->
      <div class="mb-8">
        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Réservations récentes</h3>
          </div>
          <div class="p-6">
            <div v-if="recentLessons?.length === 0" class="text-center text-gray-500 py-4">
              Aucune réservation récente
            </div>
            <div v-else class="space-y-3">
              <div v-for="lesson in recentLessons" :key="lesson.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div>
                  <p class="font-medium text-gray-900">{{ lesson.location }}</p>
                  <p class="text-sm text-gray-600">{{ lesson.teacher_name }} → {{ lesson.student_name }}</p>
                  <p class="text-sm text-gray-500" v-if="lesson.start_time">
                    📅 {{ lesson.start_time.split(' ')[0] }} de {{ lesson.start_time.split(' ')[1] }} à {{ lesson.end_time.split(' ')[1] }}
                  </p>
                  <p class="text-sm text-gray-500" v-else>
                    Créé le {{ lesson.created_at }}
                  </p>
                </div>
                <div class="text-right">
                  <span :class="getStatusClass(lesson.status)" class="inline-flex px-2 py-1 text-xs font-medium rounded-full">
                    {{ getStatusLabel(lesson.status) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Enseignants et élèves récents -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Enseignants récents</h3>
          </div>
          <div class="p-6">
            <div v-if="recentTeachers?.length === 0" class="text-center text-gray-500 py-4">
              Aucun enseignant pour le moment
            </div>
            <div v-else class="space-y-3">
              <div v-for="teacher in recentTeachers" :key="teacher.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div>
                  <p class="font-medium text-gray-900">{{ teacher.name }}</p>
                  <p class="text-sm text-gray-600">{{ teacher.email }}</p>
                </div>
                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                  Enseignant
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Élèves récents</h3>
          </div>
          <div class="p-6">
            <div v-if="recentStudents?.length === 0" class="text-center text-gray-500 py-4">
              Aucun élève pour le moment
            </div>
            <div v-else class="space-y-3">
              <div v-for="student in recentStudents" :key="student.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div>
                  <p class="font-medium text-gray-900">{{ student.name }}</p>
                  <p class="text-sm text-gray-600">{{ student.email }}</p>
                </div>
                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                  Élève
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <AddTeacherModal v-if="showAddTeacherModal" @close="showAddTeacherModal = false" @success="loadDashboardData" />
    <AddStudentModal v-if="showAddStudentModal" @close="showAddStudentModal = false" @success="loadDashboardData" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

definePageMeta({
    middleware: ['auth']
})

const authStore = useAuthStore()
const club = ref(null)
const stats = ref(null)
const recentTeachers = ref([])
const recentStudents = ref([])
const recentLessons = ref([])
const showAddTeacherModal = ref(false)
const showAddStudentModal = ref(false)

const loadDashboardData = async () => {
  try {
    console.log('🔄 Chargement des données du dashboard club...')
    
    // Utilisation directe de l'URL complète pour contourner le problème de proxy
      const response = await $fetch('http://localhost:8081/api/club/dashboard-test')
    
    console.log('✅ Données reçues:', response)
    
    if (response.success && response.data) {
      club.value = response.data.club
      stats.value = response.data.stats
      recentTeachers.value = response.data.recentTeachers
      recentStudents.value = response.data.recentStudents
      recentLessons.value = response.data.recentLessons || []
      
      console.log('📊 Stats chargées:', stats.value)
    } else {
      console.error('❌ Format de réponse invalide:', response)
    }
  } catch (error) {
    console.error('❌ Erreur lors du chargement des données:', error)
  }
}

onMounted(() => {
  loadDashboardData()
})

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
    cancelled: 'bg-red-100 text-red-800',
    no_show: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'En attente',
    confirmed: 'Confirmé',
    completed: 'Terminé',
    cancelled: 'Annulé',
    no_show: 'Absent'
  }
  return labels[status] || status
}
</script>
