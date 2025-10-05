<template>
  <div class="bg-white rounded-2xl shadow-lg border border-gray-200 w-full mt-4">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-500 to-teal-600 px-6 py-4 rounded-t-xl">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="bg-white bg-opacity-20 p-2 rounded-lg">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
            </svg>
          </div>
          <div>
            <h3 class="text-xl font-bold text-white">Modifier la spécialité</h3>
            <p class="text-blue-100 text-sm">Mettez à jour les informations ci-dessous</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Contenu -->
    <form @submit.prevent="updateCustomSpecialty" class="p-6 space-y-8">
      
      <!-- Section Informations de base -->
      <div class="bg-gray-50 rounded-xl p-6">
        <h4 class="text-lg font-semibold text-gray-900 mb-4">Informations de base</h4>
        <div class="space-y-6">
          <!-- Activity Selection (Non-editable for now) -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Activité</label>
            <p class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-lg text-gray-500">
              {{ selectedActivityName }}
            </p>
          </div>

          <!-- Specialty Name -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Nom de la spécialité <span class="text-red-500">*</span>
            </label>
            <input v-model="form.name" type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-lg"/>
          </div>

          <!-- Description -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <textarea v-model="form.description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></textarea>
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
              class="w-full px-4 py-3 border border-gray-300 rounded-lg"
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
              class="w-full px-4 py-3 border border-gray-300 rounded-lg"
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
              <label v-for="level in skillLevels" :key="level.value" class="flex items-center p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                <input
                  v-model="form.skill_levels"
                  :value="level.value"
                  type="checkbox"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 rounded mr-3"
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
                class="w-full px-4 py-3 border border-gray-300 rounded-lg"
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
                class="w-full px-4 py-3 border border-gray-300 rounded-lg"
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
              class="flex-1 px-4 py-3 border border-gray-300 rounded-lg"
            />
            <button
              type="button"
              @click="removeEquipment(index)"
              :disabled="form.equipment_required.length <= 1"
              class="p-3 text-red-500 hover:text-red-700 rounded-lg"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <button
            type="button"
            @click="addEquipment"
            class="flex items-center px-4 py-2 text-blue-600 hover:text-blue-800"
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
          <button type="button" @click="$emit('cancel')" :disabled="isSaving" class="px-6 py-3 border border-gray-300 rounded-lg">
            Annuler
          </button>
          <button type="submit" :disabled="!isFormValid || isSaving" class="px-8 py-3 rounded-lg font-medium bg-blue-600 text-white">
            <span v-if="isSaving">Mise à jour...</span>
            <span v-else>Enregistrer les modifications</span>
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from '@/composables/useToast'
import _ from 'lodash';

// Props
const props = defineProps({
  specialty: {
    type: Object,
    required: true
  },
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
  return form.value.name && form.value.name.trim() !== ''
})

const selectedActivityName = computed(() => {
  const activity = props.availableActivities.find(a => a.id === props.specialty.activity_type_id)
  return activity ? `${activity.icon} ${activity.name}` : 'Activité inconnue'
})


// Methods
const initializeForm = () => {
  form.value = _.cloneDeep(props.specialty)
}

const updateCustomSpecialty = async () => {
  if (!isFormValid.value) {
    showToast('Veuillez remplir tous les champs obligatoires', 'error')
    return
  }
  isSaving.value = true
  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    
    const response = await $fetch(`${config.public.apiBase}/club/custom-specialty/${props.specialty.id}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${tokenCookie.value}`,
        'Content-Type': 'application/json'
      },
      body: form.value
    })

    showToast('Spécialité mise à jour avec succès !', 'success')
    emit('success', response.data)
    
  } catch (error) {
    console.error('Erreur lors de la mise à jour de la spécialité:', error)
    showToast('Erreur lors de la mise à jour de la spécialité', 'error')
  } finally {
    isSaving.value = false
  }
}

// Watch for prop changes to re-initialize form
watch(() => props.specialty, () => {
  initializeForm()
}, { immediate: true, deep: true })

</script>
