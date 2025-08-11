<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <NuxtLink to="/" class="text-xl font-bold text-primary-600">
              BookYourCoach
            </NuxtLink>
          </div>
          
          <div class="flex items-center space-x-4">
            <template v-if="authStore.isAuthenticated">
              <!-- Menu utilisateur authentifié -->
              <div class="relative" v-if="showUserMenu">
                <button 
                  @click="toggleUserMenu"
                  class="flex items-center space-x-2 text-gray-700 hover:text-gray-900"
                >
                  <span>{{ authStore.userName }}</span>
                  <ChevronDownIcon class="w-4 h-4" />
                </button>
                
                <div 
                  v-if="userMenuOpen"
                  class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50"
                >
                  <NuxtLink 
                    to="/profile" 
                    class="block px-4 py-2 text-gray-700 hover:bg-gray-100"
                  >
                    Mon profil
                  </NuxtLink>
                  
                  <NuxtLink 
                    v-if="authStore.isAdmin"
                    to="/admin" 
                    class="block px-4 py-2 text-gray-700 hover:bg-gray-100"
                  >
                    Administration
                  </NuxtLink>
                  
                  <hr class="my-2">
                  
                  <button 
                    @click="handleLogout"
                    class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100"
                  >
                    Se déconnecter
                  </button>
                </div>
              </div>
            </template>
            
            <template v-else>
              <!-- Menu utilisateur non authentifié -->
              <NuxtLink 
                to="/login" 
                class="text-gray-700 hover:text-gray-900"
              >
                Se connecter
              </NuxtLink>
              <NuxtLink 
                to="/register" 
                class="btn-primary"
              >
                S'inscrire
              </NuxtLink>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <!-- Contenu principal -->
    <main>
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-auto">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center text-gray-600">
          <p>&copy; 2025 BookYourCoach. Tous droits réservés.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const toast = useToast()

const userMenuOpen = ref(false)
const showUserMenu = computed(() => authStore.isAuthenticated)

const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value
}

const handleLogout = async () => {
  try {
    await authStore.logout()
    toast.success('Déconnexion réussie')
  } catch (error) {
    toast.error('Erreur lors de la déconnexion')
  }
}

// Fermer le menu utilisateur lors du clic à l'extérieur
onMounted(() => {
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.relative')) {
      userMenuOpen.value = false
    }
  })
})

// Initialiser l'authentification
onMounted(() => {
  authStore.initializeAuth()
})
</script>
