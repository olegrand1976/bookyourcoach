<template>
  <div class="preferences-page">
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          🎯 Mes Préférences
        </h1>
        <p class="text-gray-700">
          Sélectionnez vos disciplines et types de cours préférés
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Erreur</h3>
            <div class="mt-2 text-sm text-red-700">{{ error }}</div>
            <div class="mt-4">
              <button 
                @click="refreshData"
                class="bg-red-100 bg-blue-600:bg-red-200 text-red-800 px-3 py-2 rounded-md text-sm font-medium transition-colors"
              >
                Réessayer
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Disciplines List -->
      <div v-else class="space-y-6">
        <!-- Équitation -->
        <div class="bg-white rounded-lg shadow-md border border-blue-500/20">
          <!-- Discipline Header -->
          <div class="p-6 border-b border-blue-500/20 bg-gradient-to-r from-gray-50 to-blue-50">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <span class="text-3xl">🏇</span>
                </div>
                <div>
                  <h3 class="text-xl font-semibold text-gray-900">
                    Équitation
                  </h3>
                  <p class="text-sm text-gray-700">
                    Dressage, obstacles et complet
                  </p>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  @click="toggleDisciplinePreference('equitation')"
                  :class="[
                    'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                    hasPreferenceForDiscipline('equitation')
                      ? 'bg-blue-500 text-gray-900 bg-blue-600:bg-yellow-600'
                      : 'bg-gray-100 text-gray-800 bg-blue-600:bg-gray-200'
                  ]"
                >
                  {{ hasPreferenceForDiscipline('equitation') ? 'Sélectionné' : 'Sélectionner' }}
                </button>
                <div v-if="hasPreferenceForDiscipline('equitation')" class="flex-shrink-0">
                  <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
            </div>
          </div>

          <!-- Course Types -->
          <div v-if="hasPreferenceForDiscipline('equitation')" class="p-6">
            <h4 class="text-md font-medium text-gray-900 mb-4">
              Types de cours préférés :
            </h4>
            <div class="grid gap-3">
              <!-- Dressage Particulier -->
              <div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="flex items-center space-x-3">
                  <button
                    @click="toggleCourseTypePreference('equitation', 'dressage_particulier')"
                    :class="[
                      'flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                      hasPreferenceForCourseType('equitation', 'dressage_particulier')
                        ? 'bg-blue-500 border-blue-500'
                        : 'border-gray-300 bg-blue-600:border-blue-500'
                    ]"
                  >
                    <svg 
                      v-if="hasPreferenceForCourseType('equitation', 'dressage_particulier')"
                      class="w-3 h-3 text-gray-900" 
                      fill="currentColor" 
                      viewBox="0 0 20 20"
                    >
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  <div>
                    <div class="text-sm font-medium text-gray-900">
                      Dressage Particulier
                    </div>
                    <div class="text-xs text-gray-700">
                      Individuel • Durée variable selon l'enseignant
                    </div>
                  </div>
                </div>
                <div v-if="hasPreferenceForCourseType('equitation', 'dressage_particulier')" class="flex-shrink-0">
                  <svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>

              <!-- Dressage Collectif -->
              <div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="flex items-center space-x-3">
                  <button
                    @click="toggleCourseTypePreference('equitation', 'dressage_collectif')"
                    :class="[
                      'flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                      hasPreferenceForCourseType('equitation', 'dressage_collectif')
                        ? 'bg-blue-500 border-blue-500'
                        : 'border-gray-300 bg-blue-600:border-blue-500'
                    ]"
                  >
                    <svg 
                      v-if="hasPreferenceForCourseType('equitation', 'dressage_collectif')"
                      class="w-3 h-3 text-gray-900" 
                      fill="currentColor" 
                      viewBox="0 0 20 20"
                    >
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  <div>
                    <div class="text-sm font-medium text-gray-900">
                      Dressage Collectif
                    </div>
                    <div class="text-xs text-gray-700">
                      Collectif • Durée variable selon l'enseignant
                    </div>
                  </div>
                </div>
                <div v-if="hasPreferenceForCourseType('equitation', 'dressage_collectif')" class="flex-shrink-0">
                  <svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>

              <!-- Obstacles Particulier -->
              <div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="flex items-center space-x-3">
                  <button
                    @click="toggleCourseTypePreference('equitation', 'obstacles_particulier')"
                    :class="[
                      'flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                      hasPreferenceForCourseType('equitation', 'obstacles_particulier')
                        ? 'bg-blue-500 border-blue-500'
                        : 'border-gray-300 bg-blue-600:border-blue-500'
                    ]"
                  >
                    <svg 
                      v-if="hasPreferenceForCourseType('equitation', 'obstacles_particulier')"
                      class="w-3 h-3 text-gray-900" 
                      fill="currentColor" 
                      viewBox="0 0 20 20"
                    >
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  <div>
                    <div class="text-sm font-medium text-gray-900">
                      Obstacles Particulier
                    </div>
                    <div class="text-xs text-gray-700">
                      Individuel • Durée variable selon l'enseignant
                    </div>
                  </div>
                </div>
                <div v-if="hasPreferenceForCourseType('equitation', 'obstacles_particulier')" class="flex-shrink-0">
                  <svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>

              <!-- Obstacles Collectif -->
              <div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="flex items-center space-x-3">
                  <button
                    @click="toggleCourseTypePreference('equitation', 'obstacles_collectif')"
                    :class="[
                      'flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                      hasPreferenceForCourseType('equitation', 'obstacles_collectif')
                        ? 'bg-blue-500 border-blue-500'
                        : 'border-gray-300 bg-blue-600:border-blue-500'
                    ]"
                  >
                    <svg 
                      v-if="hasPreferenceForCourseType('equitation', 'obstacles_collectif')"
                      class="w-3 h-3 text-gray-900" 
                      fill="currentColor" 
                      viewBox="0 0 20 20"
                    >
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  <div>
                    <div class="text-sm font-medium text-gray-900">
                      Obstacles Collectif
                    </div>
                    <div class="text-xs text-gray-700">
                      Collectif • Durée variable selon l'enseignant
                    </div>
                  </div>
                </div>
                <div v-if="hasPreferenceForCourseType('equitation', 'obstacles_collectif')" class="flex-shrink-0">
                  <svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Natation -->
        <div class="bg-white rounded-lg shadow-md border border-blue-200">
          <!-- Discipline Header -->
          <div class="p-6 border-b border-blue-200 bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <span class="text-3xl">🏊</span>
                </div>
                <div>
                  <h3 class="text-xl font-semibold text-blue-900">
                    Natation
                  </h3>
                  <p class="text-sm text-blue-700">
                    Cours particuliers et aquagym
                  </p>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  @click="toggleDisciplinePreference('natation')"
                  :class="[
                    'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                    hasPreferenceForDiscipline('natation')
                      ? 'bg-blue-600 text-white bg-blue-600:bg-blue-700'
                      : 'bg-gray-100 text-gray-800 bg-blue-600:bg-gray-200'
                  ]"
                >
                  {{ hasPreferenceForDiscipline('natation') ? 'Sélectionné' : 'Sélectionner' }}
                </button>
                <div v-if="hasPreferenceForDiscipline('natation')" class="flex-shrink-0">
                  <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
            </div>
          </div>

          <!-- Course Types -->
          <div v-if="hasPreferenceForDiscipline('natation')" class="p-6">
            <h4 class="text-md font-medium text-blue-900 mb-4">
              Types de cours préférés :
            </h4>
            <div class="grid gap-3">
              <!-- Cours Particulier -->
              <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg bg-blue-600:bg-blue-100 transition-colors">
                <div class="flex items-center space-x-3">
                  <button
                    @click="toggleCourseTypePreference('natation', 'cours_particulier')"
                    :class="[
                      'flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                      hasPreferenceForCourseType('natation', 'cours_particulier')
                        ? 'bg-blue-600 border-blue-600'
                        : 'border-gray-300 bg-blue-600:border-blue-400'
                    ]"
                  >
                    <svg 
                      v-if="hasPreferenceForCourseType('natation', 'cours_particulier')"
                      class="w-3 h-3 text-white" 
                      fill="currentColor" 
                      viewBox="0 0 20 20"
                    >
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  <div>
                    <div class="text-sm font-medium text-blue-900">
                      Cours Particulier
                    </div>
                    <div class="text-xs text-blue-700">
                      Individuel • 20 minutes
                    </div>
                  </div>
                </div>
                <div v-if="hasPreferenceForCourseType('natation', 'cours_particulier')" class="flex-shrink-0">
                  <svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>

              <!-- Aquagym -->
              <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg bg-blue-600:bg-blue-100 transition-colors">
                <div class="flex items-center space-x-3">
                  <button
                    @click="toggleCourseTypePreference('natation', 'aquagym')"
                    :class="[
                      'flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                      hasPreferenceForCourseType('natation', 'aquagym')
                        ? 'bg-blue-600 border-blue-600'
                        : 'border-gray-300 bg-blue-600:border-blue-400'
                    ]"
                  >
                    <svg 
                      v-if="hasPreferenceForCourseType('natation', 'aquagym')"
                      class="w-3 h-3 text-white" 
                      fill="currentColor" 
                      viewBox="0 0 20 20"
                    >
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  <div>
                    <div class="text-sm font-medium text-blue-900">
                      Aquagym
                    </div>
                    <div class="text-xs text-blue-700">
                      Collectif • 1 heure
                    </div>
                  </div>
                </div>
                <div v-if="hasPreferenceForCourseType('natation', 'aquagym')" class="flex-shrink-0">
                  <svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Summary -->
        <div v-if="hasAnyPreference()" class="bg-green-50 border border-green-200 rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-green-800">
                Préférences sauvegardées
              </h3>
              <div class="mt-2 text-sm text-green-700">
                Vos préférences ont été enregistrées avec succès.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

