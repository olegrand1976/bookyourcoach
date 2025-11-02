<template>
  <div class="min-h-screen bg-gray-50 p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header avec gradient -->
      <div class="mb-6 md:mb-8 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-lg p-4 md:p-6 border-2 border-blue-100">
        <div class="flex items-center justify-between">
          <div class="flex-1 min-w-0">
            <h1 class="text-xl md:text-3xl font-bold text-gray-900 break-words">{{ form.name || 'Mon Profil Enseignant' }}</h1>
            <p class="mt-1 md:mt-2 text-xs md:text-base text-gray-600">Gérez vos informations personnelles et professionnelles</p>
          </div>
          <div class="flex items-center space-x-4">
            <NuxtLink to="/teacher/dashboard"
              class="inline-flex items-center px-4 md:px-6 py-2 md:py-3 bg-gradient-to-r from-gray-600 to-gray-700 text-white rounded-lg hover:from-gray-700 hover:to-gray-800 transition-all duration-200 font-medium">
              <span>←</span>
              <span class="ml-2 hidden md:inline">Retour au tableau de bord</span>
            </NuxtLink>
            <div class="p-2 md:p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-md flex-shrink-0">
              <svg class="w-6 h-6 md:w-8 md:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="text-center">
          <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-gray-600">Chargement des données...</p>
        </div>
      </div>

      <!-- Formulaire -->
      <form v-else @submit.prevent="handleSubmit" class="bg-white shadow-lg rounded-lg p-4 md:p-6 space-y-6 md:space-y-8">
        <!-- Informations personnelles -->
        <section class="border-b pb-4 md:pb-6">
          <h2 class="text-lg md:text-xl font-semibold text-gray-900 mb-3 md:mb-4">Informations personnelles</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet *</label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.name }"
              />
              <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
            </div>
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                disabled
                readonly
                class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600 cursor-not-allowed"
              />
              <p class="mt-1 text-xs text-gray-500">L'email ne peut pas être modifié</p>
            </div>
            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
              <input
                id="phone"
                v-model="form.phone"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.phone }"
              />
              <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone }}</p>
            </div>
            <div>
              <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">Date de naissance</label>
              <input
                id="birth_date"
                v-model="form.birth_date"
                type="date"
                @input="onBirthDateChange"
                @change="onBirthDateChange"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.birth_date }"
              />
              <p v-if="errors.birth_date" class="mt-1 text-sm text-red-600">{{ errors.birth_date }}</p>
              <p v-if="form.birth_date && form.birth_date.length === 10" class="mt-1 text-xs text-gray-600">
                Âge : {{ calculateAge(form.birth_date) }} ans
              </p>
            </div>
          </div>
        </section>

        <!-- Informations professionnelles -->
        <section class="border-b pb-4 md:pb-6">
          <h2 class="text-lg md:text-xl font-semibold text-gray-900 mb-3 md:mb-4">Informations professionnelles</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="specialties" class="block text-sm font-medium text-gray-700 mb-1">Spécialités</label>
              <textarea
                id="specialties"
                v-model="form.specialties"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.specialties }"
                placeholder="Décrivez vos spécialités et domaines d'expertise..."
              ></textarea>
              <p v-if="errors.specialties" class="mt-1 text-sm text-red-600">{{ errors.specialties }}</p>
              <p class="mt-1 text-xs text-gray-500">Séparez les spécialités par des virgules</p>
            </div>
            <div>
              <label for="experience_years" class="block text-sm font-medium text-gray-700 mb-1">Années d'expérience</label>
              <input
                id="experience_years"
                v-model.number="form.experience_years"
                type="number"
                min="0"
                disabled
                readonly
                class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600 cursor-not-allowed"
              />
              <p class="mt-1 text-xs text-gray-500">Calculé automatiquement à partir de la date de début d'expérience</p>
            </div>
            <div>
              <label for="certifications" class="block text-sm font-medium text-gray-700 mb-1">Certifications</label>
              <textarea
                id="certifications"
                v-model="form.certifications"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.certifications }"
                placeholder="Listez vos certifications et diplômes..."
              ></textarea>
              <p v-if="errors.certifications" class="mt-1 text-sm text-red-600">{{ errors.certifications }}</p>
              <p class="mt-1 text-xs text-gray-500">Séparez les certifications par des virgules</p>
            </div>
            <div>
              <label for="hourly_rate" class="block text-sm font-medium text-gray-700 mb-1">Tarif horaire (€)</label>
              <input
                id="hourly_rate"
                v-model.number="form.hourly_rate"
                type="number"
                min="0"
                step="0.01"
                disabled
                readonly
                class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600 cursor-not-allowed"
              />
              <p class="mt-1 text-xs text-gray-500">Le tarif horaire est géré par le club</p>
            </div>
          </div>
        </section>

        <!-- Description -->
        <section>
          <h2 class="text-lg md:text-xl font-semibold text-gray-900 mb-3 md:mb-4">Description</h2>
          <div>
            <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Biographie</label>
            <textarea
              id="bio"
              v-model="form.bio"
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-red-500': errors.bio }"
              placeholder="Décrivez votre parcours, votre approche pédagogique..."
            ></textarea>
            <p v-if="errors.bio" class="mt-1 text-sm text-red-600">{{ errors.bio }}</p>
          </div>
        </section>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 pt-4 md:pt-6 border-t border-gray-200">
          <NuxtLink to="/teacher/dashboard"
            class="inline-flex items-center justify-center px-4 md:px-6 py-2 md:py-3 bg-gradient-to-r from-gray-600 to-gray-700 text-white rounded-lg hover:from-gray-700 hover:to-gray-800 transition-all duration-200 font-medium">
            Annuler
          </NuxtLink>
          <button
            type="submit"
            :disabled="isSaving"
            class="inline-flex items-center justify-center px-4 md:px-6 py-2 md:py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 font-medium disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <div v-if="isSaving" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
            <span v-if="isSaving">Enregistrement...</span>
            <span v-else>Enregistrer les modifications</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: ['auth']
})

