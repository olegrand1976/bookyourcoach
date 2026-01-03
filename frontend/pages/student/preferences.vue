<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-6 md:mb-8">
        <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
              Préférences
            </h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">
              Sélectionnez vos disciplines et types de cours préférés
            </p>
          </div>
          
          <NuxtLink 
            to="/student/dashboard"
            class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm md:text-base"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Retour au dashboard
          </NuxtLink>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-gray-600">Chargement des préférences...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3 flex-1">
            <h3 class="text-sm font-medium text-red-800">Erreur</h3>
            <div class="mt-2 text-sm text-red-700">{{ error }}</div>
            <button 
              @click="refreshData"
              class="mt-4 px-4 py-2 bg-red-100 text-red-800 rounded-lg hover:bg-red-200 transition-colors text-sm font-medium"
            >
              Réessayer
            </button>
          </div>
        </div>
      </div>

      <!-- Disciplines List -->
      <div v-else class="space-y-6">
        <div 
          v-for="discipline in disciplines" 
          :key="discipline.id"
          class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden"
        >
          <!-- Discipline Header -->
          <div class="p-4 md:p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="p-3 bg-blue-100 rounded-lg">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-lg md:text-xl font-semibold text-gray-900">
                    {{ discipline.name }}
                  </h3>
                  <p class="text-xs md:text-sm text-gray-600 mt-1">
                    {{ discipline.description || 'Sélectionnez vos types de cours préférés' }}
                  </p>
                </div>
              </div>
              <button
                @click="toggleDisciplinePreference(discipline.id)"
                :class="[
                  'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                  hasPreferenceForDiscipline(discipline.id)
                    ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-md'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                {{ hasPreferenceForDiscipline(discipline.id) ? '✓ Sélectionnée' : 'Sélectionner' }}
              </button>
            </div>
          </div>

          <!-- Course Types -->
          <div v-if="hasPreferenceForDiscipline(discipline.id) && discipline.course_types?.length" class="p-6">
            <h4 class="text-base md:text-lg font-medium text-gray-900 mb-4">
              Types de cours préférés :
            </h4>
            <div class="grid gap-3">
              <div 
                v-for="courseType in discipline.course_types" 
                :key="courseType.id"
                class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
              >
                <div class="flex items-center space-x-3">
                  <button
                    @click="toggleCourseTypePreference(discipline.id, courseType.id)"
                    :class="[
                      'flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-all',
                      hasPreferenceForCourseType(discipline.id, courseType.id)
                        ? 'bg-blue-500 border-blue-500'
                        : 'border-gray-300'
                    ]"
                  >
                    <svg 
                      v-if="hasPreferenceForCourseType(discipline.id, courseType.id)"
                      class="w-3 h-3 text-white" 
                      fill="currentColor" 
                      viewBox="0 0 20 20"
                    >
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  <div class="flex-1 min-w-0">
                    <div class="text-sm md:text-base font-medium text-gray-900">
                      {{ courseType.name }}
                    </div>
                    <div class="text-xs md:text-sm text-gray-600 mt-1">
                      {{ courseType.is_individual ? 'Individuel' : 'Collectif' }}
                      <span v-if="courseType.duration_minutes">
                        • {{ courseType.duration_minutes }} min
                      </span>
                      <span v-if="courseType.max_participants && !courseType.is_individual">
                        • Max {{ courseType.max_participants }} participants
                      </span>
                    </div>
                  </div>
                </div>
                <div v-if="hasPreferenceForCourseType(discipline.id, courseType.id)" class="flex-shrink-0 ml-3">
                  <div class="bg-emerald-100 p-1.5 rounded-full">
                    <svg class="h-4 w-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Empty course types message -->
          <div v-else-if="hasPreferenceForDiscipline(discipline.id) && !discipline.course_types?.length" class="p-6">
            <p class="text-sm text-gray-500 text-center">Aucun type de cours disponible pour cette discipline</p>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && !error && disciplines.length === 0" class="text-center py-12 bg-white rounded-xl shadow-lg">
          <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
          </svg>
          <h3 class="mt-2 text-base md:text-lg font-medium text-gray-900">Aucune discipline disponible</h3>
          <p class="mt-1 text-sm md:text-base text-gray-500">Contactez votre club pour plus d'informations.</p>
        </div>

        <!-- Save Button -->
        <div v-if="!loading && !error && disciplines.length > 0" class="flex justify-end pt-6">
          <button
            @click="savePreferences"
            :disabled="saving"
            class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all shadow-lg hover:shadow-xl text-sm md:text-base font-medium disabled:opacity-50"
          >
            <span v-if="!saving">Enregistrer les préférences</span>
            <span v-else class="flex items-center">
              <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Enregistrement...
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usePreferences } from '~/composables/usePreferences'
import { useToast } from '~/composables/useToast'

definePageMeta({
  middleware: ['auth', 'student'],
  layout: 'student'
})

const toast = useToast()

// Composable
const {
  disciplines,
  preferences,
  loading,
  error,
  hasPreferenceForDiscipline,
  hasPreferenceForCourseType,
  fetchDisciplines,
  fetchPreferences,
  addPreference,
  removePreference,
  updatePreferences
} = usePreferences()

const saving = ref(false)

// Methods
const refreshData = async () => {
  await Promise.all([
    fetchDisciplines(),
    fetchPreferences()
  ])
}

const toggleDisciplinePreference = async (disciplineId: number) => {
  try {
    if (hasPreferenceForDiscipline(disciplineId)) {
      // Supprimer toutes les préférences de cette discipline
      const disciplinePrefs = preferences.value.filter(p => p.discipline_id === disciplineId)
      for (const pref of disciplinePrefs) {
        await removePreference(pref.discipline_id, pref.course_type_id)
      }
      toast.success('Discipline retirée de vos préférences')
    } else {
      // Ajouter la discipline sans type de cours spécifique
      await addPreference(disciplineId)
      toast.success('Discipline ajoutée à vos préférences')
    }
  } catch (err: any) {
    toast.error(err.message || 'Erreur lors de la mise à jour')
    console.error('Error toggling discipline preference:', err)
  }
}

const toggleCourseTypePreference = async (disciplineId: number, courseTypeId: number) => {
  try {
    if (hasPreferenceForCourseType(disciplineId, courseTypeId)) {
      await removePreference(disciplineId, courseTypeId)
      toast.success('Type de cours retiré de vos préférences')
    } else {
      await addPreference(disciplineId, courseTypeId)
      toast.success('Type de cours ajouté à vos préférences')
    }
  } catch (err: any) {
    toast.error(err.message || 'Erreur lors de la mise à jour')
    console.error('Error toggling course type preference:', err)
  }
}

const savePreferences = async () => {
  try {
    saving.value = true
    await updatePreferences(preferences.value)
    toast.success('Préférences sauvegardées avec succès!')
  } catch (err: any) {
    toast.error(err.message || 'Erreur lors de la sauvegarde')
    console.error('Error saving preferences:', err)
  } finally {
    saving.value = false
  }
}

// Lifecycle
onMounted(() => {
  refreshData()
})
</script>