// Meta
definePageMeta({
  middleware: ['auth', 'student'],
  layout: 'student'
})

// État local pour les préférences
const loading = ref(false)
const error = ref('')

// Préférences des disciplines
const disciplinePreferences = ref({
  equitation: false,
  natation: false
})

// Préférences des types de cours
const courseTypePreferences = ref({
  equitation: {
    dressage_particulier: false,
    dressage_collectif: false,
    obstacles_particulier: false,
    obstacles_collectif: false
  },
  natation: {
    cours_particulier: false,
    aquagym: false
  }
})

// Méthodes pour vérifier les préférences
const hasPreferenceForDiscipline = (discipline: string) => {
  return disciplinePreferences.value[discipline as keyof typeof disciplinePreferences.value] || false
}

const hasPreferenceForCourseType = (discipline: string, courseType: string) => {
  return courseTypePreferences.value[discipline as keyof typeof courseTypePreferences.value]?.[courseType as keyof typeof courseTypePreferences.value[typeof discipline]] || false
}

const hasAnyPreference = () => {
  return Object.values(disciplinePreferences.value).some(Boolean) || 
         Object.values(courseTypePreferences.value).some(discipline => 
           Object.values(discipline).some(Boolean)
         )
}

// Méthodes pour basculer les préférences
const toggleDisciplinePreference = async (discipline: string) => {
  try {
    loading.value = true
    disciplinePreferences.value[discipline as keyof typeof disciplinePreferences.value] = 
      !disciplinePreferences.value[discipline as keyof typeof disciplinePreferences.value]
    
    // Sauvegarder les préférences (simulation)
    await new Promise(resolve => setTimeout(resolve, 500))
    
    console.log('Discipline preference saved:', discipline, disciplinePreferences.value[discipline as keyof typeof disciplinePreferences.value])
  } catch (err) {
    console.error('Error toggling discipline preference:', err)
    error.value = 'Erreur lors de la sauvegarde'
  } finally {
    loading.value = false
  }
}