// Utiliser le store d'authentification
const authStore = useAuthStore()

// État réactif
const loading = ref(true)
const isSaving = ref(false)
const errors = ref({})

// Initialiser le formulaire avec les données de l'auth store immédiatement
const initializeForm = () => {
  const user = authStore.user
  
  return {
    name: user?.name || '',
    email: user?.email || '',
    phone: user?.phone || '',
    birth_date: user?.birth_date || '',
    specialties: '',
    experience_years: null,
    certifications: '',
    hourly_rate: null,
    bio: ''
  }
}

// Formulaire - pré-rempli immédiatement avec les données disponibles
const form = ref(initializeForm())

// Vérifier que l'utilisateur est un enseignant
onMounted(async () => {
  try {
    // S'assurer que l'auth est initialisée
    if (!authStore.isInitialized) {
      await authStore.initializeAuth()
    }
    
    // Réinitialiser le formulaire avec les données mises à jour de l'auth store
    form.value = initializeForm()
    
    if (!authStore.canActAsTeacher) {
      await navigateTo('/teacher/dashboard')
      return
    }
    
    // Charger les données complètes
    await loadProfileData()
  } catch (error) {
    console.error('Erreur dans onMounted:', error)
    loading.value = false
  }
})

// Charger les données du profil
const loadProfileData = async () => {
  try {
    loading.value = true
    const { $api } = useNuxtApp()
    const response = await $api.get('/teacher/profile')
    
    // Le backend retourne { success: true, profile: {...}, teacher: {...} }
    const data = response.data || response
    
    let profile = null
    let teacher = null
    
    if (data.success) {
      profile = data.profile
      teacher = data.teacher
    } else if (data.profile || data.teacher) {
      profile = data.profile
      teacher = data.teacher
    }
    
    // Remplir le formulaire avec les données
    if (profile || teacher) {
      if (profile) {
        form.value.name = profile?.name || authStore.user?.name || ''
        form.value.email = profile?.email || authStore.user?.email || ''
        form.value.phone = profile?.phone || ''
        
        // Formater birth_date correctement pour l'input date (YYYY-MM-DD)
        if (profile?.birth_date) {
          let birthDateStr = profile.birth_date
          
          // Si c'est une string ISO avec timestamp, extraire juste la date
          if (typeof birthDateStr === 'string' && birthDateStr.includes('T')) {
            birthDateStr = birthDateStr.substring(0, 10)
          }
          
          // Si c'est un objet Date, le formater
          if (birthDateStr instanceof Date) {
            const year = birthDateStr.getFullYear()
            const month = String(birthDateStr.getMonth() + 1).padStart(2, '0')
            const day = String(birthDateStr.getDate()).padStart(2, '0')
            birthDateStr = `${year}-${month}-${day}`
          }
          
          form.value.birth_date = birthDateStr
          
          console.log('📅 [LOAD PROFILE] birth_date formaté:', {
            'original': profile.birth_date,
            'formatted': birthDateStr,
            'type': typeof birthDateStr
          })
        } else {
          form.value.birth_date = ''
        }
      }
      
      if (teacher) {
        // Gérer les spécialités (peuvent être un array, string JSON, ou string simple)
        if (teacher.specialties) {
          if (Array.isArray(teacher.specialties)) {
            form.value.specialties = teacher.specialties.join(', ')
          } else if (typeof teacher.specialties === 'string') {
            try {
              const parsed = JSON.parse(teacher.specialties)
              form.value.specialties = Array.isArray(parsed) ? parsed.join(', ') : teacher.specialties
            } catch {
              form.value.specialties = teacher.specialties
            }
          }
        }
        
        form.value.experience_years = teacher.experience_years || null
        
        // Gérer les certifications
        if (teacher.certifications) {
          if (Array.isArray(teacher.certifications)) {
            form.value.certifications = teacher.certifications.join(', ')
          } else if (typeof teacher.certifications === 'string') {
            try {
              const parsed = JSON.parse(teacher.certifications)
              form.value.certifications = Array.isArray(parsed) ? parsed.join(', ') : teacher.certifications
            } catch {
              form.value.certifications = teacher.certifications
            }
          }
        }
        
        form.value.hourly_rate = teacher.hourly_rate || null
        form.value.bio = teacher.bio || ''
      }
    }
  } catch (err) {
    console.error('Erreur lors du chargement du profil:', err)
    const toast = useToast()
    toast.error('Impossible de charger les données du profil')
  } finally {
    loading.value = false
  }
}

