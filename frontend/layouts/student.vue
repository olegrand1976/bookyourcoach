<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header Student Unifié -->
    <nav class="bg-white shadow-lg border-b-4 border-blue-500">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
          <div class="flex items-center">
            <NuxtLink to="/student/dashboard" class="flex items-center space-x-3 text-xl font-bold text-gray-900">
              <img src="/logo-activibe.svg" alt="Acti'Vibe" class="h-12 w-auto" />
              <span class="bg-blue-100 text-blue-700 text-sm font-semibold px-2.5 py-1 rounded-full">Élève</span>
            </NuxtLink>
          </div>

          <div class="flex items-center space-x-6">
            <div class="relative">
              <button @click="toggleUserMenu" class="flex items-center space-x-2 text-gray-900 bg-gray-50 px-4 py-2 rounded-lg">
                <span class="font-medium">{{ userName }}</span>
                <ChevronDownIcon class="w-4 h-4" />
              </button>

              <div v-if="userMenuOpen" class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl py-2 z-50 border border-gray-200">
                <p class="px-4 py-2 text-xs text-gray-500">Menu Élève</p>
                <NuxtLink 
                  to="/student/dashboard" 
                  @click="userMenuOpen = false" 
                  class="flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-gray-100"
                >
                  <span>📊</span>
                  <span>Tableau de bord</span>
                </NuxtLink>
                <NuxtLink 
                  to="/student/schedule" 
                  @click="userMenuOpen = false" 
                  class="flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-gray-100"
                >
                  <span>📅</span>
                  <span>Planning</span>
                </NuxtLink>
                <NuxtLink 
                  to="/student/profile" 
                  @click="userMenuOpen = false" 
                  class="flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-gray-100"
                >
                  <span>👤</span>
                  <span>Mon profil</span>
                </NuxtLink>
                <NuxtLink 
                  to="/student/subscriptions" 
                  @click="userMenuOpen = false" 
                  class="flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-gray-100"
                >
                  <span>💳</span>
                  <span>Abonnement</span>
                </NuxtLink>
                
                <hr class="my-2 border-gray-200">

                <button 
                  @click="userMenuOpen = false; logout()" 
                  class="flex items-center space-x-3 w-full text-left px-4 py-2 text-red-600 hover:bg-red-50"
                >
                  <span>🚪</span>
                  <span>Déconnexion</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Contenu principal -->
    <main>
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

// Composables
const authStore = useAuthStore()

// State
const userMenuOpen = ref(false)
const userName = computed(() => authStore.userName || 'Élève')

const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value
}

const closeMenu = (e: Event) => {
  if (!(e.target as Element)?.closest('.relative')) {
    userMenuOpen.value = false
  }
}

const logout = async () => {
  console.log('🚪 [STUDENT LAYOUT] Début de la déconnexion')
  
  // Fermer le menu utilisateur
  userMenuOpen.value = false
  
  try {
    // Appeler la déconnexion du store
    await authStore.logout()
    console.log('🚪 [STUDENT LAYOUT] Store logout terminé')
    
    // Attendre un peu pour que les changements se propagent
    await new Promise(resolve => setTimeout(resolve, 100))
    
    // Forcer la redirection
    await navigateTo('/login')
    console.log('🚪 [STUDENT LAYOUT] Redirection vers /login')
    
  } catch (error) {
    console.error('❌ [STUDENT LAYOUT] Erreur lors de la déconnexion:', error)
    
    // Nettoyage manuel en cas d'erreur
    authStore.user = null
    authStore.token = null
    authStore.isAuthenticated = false
    
    const tokenCookie = useCookie('auth-token')
    tokenCookie.value = null
    
    if (process.client) {
      localStorage.removeItem('user-data')
    }
    
    // Redirection forcée vers la page de connexion
    if (process.client) {
      window.location.href = '/login'
    }
  }
}

onMounted(() => {
  document.addEventListener('click', closeMenu)
})

onUnmounted(() => {
  document.removeEventListener('click', closeMenu)
})
</script>

<style scoped>
/* Styles spécifiques si nécessaire */
</style>
