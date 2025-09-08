import { ref, computed } from 'vue'

export interface Discipline {
  id: number
  name: string
  description: string
  is_active: boolean
  course_types: CourseType[]
}

export interface CourseType {
  id: number
  discipline_id: number
  name: string
  description: string
  duration_minutes?: number
  is_individual: boolean
  max_participants?: number
  is_active: boolean
}

export interface StudentPreference {
  id: number
  student_id: number
  discipline_id: number
  course_type_id?: number
  is_preferred: boolean
  priority_level: number
  discipline?: Discipline
  course_type?: CourseType
}

export const usePreferences = () => {
  const disciplines = ref<Discipline[]>([])
  const preferences = ref<StudentPreference[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed properties
  const preferencesByDiscipline = computed(() => {
    const grouped: Record<number, StudentPreference[]> = {}
    preferences.value.forEach(pref => {
      if (!grouped[pref.discipline_id]) {
        grouped[pref.discipline_id] = []
      }
      grouped[pref.discipline_id].push(pref)
    })
    return grouped
  })

  const hasPreferenceForDiscipline = (disciplineId: number) => {
    return preferences.value.some(p => p.discipline_id === disciplineId)
  }

  const hasPreferenceForCourseType = (disciplineId: number, courseTypeId: number) => {
    return preferences.value.some(p => 
      p.discipline_id === disciplineId && p.course_type_id === courseTypeId
    )
  }

  // API calls
  const fetchDisciplines = async () => {
    try {
      loading.value = true
      error.value = null
      
      const { $api } = useNuxtApp()
      const response = await $api.get('/student/disciplines')
      
      if (response.data.success) {
        disciplines.value = response.data.data
      } else {
        throw new Error('Erreur lors du chargement des disciplines')
      }
    } catch (err: any) {
      error.value = err.message || 'Erreur lors du chargement des disciplines'
      console.error('Error fetching disciplines:', err)
    } finally {
      loading.value = false
    }
  }

  const fetchPreferences = async () => {
    try {
      loading.value = true
      error.value = null
      
      const { $api } = useNuxtApp()
      const response = await $api.get('/student/preferences/advanced')
      
      if (response.data.success) {
        const data = response.data.data
        if (typeof data === 'object' && !Array.isArray(data)) {
          // Si les préférences sont groupées par discipline
          const allPreferences: StudentPreference[] = []
          Object.values(data).forEach((group: any) => {
            if (Array.isArray(group)) {
              allPreferences.push(...group)
            }
          })
          preferences.value = allPreferences
        } else if (Array.isArray(data)) {
          preferences.value = data
        }
      } else {
        throw new Error('Erreur lors du chargement des préférences')
      }
    } catch (err: any) {
      error.value = err.message || 'Erreur lors du chargement des préférences'
      console.error('Error fetching preferences:', err)
    } finally {
      loading.value = false
    }
  }

  const addPreference = async (disciplineId: number, courseTypeId?: number) => {
    try {
      const { $api } = useNuxtApp()
      const response = await $api.post('/student/preferences/advanced', {
        discipline_id: disciplineId,
        course_type_id: courseTypeId,
        is_preferred: true,
        priority_level: 1
      })
      
      if (response.data.success) {
        const newPreference = response.data.data
        preferences.value.push(newPreference)
        return newPreference
      } else {
        throw new Error(response.data.message || 'Erreur lors de l\'ajout')
      }
    } catch (err: any) {
      error.value = err.message || 'Erreur lors de l\'ajout de la préférence'
      console.error('Error adding preference:', err)
      throw err
    }
  }

  const removePreference = async (disciplineId: number, courseTypeId?: number) => {
    try {
      const { $api } = useNuxtApp()
      const response = await $api.delete('/student/preferences/advanced', {
        data: {
          discipline_id: disciplineId,
          course_type_id: courseTypeId
        }
      })
      
      if (response.data.success) {
        preferences.value = preferences.value.filter(p => 
          !(p.discipline_id === disciplineId && p.course_type_id === courseTypeId)
        )
      } else {
        throw new Error(response.data.message || 'Erreur lors de la suppression')
      }
    } catch (err: any) {
      error.value = err.message || 'Erreur lors de la suppression de la préférence'
      console.error('Error removing preference:', err)
      throw err
    }
  }

  const updatePreferences = async (newPreferences: StudentPreference[]) => {
    try {
      loading.value = true
      error.value = null
      
      const { $api } = useNuxtApp()
      const response = await $api.put('/student/preferences/advanced', {
        preferences: newPreferences
      })
      
      if (response.data.success) {
        preferences.value = newPreferences
      } else {
        throw new Error(response.data.message || 'Erreur lors de la mise à jour')
      }
    } catch (err: any) {
      error.value = err.message || 'Erreur lors de la mise à jour des préférences'
      console.error('Error updating preferences:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    disciplines: readonly(disciplines),
    preferences: readonly(preferences),
    loading: readonly(loading),
    error: readonly(error),
    
    // Computed
    preferencesByDiscipline,
    hasPreferenceForDiscipline,
    hasPreferenceForCourseType,
    
    // Methods
    fetchDisciplines,
    fetchPreferences,
    addPreference,
    removePreference,
    updatePreferences
  }
}
