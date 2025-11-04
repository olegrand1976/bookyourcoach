<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-2xl font-bold text-gray-900">
            Initialiser des Abonnements
          </h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Description -->
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-blue-800">
              <p class="font-medium mb-1">Créer des abonnements "ouverts" en lot</p>
              <p>Ces abonnements seront disponibles pour assignation ultérieure aux élèves. Vous pourrez les attribuer à tout moment depuis la liste des abonnements.</p>
            </div>
          </div>
        </div>

        <!-- Formulaire -->
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Sélection du modèle d'abonnement -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Modèle d'abonnement *
            </label>
            <select
              v-model="form.subscription_template_id"
              required
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">-- Sélectionner un modèle --</option>
              <option v-for="template in templates" :key="template.id" :value="template.id">
                {{ template.model_number }} - {{ template.total_lessons }} cours - {{ template.price }}€
              </option>
            </select>
            
            <!-- Affichage des types de cours du modèle sélectionné -->
            <div v-if="selectedTemplate" class="mt-3 p-3 bg-gray-50 rounded-lg">
              <p class="text-xs font-medium text-gray-500 mb-2">Types de cours inclus :</p>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="courseType in selectedTemplate.course_types"
                  :key="courseType.id"
                  class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full"
                >
                  {{ courseType.name }}
                </span>
              </div>
            </div>
          </div>

          <!-- Quantité -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Nombre d'abonnements à créer *
            </label>
            <input
              v-model.number="form.quantity"
              type="number"
              min="1"
              max="50"
              required
              placeholder="Ex: 10"
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
            <p class="mt-1 text-xs text-gray-500">
              Maximum : 50 abonnements par batch
            </p>
          </div>

          <!-- Date d'ouverture -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Date d'ouverture
            </label>
            <input
              v-model="form.opened_at"
              type="date"
              :min="today"
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
            <p class="mt-1 text-xs text-gray-500">
              Date à partir de laquelle les abonnements seront disponibles (aujourd'hui par défaut)
            </p>
          </div>

          <!-- Résumé -->
          <div v-if="form.subscription_template_id && form.quantity" class="p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="font-medium text-green-900 mb-2">📋 Résumé de l'initialisation</p>
            <ul class="text-sm text-green-800 space-y-1">
              <li><strong>Modèle :</strong> {{ selectedTemplate?.model_number }}</li>
              <li><strong>Quantité :</strong> {{ form.quantity }} abonnement(s)</li>
              <li><strong>Prix unitaire :</strong> {{ selectedTemplate?.price }}€</li>
              <li><strong>Total :</strong> {{ (selectedTemplate?.price || 0) * form.quantity }}€</li>
              <li><strong>Date d'ouverture :</strong> {{ formatDate(form.opened_at || today) }}</li>
            </ul>
          </div>

          <!-- Boutons d'action -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <button
              type="button"
              @click="$emit('close')"
              :disabled="loading"
              class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="loading || !form.subscription_template_id || !form.quantity"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2"
            >
              <svg v-if="loading" class="animate-spin h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span>{{ loading ? 'Initialisation...' : 'Initialiser les Abonnements' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

interface CourseType {
  id: number
  name: string
}

interface SubscriptionTemplate {
  id: number
  model_number: string
  total_lessons: number
  free_lessons: number
  price: number
  course_types?: CourseType[]
}

const props = defineProps<{
  show: boolean
  templates: SubscriptionTemplate[]
}>()

const emit = defineEmits<{
  close: []
  submit: [data: { subscription_template_id: number; quantity: number; opened_at?: string }]
}>()

const loading = ref(false)
const today = computed(() => new Date().toISOString().split('T')[0])

const form = ref({
  subscription_template_id: '' as string | number,
  quantity: 1,
  opened_at: ''
})

// Template sélectionné
const selectedTemplate = computed(() => {
  if (!form.value.subscription_template_id) return null
  return props.templates.find(t => t.id === Number(form.value.subscription_template_id))
})

// Réinitialiser le formulaire quand la modale se ferme
watch(() => props.show, (newShow) => {
  if (!newShow) {
    form.value = {
      subscription_template_id: '',
      quantity: 1,
      opened_at: ''
    }
  }
})

function formatDate(dateString: string): string {
  if (!dateString) return 'Non définie'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', { 
    day: 'numeric', 
    month: 'long', 
    year: 'numeric' 
  })
}

function handleSubmit() {
  if (!form.value.subscription_template_id || !form.value.quantity) {
    return
  }
  
  emit('submit', {
    subscription_template_id: Number(form.value.subscription_template_id),
    quantity: form.value.quantity,
    opened_at: form.value.opened_at || undefined
  })
}
</script>

