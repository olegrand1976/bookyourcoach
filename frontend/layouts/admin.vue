<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header Admin Unifié -->
    <nav class="bg-white shadow-lg border-b-4 border-red-500">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
          <div class="flex items-center">
            <NuxtLink to="/admin" class="flex items-center space-x-3 text-xl font-bold text-gray-900">
              <img src="/images/logo-activibe.svg" alt="Acti'Vibe" class="h-12 w-auto" />
              <span class="bg-red-100 text-red-700 text-sm font-semibold px-2.5 py-1 rounded-full">Admin</span>
            </NuxtLink>
          </div>

          <div class="flex items-center space-x-6">
            <div class="relative">
              <button @click="toggleUserMenu" class="flex items-center space-x-2 text-gray-900 bg-gray-50 px-4 py-2 rounded-lg">
                <span class="font-medium">{{ userName }}</span>
                <ChevronDownIcon class="w-4 h-4" />
              </button>

              <div v-if="userMenuOpen" class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl py-2 z-50 border border-gray-200">
                <p class="px-4 py-2 text-xs text-gray-500">Menu Administration</p>
                <NuxtLink to="/admin" class="flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-gray-100">
                  <span>📊</span>
                  <span>Dashboard</span>
                </NuxtLink>
                <NuxtLink to="/admin/users" class="flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-gray-100">
                  <span>👥</span>
                  <span>Utilisateurs</span>
                </NuxtLink>
                <NuxtLink to="/admin/contracts" class="flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-gray-100">
                  <span>📄</span>
                  <span>Contrats</span>
                </NuxtLink>
                <NuxtLink to="/admin/settings" class="flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-gray-100">
                  <span>⚙️</span>
                  <span>Paramètres</span>
                </NuxtLink>
                <NuxtLink to="/admin/graph-analysis" class="flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-gray-100">
                  <span>🔗</span>
                  <span>Analyse Graphique</span>
                </NuxtLink>
                
                <hr class="my-2 border-gray-200">

                <NuxtLink to="/" class="flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-gray-100">
                  <span>🌐</span>
                  <span>Retour au site</span>
                </NuxtLink>
                <button @click="logout" class="flex items-center space-x-3 w-full text-left px-4 py-2 text-red-600 hover:bg-red-50">
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

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const authStore = useAuthStore()

const userMenuOpen = ref(false)
const userName = computed(() => authStore.userName || 'Admin')

const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value
}

const closeMenu = (e) => {
  if (!e.target.closest('.relative')) {
    userMenuOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', closeMenu)
})

onUnmounted(() => {
  document.removeEventListener('click', closeMenu)
})

const logout = async () => {
  await authStore.logout()
}
</script>
