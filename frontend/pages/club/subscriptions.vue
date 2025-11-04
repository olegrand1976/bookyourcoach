<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-6">
    <!-- Header -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Gestion des Abonnements</h1>
            <p class="text-gray-600">Consultez les abonnements créés pour vos élèves</p>
          </div>
          <div class="flex space-x-3">
            <NuxtLink
              to="/club/subscription-templates"
              class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors flex items-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
              <span>Modèles</span>
            </NuxtLink>
            <button 
              @click="handleRecalculateAll"
              :disabled="recalculating"
              class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors flex items-center space-x-2 disabled:opacity-50"
            >
              <svg v-if="recalculating" class="animate-spin h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span>{{ recalculating ? 'Recalcul en cours...' : 'Recalculer les Cours Restants' }}</span>
            </button>
            <button 
              @click="showAssignModal = true"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <span>Créer un Abonnement</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Filtre de recherche -->
      <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="flex items-center space-x-4">
          <div class="flex-1">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
              Rechercher par nom/prénom d'élève
            </label>
            <input
              id="search"
              v-model="searchQuery"
              type="text"
              placeholder="Ex: Jean, Dupont, Jean Dupont..."
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <div class="w-64">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Afficher
            </label>
            <select
              v-model="showOpenSubscriptions"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option :value="false">Abonnements assignés uniquement</option>
              <option :value="true">Tous les abonnements</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Liste des abonnements -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="subscription in filteredSubscriptions" 
          :key="subscription.id"
          @click="viewSubscriptionHistory(subscription)"
          class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all cursor-pointer overflow-hidden border-2 hover:border-blue-400"
        >
          <!-- Header carte -->
          <div class="p-6 border-b border-gray-200">
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                  Abonnement {{ subscription.subscription_number }}
                </h3>
                <p v-if="subscription.template" class="text-sm text-gray-600">
                  Modèle: {{ subscription.template.model_number }}
                </p>
              </div>
              <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                {{ subscription.instances?.length || 0 }} instance(s)
              </span>
            </div>

            <!-- Détails de l'abonnement -->
            <div class="space-y-2" v-if="subscription.template">
              <div class="flex items-center text-sm">
                <svg class="w-4 h-4 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-gray-700">
                  <strong>{{ subscription.template.total_lessons }}</strong> cours
                  <span v-if="subscription.template.free_lessons > 0" class="text-green-600">
                    + {{ subscription.template.free_lessons }} gratuit{{ subscription.template.free_lessons > 1 ? 's' : '' }}
                  </span>
                </span>
              </div>
              
              <div class="flex items-center text-sm">
                <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-gray-700 font-semibold">{{ subscription.template.price }} €</span>
              </div>

              <div class="flex items-center text-sm">
                <svg class="w-4 h-4 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <span class="text-gray-700">Validité: {{ subscription.template.validity_months }} mois</span>
              </div>
            </div>
          </div>

          <!-- Types de cours inclus -->
          <div v-if="subscription.template?.course_types?.length" class="p-4 bg-gray-50">
            <div class="text-xs font-medium text-gray-500 uppercase mb-2">Types de cours inclus</div>
            <div class="flex flex-wrap gap-1">
              <span 
                v-for="courseType in subscription.template.course_types" 
                :key="courseType.id"
                class="bg-white text-gray-700 px-2 py-1 rounded text-xs border border-gray-200"
              >
                {{ courseType.name }}
              </span>
            </div>
          </div>

          <!-- Instances actives avec élèves -->
          <div class="p-4 bg-blue-50 border-t border-blue-100">
            <div class="text-xs font-medium text-blue-700 uppercase mb-2">Élèves avec cet abonnement</div>
            <!-- Liste des instances actives avec élèves -->
            <div v-if="subscription.instances?.length > 0" class="space-y-2 mt-2">
              <div 
                v-for="instance in subscription.instances.slice(0, 3)" 
                :key="instance.id"
                class="text-xs text-gray-700 bg-white rounded px-2 py-2 border border-blue-200"
              >
                <div class="flex items-center justify-between">
                  <span class="font-medium">
                    {{ getInstanceStudentNames(instance) }}
                  </span>
                  <span 
                    :class="{
                      'bg-green-100 text-green-800': instance.status === 'active',
                      'bg-gray-100 text-gray-800': instance.status === 'completed',
                      'bg-red-100 text-red-800': instance.status === 'expired'
                    }"
                    class="px-2 py-1 rounded text-xs"
                  >
                    {{ getStatusLabel(instance.status) }}
                  </span>
                </div>
                <div class="mt-1 text-gray-500">
                  {{ getInstanceLessonsUsed(instance) }} / {{ subscription.template?.total_available_lessons || 0 }} cours utilisés
                </div>
              </div>
              <div v-if="subscription.instances.length > 3" class="text-xs text-gray-500 italic px-2">
                +{{ subscription.instances.length - 3 }} autre(s)...
              </div>
            </div>
            <p v-else class="text-xs text-gray-500 italic">
              Aucun élève assigné
            </p>
          </div>
        </div>
      </div>

      <!-- Message si aucun abonnement -->
      <div v-if="subscriptions.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun abonnement créé</h3>
        <p class="text-gray-600 mb-4">
          Créez des abonnements pour vos élèves en utilisant les modèles d'abonnements.
        </p>
        <div class="flex justify-center gap-3">
          <NuxtLink
            to="/club/subscription-templates"
            class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors inline-flex items-center space-x-2"
          >
            <span>Gérer les modèles</span>
          </NuxtLink>
          <button 
            @click="openAssignModal"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center space-x-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span>Créer un abonnement</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal : Attribuer un abonnement -->
    <AssignSubscriptionModal
      v-if="showAssignModal"
      :student="selectedStudent"
      :show-family-option="true"
      @close="closeAssignModal"
      @success="handleSubscriptionAssigned"
    />

    <!-- Modal : Historique de l'abonnement -->
    <div 
      v-if="showHistoryModal && selectedSubscription"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="closeHistoryModal"
    >
      <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h3 class="text-2xl font-semibold text-gray-900">
                Historique - Abonnement {{ selectedSubscription.subscription_number }}
              </h3>
              <p v-if="selectedSubscription.template" class="text-sm text-gray-600 mt-1">
                Modèle: {{ selectedSubscription.template.model_number }}
              </p>
            </div>
            <button 
              @click="closeHistoryModal"
              class="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Instances d'abonnement -->
          <div v-if="selectedSubscription.instances?.length > 0" class="space-y-6">
            <div 
              v-for="instance in selectedSubscription.instances" 
              :key="instance.id"
              class="border border-gray-200 rounded-lg p-4"
            >
              <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                  <h4 class="font-semibold text-gray-900 mb-2">
                    {{ getInstanceStudentNames(instance) }}
                  </h4>
                  <div class="text-sm text-gray-600 space-y-1">
                    <p>
                      <strong>Début:</strong> {{ formatDate(instance.started_at) }}
                    </p>
                    <p v-if="instance.expires_at">
                      <strong>Expiration:</strong> {{ formatDate(instance.expires_at) }}
                    </p>
                    <p>
                      <strong>Statut:</strong> 
                      <span 
                        :class="{
                          'text-green-600': instance.status === 'active',
                          'text-gray-600': instance.status === 'completed',
                          'text-red-600': instance.status === 'expired'
                        }"
                      >
                        {{ getStatusLabel(instance.status) }}
                      </span>
                    </p>
                  </div>
                </div>
                <div class="text-right">
                  <div class="text-2xl font-bold text-gray-900">
                    {{ getInstanceLessonsUsed(instance) }} / {{ selectedSubscription.template?.total_available_lessons || 0 }}
                  </div>
                  <div class="text-sm text-gray-500">cours utilisés</div>
                </div>
              </div>

              <!-- Liste des cours -->
              <div v-if="instance.lessons && instance.lessons.length > 0" class="mt-4">
                <h5 class="text-sm font-medium text-gray-700 mb-2">Cours consommés:</h5>
                <div class="space-y-2">
                  <div 
                    v-for="lesson in instance.lessons" 
                    :key="lesson.id"
                    class="bg-gray-50 rounded p-3 text-sm"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex-1">
                        <p class="font-medium text-gray-900">
                          {{ formatDate(lesson.start_time) }} à {{ formatTime(lesson.start_time) }}
                        </p>
                        <p class="text-gray-600">
                          {{ lesson.course_type?.name || 'Type de cours non défini' }}
                          <span v-if="lesson.teacher?.user"> - {{ lesson.teacher.user.name }}</span>
                        </p>
                        <p v-if="lesson.location" class="text-gray-500 text-xs mt-1">
                          📍 {{ lesson.location.name }}
                        </p>
                      </div>
                      <span 
                        :class="{
                          'bg-green-100 text-green-800': lesson.status === 'completed',
                          'bg-blue-100 text-blue-800': lesson.status === 'confirmed',
                          'bg-gray-100 text-gray-800': lesson.status === 'cancelled'
                        }"
                        class="px-2 py-1 rounded text-xs font-medium"
                      >
                        {{ lesson.status === 'completed' ? 'Terminé' : lesson.status === 'confirmed' ? 'Confirmé' : 'Annulé' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="mt-4 text-sm text-gray-500 italic">
                Aucun cours consommé pour cette instance
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            Aucune instance d'abonnement trouvée
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useNuxtApp } from '#app'
import { useToast } from '~/composables/useToast'

definePageMeta({
  middleware: ['auth']
})

// État
const subscriptions = ref([])
const availableDisciplines = ref([])
const students = ref([])
const selectedStudent = ref(null)
const searchQuery = ref('')
const showOpenSubscriptions = ref(false) // Par défaut, masquer les abonnements "open"
const recalculating = ref(false)

// Modals
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showAssignModal = ref(false)
const showHistoryModal = ref(false)
const selectedSubscription = ref(null)
const subscriptionHistory = ref(null)

// Formulaires
const form = ref({
  name: '',
  description: '',
  total_lessons: 10,
  free_lessons: 0,
  price: 0,
  validity_months: 12,
  course_type_ids: [],
  is_active: true
})

const assignForm = ref({
  student_ids: [],
  started_at: new Date().toISOString().split('T')[0],
  expires_at: ''
})

const editingSubscription = ref(null)

// Computed
const isFormValid = computed(() => {
  return form.value.name && 
         form.value.total_lessons > 0 && 
         form.value.price >= 0 && 
         form.value.course_type_ids.length > 0
})

// Méthodes
const loadSubscriptions = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/subscriptions')
    if (response.data.success) {
      subscriptions.value = response.data.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement des abonnements:', error)
    alert('Erreur lors du chargement des abonnements')
  }
}