// Enregistrer le profil
const handleSubmit = async () => {
  try {
    isSaving.value = true
    errors.value = {}
    
    const { $api } = useNuxtApp()
    
    // Préparer les données à envoyer
    console.log('🔵 [HANDLE SUBMIT] Début - Données du formulaire:', {
      'form.birth_date (raw)': form.value.birth_date,
      'form.birth_date (type)': typeof form.value.birth_date,
      'form.birth_date (length)': form.value.birth_date?.length,
      'form.birth_date (trimmed)': form.value.birth_date?.trim(),
      'form complet': { ...form.value }
    })
    
    // Traitement spécifique pour birth_date
    let processedBirthDate = null
    if (form.value.birth_date) {
      const trimmed = form.value.birth_date.trim()
      if (trimmed && trimmed.length > 0) {
        processedBirthDate = trimmed
        console.log('✅ [BIRTH_DATE] Date valide après trim:', processedBirthDate)
      } else {
        console.log('⚠️ [BIRTH_DATE] Chaîne vide après trim, conversion en null')
        processedBirthDate = null
      }
    } else {
      console.log('ℹ️ [BIRTH_DATE] Pas de valeur, null')
      processedBirthDate = null
    }
    
    const updateData = {
      name: form.value.name,
      phone: form.value.phone && form.value.phone.trim() ? form.value.phone.trim() : null,
      // Gérer la date de naissance : convertir chaîne vide en null
      birth_date: processedBirthDate,
      bio: form.value.bio && form.value.bio.trim() ? form.value.bio.trim() : null,
      // Convertir specialties et certifications en arrays si ce sont des strings séparées par des virgules
      specialties: form.value.specialties && form.value.specialties.trim()
        ? form.value.specialties.split(',').map(s => s.trim()).filter(s => s.length > 0)
        : null,
      certifications: form.value.certifications && form.value.certifications.trim()
        ? form.value.certifications.split(',').map(c => c.trim()).filter(c => c.length > 0)
        : null
    }
    
    // Log détaillé pour debug
    console.log('📤 [HANDLE SUBMIT] Données finales à envoyer:', {
      ...updateData,
      'birth_date_details': {
        'value': updateData.birth_date,
        'type': typeof updateData.birth_date,
        'isNull': updateData.birth_date === null,
        'isEmpty': updateData.birth_date === '',
        'length': updateData.birth_date?.length
      }
    })
    
    console.log('🚀 [HANDLE SUBMIT] Envoi de la requête PUT vers /teacher/profile')
    
    const response = await $api.put('/teacher/profile', updateData)
    
    console.log('✅ [HANDLE SUBMIT] Réponse reçue:', {
      'status': response.status,
      'data': response.data,
      'profile_birth_date': response.data?.profile?.birth_date,
      'teacher_birth_date': response.data?.teacher?.birth_date,
      'full_response': response.data
    })
    
    if (response.data) {
      const toast = useToast()
      toast.success('Profil mis à jour avec succès')
      
      // Recharger les données pour avoir les valeurs à jour
      console.log('🔄 [HANDLE SUBMIT] Rechargement des données du profil...')
      await loadProfileData()
      
      console.log('✅ [HANDLE SUBMIT] Données rechargées, vérification birth_date:', {
        'form.birth_date après reload': form.value.birth_date,
        'doit correspondre à': updateData.birth_date
      })
    }
  } catch (err) {
    console.error('Erreur lors de la mise à jour du profil:', err)
    
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      const toast = useToast()
      toast.error(err.response?.data?.message || 'Erreur lors de la mise à jour du profil')
    }
  } finally {
    isSaving.value = false
  }
}

