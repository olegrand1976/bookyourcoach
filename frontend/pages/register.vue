<template>
  <div class="min-h-screen bg-gray-50">
    <div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-md w-full space-y-8">
        <div>
          <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Inscription
          </h2>
          <p class="mt-2 text-center text-sm text-gray-600">
            Déjà un compte ?
            <NuxtLink to="/login" class="font-medium text-primary-600 hover:text-primary-500">
              Connexion
            </NuxtLink>
          </p>
        </div>

        <!-- Sélection du type de profil -->
        <div v-if="!selectedProfile" class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900 text-center">Choisissez votre profil</h3>
          
          <div class="grid grid-cols-1 gap-4">
            <button
              @click="selectProfile('student')"
              class="p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors text-left"
            >
              <div class="flex items-center space-x-3">
                <span class="text-2xl">🎓</span>
                <div>
                  <h4 class="font-medium text-gray-900">Élève</h4>
                  <p class="text-sm text-gray-600">Réservez des cours d'équitation et de natation</p>
                </div>
              </div>
            </button>

            <button
              @click="selectProfile('teacher')"
              class="p-4 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-colors text-left"
            >
              <div class="flex items-center space-x-3">
                <span class="text-2xl">👨‍🏫</span>
                <div>
                  <h4 class="font-medium text-gray-900">Enseignant</h4>
                  <p class="text-sm text-gray-600">Proposez vos services et gérez vos cours</p>
                </div>
              </div>
            </button>

            <button
              @click="selectProfile('club')"
              class="p-4 border-2 border-gray-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-colors text-left"
            >
              <div class="flex items-center space-x-3">
                <span class="text-2xl">🏢</span>
                <div>
                  <h4 class="font-medium text-gray-900">Club</h4>
                  <p class="text-sm text-gray-600">Gérez votre centre équestre ou piscine</p>
                </div>
              </div>
            </button>
          </div>
        </div>

        <!-- Formulaire d'inscription -->
        <form v-else class="mt-8 space-y-6" @submit.prevent="handleRegister">
          <!-- Informations de base -->
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-medium text-gray-900">
                Inscription {{ getProfileLabel(selectedProfile) }}
              </h3>
              <button
                type="button"
                @click="selectedProfile = null"
                class="text-sm text-gray-500 hover:text-gray-700"
              >
                ← Changer de profil
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700">
                  Prénom *
                </label>
                <input
                  id="first_name"
                  v-model="form.first_name"
                  name="first_name"
                  type="text"
                  required
                  class="input-field"
                  placeholder="Prénom"
                />
              </div>
              
              <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700">
                  Nom *
                </label>
                <input
                  id="last_name"
                  v-model="form.last_name"
                  name="last_name"
                  type="text"
                  required
                  class="input-field"
                  placeholder="Nom"
                />
              </div>
            </div>
            
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700">
                Adresse email *
              </label>
              <input
                id="email"
                v-model="form.email"
                name="email"
                type="email"
                autocomplete="email"
                required
                class="input-field"
                placeholder="Adresse email"
              />
            </div>

            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700">
                Téléphone
              </label>
              <input
                id="phone"
                v-model="form.phone"
                name="phone"
                type="tel"
                class="input-field"
                placeholder="Téléphone"
              />
            </div>

            <div>
              <label for="birth_date" class="block text-sm font-medium text-gray-700">
                Date de naissance
              </label>
              <input
                id="birth_date"
                v-model="form.birth_date"
                name="birth_date"
                type="date"
                class="input-field"
              />
            </div>

            <!-- Champs spécifiques selon le profil -->
            <div v-if="selectedProfile === 'club'" class="space-y-4">
              <div>
                <label for="club_name" class="block text-sm font-medium text-gray-700">
                  Nom du club *
                </label>
                <input
                  id="club_name"
                  v-model="form.club_name"
                  name="club_name"
                  type="text"
                  required
                  class="input-field"
                  placeholder="Nom du club"
                />
              </div>

              <div>
                <label for="club_description" class="block text-sm font-medium text-gray-700">
                  Description du club
                </label>
                <textarea
                  id="club_description"
                  v-model="form.club_description"
                  name="club_description"
                  rows="3"
                  class="input-field"
                  placeholder="Décrivez votre club..."
                ></textarea>
              </div>
            </div>

            <div v-if="selectedProfile === 'teacher'" class="space-y-4">
              <div>
                <label for="specialties" class="block text-sm font-medium text-gray-700">
                  Spécialités
                </label>
                <div class="space-y-2">
                  <label v-for="specialty in specialties" :key="specialty" class="flex items-center">
                    <input
                      v-model="form.specialties"
                      :value="specialty"
                      type="checkbox"
                      class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                    />
                    <span class="ml-2 text-sm text-gray-700">{{ specialty }}</span>
                  </label>
                </div>
              </div>

              <div>
                <label for="experience_years" class="block text-sm font-medium text-gray-700">
                  Années d'expérience
                </label>
                <input
                  id="experience_years"
                  v-model="form.experience_years"
                  name="experience_years"
                  type="number"
                  min="0"
                  class="input-field"
                  placeholder="Nombre d'années"
                />
              </div>
            </div>

            <!-- Adresse -->
            <div class="space-y-4">
              <h4 class="text-md font-medium text-gray-700">Adresse</h4>
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                  <label for="street" class="block text-sm text-gray-600 mb-1">Rue</label>
                  <input
                    id="street"
                    v-model="form.street"
                    name="street"
                    type="text"
                    class="input-field"
                    placeholder="Nom de la rue"
                  />
                </div>
                <div>
                  <label for="street_number" class="block text-sm text-gray-600 mb-1">Numéro</label>
                  <input
                    id="street_number"
                    v-model="form.street_number"
                    name="street_number"
                    type="text"
                    class="input-field"
                    placeholder="92, 92/A, 92B..."
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label for="postal_code" class="block text-sm text-gray-600 mb-1">Code postal</label>
                  <input
                    id="postal_code"
                    v-model="form.postal_code"
                    name="postal_code"
                    type="text"
                    class="input-field"
                    placeholder="1000"
                  />
                </div>
                <div>
                  <label for="city" class="block text-sm text-gray-600 mb-1">Ville</label>
                  <input
                    id="city"
                    v-model="form.city"
                    name="city"
                    type="text"
                    class="input-field"
                    placeholder="Bruxelles"
                  />
                </div>
                <div>
                  <label for="country" class="block text-sm text-gray-600 mb-1">Pays</label>
                  <select
                    id="country"
                    v-model="form.country"
                    name="country"
                    class="input-field"
                  >
                    <option value="Belgium">Belgique</option>
                    <option value="France">France</option>
                    <option value="Netherlands">Pays-Bas</option>
                    <option value="Germany">Allemagne</option>
                    <option value="Luxembourg">Luxembourg</option>
                    <option value="Switzerland">Suisse</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Mot de passe -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="password" class="block text-sm font-medium text-gray-700">
                  Mot de passe *
                </label>
                <input
                  id="password"
                  v-model="form.password"
                  name="password"
                  type="password"
                  required
                  class="input-field"
                  placeholder="Mot de passe"
                />
              </div>
              
              <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                  Confirmer le mot de passe *
                </label>
                <input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  name="password_confirmation"
                  type="password"
                  required
                  class="input-field"
                  placeholder="Confirmer le mot de passe"
                />
              </div>
            </div>
          </div>

          <!-- Conditions d'utilisation -->
          <div class="flex items-start">
            <input
              id="terms"
              v-model="form.terms"
              name="terms"
              type="checkbox"
              required
              class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded mt-1"
            />
            <label for="terms" class="ml-2 block text-sm text-gray-900">
              J'accepte les 
              <a href="#" class="text-primary-600 hover:text-primary-500">conditions d'utilisation</a>
              et la 
              <a href="#" class="text-primary-600 hover:text-primary-500">politique de confidentialité</a>
            </label>
          </div>

          <!-- Message d'information sur la validation -->
          <div v-if="selectedProfile === 'club'" class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <span class="text-yellow-400">⚠️</span>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">
                  Validation requise
                </h3>
                <div class="mt-2 text-sm text-yellow-700">
                  <p>Votre inscription sera soumise à validation par un administrateur. Vous recevrez un email de confirmation une fois approuvée.</p>
                </div>
              </div>
            </div>
          </div>

          <div v-if="selectedProfile === 'teacher'" class="bg-blue-50 border border-blue-200 rounded-md p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <span class="text-blue-400">ℹ️</span>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">
                  Validation par un club
                </h3>
                <div class="mt-2 text-sm text-blue-700">
                  <p>Votre inscription sera soumise à validation par un club partenaire. Vous pourrez ensuite proposer vos services.</p>
                </div>
              </div>
            </div>
          </div>

          <div>
            <button
              type="submit"
              :disabled="loading"
              class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
            >
              <span v-if="loading">Création du compte...</span>
              <span v-else>S'inscrire comme {{ getProfileLabel(selectedProfile) }}</span>
            </button>
          </div>

          <!-- Messages d'erreur -->
          <div v-if="errors.length > 0" class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="text-sm text-red-600">
              <ul class="list-disc list-inside space-y-1">
                <li v-for="error in errors" :key="error">{{ error }}</li>
              </ul>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