const loadDisciplines = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/disciplines')
    if (response.data.success) {
      availableDisciplines.value = response.data.data || []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des disciplines:', error)
    // Ne pas bloquer si les disciplines ne chargent pas
    availableDisciplines.value = []
  }
}

const loadStudents = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/students')
    if (response.data.success) {
      students.value = response.data.data || []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des élèves:', error)
    // Ne pas bloquer si les élèves ne chargent pas
    students.value = []
  }
}

// Ouvrir le modal d'assignation (sans élève pré-sélectionné, on en choisira un dans le modal)
const openAssignModal = () => {
  // Initialiser avec un objet élève générique pour permettre la sélection dans le modal
  selectedStudent.value = { id: null, name: 'Nouvel abonnement' }
  showAssignModal.value = true
}

const closeAssignModal = () => {
  showAssignModal.value = false
  selectedStudent.value = null
}

const handleSubscriptionAssigned = () => {
  loadSubscriptions()
}

const getActiveSubscribersCount = (subscription) => {
  return subscription.instances?.filter(i => i.status === 'active').length || 0
}

const getActiveInstances = (subscription) => {
  return subscription.instances?.filter(i => i.status === 'active') || []
}

const getInstanceStudentNames = (instance) => {
  if (!instance.students || instance.students.length === 0) {
    return '(Aucun élève)'
  }
  const names = instance.students.map(s => s.user?.name || s.name || 'Élève').join(' & ')
  if (instance.students.length > 1) {
    return `👥 ${names}`
  }
  return names
}

