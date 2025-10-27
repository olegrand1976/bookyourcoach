<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden">
      <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="bg-white bg-opacity-20 p-2 rounded-lg">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
            </div>
            <div>
              <h3 class="text-xl font-bold text-white">Modifier l'élève</h3>
              <p class="text-emerald-100 text-sm">Mettez à jour les informations</p>
            </div>
          </div>
          <button @click="$emit('close')" class="text-white hover:text-emerald-200 transition-colors p-2 hover:bg-white hover:bg-opacity-20 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>

      <div class="overflow-y-auto max-h-[calc(90vh-120px)]">
        <form @submit.prevent="updateStudent" class="p-6 space-y-6">
          <div class="bg-gray-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-emerald-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">Informations personnelles</h4>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Nom complet <span class="text-red-500">*</span></label>
                <input v-model="form.name" type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                <input v-model="form.email" type="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                <input v-model="form.phone" type="tel" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Date de naissance</label>
                <div class="flex items-center gap-3">
                  <input v-model="form.date_of_birth" type="date" :max="maxDate" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                  <div v-if="calculatedAge !== null" class="flex items-center bg-emerald-100 px-4 py-3 rounded-lg min-w-[100px] justify-center">
                    <span class="text-lg font-bold text-emerald-700">{{ calculatedAge }} ans</span>
                  </div>
                </div>
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Niveau</label>
                <select v-model="form.level" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                  <option value="">Sélectionner un niveau</option>
                  <option value="debutant">🌱 Débutant</option>
                  <option value="intermediaire">📈 Intermédiaire</option>
                  <option value="avance">⭐ Avancé</option>
                  <option value="expert">🏆 Expert</option>
                </select>
              </div>
            </div>
          </div>

          <div class="bg-purple-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-purple-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">Objectifs et informations médicales</h4>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Objectifs</label>
                <textarea v-model="form.goals" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none"></textarea>
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Informations médicales</label>
                <textarea v-model="form.medical_info" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none"></textarea>
              </div>
            </div>
          </div>
          
          <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
            <button type="button" @click="$emit('close')" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors font-medium">
              Annuler
            </button>
            <button type="submit" :disabled="loading" class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-lg hover:from-emerald-600 hover:to-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-medium flex items-center space-x-2">
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
import { ref, watch, computed } from 'vue'

const props = defineProps({
  student: { type: Object, required: true }
})

const emit = defineEmits(['close', 'success'])

const loading = ref(false)
const form = ref({
  name: '',
  email: '',
  phone: '',
  date_of_birth: '',
  level: '',
  goals: '',
  medical_info: ''
})

const maxDate = computed(() => new Date().toISOString().split('T')[0])

const calculatedAge = computed(() => {
  if (!form.value.date_of_birth) return null
  const birthDate = new Date(form.value.date_of_birth)
  const today = new Date()
  let age = today.getFullYear() - birthDate.getFullYear()
  const monthDiff = today.getMonth() - birthDate.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) age--
  return age
})

watch(() => props.student, (newStudent) => {
  if (newStudent) {
    form.value = {
      name: newStudent.name || '',
      email: newStudent.email || '',
      phone: newStudent.phone || '',
      date_of_birth: newStudent.date_of_birth || '',
      level: newStudent.level || '',
      goals: newStudent.goals || '',
      medical_info: newStudent.medical_info || ''
    }
  }
}, { immediate: true })

const updateStudent = async () => {
  loading.value = true
  try {
    const { $api } = useNuxtApp()
    const nameParts = form.value.name.trim().split(' ')
    const firstName = nameParts[0]
    const lastName = nameParts.slice(1).join(' ') || nameParts[0]
    
    const response = await $api.put(`/club/students/${props.student.id}`, {
      first_name: firstName,
      last_name: lastName,
      email: form.value.email,
      phone: form.value.phone,
      date_of_birth: form.value.date_of_birth || null,
      level: form.value.level,
      goals: form.value.goals,
      medical_info: form.value.medical_info
    })
    
    alert('Élève mis à jour avec succès !')
    emit('success')
    emit('close')
  } catch (error) {
    console.error('Erreur lors de la mise à jour:', error)
    alert('Erreur lors de la mise à jour de l\'élève')
  } finally {
    loading.value = false
  }
}
</script>

