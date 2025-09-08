<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header Admin -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-4">
          <div class="flex items-center">
            <NuxtLink to="/admin" class="text-xl font-bold text-primary-600">
              🏆 activibe Admin
            </NuxtLink>
          </div>
          
          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600">
              Connecté en tant qu'administrateur
            </span>
            <NuxtLink to="/dashboard" class="text-sm text-primary-600 hover:text-primary-700">
              Retour au site
            </NuxtLink>
            <button @click="logout" class="text-sm text-red-600 hover:text-red-700">
              Déconnexion
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Navigation Admin -->
    <nav class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex space-x-8">
          <NuxtLink to="/admin" class="py-4 px-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
            Dashboard
          </NuxtLink>
          <NuxtLink to="/admin/users" class="py-4 px-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
            Utilisateurs
          </NuxtLink>
          <NuxtLink to="/admin/settings" class="py-4 px-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
            Paramètres
          </NuxtLink>
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
const authStore = useAuthStore()

const logout = async () => {
  await authStore.logout()
}
</script>
