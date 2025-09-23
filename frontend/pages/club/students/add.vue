<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">
              Ajouter un élève
            </h1>
            <p class="mt-2 text-gray-600">
              Inviter un élève à rejoindre votre club
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
        <form @submit.prevent="addStudent" class="space-y-6">
          <!-- Email de l'élève -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
              Email de l'élève *
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              placeholder="eleve@example.com"
            />
            <p class="mt-2 text-sm text-gray-500">
              L'élève doit déjà avoir un compte sur la plateforme
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
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              placeholder="Message personnalisé pour l'invitation..."
            ></textarea>
          </div>

          <!-- Informations sur l'élève -->
          <div class="bg-emerald-50 p-6 rounded-lg">
            <h3 class="text-lg font-medium text-emerald-900 mb-4">
              Informations sur l'élève
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-emerald-700 mb-2">
                  Nom complet
                </label>
                <input
                  v-model="studentInfo.name"
                  type="text"
                  readonly
                  class="w-full px-4 py-3 border border-emerald-200 rounded-lg bg-emerald-50 text-emerald-900"
                  placeholder="Nom de l'élève"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-emerald-700 mb-2">
                  Niveau
                </label>
                <input
                  v-model="studentInfo.level"
                  type="text"
                  readonly
                  class="w-full px-4 py-3 border border-emerald-200 rounded-lg bg-emerald-50 text-emerald-900"
                  placeholder="Niveau de l'élève"
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
              :disabled="loading || !studentInfo.name"
              class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading">Ajout en cours...</span>
              <span v-else>Ajouter l'élève</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Liste des élèves existants -->
      <div class="mt-8 bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">
          Élèves du club
        </h2>
        <div v-if="existingStudents.length === 0" class="text-center text-gray-500 py-8">
          <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
          </svg>
          <p>Aucun élève dans le club pour le moment</p>
        </div>
        <div v-else class="space-y-4">
          <div 
            v-for="student in existingStudents" 
            :key="student.id" 
            class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
          >
            <div class="flex items-center space-x-3">
              <div class="bg-emerald-100 p-2 rounded-lg">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <div>
                <p class="font-medium text-gray-900">{{ student.name }}</p>
                <p class="text-sm text-gray-600">{{ student.email }}</p>
              </div>
            </div>
            <span v-if="student.level" class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
              {{ getLevelLabel(student.level) }}
            </span>
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
const existingStudents = ref([])

const form = ref({
  email: '',
  message: ''
})

const studentInfo = ref({
  name: '',
  level: ''
})

// Vérifier si l'élève existe
const checkStudent = async () => {
  if (!form.value.email) {
    studentInfo.value = { name: '', level: '' }
    return
  }

  try {
    // TODO: Appeler l'API pour vérifier l'élève
    // const response = await $fetch(`/api/students/check?email=${form.value.email}`)
    
    // Simulation de vérification
    if (form.value.email === 'marie.dupont@example.com') {
      studentInfo.value = {
        name: 'Marie Dupont',
        level: 'debutant'
      }
    } else if (form.value.email === 'jean.martin@example.com') {
      studentInfo.value = {
        name: 'Jean Martin',
        level: 'intermediaire'
      }
    } else if (form.value.email === 'emma.rousseau@example.com') {
      studentInfo.value = {
        name: 'Emma Rousseau',
        level: 'avance'
      }
    } else {
      studentInfo.value = { name: '', level: '' }
    }
  } catch (error) {
    console.error('Erreur lors de la vérification:', error)
    studentInfo.value = { name: '', level: '' }
  }
}

// Ajouter l'élève
const addStudent = async () => {
  loading.value = true
  
  try {
    const data = {
      email: form.value.email,
      message: form.value.message
    }
    
    console.log('Ajout de l\'élève:', data)
    
    // TODO: Appeler l'API pour ajouter l'élève
    // const response = await $fetch('/api/club/add-student', {
    //   method: 'POST',
    //   body: data
    // })
    
    // Simulation de succès
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Rediriger vers le dashboard avec un message de succès
    await navigateTo('/club/dashboard')
    
  } catch (error) {
    console.error('Erreur lors de l\'ajout de l\'élève:', error)
    alert('Erreur lors de l\'ajout de l\'élève. Veuillez réessayer.')
  } finally {
    loading.value = false
  }
}

// Charger les élèves existants
const loadExistingStudents = async () => {
  try {
    // TODO: Appeler l'API pour récupérer les élèves
    // const response = await $fetch('/api/club/students')
    
    // Données de test
    existingStudents.value = [
      { id: 1, name: 'Marie Dupont', email: 'marie.dupont@example.com', level: 'debutant' },
      { id: 2, name: 'Jean Martin', email: 'jean.martin@example.com', level: 'intermediaire' },
      { id: 3, name: 'Emma Rousseau', email: 'emma.rousseau@example.com', level: 'avance' }
    ]
  } catch (error) {
    console.error('Erreur lors du chargement des élèves:', error)
  }
}

// Fonction pour obtenir le label du niveau
const getLevelLabel = (level) => {
  const labels = {
    debutant: '🌱 Débutant',
    intermediaire: '📈 Intermédiaire',
    avance: '⭐ Avancé',
    expert: '🏆 Expert'
  }
  return labels[level] || level
}

// Watcher pour vérifier l'élève quand l'email change
watch(() => form.value.email, checkStudent)

onMounted(() => {
  loadExistingStudents()
})
</script>
