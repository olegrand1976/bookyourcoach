<template>
  <div class="student-layout min-h-screen bg-gray-50">
    <!-- Navigation principale -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <NuxtLink to="/student/dashboard" class="flex items-center space-x-3">
              <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
              </div>
              <span class="text-xl font-bold text-gray-900">Espace Étudiant</span>
            </NuxtLink>
          </div>

          <div class="flex items-center space-x-2 md:space-x-4">
            <!-- Accès rapides - Desktop -->
            <div class="hidden md:flex items-center space-x-2">
              <NuxtLink 
                to="/student/lessons"
                class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors"
                :class="isActiveRoute('/student/lessons') ? 'text-blue-600 bg-blue-50' : ''"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                Cours
              </NuxtLink>
              
              <NuxtLink 
                to="/student/bookings"
                class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 rounded-md transition-colors"
                :class="isActiveRoute('/student/bookings') ? 'text-emerald-600 bg-emerald-50' : ''"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Réservations
              </NuxtLink>
              
              <NuxtLink 
                to="/student/schedule"
                class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-orange-600 hover:bg-orange-50 rounded-md transition-colors"
                :class="isActiveRoute('/student/schedule') ? 'text-orange-600 bg-orange-50' : ''"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Planning
              </NuxtLink>
              
              <NuxtLink 
                to="/student/preferences"
                class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-purple-600 hover:bg-purple-50 rounded-md transition-colors"
                :class="isActiveRoute('/student/preferences') ? 'text-purple-600 bg-purple-50' : ''"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Préférences
              </NuxtLink>
            </div>

            <!-- Menu mobile - Bouton -->
            <div class="md:hidden">
              <button 
                @click="mobileMenuOpen = !mobileMenuOpen"
                class="p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-md"
              >
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
              </button>
            </div>

            <!-- Notifications -->
            <button class="hidden md:block p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-md">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12 7H4.828z" />
              </svg>
            </button>

            <!-- Profil utilisateur -->
            <div class="relative">
              <button 
                @click="userMenuOpen = !userMenuOpen"
                class="flex items-center space-x-2 text-sm rounded-md text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                  <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
                <span class="font-medium">{{ authStore.userName }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- Menu utilisateur -->
              <div v-if="userMenuOpen" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200">
                <NuxtLink 
                  to="/profile"
                  @click="userMenuOpen = false"
                  class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                >
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  Mon profil
                </NuxtLink>
                <button 
                  @click="userMenuOpen = false; handleLogout()"
                  class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                >
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                  Déconnexion
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Contenu principal avec sidebar -->
    <div class="flex">
      <!-- Sidebar -->
      <div class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 lg:pt-16 lg:pb-0 lg:bg-white lg:border-r lg:border-gray-200">
        <div class="flex-1 flex flex-col min-h-0">
          <nav class="flex-1 px-2 py-4 space-y-1">
            <NuxtLink 
              v-for="item in navigationItems" 
              :key="item.name"
              :to="item.href"
              :class="[
                'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors',
                isActiveRoute(item.href) 
                  ? 'bg-blue-100 text-blue-900' 
                  : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
              ]"
            >
              <component 
                :is="item.icon" 
                :class="[
                  'mr-3 flex-shrink-0 h-5 w-5',
                  isActiveRoute(item.href) ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500'
                ]"
              />
              {{ item.name }}
            </NuxtLink>
          </nav>
        </div>
      </div>

      <!-- Contenu principal -->
      <div class="flex-1 lg:pl-64">
        <main class="py-6">
          <slot />
        </main>
      </div>
    </div>

    <!-- Mobile menu -->
    <div v-if="mobileMenuOpen" class="md:hidden lg:hidden">
      <div class="fixed inset-0 z-40 flex">
        <div class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="mobileMenuOpen = false"></div>
        <div class="relative flex-1 flex flex-col max-w-xs w-full bg-white">
          <div class="absolute top-0 right-0 -mr-12 pt-2">
            <button 
              @click="mobileMenuOpen = false"
              class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
            >
              <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
            <!-- Accès rapides -->
            <div class="px-4 mb-4">
              <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Accès rapides</h3>
              <div class="space-y-1">
                <NuxtLink 
                  to="/student/lessons"
                  @click="mobileMenuOpen = false"
                  class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                  :class="isActiveRoute('/student/lessons') ? 'bg-blue-100 text-blue-900' : 'text-gray-700 hover:bg-gray-100'"
                >
                  <svg class="w-5 h-5 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                  </svg>
                  Cours disponibles
                </NuxtLink>
                
                <NuxtLink 
                  to="/student/bookings"
                  @click="mobileMenuOpen = false"
                  class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                  :class="isActiveRoute('/student/bookings') ? 'bg-emerald-100 text-emerald-900' : 'text-gray-700 hover:bg-gray-100'"
                >
                  <svg class="w-5 h-5 mr-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Mes réservations
                </NuxtLink>
                
                <NuxtLink 
                  to="/student/schedule"
                  @click="mobileMenuOpen = false"
                  class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                  :class="isActiveRoute('/student/schedule') ? 'bg-orange-100 text-orange-900' : 'text-gray-700 hover:bg-gray-100'"
                >
                  <svg class="w-5 h-5 mr-3 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  Mon planning
                </NuxtLink>
                
                <NuxtLink 
                  to="/student/preferences"
                  @click="mobileMenuOpen = false"
                  class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                  :class="isActiveRoute('/student/preferences') ? 'bg-purple-100 text-purple-900' : 'text-gray-700 hover:bg-gray-100'"
                >
                  <svg class="w-5 h-5 mr-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  Préférences
                </NuxtLink>
              </div>
            </div>
            
            <!-- Navigation principale -->
            <div class="px-4">
              <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Navigation</h3>
              <nav class="space-y-1">
                <NuxtLink 
                  v-for="item in navigationItems" 
                  :key="item.name"
                  :to="item.href"
                  @click="mobileMenuOpen = false"
                  :class="[
                    'group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors',
                    isActiveRoute(item.href) 
                      ? 'bg-blue-100 text-blue-900' 
                      : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                  ]"
                >
                  <component 
                    :is="item.icon" 
                    :class="[
                      'mr-4 flex-shrink-0 h-6 w-6',
                      isActiveRoute(item.href) ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500'
                    ]"
                  />
                  {{ item.name }}
                </NuxtLink>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

