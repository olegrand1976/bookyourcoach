<template>
  <!-- Modal avec design moderne et responsive -->
  <div class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden">
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
                <label class="block text-sm font-medium text-gray-700">Registre national (NISS)</label>
                <input 
                  v-model="form.niss" 
                  type="text" 
                  maxlength="15"
                  placeholder="XX.XX.XX-XXX.XX"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Années d'expérience</label>
                <input 
                  v-model.number="form.experience_years" 
                  type="number" 
                  min="0" 
                  placeholder="Ex: 5"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Type de contrat <span class="text-red-500">*</span>
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

          <!-- Section Informations bancaires -->
          <div class="bg-yellow-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">Informations bancaires</h4>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Numéro de compte bancaire</label>
                <input 
                  v-model="form.bank_account_number" 
                  type="text" 
                  maxlength="50"
                  placeholder="BE12 3456 7890 1234"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
              </div>
            </div>
          </div>

          <!-- Section Adresse -->
          <div class="bg-indigo-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-indigo-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">Adresse</h4>
            </div>
            
            <div class="grid grid-cols-1 gap-6">
              <div class="grid grid-cols-4 gap-4">
                <div class="col-span-2 space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Rue</label>
                  <input 
                    v-model="form.street" 
                    type="text" 
                    maxlength="255"
                    placeholder="Rue de..."
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  >
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Numéro</label>
                  <input 
                    v-model="form.street_number" 
                    type="text" 
                    maxlength="20"
                    placeholder="123"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  >
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Boîte</label>
                  <input 
                    v-model="form.street_box" 
                    type="text" 
                    maxlength="20"
                    placeholder="Bte 5"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  >
                </div>
              </div>
              
              <div class="grid grid-cols-3 gap-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Code postal</label>
                  <input 
                    v-model="form.postal_code" 
                    type="text" 
                    maxlength="10"
                    placeholder="1000"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  >
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Ville</label>
                  <input 
                    v-model="form.city" 
                    type="text" 
                    maxlength="255"
                    placeholder="Bruxelles"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  >
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Pays</label>
                  <input 
                    v-model="form.country" 
                    type="text" 
                    maxlength="255"
                    placeholder="Belgium"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  >
                </div>
              </div>
            </div>
          </div>

          <!-- Section Tarifs et Bio -->
          <div class="bg-emerald-50 rounded-xl p-6">
            <div class="flex items-center mb-4">
              <div class="bg-emerald-100 p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">Tarifs et présentation</h4>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Bio / Présentation</label>
                <textarea 
                  v-model="form.bio" 
                  rows="4" 
                  placeholder="Décrivez votre expérience et votre approche pédagogique..."
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
                ></textarea>
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
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
  teacher: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'success'])

const loading = ref(false)
const loadingData = ref(false)

const form = ref({
  name: '',
  email: '',
  phone: '',
  experience_years: 0,
  hourly_rate: 0,
  bio: '',
  contract_type: 'freelance',
  // Informations bancaires et nationales
  bank_account_number: '',
  niss: '',
  // Adresse
  street: '',
  street_number: '',
  street_box: '',
  postal_code: '',
  city: '',
  country: 'Belgium'
})

// Charger les données complètes du teacher depuis l'API
const loadFullTeacherData = async () => {
  if (!props.teacher || !props.teacher.id) {
    initializeFormFromProps()
    return
  }
  
  loadingData.value = true
  try {
    const { $api } = useNuxtApp()
    // Recharger depuis la liste des teachers pour avoir toutes les données
    const response = await $api.get('/club/teachers')
    
    if (response.data.success && response.data.teachers) {
      const fullTeacher = response.data.teachers.find(t => t.id === props.teacher.id)
      if (fullTeacher) {
        console.log('✅ [EditTeacherModal] Données complètes rechargées:', fullTeacher)
        console.log('✅ [EditTeacherModal] User object complet:', fullTeacher.user)
        
        // Utiliser les données complètes du teacher
        const user = fullTeacher.user || {}
        form.value = {
          name: fullTeacher.name || user.name || '',
          email: fullTeacher.email || user.email || '',
          phone: fullTeacher.phone || user.phone || '',
          experience_years: parseInt(fullTeacher.experience_years) || 0,
          hourly_rate: fullTeacher.hourly_rate || 0,
          bio: fullTeacher.bio || '',
          contract_type: fullTeacher.contract_type || 'freelance',
          // Informations bancaires et nationales
          bank_account_number: user.bank_account_number || '',
          niss: user.niss || '',
          // Adresse
          street: user.street || '',
          street_number: user.street_number || '',
          street_box: user.street_box || '',
          postal_code: user.postal_code || '',
          city: user.city || '',
          country: user.country || 'Belgium'
        }
        
        console.log('✅ [EditTeacherModal] Formulaire initialisé avec données complètes:', form.value)
        return
      }
    }
  } catch (error) {
    console.error('❌ [EditTeacherModal] Erreur lors du rechargement:', error)
  } finally {
    loadingData.value = false
  }
  
  // Fallback : utiliser les données passées en props
  initializeFormFromProps()
}

// Initialiser le formulaire depuis les props
const initializeFormFromProps = () => {
  if (!props.teacher) return
  
  console.log('🔵 [EditTeacherModal] Teacher reçu:', props.teacher)
  console.log('🔵 [EditTeacherModal] User object:', props.teacher.user)
  
  // Récupérer les données de l'utilisateur associé si disponible
  const user = props.teacher.user || {}
  
  console.log('🔵 [EditTeacherModal] Données utilisateur:', {
    niss: user.niss,
    bank_account_number: user.bank_account_number,
    street: user.street,
    street_number: user.street_number,
    street_box: user.street_box,
    postal_code: user.postal_code,
    city: user.city,
    country: user.country,
    user_keys: Object.keys(user)
  })
  
  form.value = {
    name: props.teacher.name || user.name || '',
    email: props.teacher.email || user.email || '',
    phone: props.teacher.phone || user.phone || '',
    experience_years: parseInt(props.teacher.experience_years) || 0,
    hourly_rate: props.teacher.hourly_rate || 0,
    bio: props.teacher.bio || '',
    contract_type: props.teacher.contract_type || 'freelance',
    // Informations bancaires et nationales
    bank_account_number: user.bank_account_number || '',
    niss: user.niss || '',
    // Adresse
    street: user.street || '',
    street_number: user.street_number || '',
    street_box: user.street_box || '',
    postal_code: user.postal_code || '',
    city: user.city || '',
    country: user.country || 'Belgium'
  }
  
  console.log('✅ [EditTeacherModal] Formulaire initialisé:', form.value)
}

// Initialiser le formulaire avec les données de l'enseignant
watch(() => props.teacher?.id, async (teacherId) => {
  if (teacherId) {
    console.log('🔵 [EditTeacherModal] Watch déclenché pour teacher ID:', teacherId)
    await loadFullTeacherData()
  }
}, { immediate: true })

// Recharger les données complètes au montage du composant
onMounted(async () => {
  if (props.teacher?.id) {
    await loadFullTeacherData()
  }
})

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
      experience_years: form.value.experience_years,
      hourly_rate: form.value.hourly_rate,
      bio: form.value.bio,
      contract_type: form.value.contract_type,
      // Informations bancaires et nationales
      bank_account_number: form.value.bank_account_number || null,
      niss: form.value.niss || null,
      // Adresse
      street: form.value.street || null,
      street_number: form.value.street_number || null,
      street_box: form.value.street_box || null,
      postal_code: form.value.postal_code || null,
      city: form.value.city || null,
      country: form.value.country || null,
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

