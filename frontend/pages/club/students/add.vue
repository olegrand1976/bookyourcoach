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
              Créer un nouvel élève pour votre club
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
          <!-- Nom complet -->
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
              Nom complet *
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              placeholder="Jean Dupont"
            />
          </div>

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
          </div>

          <!-- Mot de passe -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
              Mot de passe *
            </label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              minlength="8"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              placeholder="Minimum 8 caractères"
            />
          </div>

          <!-- Date de naissance avec âge -->
          <div>
            <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-2">
              Date de naissance
            </label>
            <div class="flex items-center gap-4">
              <input
                id="date_of_birth"
                v-model="form.date_of_birth"
                type="date"
                :max="maxDate"
                class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              />
              <span v-if="calculatedAge !== null" class="text-lg font-semibold text-emerald-600 whitespace-nowrap">
                {{ calculatedAge }} ans
              </span>
            </div>
          </div>

          <!-- Niveau -->
          <div>
            <label for="level" class="block text-sm font-medium text-gray-700 mb-2">
              Niveau
            </label>
            <select
              id="level"
              v-model="form.level"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            >
              <option value="">Sélectionner un niveau</option>
              <option value="debutant">🌱 Débutant</option>
              <option value="intermediaire">📈 Intermédiaire</option>
              <option value="avance">⭐ Avancé</option>
              <option value="expert">🏆 Expert</option>
            </select>
          </div>

          <!-- Objectifs -->
          <div>
            <label for="goals" class="block text-sm font-medium text-gray-700 mb-2">
              Objectifs (optionnel)
            </label>
            <textarea
              id="goals"
              v-model="form.goals"
              rows="3"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              placeholder="Les objectifs de l'élève..."
            ></textarea>
          </div>

          <!-- Informations médicales -->
          <div>
            <label for="medical_info" class="block text-sm font-medium text-gray-700 mb-2">
              Informations médicales (optionnel)
            </label>
            <textarea
              id="medical_info"
              v-model="form.medical_info"
              rows="3"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              placeholder="Allergies, restrictions, etc..."
            ></textarea>
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
              :disabled="loading"
              class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading">Création en cours...</span>
              <span v-else>Créer l'élève</span>
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
                <p class="font-medium text-gray-900">
                  {{ student.name }}
                  <span v-if="student.age" class="text-sm text-gray-500 ml-2">({{ student.age }} ans)</span>
                </p>
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
import { ref, onMounted, computed } from 'vue'

definePageMeta({
  middleware: ['auth']
})

const { $api } = useNuxtApp()
const loading = ref(false)
const existingStudents = ref([])

const form = ref({
  name: '',
  email: '',
  password: '',
  date_of_birth: '',
  level: '',
  goals: '',
  medical_info: ''
})

// Date maximale (aujourd'hui)
const maxDate = computed(() => {
  return new Date().toISOString().split('T')[0]
})

// Calculer l'âge à partir de la date de naissance
const calculatedAge = computed(() => {
  if (!form.value.date_of_birth) return null
  
  const birthDate = new Date(form.value.date_of_birth)
  const today = new Date()
  let age = today.getFullYear() - birthDate.getFullYear()
  const monthDiff = today.getMonth() - birthDate.getMonth()
  
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
    age--
  }
  
  return age
})

// Ajouter l'élève
const addStudent = async () => {
  loading.value = true
  
  try {
    console.log('Création de l\'élève:', form.value)
    
    const response = await $api.post('/club/students', form.value)
    
    console.log('✅ Élève créé:', response.data)
    
    // Recharger la liste
    await loadExistingStudents()
    
    // Réinitialiser le formulaire
    form.value = {
      name: '',
      email: '',
      password: '',
      date_of_birth: '',
      level: '',
      goals: '',
      medical_info: ''
    }
    
    alert('✅ Élève créé avec succès!')
    
  } catch (error) {
    console.error('Erreur lors de la création de l\'élève:', error)
    const errorMessage = error.response?.data?.message || 'Erreur lors de la création de l\'élève'
    alert('❌ ' + errorMessage)
  } finally {
    loading.value = false
  }
}

// Charger les élèves existants
const loadExistingStudents = async () => {
  try {
    const response = await $api.get('/club/students')
    existingStudents.value = response.data.data || []
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

onMounted(() => {
  loadExistingStudents()
})
</script>
