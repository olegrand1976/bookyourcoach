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

      <!-- Liste des abonnements -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="subscription in subscriptions" 
          :key="subscription.id"
          class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden"
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
                  {{ instance.lessons_used }} / {{ subscription.template?.total_available_lessons || 0 }} cours utilisés
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useNuxtApp } from '#app'

definePageMeta({
  middleware: ['auth']
})

// État
const subscriptions = ref([])
const availableDisciplines = ref([])
const students = ref([])
const selectedStudent = ref(null)

// Modals
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showAssignModal = ref(false)

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

const selectedSubscription = ref(null)
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

const viewSubscriptionDetails = (subscription) => {
  // TODO: Implémenter la vue détaillée avec la liste des abonnés
  alert('Fonctionnalité à venir : vue détaillée des abonnés')
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

// Initialisation
onMounted(async () => {
  await Promise.all([
    loadSubscriptions(),
    loadDisciplines(),
    loadStudents()
  ])
})
</script>

