<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-6">
    <!-- Header -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Gestion des Abonnements</h1>
            <p class="text-gray-600">Créez et gérez les formules d'abonnement pour vos élèves</p>
          </div>
          <button 
            @click="showCreateModal = true"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span>Nouvel Abonnement</span>
          </button>
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
                <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ subscription.name }}</h3>
                <p v-if="subscription.description" class="text-sm text-gray-600">{{ subscription.description }}</p>
              </div>
              <span 
                :class="subscription.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                class="px-2 py-1 text-xs font-medium rounded-full"
              >
                {{ subscription.is_active ? 'Actif' : 'Inactif' }}
              </span>
            </div>

            <!-- Détails de l'abonnement -->
            <div class="space-y-2">
              <div class="flex items-center text-sm">
                <svg class="w-4 h-4 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-gray-700">
                  <strong>{{ subscription.total_lessons }}</strong> cours
                  <span v-if="subscription.free_lessons > 0" class="text-green-600">
                    + {{ subscription.free_lessons }} gratuit{{ subscription.free_lessons > 1 ? 's' : '' }}
                  </span>
                </span>
              </div>
              
              <div class="flex items-center text-sm">
                <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-gray-700 font-semibold">{{ subscription.price }} €</span>
              </div>

              <div class="flex items-center text-sm">
                <svg class="w-4 h-4 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <span class="text-gray-700">{{ subscription.course_types?.length || 0 }} type(s) de cours</span>
              </div>
            </div>
          </div>

          <!-- Types de cours inclus -->
          <div class="p-4 bg-gray-50">
            <div class="text-xs font-medium text-gray-500 uppercase mb-2">Types de cours inclus</div>
            <div class="flex flex-wrap gap-1">
              <span 
                v-for="courseType in subscription.course_types" 
                :key="courseType.id"
                class="bg-white text-gray-700 px-2 py-1 rounded text-xs border border-gray-200"
              >
                {{ courseType.name }}
              </span>
              <span v-if="!subscription.course_types?.length" class="text-xs text-gray-500 italic">
                Aucun type défini
              </span>
            </div>
          </div>

          <!-- Abonnés actifs -->
          <div class="p-4 bg-blue-50 border-t border-blue-100">
            <div class="flex items-center justify-between text-sm mb-2">
              <span class="text-gray-700">
                <strong>{{ getActiveSubscribersCount(subscription) }}</strong> abonné(s) actif(s)
              </span>
              <button 
                @click="viewSubscriptionDetails(subscription)"
                class="text-blue-600 hover:text-blue-800 text-xs font-medium"
              >
                Voir détails →
              </button>
            </div>
            <!-- Liste des instances actives avec élèves -->
            <div v-if="getActiveInstances(subscription).length > 0" class="space-y-1 mt-2">
              <div 
                v-for="instance in getActiveInstances(subscription).slice(0, 3)" 
                :key="instance.id"
                class="text-xs text-gray-600 bg-white rounded px-2 py-1"
              >
                <span class="font-medium">{{ getInstanceStudentNames(instance) }}</span>
                <span class="text-gray-500 ml-1">
                  ({{ instance.lessons_used }}/{{ subscription.total_lessons + subscription.free_lessons }} cours)
                </span>
              </div>
              <div v-if="getActiveInstances(subscription).length > 3" class="text-xs text-gray-500 italic px-2">
                +{{ getActiveInstances(subscription).length - 3 }} autre(s)...
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="p-4 bg-white border-t border-gray-200 flex justify-end gap-2">
            <button 
              @click="editSubscription(subscription)"
              class="text-blue-600 hover:text-blue-800 text-sm font-medium"
            >
              Modifier
            </button>
            <button 
              @click="assignSubscription(subscription)"
              class="text-green-600 hover:text-green-800 text-sm font-medium"
            >
              Attribuer
            </button>
            <button 
              @click="deleteSubscription(subscription)"
              class="text-red-600 hover:text-red-800 text-sm font-medium"
            >
              Supprimer
            </button>
          </div>
        </div>
      </div>

      <!-- Message si aucun abonnement -->
      <div v-if="subscriptions.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun abonnement</h3>
        <p class="text-gray-600 mb-4">Commencez par créer votre premier abonnement</p>
        <button 
          @click="showCreateModal = true"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center space-x-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          <span>Créer un abonnement</span>
        </button>
      </div>
    </div>

    <!-- Modal : Créer/Modifier un abonnement -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          {{ showEditModal ? 'Modifier l\'abonnement' : 'Nouvel abonnement' }}
        </h3>
        
        <div class="space-y-4">
          <!-- Nom -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nom de l'abonnement *</label>
            <input 
              v-model="form.name"
              type="text" 
              placeholder="Ex: Formule 10 cours"
              class="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea 
              v-model="form.description"
              rows="2"
              placeholder="Décrivez brièvement cet abonnement"
              class="w-full border border-gray-300 rounded-lg px-3 py-2"
            ></textarea>
          </div>

          <!-- Nombre de cours -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de cours *</label>
              <input 
                v-model.number="form.total_lessons"
                type="number" 
                min="1"
                class="w-full border border-gray-300 rounded-lg px-3 py-2"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Cours gratuits offerts</label>
              <input 
                v-model.number="form.free_lessons"
                type="number" 
                min="0"
                class="w-full border border-gray-300 rounded-lg px-3 py-2"
              />
            </div>
          </div>

          <!-- Prix -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Prix de l'abonnement (€) *</label>
            <input 
              v-model.number="form.price"
              type="number" 
              min="0"
              step="0.01"
              class="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
            <p class="text-xs text-gray-500 mt-1">
              <span v-if="form.total_lessons > 0 && form.price > 0">
                Prix par cours : {{ (form.price / form.total_lessons).toFixed(2) }} €
              </span>
            </p>
          </div>

          <!-- Types de cours -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Types de cours inclus *</label>
            <div class="border border-gray-300 rounded-lg p-3 max-h-48 overflow-y-auto space-y-2">
              <label 
                v-for="discipline in availableDisciplines" 
                :key="discipline.id"
                class="flex items-center space-x-2 hover:bg-gray-50 p-2 rounded cursor-pointer"
              >
                <input 
                  type="checkbox" 
                  :value="discipline.id"
                  v-model="form.course_type_ids"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-sm text-gray-700">{{ discipline.name }}</span>
              </label>
              <p v-if="availableDisciplines.length === 0" class="text-sm text-gray-500 italic">
                Aucun type de cours disponible
              </p>
            </div>
          </div>

          <!-- Statut actif -->
          <div class="flex items-center space-x-2">
            <input 
              type="checkbox" 
              v-model="form.is_active"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <label class="text-sm text-gray-700">Abonnement actif (proposé aux élèves)</label>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
          <button 
            @click="closeModals"
            class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
          >
            Annuler
          </button>
          <button 
            @click="showEditModal ? updateSubscription() : createSubscription()"
            :disabled="!isFormValid"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ showEditModal ? 'Mettre à jour' : 'Créer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal : Attribuer un abonnement à un ou plusieurs élèves -->
    <div v-if="showAssignModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
          Attribuer : {{ selectedSubscription?.name }}
        </h3>
        <p class="text-sm text-gray-600 mb-4">
          💡 Vous pouvez sélectionner plusieurs élèves pour un abonnement familial partagé
        </p>
        
        <div class="space-y-4">
          <!-- Sélection multiple d'élèves -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Élève(s) * 
              <span class="text-xs text-gray-500">({{ assignForm.student_ids.length }} sélectionné{{ assignForm.student_ids.length > 1 ? 's' : '' }})</span>
            </label>
            <div class="border border-gray-300 rounded-lg p-3 max-h-48 overflow-y-auto space-y-2">
              <label 
                v-for="student in students" 
                :key="student.id"
                class="flex items-center space-x-2 hover:bg-gray-50 p-2 rounded cursor-pointer"
              >
                <input 
                  type="checkbox" 
                  :value="student.id"
                  v-model="assignForm.student_ids"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-sm text-gray-700">{{ student.name }}</span>
              </label>
              <p v-if="students.length === 0" class="text-sm text-gray-500 italic">
                Aucun élève disponible
              </p>
            </div>
            <p v-if="assignForm.student_ids.length > 1" class="text-xs text-blue-600 mt-2">
              ℹ️ Abonnement familial : les {{ assignForm.student_ids.length }} élèves partageront le même pool de cours
            </p>
          </div>

          <!-- Date de début -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Date de début *</label>
            <input 
              v-model="assignForm.started_at"
              type="date" 
              class="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
          </div>

          <!-- Date d'expiration -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Date d'expiration (optionnel)</label>
            <input 
              v-model="assignForm.expires_at"
              type="date" 
              :min="assignForm.started_at"
              class="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
            <p class="text-xs text-gray-500 mt-1">Si non renseignée, l'abonnement n'expire pas</p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
          <button 
            @click="showAssignModal = false"
            class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
          >
            Annuler
          </button>
          <button 
            @click="confirmAssign"
            :disabled="assignForm.student_ids.length === 0 || !assignForm.started_at"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Attribuer
          </button>
        </div>
      </div>
    </div>
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
      availableDisciplines.value = response.data.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement des disciplines:', error)
  }
}

