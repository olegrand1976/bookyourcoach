<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50">
    <!-- Hero Section -->
    <section class="relative py-20 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto text-center">
        <div class="flex justify-center mb-6">
          <Logo size="lg" />
        </div>
        <p class="text-xl md:text-2xl text-gray-700 mb-8 max-w-3xl mx-auto">
          Réservez vos cours d'équitation et de natation avec les meilleurs instructeurs
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <NuxtLink to="/login"
            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-lg transition-colors">
            Connexion
          </NuxtLink>
          <NuxtLink to="/register"
            class="bg-blue-500 bg-blue-600:bg-yellow-600 text-gray-900 font-semibold px-8 py-3 rounded-lg transition-colors">
            Inscription
          </NuxtLink>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-white">
      <div class="max-w-7xl mx-auto">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">
          Pourquoi choisir activibe ?
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="text-center p-6">
            <div class="text-4xl mb-4">🏇</div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Équitation</h3>
            <p class="text-gray-700">Cours de dressage, obstacles et complet avec des instructeurs qualifiés</p>
          </div>
          
          <div class="text-center p-6">
            <div class="text-4xl mb-4">🏊</div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Natation</h3>
            <p class="text-gray-700">Cours particuliers et aquagym pour tous les niveaux</p>
          </div>
          
          <div class="text-center p-6">
            <div class="text-4xl mb-4">📅</div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Réservation facile</h3>
            <p class="text-gray-700">Réservez vos cours en quelques clics, 24h/24</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-gray-800 text-gray-100">
      <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-3xl font-bold mb-6">Prêt à commencer ?</h2>
        <p class="text-xl mb-8">
          Rejoignez des milliers d'élèves qui ont déjà choisi activibe
        </p>
        <NuxtLink to="/register"
          class="bg-blue-500 bg-blue-600:bg-yellow-600 text-gray-900 font-semibold px-8 py-3 rounded-lg transition-colors inline-block">
          Commencer maintenant
        </NuxtLink>
      </div>
    </section>
  </div>
</template>

<script setup>
import Logo from '~/components/Logo.vue'
definePageMeta({
  layout: 'minimal'
})
</script>