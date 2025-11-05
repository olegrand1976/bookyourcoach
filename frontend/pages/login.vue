<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 flex items-center justify-center p-4">
    <div class="w-full max-w-6xl">
      <div class="grid md:grid-cols-2 gap-8 items-center">
        
        <!-- Section Gauche - Branding & Illustration -->
        <div class="hidden md:flex flex-col justify-center space-y-8 p-12">
          <div class="space-y-4">
            <div class="inline-flex items-center space-x-2 bg-blue-600 text-white px-4 py-2 rounded-full text-sm font-semibold">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
              </svg>
              <span>activibe</span>
            </div>
            <h1 class="text-5xl font-bold text-gray-900 leading-tight">
              Bienvenue sur<br/>
              <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                activibe
              </span>
            </h1>
            <p class="text-xl text-gray-600 leading-relaxed">
              Votre plateforme de réservation de cours et d'activités sportives
            </p>
          </div>

          <!-- Illustration / Features -->
          <div class="space-y-4">
            <div class="flex items-start space-x-3">
              <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">Réservation simplifiée</h3>
                <p class="text-sm text-gray-600">Gérez vos cours en quelques clics</p>
              </div>
            </div>

            <div class="flex items-start space-x-3">
              <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">Multi-rôles</h3>
                <p class="text-sm text-gray-600">Élèves, enseignants et clubs</p>
              </div>
            </div>

            <div class="flex items-start space-x-3">
              <div class="flex-shrink-0 w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">Sécurisé</h3>
                <p class="text-sm text-gray-600">Vos données sont protégées</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Section Droite - Formulaire -->
        <div class="w-full">
          <div class="bg-white rounded-2xl shadow-2xl p-8 md:p-10 border border-gray-100">
            <!-- En-tête -->
            <div class="text-center mb-8">
              <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
              </div>
              <h2 class="text-3xl font-bold text-gray-900 mb-2">Connexion</h2>
              <p class="text-gray-600">Accédez à votre espace personnel</p>
            </div>

            <!-- Formulaire -->
            <form @submit.prevent="handleLogin" class="space-y-5">
              <!-- Email -->
              <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                  Adresse email
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                    </svg>
                  </div>
                  <input 
                    id="email" 
                    v-model="form.email" 
                    type="email" 
                    autocomplete="email" 
                    required
                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                    placeholder="vous@exemple.com"
                  />
                </div>
              </div>

              <!-- Mot de passe -->
              <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                  Mot de passe
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                  </div>
                  <input 
                    id="password" 
                    v-model="form.password" 
                    :type="showPassword ? 'text' : 'password'" 
                    autocomplete="current-password"
                    required
                    class="block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                    placeholder="••••••••"
                  />
                  <button 
                    type="button" 
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                  >
                    <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Se souvenir / Mot de passe oublié -->
              <div class="flex items-center justify-between">
                <label class="flex items-center cursor-pointer group">
                  <input 
                    id="remember-me" 
                    v-model="form.remember" 
                    type="checkbox"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-all duration-200 cursor-pointer"
                  />
                  <span class="ml-2 text-sm text-gray-700 group-hover:text-gray-900 transition-colors">
                    Se souvenir de moi
                  </span>
                </label>

                <button 
                  type="button" 
                  @click="showForgotPassword = true" 
                  class="text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors"
                >
                  Mot de passe oublié ?
                </button>
              </div>

              <!-- Messages d'erreur de validation -->
              <div v-if="validationErrors.length > 0" class="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-4 animate-shake">
                <div class="flex items-start">
                  <svg class="h-5 w-5 text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  <div class="ml-3">
                    <ul class="text-sm text-yellow-700 space-y-1">
                      <li v-for="validationError in validationErrors" :key="validationError">
                        {{ validationError }}
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Messages d'erreur -->
              <div v-if="error" class="bg-red-50 border-l-4 border-red-400 rounded-lg p-4 animate-shake">
                <div class="flex items-start">
                  <svg class="h-5 w-5 text-red-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                  </svg>
                  <div class="ml-3">
                    <p class="text-sm text-red-700">{{ error }}</p>
                  </div>
                </div>
              </div>

              <!-- Bouton de connexion -->
              <button 
                type="submit" 
                :disabled="loading"
                class="w-full flex items-center justify-center py-3 px-4 border border-transparent rounded-lg text-white font-semibold bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none shadow-lg hover:shadow-xl"
              >
                <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span v-if="loading">Connexion en cours...</span>
                <span v-else>Connexion</span>
              </button>
            </form>

            <!-- Liens d'inscription -->
            <div class="mt-8 pt-6 border-t border-gray-200">
              <p class="text-center text-sm text-gray-600 mb-3">
                Pas encore de compte ?
              </p>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Inscription Élève -->
                <NuxtLink 
                  to="/register" 
                  class="flex items-center justify-center px-4 py-2.5 border-2 border-blue-200 rounded-lg text-blue-700 font-medium hover:bg-blue-50 hover:border-blue-300 transition-all duration-200"
                >
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                  </svg>
                  <span class="hidden sm:inline">Inscription</span>
                  <span class="sm:hidden">Élève</span>
                </NuxtLink>
                
                <!-- Inscription Enseignant -->
                <NuxtLink 
                  to="/register-teacher" 
                  class="flex items-center justify-center px-4 py-2.5 border-2 border-emerald-200 rounded-lg text-emerald-700 font-medium hover:bg-emerald-50 hover:border-emerald-300 transition-all duration-200"
                >
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                  Enseignant
                </NuxtLink>
                
                <!-- Inscription Club -->
                <NuxtLink 
                  to="/register-club" 
                  class="flex items-center justify-center px-4 py-2.5 border-2 border-purple-200 rounded-lg text-purple-700 font-medium hover:bg-purple-50 hover:border-purple-300 transition-all duration-200"
                >
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                  </svg>
                  Club
                </NuxtLink>
              </div>
              
              <!-- Description des types d'inscription -->
              <div class="mt-4 text-center">
                <p class="text-xs text-gray-500">
                  Choisissez le type de compte correspondant à votre profil
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Mot de passe oublié -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div 
          v-if="showForgotPassword" 
          class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
          @click.self="closeForgotPassword"
        >
          <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-200"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4"
          >
            <div 
              v-if="showForgotPassword"
              class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 border border-gray-100"
            >
              <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                  <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-xl">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                  </div>
                  <h3 class="text-2xl font-bold text-gray-900">Mot de passe oublié</h3>
                </div>
                <button 
                  @click="closeForgotPassword"
                  class="text-gray-400 hover:text-gray-600 transition-colors"
                >
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
              
              <p class="text-gray-600 mb-6">
                Entrez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.
              </p>

              <form @submit.prevent="handleForgotPassword" class="space-y-5">
                <div>
                  <label for="forgot-email" class="block text-sm font-semibold text-gray-700 mb-2">
                    Adresse email
                  </label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                      </svg>
                    </div>
                    <input 
                      id="forgot-email" 
                      v-model="forgotPasswordForm.email" 
                      type="email" 
                      required
                      class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                      placeholder="vous@exemple.com"
                    />
                  </div>
                </div>

                <!-- Messages -->
                <div v-if="forgotPasswordError" class="bg-red-50 border-l-4 border-red-400 rounded-lg p-4 animate-shake">
                  <div class="flex items-start">
                    <svg class="h-5 w-5 text-red-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="ml-3 text-sm text-red-700">{{ forgotPasswordError }}</p>
                  </div>
                </div>

                <div v-if="forgotPasswordSuccess" class="bg-green-50 border-l-4 border-green-400 rounded-lg p-4 animate-pulse">
                  <div class="flex items-start">
                    <svg class="h-5 w-5 text-green-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="ml-3 text-sm text-green-700">{{ forgotPasswordSuccess }}</p>
                  </div>
                </div>

                <!-- Boutons -->
                <div class="flex space-x-3">
                  <button 
                    type="button" 
                    @click="closeForgotPassword"
                    class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 hover:border-gray-400 transition-all duration-200"
                  >
                    Annuler
                  </button>
                  <button 
                    type="submit" 
                    :disabled="forgotPasswordLoading"
                    class="flex-1 px-4 py-3 border border-transparent rounded-lg text-white font-semibold bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl"
                  >
                    <span v-if="forgotPasswordLoading">Envoi en cours...</span>
                    <span v-else>Envoyer</span>
                  </button>
                </div>
              </form>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
