<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">
          🏃 Disciplines
        </h1>
        <p class="text-gray-700 mb-8">
          Explorez toutes les disciplines disponibles
        </p>
        
        <div class="bg-white rounded-lg shadow-lg p-6">
          <p class="text-gray-700">
            Cette page sera bientôt disponible avec la liste complète des disciplines.
          </p>
          <NuxtLink to="/" class="btn-primary mt-4 inline-block">
            Retour à l'accueil
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
useHead({
  title: 'Disciplines | activibe',
  meta: [
    { name: 'description', content: 'Explorez toutes les disciplines disponibles sur activibe' }
  ]
})
</script>