const authStore = useAuthStore()

// État du formulaire
const selectedProfile = ref(null)
const loading = ref(false)
const errors = ref([])

// Spécialités pour les enseignants
const specialties = ref([
  'Équitation',
  'Natation',
  'Dressage',
  'Saut d\'obstacles',
  'Cross-country',
  'Voltige',
  'Pony-games',
  'Aquagym',
  'Aquabike',
  'Nage libre'
])

// Formulaire réactif
const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  birth_date: '',
  street: '',
  street_number: '',
  postal_code: '',
  city: '',
  country: 'Belgium',
  password: '',
  password_confirmation: '',
  terms: false,
  // Champs spécifiques
  club_name: '',
  club_description: '',
  specialties: [],
  experience_years: ''
})

// Fonctions utilitaires
const getProfileLabel = (profile) => {
  const labels = {
    student: 'Élève',
    teacher: 'Enseignant',
    club: 'Club'
  }
  return labels[profile] || ''
}

const selectProfile = (profile) => {
  selectedProfile.value = profile
  form.role = profile
}

// Fonction toast simple
const showToast = (message, type = 'info') => {
  console.log(`[${type.toUpperCase()}] ${message}`)
  // TODO: Implémenter un vrai système de toast
}

// Gestion de l'inscription
const handleRegister = async () => {
  loading.value = true
  errors.value = []
  
  try {
    // Préparer les données selon le profil
    const registrationData = {
      first_name: form.first_name,
      last_name: form.last_name,
      email: form.email,
      phone: form.phone,
      birth_date: form.birth_date,
      street: form.street,
      street_number: form.street_number,
      postal_code: form.postal_code,
      city: form.city,
      country: form.country,
      password: form.password,
      password_confirmation: form.password_confirmation,
      role: selectedProfile.value,
      // Champs spécifiques
      ...(selectedProfile.value === 'club' && {
        club_name: form.club_name,
        club_description: form.club_description
      }),
      ...(selectedProfile.value === 'teacher' && {
        specialties: form.specialties,
        experience_years: form.experience_years
      })
    }

    console.log('Données d\'inscription:', registrationData)

    // Appel à l'API d'inscription
    const config = useRuntimeConfig()
    const response = await $fetch('/auth/register', {
      method: 'POST',
      baseURL: config.public.apiBase,
      body: registrationData,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    })

    console.log('Réponse d\'inscription:', response)
    
    // Message de succès selon le profil
    let successMessage = 'Inscription réussie !'
    if (selectedProfile.value === 'club') {
      successMessage = 'Votre inscription a été soumise. Un administrateur validera votre compte sous 24-48h.'
    } else if (selectedProfile.value === 'teacher') {
      successMessage = 'Votre inscription a été soumise. Un club partenaire validera votre compte.'
    } else {
      successMessage = 'Votre compte a été créé avec succès !'
    }

    showToast(successMessage, 'success')
    
    // Redirection selon le profil
    if (selectedProfile.value === 'student') {
      await navigateTo('/login')
    } else {
      await navigateTo('/login?pending=true')
    }
    
  } catch (err) {
    console.error('Erreur d\'inscription:', err)
    
    if (err.response?.data?.errors) {
      // Erreurs de validation Laravel
      const validationErrors = err.response.data.errors
      errors.value = Object.values(validationErrors).flat()
    } else {
      errors.value = [err.response?.data?.message || 'Une erreur est survenue lors de l\'inscription']
    }
    showToast('Erreur lors de l\'inscription', 'error')
  } finally {
    loading.value = false
  }
}

// Rediriger si déjà connecté
watchEffect(() => {
  if (authStore.isAuthenticated) {
    navigateTo('/dashboard')
  }
})
</script>