// Fonction pour calculer l'âge à partir d'une date de naissance (format YYYY-MM-DD)
const calculateAge = (birthDate) => {
  if (!birthDate || birthDate.length !== 10) {
    return ''
  }
  
  try {
    const birth = new Date(birthDate)
    const today = new Date()
    
    let age = today.getFullYear() - birth.getFullYear()
    const monthDiff = today.getMonth() - birth.getMonth()
    
    // Si l'anniversaire n'est pas encore passé cette année, on soustrait 1 an
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
      age--
    }
    
    return age
  } catch (error) {
    console.error('Erreur calcul âge:', error)
    return ''
  }
}

// Fonction appelée lors de la modification de la date de naissance
const onBirthDateChange = (event) => {
  const newValue = event.target.value
  console.log('📅 [BIRTH_DATE CHANGE] Changement détecté:', {
    'event.type': event.type,
    'event.target.value': event.target.value,
    'form.birth_date (avant)': form.value.birth_date,
    'form.birth_date (après)': newValue,
    'newValue type': typeof newValue,
    'newValue length': newValue?.length,
    'is empty': !newValue || newValue.trim() === '',
    'timestamp': new Date().toISOString()
  })
  
  // Le v-model mettra automatiquement à jour form.birth_date
  // On attend un tick pour vérifier la valeur finale
  nextTick(() => {
    console.log('✅ [BIRTH_DATE CHANGE] Après nextTick:', {
      'form.birth_date final': form.value.birth_date,
      'type': typeof form.value.birth_date,
      'is empty string': form.value.birth_date === '',
      'is null': form.value.birth_date === null,
      'is undefined': form.value.birth_date === undefined,
      'âge calculé': calculateAge(form.value.birth_date)
    })
  })
}

useHead({
  title: 'Mon Profil Enseignant'
})
</script>