const authStore = useAuthStore()
const router = useRouter()

// Fonction toast simple
const showToast = (message, type = 'info') => {
  console.log(`[${type.toUpperCase()}] ${message}`)
  // TODO: Implémenter un vrai système de toast
}

const form = reactive({
  email: '',
  password: '',
  remember: false
})

const loading = ref(false)
const error = ref('')
const validationErrors = ref([])
const showPassword = ref(false)

// Variables pour "Mot de passe oublié"
const showForgotPassword = ref(false)
const forgotPasswordLoading = ref(false)
const forgotPasswordError = ref('')
const forgotPasswordSuccess = ref('')
const forgotPasswordForm = reactive({
  email: ''
})

// Fonction de validation renforcée
const validateForm = () => {
  const errors = []

  if (!form.email) {
    errors.push('Email requis')
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.push('Format email invalide')
  }

  if (!form.password) {
    errors.push('Mot de passe requis')
  } else if (form.password.length < 6) {
    errors.push('Mot de passe trop court (minimum 6 caractères)')
  }

  return errors
}

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  validationErrors.value = []

  // Validation côté client
  const clientErrors = validateForm()
  if (clientErrors.length > 0) {
    validationErrors.value = clientErrors
    loading.value = false
    return
  }

  try {
    await authStore.login({
      email: form.email,
      password: form.password,
      remember: form.remember
    })

    showToast('Connexion réussie', 'success')

    // Rediriger selon le rôle (admin en priorité)
    if (authStore.isAdmin) {
      await navigateTo('/admin')
    } else if (authStore.isTeacher) {
      await navigateTo('/teacher/dashboard')
    } else if (authStore.isClub) {
      await navigateTo('/club/dashboard')
    } else if (authStore.isStudent) {
      await navigateTo('/student/dashboard')
    } else {
      await navigateTo('/dashboard')
    }
  } catch (err) {
    console.error('Erreur de connexion complète:', err)
    console.error('Response data:', err.response?.data)
    console.error('Response status:', err.response?.status)
    console.error('URL de base API:', useRuntimeConfig().public.apiBase)

    let errorMessage = 'Une erreur est survenue'

    if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.code === 'ECONNREFUSED' || err.message.includes('Network Error')) {
      errorMessage = 'Impossible de se connecter au serveur. Vérifiez que l\'API est accessible.'
    } else if (err.response?.status === 422) {
      errorMessage = 'Informations de connexion invalides'
    } else if (err.response?.status === 401) {
      errorMessage = 'Email ou mot de passe incorrect'
    } else {
      errorMessage = `Erreur: ${err.message || 'Erreur inconnue'}`
    }

    error.value = errorMessage
    showToast('Erreur de connexion', 'error')
  } finally {
    loading.value = false
  }
}