// Composables
const authStore = useAuthStore()
const toast = useToast()

// State
const userMenuOpen = ref(false)
const mobileMenuOpen = ref(false)

// Navigation items
const navigationItems = [
  {
    name: 'Tableau de bord',
    href: '/student/dashboard',
    icon: 'svg'
  },
  {
    name: 'Leçons disponibles',
    href: '/student/lessons',
    icon: 'svg'
  },
  {
    name: 'Mes réservations',
    href: '/student/bookings',
    icon: 'svg'
  },
  {
    name: 'Mes préférences',
    href: '/student/preferences',
    icon: 'svg'
  },
  {
    name: 'Historique',
    href: '/student/history',
    icon: 'svg'
  },
  {
    name: 'Mes abonnements',
    href: '/student/subscriptions',
    icon: 'svg'
  },
  {
    name: 'Enseignants',
    href: '/student/teachers',
    icon: 'svg'
  }
]

// Methods
const isActiveRoute = (href: string) => {
  const route = useRoute()
  return route.path === href
}

const handleLogout = async () => {
  try {
    await authStore.logout()
    toast.success('Déconnexion réussie')
  } catch (error) {
    toast.error('Erreur lors de la déconnexion')
  }
}

// Fermer les menus lors du clic à l'extérieur
onMounted(() => {
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.relative')) {
      userMenuOpen.value = false
    }
  })
})
</script>

<style scoped>
.student-layout {
  @apply relative;
}
</style>
