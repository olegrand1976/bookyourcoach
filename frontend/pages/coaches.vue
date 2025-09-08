<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">
          🏇 Nos Instructeurs
        </h1>
        <p class="text-gray-700 mb-8">
          Découvrez nos instructeurs qualifiés et expérimentés
        </p>
        
        <div class="bg-white rounded-lg shadow-lg p-6">
          <p class="text-gray-700">
            Cette page sera bientôt disponible avec la liste complète de nos instructeurs.
          </p>
          <NuxtLink to="/teachers" class="btn-primary mt-4 inline-block">
            Voir les instructeurs disponibles
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
useHead({
  title: 'Instructeurs | activibe',
  meta: [
    { name: 'description', content: 'Découvrez nos instructeurs qualifiés et expérimentés sur activibe' }
  ]
})
</script>