const loadStudents = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/students')
    if (response.data.success) {
      students.value = response.data.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement des élèves:', error)
  }
}

const createSubscription = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.post('/club/subscriptions', form.value)
    if (response.data.success) {
      await loadSubscriptions()
      closeModals()
      alert('Abonnement créé avec succès')
    }
  } catch (error) {
    console.error('Erreur lors de la création:', error)
    if (error.response?.data?.errors) {
      const errorMessages = Object.values(error.response.data.errors).flat().join('\n')
      alert(`Erreur de validation:\n${errorMessages}`)
    } else {
      alert('Erreur lors de la création de l\'abonnement')
    }
  }
}

const editSubscription = (subscription) => {
  editingSubscription.value = subscription
  form.value = {
    name: subscription.name,
    description: subscription.description || '',
    total_lessons: subscription.total_lessons,
    free_lessons: subscription.free_lessons,
    price: parseFloat(subscription.price),
    course_type_ids: subscription.course_types?.map(ct => ct.id) || [],
    is_active: subscription.is_active
  }
  showEditModal.value = true
}

const updateSubscription = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.put(`/club/subscriptions/${editingSubscription.value.id}`, form.value)
    if (response.data.success) {
      await loadSubscriptions()
      closeModals()
      alert('Abonnement mis à jour avec succès')
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour:', error)
    alert('Erreur lors de la mise à jour de l\'abonnement')
  }
}

