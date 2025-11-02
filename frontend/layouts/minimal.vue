<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation simplifiée -->
    <nav class="bg-white shadow-lg border-b-4 border-blue-500">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
          <div class="flex items-center">
            <NuxtLink to="/"
              class="flex items-center space-x-3 text-xl font-bold text-gray-900 hover:text-gray-700 transition-colors">
              🏊‍♀️ activibe
            </NuxtLink>
          </div>

          <div class="flex items-center space-x-6">
            <NuxtLink to="/login"
              class="text-gray-900 hover:text-gray-700 font-medium px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">
              Connexion
            </NuxtLink>
            <NuxtLink to="/register"
              class="btn-primary bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-6 py-2 rounded-lg transition-colors">
              🏃 Inscription
            </NuxtLink>
          </div>
        </div>
      </div>
    </nav>

    <!-- Contenu principal -->
    <main>
      <slot />
    </main>

    <!-- Footer simplifié -->
    <footer class="bg-gray-800 text-gray-100 border-t-4 border-blue-500 mt-auto">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center text-gray-100/60">
          <p>&copy; 2025 LL-IT Software & Computer. Tous droits réservés. 🏃</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
// Layout minimal sans composants complexes
</script>