const getStatusLabel = (status) => {
  const labels = {
    'active': 'Actif',
    'completed': 'Terminé',
    'expired': 'Expiré',
    'cancelled': 'Annulé'
  }
  return labels[status] || status
}

const getInstanceLessonsUsed = (instance) => {
  // Utiliser lessons_count si disponible, sinon lessons_used
  if (instance.lessons_count !== undefined) {
    return instance.lessons_count
  }
  // Si lessons existe et est un array, utiliser sa longueur
  if (instance.lessons && Array.isArray(instance.lessons)) {
    return instance.lessons.length
  }
  return instance.lessons_used || 0
}

// Filtrer les abonnements par nom/prénom d'élève ET statut
const filteredSubscriptions = computed(() => {
  let filtered = subscriptions.value
  
  // 1. Filtrer par statut (masquer les abonnements "open" par défaut)
  if (!showOpenSubscriptions.value) {
    filtered = filtered.filter(subscription => {
      // Vérifier si l'abonnement a au moins une instance qui n'est pas "open"
      if (!subscription.instances || subscription.instances.length === 0) {
        return false
      }
      return subscription.instances.some(instance => instance.status !== 'open')
    })
  }
  
  // 2. Filtrer par recherche de nom/prénom
  if (!searchQuery.value.trim()) {
    return filtered
  }
  
  const query = searchQuery.value.toLowerCase().trim()
  
  return filtered.filter(subscription => {
    // Vérifier dans toutes les instances et leurs élèves
    if (!subscription.instances || subscription.instances.length === 0) {
      return false
    }
    
    return subscription.instances.some(instance => {
      // Si l'instance est "open", ne pas chercher dans les élèves (il n'y en a pas)
      if (instance.status === 'open') {
        // Chercher dans le numéro d'abonnement
        return subscription.subscription_number?.toLowerCase().includes(query)
      }
      
      if (!instance.students || instance.students.length === 0) {
        return false
      }
      
      return instance.students.some(student => {
        const user = student.user || {}
        const firstName = (user.first_name || '').toLowerCase()
        const lastName = (user.last_name || '').toLowerCase()
        const name = (user.name || '').toLowerCase()
        
        // Rechercher dans le nom complet, prénom ou nom
        return firstName.includes(query) || 
               lastName.includes(query) || 
               name.includes(query) ||
               `${firstName} ${lastName}`.includes(query)
      })
    })
  })
})

