<template>
  <div class="bg-white rounded-2xl shadow-lg border border-gray-200 w-full mt-4">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-500 to-pink-600 px-6 py-4 rounded-t-xl">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="bg-white bg-opacity-20 p-2 rounded-lg">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
          </div>
          <div>
            <h3 class="text-xl font-bold text-white">Ajouter une spécialité personnalisée</h3>
            <p class="text-purple-100 text-sm">Créez une spécialité unique pour votre club</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Contenu -->
    <form @submit.prevent="addCustomSpecialty" class="p-6 space-y-8">
      
      <!-- Section Informations de base -->
      <div class="bg-gray-50 rounded-xl p-6">
        <div class="flex items-center mb-4">
          <div class="bg-purple-100 p-2 rounded-lg mr-3">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <h4 class="text-lg font-semibold text-gray-900">Informations de base</h4>
        </div>
        
        <div class="space-y-6">
          <!-- Activity Selection -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Activité <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.activity_id"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
            >
              <option value="">Sélectionnez une activité</option>
              <option 
                v-for="activity in availableActivities" 
                :key="activity.id" 
                :value="activity.id"
              >
                {{ activity.icon }} {{ activity.name }}
              </option>
            </select>
          </div>

          <!-- Specialty Name -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Nom de la spécialité <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="Ex: Cours particuliers, Initiation poney..."
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
            />
          </div>

          <!-- Description -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Description
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Décrivez cette spécialité..."
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors resize-none"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Section Configuration -->
      <div class="bg-gray-50 rounded-xl p-6">
        <div class="flex items-center mb-4">
          <div class="bg-pink-100 p-2 rounded-lg mr-3">
            <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <h4 class="text-lg font-semibold text-gray-900">Configuration</h4>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Duration -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Durée (minutes)
            </label>
            <input
              v-model.number="form.duration_minutes"
              type="number"
              min="15"
              max="180"
              step="15"
              placeholder="60"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
            />
          </div>
          
          <!-- Price -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Prix de base (€)
            </label>
            <input
              v-model.number="form.base_price"
              type="number"
              min="0"
              step="0.01"
              placeholder="25.00"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
            />
          </div>
        </div>
      </div>

      <!-- Section Niveaux et Participants -->
      <div class="bg-gray-50 rounded-xl p-6">
        <div class="flex items-center mb-4">
          <div class="bg-blue-100 p-2 rounded-lg mr-3">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
          </div>
          <h4 class="text-lg font-semibold text-gray-900">Niveaux et Participants</h4>
        </div>
        
        <div class="space-y-6">
          <!-- Skill Levels -->
          <div class="space-y-3">
            <label class="block text-sm font-medium text-gray-700">
              Niveaux proposés
            </label>
            <div class="grid grid-cols-2 gap-3">
              <label v-for="level in skillLevels" :key="level.value" class="flex items-center p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                <input
                  v-model="form.skill_levels"
                  :value="level.value"
                  type="checkbox"
                  class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded mr-3"
                />
                <span class="text-sm text-gray-700">{{ level.label }}</span>
              </label>
            </div>
          </div>

          <!-- Participants -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Participants minimum
              </label>
              <input
                v-model.number="form.min_participants"
                type="number"
                min="1"
                placeholder="1"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
              />
            </div>
            
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Participants maximum
              </label>
              <input
                v-model.number="form.max_participants"
                type="number"
                min="1"
                placeholder="8"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Section Équipement -->
      <div class="bg-gray-50 rounded-xl p-6">
        <div class="flex items-center mb-4">
          <div class="bg-green-100 p-2 rounded-lg mr-3">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <h4 class="text-lg font-semibold text-gray-900">Équipement requis</h4>
        </div>
        
        <div class="space-y-3">
          <div v-for="(equipment, index) in form.equipment_required" :key="index" class="flex items-center space-x-3">
            <input
              v-model="form.equipment_required[index]"
              type="text"
              placeholder="Ex: Casque, Bottes..."
              class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
            />
            <button
              type="button"
              @click="removeEquipment(index)"
              :disabled="form.equipment_required.length <= 1"
              class="p-3 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <button
            type="button"
            @click="addEquipment"
            class="flex items-center px-4 py-2 text-purple-600 hover:text-purple-800 hover:bg-purple-50 rounded-lg transition-colors text-sm"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Ajouter un équipement
          </button>
        </div>
      </div>

      <!-- Footer avec boutons -->
      <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 rounded-b-xl">
        <div class="flex items-center justify-end space-x-4">
          <button
            type="button"
            @click="$emit('cancel')"
            :disabled="isSaving"
            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors disabled:opacity-50"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="!isFormValid || isSaving"
            :class="[
              'px-8 py-3 rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500',
              isFormValid && !isSaving
                ? 'bg-gradient-to-r from-purple-500 to-pink-600 text-white hover:from-purple-600 hover:to-pink-700'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
            ]"
          >
            <span v-if="isSaving" class="flex items-center space-x-2">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              <span>Ajout...</span>
            </span>
            <span v-else>Ajouter la spécialité</span>
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from '@/composables/useToast'

// Props
const props = defineProps({
  availableActivities: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['cancel', 'success'])

// Composables
const { showToast } = useToast()

// Reactive data
const isSaving = ref(false)
const form = ref({})

// Constants
const skillLevels = [
  { value: 'debutant', label: '🌱 Débutant' },
  { value: 'intermediaire', label: '📈 Intermédiaire' },
  { value: 'avance', label: '⭐ Avancé' },
  { value: 'expert', label: '🏆 Expert' }
]

// Computed
const isFormValid = computed(() => {
  return form.value.activity_id && form.value.name
})

// Methods
const addEquipment = () => {
  form.value.equipment_required.push('')
}

const removeEquipment = (index) => {
  if (form.value.equipment_required.length > 1) {
    form.value.equipment_required.splice(index, 1)
  }
}

const addCustomSpecialty = async () => {
  if (!isFormValid.value) {
    showToast('Veuillez remplir tous les champs obligatoires', 'error')
    return
  }

  isSaving.value = true

  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    
    // Clean equipment array (remove empty strings)
    const cleanEquipment = form.value.equipment_required.filter(item => item.trim() !== '')
    
    const response = await $fetch(`${config.public.apiBase}/club/custom-specialty`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${tokenCookie.value}`,
        'Content-Type': 'application/json'
      },
      body: {
        activity_id: form.value.activity_id,
        name: form.value.name,
        description: form.value.description,
        duration_minutes: form.value.duration_minutes,
        base_price: form.value.base_price,
        skill_levels: form.value.skill_levels,
        min_participants: form.value.min_participants,
        max_participants: form.value.max_participants,
        equipment_required: cleanEquipment
      }
    })

    showToast('Spécialité ajoutée avec succès !', 'success')
    emit('success', response.data)
    
  } catch (error) {
    console.error('Erreur lors de l\'ajout de la spécialité:', error)
    showToast('Erreur lors de l\'ajout de la spécialité', 'error')
  } finally {
    isSaving.value = false
  }
}

const resetForm = () => {
  form.value = {
    activity_id: '',
    name: '',
    description: '',
    duration_minutes: 60,
    base_price: 0,
    skill_levels: ['debutant'],
    min_participants: 1,
    max_participants: 8,
    equipment_required: ['']
  }
}

onMounted(() => {
  resetForm()
})
</script>
