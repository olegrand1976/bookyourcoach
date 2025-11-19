<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
    <div class="bg-white rounded-lg max-w-6xl w-full max-h-[90vh] overflow-y-auto my-8">
      <div class="p-6">
        <!-- En-tête -->
        <div class="flex items-center justify-between mb-6 pb-4 border-b">
          <div>
            <h3 class="text-2xl font-bold text-gray-900">
              Modifier les paiements
            </h3>
            <p v-if="paymentData" class="text-sm text-gray-600 mt-1">
              {{ paymentData.period?.month_name }} {{ paymentData.period?.year }} - {{ teacherName }}
            </p>
          </div>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-4 text-gray-500">Chargement des paiements...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
          <p class="text-red-800">{{ error }}</p>
        </div>

        <!-- Payment Details -->
        <div v-else-if="paymentData" class="space-y-6">
          <!-- Totaux -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 rounded-lg p-4">
            <div>
              <p class="text-xs text-gray-600">Total Cours</p>
              <p class="text-lg font-semibold text-gray-900">{{ formatCurrency(paymentData.totals?.total_lessons || 0) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Total Abonnements</p>
              <p class="text-lg font-semibold text-gray-900">{{ formatCurrency(paymentData.totals?.total_subscriptions || 0) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">DCL</p>
              <p class="text-lg font-semibold text-green-600">{{ formatCurrency(paymentData.totals?.total_commissions_dcl || 0) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">NDCL</p>
              <p class="text-lg font-semibold text-purple-600">{{ formatCurrency(paymentData.totals?.total_commissions_ndcl || 0) }}</p>
            </div>
          </div>

          <!-- Actions en masse -->
          <div v-if="paymentData.lessons && paymentData.lessons.length > 0" class="mb-4 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-gray-700">
                  {{ selectedLessons.length }} cours sélectionné(s)
                </span>
                <div class="flex gap-2">
                  <button
                    @click="markSelectedAsPaid"
                    :disabled="selectedLessons.length === 0 || saving"
                    class="px-3 py-1.5 text-sm bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Marquer comme payé
                  </button>
                  <button
                    @click="showUnpaidReasonModal = true"
                    :disabled="selectedLessons.length === 0 || saving"
                    class="px-3 py-1.5 text-sm bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Marquer comme non payé
                  </button>
                  <button
                    @click="markSelectedAsDeferred"
                    :disabled="selectedLessons.length === 0 || saving"
                    class="px-3 py-1.5 text-sm bg-orange-600 text-white rounded hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Reporter au mois suivant
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal pour raison du non-paiement -->
          <div v-if="showUnpaidReasonModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
              <h3 class="text-lg font-semibold mb-4">Raison du non-paiement</h3>
              <textarea
                v-model="unpaidReason"
                rows="4"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg mb-4"
                placeholder="Indiquez la raison du non-paiement..."
              ></textarea>
              <div class="flex justify-end gap-3">
                <button
                  @click="showUnpaidReasonModal = false; unpaidReason = ''"
                  class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                >
                  Annuler
                </button>
                <button
                  @click="markSelectedAsUnpaid"
                  :disabled="!unpaidReason.trim()"
                  class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
                >
                  Confirmer
                </button>
              </div>
            </div>
          </div>

          <!-- Cours individuels -->
          <div v-if="paymentData.lessons && paymentData.lessons.length > 0">
            <h4 class="text-lg font-semibold text-gray-900 mb-3">Cours individuels</h4>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      <input
                        type="checkbox"
                        :checked="allLessonsSelected"
                        @change="toggleSelectAll"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Heure</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Élève</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prix initial</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant payé</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commission</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="lesson in paymentData.lessons" :key="`lesson-${lesson.id}`" 
                      :class="editingItems[`lesson-${lesson.id}`] ? 'bg-blue-50' : (isSelected(lesson.id) ? 'bg-yellow-50' : '')">
                    <td class="px-4 py-3 whitespace-nowrap">
                      <input
                        type="checkbox"
                        :checked="isSelected(lesson.id)"
                        @change="toggleSelect(lesson.id)"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                      {{ formatDate(lesson.date) }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                      {{ lesson.time }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                      {{ lesson.course_type }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                      {{ lesson.student_name }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                      {{ formatCurrency(lesson.price) }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                      <div v-if="editingItems[`lesson-${lesson.id}`]" class="space-y-2">
                        <input
                          v-model.number="editingItems[`lesson-${lesson.id}`].montant"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-24 px-2 py-1 border border-gray-300 rounded text-sm"
                        />
                        <input
                          v-model="editingItems[`lesson-${lesson.id}`].date_paiement"
                          type="date"
                          class="w-32 px-2 py-1 border border-gray-300 rounded text-sm"
                        />
                      </div>
                      <div v-else>
                        <span :class="lesson.is_manual_override ? 'text-orange-600 font-semibold' : 'text-gray-900'">
                          {{ formatCurrency(lesson.montant || lesson.price) }}
                        </span>
                        <span v-if="lesson.is_manual_override" class="ml-1 text-xs text-orange-600">(modifié)</span>
                        <p v-if="lesson.date_paiement" class="text-xs text-gray-500">
                          Payé le {{ formatDate(lesson.date_paiement) }}
                        </p>
                      </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900">
                      {{ formatCurrency(lesson.commission) }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                      <span :class="lesson.est_legacy ? 'px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs font-medium' : 'px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium'">
                        {{ lesson.est_legacy ? 'NDCL' : 'DCL' }}
                      </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                      <span v-if="lesson.date_paiement" class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">
                        Payé
                      </span>
                      <span v-else-if="lesson.non_paiement_reason" class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-medium" :title="lesson.non_paiement_reason">
                        Non payé
                      </span>
                      <span v-else-if="isDeferred(lesson)" class="px-2 py-1 bg-orange-100 text-orange-800 rounded text-xs font-medium">
                        Reporté
                      </span>
                      <span v-else class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-medium">
                        En attente
                      </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                      <div v-if="editingItems[`lesson-${lesson.id}`]" class="flex gap-2">
                        <button
                          @click="saveItem(`lesson-${lesson.id}`, lesson, 'modify')"
                          class="text-green-600 hover:text-green-900"
                          title="Enregistrer"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </button>
                        <button
                          @click="cancelEdit(`lesson-${lesson.id}`)"
                          class="text-gray-600 hover:text-gray-900"
                          title="Annuler"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                        </button>
                      </div>
                      <div v-else class="flex gap-2">
                        <button
                          @click="validateItem(lesson)"
                          class="text-blue-600 hover:text-blue-900"
                          title="Valider le paiement"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </button>
                        <button
                          @click="startEdit(`lesson-${lesson.id}`, lesson)"
                          class="text-yellow-600 hover:text-yellow-900"
                          title="Modifier"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>
                        <button
                          @click="deferItem(lesson)"
                          class="text-orange-600 hover:text-orange-900"
                          title="Reporter au mois suivant"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Abonnements -->
          <div v-if="paymentData.subscriptions && paymentData.subscriptions.length > 0" class="mt-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-3">Abonnements</h4>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date paiement</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Abonnement</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commission</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="subscription in paymentData.subscriptions" :key="`subscription-${subscription.id}`"
                      :class="editingItems[`subscription-${subscription.id}`] ? 'bg-blue-50' : ''">
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                      <div v-if="editingItems[`subscription-${subscription.id}`]">
                        <input
                          v-model="editingItems[`subscription-${subscription.id}`].date_paiement"
                          type="date"
                          class="w-32 px-2 py-1 border border-gray-300 rounded text-sm"
                        />
                      </div>
                      <span v-else>
                        {{ subscription.date_paiement ? formatDate(subscription.date_paiement) : 'Non défini' }}
                      </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                      {{ subscription.subscription_name }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                      <div v-if="editingItems[`subscription-${subscription.id}`]">
                        <input
                          v-model.number="editingItems[`subscription-${subscription.id}`].montant"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-24 px-2 py-1 border border-gray-300 rounded text-sm"
                        />
                      </div>
                      <span v-else>{{ formatCurrency(subscription.montant) }}</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900">
                      {{ formatCurrency(subscription.commission) }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                      <span :class="subscription.est_legacy ? 'px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs font-medium' : 'px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium'">
                        {{ subscription.est_legacy ? 'NDCL' : 'DCL' }}
                      </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                      <div v-if="editingItems[`subscription-${subscription.id}`]" class="flex gap-2">
                        <button
                          @click="saveItem(`subscription-${subscription.id}`, subscription, 'modify')"
                          class="text-green-600 hover:text-green-900"
                          title="Enregistrer"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </button>
                        <button
                          @click="cancelEdit(`subscription-${subscription.id}`)"
                          class="text-gray-600 hover:text-gray-900"
                          title="Annuler"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                        </button>
                      </div>
                      <div v-else class="flex gap-2">
                        <button
                          @click="validateItem(subscription)"
                          class="text-blue-600 hover:text-blue-900"
                          title="Valider le paiement"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </button>
                        <button
                          @click="startEdit(`subscription-${subscription.id}`, subscription)"
                          class="text-yellow-600 hover:text-yellow-900"
                          title="Modifier"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>
                        <button
                          @click="deferItem(subscription)"
                          class="text-orange-600 hover:text-orange-900"
                          title="Reporter au mois suivant"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Message si aucun paiement -->
          <div v-if="(!paymentData.lessons || paymentData.lessons.length === 0) && (!paymentData.subscriptions || paymentData.subscriptions.length === 0)" 
               class="text-center py-12 text-gray-500">
            <p>Aucun paiement trouvé pour cette période</p>
          </div>

          <!-- Actions globales -->
          <div class="flex justify-between items-center pt-6 border-t">
            <button
              @click="reloadReport(false)"
              :disabled="saving"
              class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors disabled:opacity-50"
            >
              Recharger (garder modifications)
            </button>
            <div class="flex gap-3">
              <button
                @click="reloadReport(true)"
                :disabled="saving"
                class="px-4 py-2 text-orange-700 bg-orange-100 rounded-lg hover:bg-orange-200 transition-colors disabled:opacity-50"
              >
                Recharger (réinitialiser)
              </button>
              <button
                @click="$emit('close')"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
              >
                Fermer
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'

interface PaymentItem {
  id: number
  type: 'lesson' | 'subscription'
  montant?: number | null
  date_paiement?: string | null
  [key: string]: any
}

interface PaymentData {
  teacher_id: number
  period: {
    year: number
    month: number
    month_name: string
  }
  lessons: PaymentItem[]
  subscriptions: PaymentItem[]
  totals: {
    total_lessons: number
    total_subscriptions: number
    total_commissions_dcl: number
    total_commissions_ndcl: number
    total_a_payer: number
  }
}

interface Props {
  show: boolean
  year: number
  month: number
  teacherId: number
  teacherName: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'close': []
  'reload': []
}>()

const { $api } = useNuxtApp()
const toast = useToast()

const loading = ref(false)
const error = ref<string | null>(null)
const saving = ref(false)
const paymentData = ref<PaymentData | null>(null)
const editingItems = ref<Record<string, Partial<PaymentItem>>>({})
const selectedLessons = ref<number[]>([])
const showUnpaidReasonModal = ref(false)
const unpaidReason = ref('')

// Charger les données quand la modale s'ouvre
watch(() => props.show, async (newValue) => {
  if (newValue && props.teacherId) {
    await loadPayments()
  } else {
    paymentData.value = null
    editingItems.value = {}
    selectedLessons.value = []
    showUnpaidReasonModal.value = false
    unpaidReason.value = ''
  }
})

async function loadPayments() {
  loading.value = true
  error.value = null
  
  try {
    const response = await $api.get(`/club/payroll/reports/${props.year}/${props.month}/teachers/${props.teacherId}/payments`)
    
    if (response.data?.success) {
      paymentData.value = response.data.data
    } else {
      error.value = response.data?.message || 'Erreur lors du chargement'
    }
  } catch (err: any) {
    console.error('Erreur chargement paiements:', err)
    error.value = err.response?.data?.message || err.message || 'Erreur lors du chargement'
  } finally {
    loading.value = false
  }
}

function formatCurrency(amount: number | null | undefined): string {
  if (amount === null || amount === undefined) return '0,00 €'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2
  }).format(amount)
}

function formatDate(dateString: string | null | undefined): string {
  if (!dateString) return ''
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  }).format(date)
}

function startEdit(key: string, item: PaymentItem) {
  editingItems.value[key] = {
    montant: item.montant ?? item.price ?? item.montant_initial,
    date_paiement: item.date_paiement || null
  }
}

function cancelEdit(key: string) {
  delete editingItems.value[key]
}

async function validateItem(item: PaymentItem) {
  await updateItem(item, 'validate')
}

async function deferItem(item: PaymentItem) {
  if (!confirm('Êtes-vous sûr de vouloir reporter ce paiement au mois suivant ?')) {
    return
  }
  await updateItem(item, 'defer')
}

async function saveItem(key: string, item: PaymentItem, action: string) {
  const editData = editingItems.value[key]
  if (!editData) return

  await updateItem({
    ...item,
    montant: editData.montant,
    date_paiement: editData.date_paiement
  }, action)
  
  delete editingItems.value[key]
}

async function updateItem(item: PaymentItem, action: 'validate' | 'modify' | 'defer') {
  saving.value = true
  
  try {
    const response = await $api.put(
      `/club/payroll/reports/${props.year}/${props.month}/teachers/${props.teacherId}/payments`,
      {
        updates: [{
          id: item.id,
          type: item.type,
          action: action,
          montant: item.montant,
          date_paiement: item.date_paiement
        }]
      }
    )
    
    if (response.data?.success) {
      toast.success('Paiement mis à jour avec succès')
      await loadPayments()
      emit('reload')
    } else {
      toast.error(response.data?.message || 'Erreur lors de la mise à jour')
    }
  } catch (err: any) {
    console.error('Erreur mise à jour paiement:', err)
    toast.error(err.response?.data?.message || err.message || 'Erreur lors de la mise à jour')
  } finally {
    saving.value = false
  }
}

async function reloadReport(resetManualChanges: boolean) {
  saving.value = true
  
  try {
    const response = await $api.post(
      `/club/payroll/reports/${props.year}/${props.month}/reload`,
      { reset_manual_changes: resetManualChanges }
    )
    
    if (response.data?.success) {
      toast.success(resetManualChanges ? 'Rapport rechargé et modifications réinitialisées' : 'Rapport rechargé')
      await loadPayments()
      selectedLessons.value = []
      emit('reload')
    } else {
      toast.error(response.data?.message || 'Erreur lors du rechargement')
    }
  } catch (err: any) {
    console.error('Erreur rechargement rapport:', err)
    toast.error(err.response?.data?.message || err.message || 'Erreur lors du rechargement')
  } finally {
    saving.value = false
  }
}

// Fonctions de sélection
function isSelected(lessonId: number): boolean {
  return selectedLessons.value.includes(lessonId)
}

function toggleSelect(lessonId: number) {
  const index = selectedLessons.value.indexOf(lessonId)
  if (index > -1) {
    selectedLessons.value.splice(index, 1)
  } else {
    selectedLessons.value.push(lessonId)
  }
}

function toggleSelectAll() {
  if (allLessonsSelected.value) {
    selectedLessons.value = []
  } else {
    selectedLessons.value = paymentData.value?.lessons.map(l => l.id) || []
  }
}

const allLessonsSelected = computed(() => {
  if (!paymentData.value?.lessons || paymentData.value.lessons.length === 0) return false
  return paymentData.value.lessons.every(lesson => selectedLessons.value.includes(lesson.id))
})

// Actions en masse
async function markSelectedAsPaid() {
  if (selectedLessons.value.length === 0) return
  
  saving.value = true
  try {
    const updates = selectedLessons.value.map(id => ({
      id,
      type: 'lesson' as const,
      action: 'validate' as const
    }))
    
    const response = await $api.put(
      `/club/payroll/reports/${props.year}/${props.month}/teachers/${props.teacherId}/payments`,
      { updates }
    )
    
    if (response.data?.success) {
      toast.success(`${selectedLessons.value.length} cours marqué(s) comme payé(s)`)
      selectedLessons.value = []
      await loadPayments()
      emit('reload')
    } else {
      toast.error(response.data?.message || 'Erreur lors de la mise à jour')
    }
  } catch (err: any) {
    console.error('Erreur marquage payé:', err)
    toast.error(err.response?.data?.message || err.message || 'Erreur lors de la mise à jour')
  } finally {
    saving.value = false
  }
}

async function markSelectedAsUnpaid() {
  if (selectedLessons.value.length === 0 || !unpaidReason.value.trim()) return
  
  saving.value = true
  try {
    const updates = selectedLessons.value.map(id => ({
      id,
      type: 'lesson' as const,
      action: 'unpaid' as const,
      non_paiement_reason: unpaidReason.value.trim()
    }))
    
    const response = await $api.put(
      `/club/payroll/reports/${props.year}/${props.month}/teachers/${props.teacherId}/payments`,
      { updates }
    )
    
    if (response.data?.success) {
      toast.success(`${selectedLessons.value.length} cours marqué(s) comme non payé(s)`)
      selectedLessons.value = []
      showUnpaidReasonModal.value = false
      unpaidReason.value = ''
      await loadPayments()
      emit('reload')
    } else {
      toast.error(response.data?.message || 'Erreur lors de la mise à jour')
    }
  } catch (err: any) {
    console.error('Erreur marquage non payé:', err)
    toast.error(err.response?.data?.message || err.message || 'Erreur lors de la mise à jour')
  } finally {
    saving.value = false
  }
}

async function markSelectedAsDeferred() {
  if (selectedLessons.value.length === 0) return
  
  if (!confirm(`Êtes-vous sûr de vouloir reporter ${selectedLessons.value.length} cours au mois suivant ?`)) {
    return
  }
  
  saving.value = true
  try {
    const updates = selectedLessons.value.map(id => ({
      id,
      type: 'lesson' as const,
      action: 'defer' as const
    }))
    
    const response = await $api.put(
      `/club/payroll/reports/${props.year}/${props.month}/teachers/${props.teacherId}/payments`,
      { updates }
    )
    
    if (response.data?.success) {
      toast.success(`${selectedLessons.value.length} cours reporté(s) au mois suivant`)
      selectedLessons.value = []
      await loadPayments()
      emit('reload')
    } else {
      toast.error(response.data?.message || 'Erreur lors de la mise à jour')
    }
  } catch (err: any) {
    console.error('Erreur report:', err)
    toast.error(err.response?.data?.message || err.message || 'Erreur lors de la mise à jour')
  } finally {
    saving.value = false
  }
}

function isDeferred(lesson: PaymentItem): boolean {
  if (!lesson.date_paiement) return false
  const paymentDate = new Date(lesson.date_paiement)
  const nextMonth = new Date(props.year, props.month, 1) // Mois suivant
  return paymentDate >= nextMonth
}
</script>

