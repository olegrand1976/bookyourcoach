<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 class="text-3xl font-bold text-gray-900">
        Paramètres Système
      </h1>
      <p class="mt-2 text-gray-600">Configuration temporaire - reconstruction en cours</p>
      
      <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
        <p class="text-gray-700">
          Le système de paramètres est en cours de reconstruction après le nettoyage du cache.
          Veuillez patienter quelques instants.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth-admin'
})

useHead({
  title: 'Paramètres Système'
})
</script>