// Vue historique d'un abonnement
const viewSubscriptionHistory = async (subscription) => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get(`/club/subscriptions/${subscription.id}`)
    
    if (response.data.success) {
      selectedSubscription.value = response.data.data
      showHistoryModal.value = true
    }
  } catch (error) {
    console.error('Erreur lors du chargement de l\'historique:', error)
    const { error: showError } = useToast()
    showError('Erreur lors du chargement de l\'historique')
  }
}

const closeHistoryModal = () => {
  showHistoryModal.value = false
  selectedSubscription.value = null
  subscriptionHistory.value = null
}

// Formats de date
const formatDate = (date) => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleDateString('fr-FR', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

const formatTime = (date) => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleTimeString('fr-FR', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

const closeModals = () => {
  showCreateModal.value = false
  showEditModal.value = false
  editingSubscription.value = null
  form.value = {
    name: '',
    description: '',
    total_lessons: 10,
    free_lessons: 0,
    price: 0,
    validity_months: 12,
    course_type_ids: [],
    is_active: true
  }
}

// Recalculer tous les abonnements
const handleRecalculateAll = async () => {
  if (!confirm('Voulez-vous recalculer le nombre de cours restants pour tous les abonnements actifs ?\n\nCette opération va mettre à jour les compteurs en se basant sur l\'historique réel des cours suivis.')) {
    return
  }

  try {
    recalculating.value = true
    const { $api } = useNuxtApp()
    const { success, error } = useToast()
    
    const response = await $api.post('/club/subscriptions/recalculate')
    
    if (response.data.success) {
      const stats = response.data.data
      success(`✅ ${response.data.message}`)
      
      // Afficher les détails si des abonnements ont été mis à jour
      if (stats.total_updated > 0 && stats.details && stats.details.length > 0) {
        console.log('📊 Détails du recalcul:', stats.details)
      }
      
      await loadSubscriptions() // Recharger la liste
    }
  } catch (error) {
    console.error('Erreur lors du recalcul:', error)
    const { error: showError } = useToast()
    showError('Erreur lors du recalcul des abonnements')
  } finally {
    recalculating.value = false
  }
}

// Initialisation
onMounted(async () => {
  await Promise.all([
    loadSubscriptions(),
    loadDisciplines(),
    loadStudents()
  ])
})
</script>

