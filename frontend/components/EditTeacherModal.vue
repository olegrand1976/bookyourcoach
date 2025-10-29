<template>
  <!-- Modal avec design moderne et responsive -->
  <div class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
      <!-- Header avec gradient et icône -->
      <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="bg-white bg-opacity-20 p-2 rounded-lg">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
            </div>
            <div>
              <h3 class="text-xl font-bold text-white">Modifier l'enseignant</h3>
              <p class="text-blue-100 text-sm">Mettez à jour les informations</p>
            </div>
          </div>
          <button @click="$emit('close')" class="text-white hover:text-blue-200 transition-colors p-2 hover:bg-white hover:bg-opacity-20 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- Contenu avec scroll -->
      <div class="overflow-y-auto max-h-[calc(90vh-120px)]">
        <form @submit.prevent="updateTeacher" class="p-6 space-y-6">
          
          <!-- Section Informations personnelles -->
          <div class="bg-gray-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-blue-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">Informations personnelles</h4>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">
                  Nom complet <span class="text-red-500">*</span>
                </label>
                <input 
                  v-model="form.name" 
                  type="text" 
                  required 
                  placeholder="Ex: Marie Dubois"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Email <span class="text-red-500">*</span>
                </label>
                <input 
                  v-model="form.email" 
                  type="email" 
                  required 
                  placeholder="Ex: marie.dubois@email.com"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                <input 
                  v-model="form.phone" 
                  type="tel" 
                  placeholder="Ex: 06 12 34 56 78"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Type de contrat
                </label>
                <select 
                  v-model="form.contract_type"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
                  <option value="freelance">Indépendant</option>
                  <option value="employee">Salarié</option>
                  <option value="volunteer">Bénévole</option>
                  <option value="article17">Article 17</option>
                  <option value="student">Étudiant</option>
                  <option value="intern">Stagiaire</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Section Tarifs -->
          <div class="bg-emerald-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-emerald-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">Tarifs</h4>
            </div>
            
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Tarif horaire (€)</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500 sm:text-sm">€</span>
                </div>
                <input 
                  v-model.number="form.hourly_rate" 
                  type="number" 
                  min="0" 
                  step="0.01" 
                  placeholder="50.00"
                  class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>
            </div>
          </div>
          
          <!-- Boutons d'action -->
          <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
            <button 
              type="button" 
              @click="$emit('close')" 
              class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors font-medium"
            >
              Annuler
            </button>
            <button 
              type="submit" 
              :disabled="loading" 
              class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg hover:from-blue-600 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-medium flex items-center space-x-2"
            >
              <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ loading ? 'Mise à jour...' : 'Enregistrer' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  teacher: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'success'])

const loading = ref(false)

const form = ref({
  name: '',
  email: '',
  phone: '',
  hourly_rate: 0,
  contract_type: 'freelance',
})

// Initialiser le formulaire avec les données de l'enseignant
watch(() => props.teacher, (newTeacher) => {
  if (newTeacher) {
    form.value = {
      name: newTeacher.name || '',
      email: newTeacher.email || '',
      phone: newTeacher.phone || '',
      hourly_rate: newTeacher.hourly_rate || 0,
      contract_type: newTeacher.contract_type || 'freelance',
    }
  }
}, { immediate: true })

const updateTeacher = async () => {
  loading.value = true
  try {
    const { $api } = useNuxtApp()
    
    // Séparer le nom en prénom et nom de famille
    const nameParts = form.value.name.trim().split(' ')
    const firstName = nameParts[0]
    const lastName = nameParts.slice(1).join(' ') || nameParts[0]
    
    const response = await $api.put(`/club/teachers/${props.teacher.id}`, {
      first_name: firstName,
      last_name: lastName,
      email: form.value.email,
      phone: form.value.phone,
      hourly_rate: form.value.hourly_rate,
      contract_type: form.value.contract_type,
    })
    
    console.log('✅ Enseignant mis à jour avec succès:', response)
    
    alert('Enseignant mis à jour avec succès !')
    
    // Émettre les événements
    emit('success')
    emit('close')
    
  } catch (error) {
    console.error('❌ Erreur lors de la mise à jour de l\'enseignant:', error)
    alert('Erreur lors de la mise à jour de l\'enseignant')
  } finally {
    loading.value = false
  }
}
</script>