const toggleCourseTypePreference = async (discipline: string, courseType: string) => {
  try {
    loading.value = true
    
    const disciplinePrefs = courseTypePreferences.value[discipline as keyof typeof courseTypePreferences.value]
    if (disciplinePrefs) {
      disciplinePrefs[courseType as keyof typeof disciplinePrefs] = 
        !disciplinePrefs[courseType as keyof typeof disciplinePrefs]
    }
    
    // Sauvegarder les préférences (simulation)
    await new Promise(resolve => setTimeout(resolve, 500))
    
    console.log('Course type preference saved:', discipline, courseType, 
      courseTypePreferences.value[discipline as keyof typeof courseTypePreferences.value]?.[courseType as keyof typeof courseTypePreferences.value[typeof discipline]])
  } catch (err) {
    console.error('Error toggling course type preference:', err)
    error.value = 'Erreur lors de la sauvegarde'
  } finally {
    loading.value = false
  }
}

const refreshData = async () => {
  try {
    loading.value = true
    error.value = ''
    
    // Simuler le chargement des données
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    console.log('Data refreshed')
  } catch (err) {
    console.error('Error refreshing data:', err)
    error.value = 'Erreur lors du chargement'
  } finally {
    loading.value = false
  }
}

// Charger les données au montage
onMounted(() => {
  refreshData()
})
</script>

<style scoped>
.preferences-page {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>