// Rediriger si déjà connecté (admin en priorité)
watchEffect(() => {
  if (authStore.isAuthenticated) {
    if (authStore.isAdmin) {
      navigateTo('/admin')
    } else if (authStore.isTeacher) {
      navigateTo('/teacher/dashboard')
    } else if (authStore.isClub) {
      navigateTo('/club/dashboard')
    } else if (authStore.isStudent) {
      navigateTo('/student/dashboard')
    } else {
      navigateTo('/dashboard')
    }
  }
})

// Fonctions pour "Mot de passe oublié"
const handleForgotPassword = async () => {
  forgotPasswordLoading.value = true
  forgotPasswordError.value = ''
  forgotPasswordSuccess.value = ''

  try {
    await authStore.forgotPassword(forgotPasswordForm.email)
    forgotPasswordSuccess.value = 'Un email de réinitialisation a été envoyé à votre adresse email.'
    forgotPasswordForm.email = ''
    
    // Fermer la modale après 3 secondes
    setTimeout(() => {
      closeForgotPassword()
    }, 3000)
  } catch (err) {
    console.error('Erreur lors de la demande de réinitialisation:', err)
    
    if (err.response?.data?.message) {
      forgotPasswordError.value = err.response.data.message
    } else if (err.response?.status === 404) {
      forgotPasswordError.value = 'Aucun compte trouvé avec cette adresse email.'
    } else {
      forgotPasswordError.value = 'Une erreur est survenue. Veuillez réessayer.'
    }
  } finally {
    forgotPasswordLoading.value = false
  }
}

const closeForgotPassword = () => {
  showForgotPassword.value = false
  forgotPasswordForm.email = ''
  forgotPasswordError.value = ''
  forgotPasswordSuccess.value = ''
}

// SEO
useHead({
  title: 'Connexion | activibe',
  meta: [
    { name: 'description', content: 'Connectez-vous à votre compte activibe pour gérer vos cours et activités sportives.' }
  ]
})
</script>

<style scoped>
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
  20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.animate-shake {
  animation: shake 0.5s;
}
</style>