const deleteSubscription = async (subscription) => {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer l'abonnement "${subscription.name}" ?`)) {
    return
  }

  try {
    const { $api } = useNuxtApp()
    const response = await $api.delete(`/club/subscriptions/${subscription.id}`)
    if (response.data.success) {
      await loadSubscriptions()
      alert('Abonnement supprimé avec succès')
    }
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
    if (error.response?.status === 422) {
      alert(error.response.data.message || 'Impossible de supprimer cet abonnement')
    } else {
      alert('Erreur lors de la suppression de l\'abonnement')
    }
  }
}

const assignSubscription = (subscription) => {
  selectedSubscription.value = subscription
  assignForm.value = {
    student_ids: [],
    started_at: new Date().toISOString().split('T')[0],
    expires_at: ''
  }
  showAssignModal.value = true
}

const confirmAssign = async () => {
  try {
    const { $api } = useNuxtApp()
    const payload = {
      subscription_id: selectedSubscription.value.id,
      ...assignForm.value
    }
    
    // Ne pas envoyer expires_at si vide
    if (!payload.expires_at) {
      delete payload.expires_at
    }

    const response = await $api.post('/club/subscriptions/assign', payload)
    if (response.data.success) {
      await loadSubscriptions()
      showAssignModal.value = false
      const studentCount = assignForm.value.student_ids.length
      const message = studentCount > 1 
        ? `Abonnement familial créé avec succès pour ${studentCount} élèves` 
        : 'Abonnement attribué avec succès à l\'élève'
      alert(message)
    }
  } catch (error) {
    console.error('Erreur lors de l\'attribution:', error)
    if (error.response?.data?.errors) {
      const errorMessages = Object.values(error.response.data.errors).flat().join('\n')
      alert(`Erreur de validation:\n${errorMessages}`)
    } else {
      alert('Erreur lors de l\'attribution de l\'abonnement')
    }
  }
}

const getActiveSubscribersCount = (subscription) => {
  return subscription.subscription_students?.filter(s => s.status === 'active').length || 0
}

const getActiveInstances = (subscription) => {
  return subscription.subscription_students?.filter(s => s.status === 'active') || []
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

