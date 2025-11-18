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
            <NuxtLink
              to="/club/recurring-slots"
              class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors flex items-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              <span>Créneaux Récurrents</span>
            </NuxtLink>
            <NuxtLink
              to="/club/payroll"
              class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors flex items-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>Rapports de Paie</span>
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

      <!-- Filtres -->
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
              Filtrer par statut
            </label>
            <select
              v-model="statusFilter"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="all">Tous les abonnements</option>
              <option value="normal">✅ Normal (< 70%)</option>
              <option value="warning">⚠️ Approchant (70-89%)</option>
              <option value="urgent">🚨 Urgent (≥ 90%)</option>
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
                <span class="text-gray-700">Validité: {{ formatValidity(subscription.template) }}</span>
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
                :class="[
                  'text-xs rounded px-2 py-2 border',
                  getInstanceColorClass(instance, subscription.template)
                ]"
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
                
                <!-- Progression avec code couleur -->
                <div class="mt-1 flex items-center justify-between">
                  <span :class="getUsageTextColor(instance, subscription.template)">
                    <strong>{{ getInstanceLessonsUsed(instance) }} / {{ subscription.template?.total_available_lessons || 0 }}</strong> cours utilisés
                    <span v-if="getUsagePercentage(instance, subscription.template) >= 70" class="ml-1">
                      ({{ getUsagePercentage(instance, subscription.template) }}%)
                    </span>
                  </span>
                </div>
                
                <!-- Période de validité -->
                <div v-if="instance.started_at || instance.expires_at || instance.created_at" class="mt-2 pt-2 border-t border-gray-200 text-xs text-gray-600">
                  <div v-if="instance.created_at" class="flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Créé: {{ formatDate(instance.created_at) }}
                  </div>
                  <div v-if="instance.started_at" class="flex items-center mt-1">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Début: {{ formatDate(instance.started_at) }}
                  </div>
                  <div v-if="instance.expires_at" class="flex items-center mt-1">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span :class="isExpiringSoon(instance) ? 'text-red-600 font-semibold' : ''">
                      Expire: {{ formatDate(instance.expires_at) }}
                    </span>
                  </div>
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
      <div v-if="filteredSubscriptions.length === 0 && subscriptions.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
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
                    <p v-if="instance.created_at">
                      <strong>Créé:</strong> {{ formatDate(instance.created_at) }}
                    </p>
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

              <!-- Récurrences planifiées -->
              <div v-if="instance.legacy_recurring_slots && instance.legacy_recurring_slots.length > 0" class="mt-6 pt-4 border-t border-gray-200">
                <h5 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                  Créneaux récurrents planifiés ({{ instance.legacy_recurring_slots.length }})
                </h5>
                <div class="space-y-2">
                  <div 
                    v-for="recurring in instance.legacy_recurring_slots" 
                    :key="recurring.id"
                    class="bg-purple-50 rounded-lg p-3 text-sm border border-purple-200"
                  >
                    <div class="flex items-start justify-between">
                      <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                          <span class="text-lg">{{ getDayEmoji(recurring.day_of_week) }}</span>
                          <span class="font-medium text-gray-900">{{ getDayName(recurring.day_of_week) }}</span>
                          <span class="text-gray-600">
                            {{ formatTimeOnly(recurring.start_time) }} - {{ formatTimeOnly(recurring.end_time) }}
                          </span>
                        </div>
                        <div class="text-xs text-gray-600 space-y-1 mt-2">
                          <p v-if="recurring.student?.user">
                            👤 <strong>Élève:</strong> {{ recurring.student.user.name }}
                          </p>
                          <p v-if="recurring.teacher?.user">
                            🎓 <strong>Enseignant:</strong> {{ recurring.teacher.user.name }}
                          </p>
                          <p>
                            📅 <strong>Du</strong> {{ formatDate(recurring.start_date) }} 
                            <strong>au</strong> {{ formatDate(recurring.end_date) }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="mt-6 pt-4 border-t border-gray-200">
                <p class="text-sm text-gray-500 italic">
                  Aucun créneau récurrent planifié pour cette instance
                </p>
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

console.log('🚀🚀🚀 FICHIER SUBSCRIPTIONS.VUE CHARGÉ - VERSION DEBUG ACTIVE 🚀🚀🚀')
console.log('🚀 Timestamp:', new Date().toISOString())
console.log('🚀 [SUBSCRIPTIONS] Script setup exécuté')

definePageMeta({
  middleware: ['auth']
})

// État
const subscriptions = ref([])
const availableDisciplines = ref([])
const students = ref([])
const selectedStudent = ref(null)
const searchQuery = ref('')
const statusFilter = ref('all') // Filtre par statut: all, normal, warning, urgent

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
  console.log('📦 [SUBSCRIPTIONS] loadSubscriptions appelé')
  try {
    const { $api } = useNuxtApp()
    console.log('📦 [SUBSCRIPTIONS] $api disponible:', !!$api)
    console.log('📦 [SUBSCRIPTIONS] Appel API vers /club/subscriptions')
    const response = await $api.get('/club/subscriptions')
    console.log('📦 [SUBSCRIPTIONS] Réponse API reçue:', response)
    console.log('📦 [SUBSCRIPTIONS] Réponse API data:', response.data)
    if (response.data.success) {
      subscriptions.value = response.data.data || []
      console.log('📦 [SUBSCRIPTIONS] Abonnements chargés:', subscriptions.value.length)
      console.log('📦 [SUBSCRIPTIONS] Détails:', subscriptions.value)
    } else {
      console.error('📦 [SUBSCRIPTIONS] Réponse non réussie:', response.data)
      subscriptions.value = []
    }
  } catch (error) {
    console.error('📦 [SUBSCRIPTIONS] Erreur lors du chargement des abonnements:', error)
    console.error('📦 [SUBSCRIPTIONS] Erreur complète:', error.response?.data || error.message)
    subscriptions.value = []
    // Ne pas afficher d'alert pour ne pas bloquer l'interface
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
  if (!instance || !instance.students || instance.students.length === 0) {
    return '(Aucun élève)'
  }
  
  const names = instance.students.map(s => {
    if (!s) {
      return 'Élève'
    }
    
    // Essayer d'abord le nom de l'utilisateur (si l'élève a un compte)
    if (s.user && s.user.name) {
      return s.user.name
    }
    
    // Utiliser first_name et last_name de l'élève
    // Les données viennent directement de l'API avec ces clés exactes
    const firstName = s.first_name || ''
    const lastName = s.last_name || ''
    const fullName = `${firstName} ${lastName}`.trim()
    
    if (fullName) {
      return fullName
    }
    
    // En dernier recours, utiliser le nom de l'utilisateur si disponible
    if (s.name) {
      return s.name
    }
    
    // Si vraiment rien n'est disponible, retourner "Élève"
    return 'Élève'
  }).join(' & ')
  
  // Si on n'a toujours rien trouvé
  if (!names || names.trim() === '') {
    return 'Élève'
  }
  
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
  // ⚠️ IMPORTANT : Utiliser lessons_used en PRIORITÉ car c'est la valeur source de vérité
  // Cette valeur peut être manuelle (entrée lors de la création) ou calculée automatiquement
  // Ne pas utiliser lessons.length car cela ne reflète que les cours chargés dans la relation,
  // pas nécessairement tous les cours attachés dans subscription_lessons
  
  // Priorité 1 : lessons_used (valeur source de vérité, peut être manuelle)
  if (instance.lessons_used !== undefined && instance.lessons_used !== null) {
    return instance.lessons_used
  }
  
  // Priorité 2 : lessons_count (si fourni par l'API)
  if (instance.lessons_count !== undefined && instance.lessons_count !== null) {
    return instance.lessons_count
  }
  
  // Priorité 3 : Compter les cours dans le tableau (fallback)
  if (instance.lessons && Array.isArray(instance.lessons)) {
    return instance.lessons.length
  }
  
  // Par défaut : 0
  return 0
}

// Déterminer le statut d'une instance pour le filtrage
const getInstanceStatus = (instance, template) => {
  const percentage = getUsagePercentage(instance, template)
  if (percentage >= 90) return 'urgent'
  if (percentage >= 70) return 'warning'
  return 'normal'
}

// Fonction helper pour convertir le statut en priorité numérique
const getUrgencyPriority = (status) => {
  if (status === 'urgent') return 3
  if (status === 'warning') return 2
  return 1 // normal
}

// Fonction helper pour obtenir l'instance la plus urgente d'un abonnement
const getMostUrgentInstance = (subscription) => {
  if (!subscription.instances || subscription.instances.length === 0) {
    return null
  }
  
  const activeInstances = subscription.instances.filter(i => i.status === 'active')
  if (activeInstances.length === 0) return null
  
  // Trouver l'instance la plus urgente en considérant :
  // 1. La priorité (urgent > warning > normal)
  // 2. À priorité égale, le pourcentage le plus élevé
  // 3. À pourcentage égal, la date d'expiration la plus proche
  let mostUrgent = activeInstances[0]
  let highestPriority = getUrgencyPriority(getInstanceStatus(mostUrgent, subscription.template))
  let highestPercentage = getUsagePercentage(mostUrgent, subscription.template)
  
  activeInstances.forEach(instance => {
    const priority = getUrgencyPriority(getInstanceStatus(instance, subscription.template))
    const percentage = getUsagePercentage(instance, subscription.template)
    
    // Priorité plus haute = toujours sélectionner
    if (priority > highestPriority) {
      highestPriority = priority
      highestPercentage = percentage
      mostUrgent = instance
    } 
    // Même priorité mais pourcentage plus élevé = sélectionner
    else if (priority === highestPriority && percentage > highestPercentage) {
      highestPercentage = percentage
      mostUrgent = instance
    }
    // Même priorité et même pourcentage, vérifier la date d'expiration
    else if (priority === highestPriority && percentage === highestPercentage) {
      const currentExpires = mostUrgent.expires_at ? new Date(mostUrgent.expires_at).getTime() : Infinity
      const newExpires = instance.expires_at ? new Date(instance.expires_at).getTime() : Infinity
      if (newExpires < currentExpires) {
        mostUrgent = instance
      }
    }
  })
  
  return mostUrgent
}

// Filtrer les abonnements par nom/prénom d'élève ET statut d'utilisation
const filteredSubscriptions = computed(() => {
  let filtered = subscriptions.value
  
  console.log('🔍 [DEBUG TRI] Abonnements bruts reçus:', filtered.length)
  console.log('🔍 [DEBUG TRI] Abonnements bruts:', filtered)
  
  // 1. Filtrer par statut d'utilisation (couleur)
  if (statusFilter.value !== 'all') {
    filtered = filtered.filter(subscription => {
      if (!subscription.instances || subscription.instances.length === 0) {
        return false
      }
      // Un abonnement est inclus si AU MOINS UNE de ses instances correspond au filtre
      return subscription.instances.some(instance => {
        if (instance.status !== 'active') return false
        const instanceStatus = getInstanceStatus(instance, subscription.template)
        return instanceStatus === statusFilter.value
      })
    })
  }
  
  // 2. Filtrer par recherche de nom/prénom
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim()
    
    filtered = filtered.filter(subscription => {
      // Vérifier dans toutes les instances et leurs élèves
      if (!subscription.instances || subscription.instances.length === 0) {
        return false
      }
      
      return subscription.instances.some(instance => {
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
  }
  
  console.log('🔍 [DEBUG TRI] Après filtrage:', filtered.length)
  
  // 3. Tri par urgence décroissante, puis date d'expiration croissante
  // IMPORTANT: Créer une copie avant de trier pour que Vue détecte les changements
  const sorted = [...filtered].sort((a, b) => {
    const instanceA = getMostUrgentInstance(a)
    const instanceB = getMostUrgentInstance(b)
    
    // Si pas d'instance active, mettre à la fin
    if (!instanceA && !instanceB) return 0
    if (!instanceA) return 1
    if (!instanceB) return -1
    
    const priorityA = getUrgencyPriority(getInstanceStatus(instanceA, a.template))
    const priorityB = getUrgencyPriority(getInstanceStatus(instanceB, b.template))
    const percentageA = getUsagePercentage(instanceA, a.template)
    const percentageB = getUsagePercentage(instanceB, b.template)
    
    console.log(`📊 [DEBUG TRI] Comparaison:
      ${a.subscription_number} (P:${priorityA}, %:${percentageA}, Inst:${instanceA?.id})
      vs
      ${b.subscription_number} (P:${priorityB}, %:${percentageB}, Inst:${instanceB?.id})`)
    
    // Tri par urgence décroissante (priorité la plus haute en premier)
    if (priorityA !== priorityB) {
      console.log(`  → Tri par priorité: ${priorityB - priorityA}`)
      return priorityB - priorityA
    }
    
    // Si même urgence, trier par date d'expiration croissante (plus proche en premier)
    const expiresA = instanceA.expires_at ? new Date(instanceA.expires_at).getTime() : Infinity
    const expiresB = instanceB.expires_at ? new Date(instanceB.expires_at).getTime() : Infinity
    
    console.log(`  → Même priorité, tri par date: ${expiresA - expiresB}`)
    return expiresA - expiresB
  })
  
  console.log('🎯 [DEBUG TRI] ORDRE FINAL:')
  sorted.forEach((sub, index) => {
    const instance = getMostUrgentInstance(sub)
    if (instance) {
      const priority = getUrgencyPriority(getInstanceStatus(instance, sub.template))
      const percentage = getUsagePercentage(instance, sub.template)
      console.log(`  ${index + 1}. ${sub.subscription_number} - Priorité:${priority} - ${percentage}% - Instance:${instance.id}`)
    }
  })
  
  return sorted
})

// Vue historique d'un abonnement
const viewSubscriptionHistory = async (subscription) => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get(`/club/subscriptions/${subscription.id}`)
    
    if (response.data.success) {
      selectedSubscription.value = response.data.data
      
      // Debug: vérifier les données reçues
      console.log('🔍 [viewSubscriptionHistory] Subscription data:', selectedSubscription.value)
      if (selectedSubscription.value.instances) {
        selectedSubscription.value.instances.forEach((inst, idx) => {
          console.log(`🔍 [viewSubscriptionHistory] Instance ${idx} (ID: ${inst.id}):`, inst)
          if (inst.students) {
            inst.students.forEach((student, sIdx) => {
              console.log(`🔍 [viewSubscriptionHistory] Student ${sIdx} in instance ${idx}:`, {
                id: student.id,
                first_name: student.first_name,
                last_name: student.last_name,
                user: student.user,
                allKeys: Object.keys(student)
              })
            })
          }
        })
      }
      
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

// Formats de date (voir plus bas pour la fonction formatDate)

const formatTime = (date) => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleTimeString('fr-FR', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

// Formater uniquement une heure (format HH:mm:ss ou HH:mm)
const formatTimeOnly = (time) => {
  if (!time) return 'N/A'
  // Si c'est déjà au format HH:mm, le retourner tel quel
  if (typeof time === 'string' && time.match(/^\d{2}:\d{2}/)) {
    return time.substring(0, 5) // Retourner HH:mm
  }
  // Sinon, essayer de parser comme une date
  try {
    const d = new Date(time)
    if (!isNaN(d.getTime())) {
      return d.toLocaleTimeString('fr-FR', { 
        hour: '2-digit', 
        minute: '2-digit' 
      })
    }
  } catch (e) {
    // Ignorer l'erreur
  }
  return time
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

// Obtenir le pourcentage d'utilisation
const getUsagePercentage = (instance, template) => {
  if (!template || !template.total_available_lessons) return 0
  const lessonsUsed = getInstanceLessonsUsed(instance)
  return Math.round((lessonsUsed / template.total_available_lessons) * 100)
}

// Obtenir la classe de couleur pour l'instance selon le pourcentage
const getInstanceColorClass = (instance, template) => {
  const percentage = getUsagePercentage(instance, template)
  
  if (percentage >= 90) {
    // Rouge foncé : >90% (renouvellement urgent)
    return 'bg-red-100 border-red-300 text-red-900'
  } else if (percentage >= 70) {
    // Rouge clair : >70% (approchant de la fin)
    return 'bg-orange-50 border-orange-300 text-orange-900'
  } else {
    // Blanc : <70% (normal)
    return 'bg-white border-blue-200 text-gray-700'
  }
}

// Obtenir la couleur du texte d'utilisation
const getUsageTextColor = (instance, template) => {
  const percentage = getUsagePercentage(instance, template)
  
  if (percentage >= 90) {
    return 'text-red-700 font-bold'
  } else if (percentage >= 70) {
    return 'text-orange-700 font-semibold'
  } else {
    return 'text-gray-600'
  }
}

// Formater une date
const formatDate = (date) => {
  if (!date) return '-'
  const d = new Date(date)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const getDayName = (dayOfWeek) => {
  const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
  return days[dayOfWeek] || 'Jour inconnu'
}

const getDayEmoji = (dayOfWeek) => {
  const emojis = ['☀️', '📅', '📅', '📅', '📅', '📅', '🎉']
  return emojis[dayOfWeek] || '📅'
}

// Vérifier si l'abonnement expire bientôt (moins de 30 jours)
const isExpiringSoon = (instance) => {
  if (!instance.expires_at) return false
  const expiresAt = new Date(instance.expires_at)
  const now = new Date()
  const diffTime = expiresAt - now
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays <= 30 && diffDays >= 0
}

// Formater l'affichage de la validité en fonction du modèle (semaines ou mois)
const formatValidity = (template) => {
  if (!template) return 'N/A'
  
  // Utiliser validity_value et validity_unit si disponibles (vérifier explicitement null/undefined)
  if (template.validity_value != null && template.validity_unit != null) {
    if (template.validity_unit === 'weeks') {
      return `${template.validity_value} semaine${template.validity_value > 1 ? 's' : ''}`
    } else {
      return `${template.validity_value} mois`
    }
  }
  
  // Fallback pour les anciens modèles sans validity_value/validity_unit
  const months = template.validity_months || 12
  if (months < 3) {
    const weeks = Math.round(months * 4.33)
    return `${weeks} semaine${weeks > 1 ? 's' : ''} (${months} mois)`
  }
  return `${months} mois`
}

// Initialisation
onMounted(async () => {
  console.log('🚀 [SUBSCRIPTIONS] onMounted appelé')
  try {
    await Promise.all([
      loadSubscriptions(),
      loadDisciplines(),
      loadStudents()
    ])
    console.log('🚀 [SUBSCRIPTIONS] Toutes les données chargées')
  } catch (error) {
    console.error('🚀 [SUBSCRIPTIONS] Erreur lors du chargement initial:', error)
  }
})
</script>

