<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">
              Ajouter un enseignant
            </h1>
            <p class="mt-2 text-gray-600">
              Inviter un enseignant à rejoindre votre club
            </p>
          </div>
          <button 
            @click="navigateTo('/club/dashboard')"
            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors"
          >
            Retour au dashboard
          </button>
        </div>
      </div>

      <!-- Formulaire -->
      <div class="bg-white rounded-xl shadow-lg p-8">
        <form @submit.prevent="addTeacher" class="space-y-6">
          <!-- Email de l'enseignant -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
              Email de l'enseignant *
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="enseignant@example.com"
            />
            <p class="mt-2 text-sm text-gray-500">
              L'enseignant doit déjà avoir un compte sur la plateforme
            </p>
          </div>

          <!-- Message d'invitation -->
          <div>
            <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
              Message d'invitation (optionnel)
            </label>
            <textarea
              id="message"
              v-model="form.message"
              rows="4"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Message personnalisé pour l'invitation..."
            ></textarea>
          </div>

          <!-- Informations sur l'enseignant -->
          <div class="bg-blue-50 p-6 rounded-lg">
            <h3 class="text-lg font-medium text-blue-900 mb-4">
              Informations sur l'enseignant
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-blue-700 mb-2">
                  Nom complet
                </label>
                <input
                  v-model="teacherInfo.name"
                  type="text"
                  readonly
                  class="w-full px-4 py-3 border border-blue-200 rounded-lg bg-blue-50 text-blue-900"
                  placeholder="Nom de l'enseignant"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-blue-700 mb-2">
                  Spécialités
                </label>
                <input
                  v-model="teacherInfo.specialties"
                  type="text"
                  readonly
                  class="w-full px-4 py-3 border border-blue-200 rounded-lg bg-blue-50 text-blue-900"
                  placeholder="Spécialités de l'enseignant"
                />
              </div>
            </div>
          </div>

          <!-- Boutons -->
          <div class="flex items-center justify-end space-x-4 pt-6">
            <button
              type="button"
              @click="navigateTo('/club/dashboard')"
              class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="loading || !teacherInfo.name"
              class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading">Ajout en cours...</span>
              <span v-else>Ajouter l'enseignant</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Liste des enseignants existants -->
      <div class="mt-8 bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">
          Enseignants du club
        </h2>
        <div v-if="existingTeachers.length === 0" class="text-center text-gray-500 py-8">
          <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
          </svg>
          <p>Aucun enseignant dans le club pour le moment</p>
        </div>
        <div v-else class="space-y-4">
          <div 
            v-for="teacher in existingTeachers" 
            :key="teacher.id" 
            class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
          >
            <div class="flex items-center space-x-3">
              <div class="bg-blue-100 p-2 rounded-lg">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <div>
                <p class="font-medium text-gray-900">{{ teacher.name }}</p>
                <p class="text-sm text-gray-600">{{ teacher.email }}</p>
              </div>
            </div>
            <div class="flex items-center space-x-3">
              <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                {{ teacher.hourly_rate }}€/h
              </span>
              <button
                @click="resendInvitation(teacher.id)"
                :disabled="resending[teacher.id]"
                class="px-3 py-1 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors disabled:opacity-50"
                title="Renvoyer l'email d'invitation"
              >
                <svg v-if="resending[teacher.id]" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'

definePageMeta({
  middleware: ['auth']
})

const loading = ref(false)
const existingTeachers = ref([])
const resending = ref({})

const form = ref({
  email: '',
  message: ''
})

const teacherInfo = ref({
  name: '',
  specialties: '',
  hourly_rate: ''
})

// Vérifier si l'enseignant existe
const checkTeacher = async () => {
  if (!form.value.email) {
    teacherInfo.value = { name: '', specialties: '', hourly_rate: '' }
    return
  }

  try {
    // TODO: Appeler l'API pour vérifier l'enseignant
    // const response = await $fetch(`/api/teachers/check?email=${form.value.email}`)
    
    // Simulation de vérification
    if (form.value.email === 'sophie.martin@activibe.com') {
      teacherInfo.value = {
        name: 'Sophie Martin',
        specialties: 'Équitation, Dressage',
        hourly_rate: '45'
      }
    } else if (form.value.email === 'pierre.dubois@activibe.com') {
      teacherInfo.value = {
        name: 'Pierre Dubois',
        specialties: 'Saut d\'obstacles, Cross',
        hourly_rate: '50'
      }
    } else {
      teacherInfo.value = { name: '', specialties: '', hourly_rate: '' }
    }
  } catch (error) {
    console.error('Erreur lors de la vérification:', error)
    teacherInfo.value = { name: '', specialties: '', hourly_rate: '' }
  }
}

// Ajouter l'enseignant
const addTeacher = async () => {
  loading.value = true
  
  try {
    const data = {
      email: form.value.email,
      message: form.value.message
    }
    
    console.log('Ajout de l\'enseignant:', data)
    
    // Appeler l'API pour ajouter l'enseignant
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/club/add-teacher`, {
      method: 'POST',
      body: data
    })
    
    console.log('✅ Enseignant ajouté:', response)
    
    // Rediriger vers le dashboard avec un message de succès
    await navigateTo('/club/dashboard')
    
  } catch (error) {
    console.error('Erreur lors de l\'ajout de l\'enseignant:', error)
    alert('Erreur lors de l\'ajout de l\'enseignant. Veuillez réessayer.')
  } finally {
    loading.value = false
  }
}

// Charger les enseignants existants
const loadExistingTeachers = async () => {
  try {
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/club/teachers`)
    existingTeachers.value = response.data || []
  } catch (error) {
    console.error('Erreur lors du chargement des enseignants:', error)
    existingTeachers.value = []
  }
}

// Renvoyer l'invitation à un enseignant
const resendInvitation = async (teacherId) => {
  resending.value[teacherId] = true
  
  try {
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/club/teachers/${teacherId}/resend-invitation`, {
      method: 'POST'
    })
    
    console.log('✅ Email renvoyé:', response)
    alert(response.message || 'Email d\'invitation renvoyé avec succès')
    
  } catch (error) {
    console.error('Erreur lors du renvoi de l\'invitation:', error)
    alert('Erreur lors du renvoi de l\'invitation. Veuillez réessayer.')
  } finally {
    resending.value[teacherId] = false
  }
}

// Watcher pour vérifier l'enseignant quand l'email change
watch(() => form.value.email, checkTeacher)

onMounted(() => {
  loadExistingTeachers()
})
</script>
