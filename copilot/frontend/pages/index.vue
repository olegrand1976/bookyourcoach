<template>
  <div>
    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-primary-600 to-primary-800 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="text-center">
          <h1 class="text-4xl md:text-6xl font-bold mb-6">
            Trouvez votre coach parfait
          </h1>
          <p class="text-xl md:text-2xl mb-8 text-primary-100">
            Réservez des cours avec les meilleurs enseignants équestres
          </p>
          <div class="space-x-4">
            <NuxtLink to="/register" class="btn-primary bg-white text-primary-600 hover:bg-gray-100">
              Commencer maintenant
            </NuxtLink>
            <NuxtLink to="/teachers" class="btn-secondary border-white text-white hover:bg-white hover:text-primary-600">
              Découvrir les coaches
            </NuxtLink>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="py-24 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            Pourquoi choisir BookYourCoach ?
          </h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Une plateforme moderne pour connecter élèves et enseignants équestres
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="text-center">
            <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <UserGroupIcon class="w-8 h-8 text-primary-600" />
            </div>
            <h3 class="text-xl font-semibold mb-4">Coaches certifiés</h3>
            <p class="text-gray-600">
              Tous nos enseignants sont certifiés et expérimentés dans leur domaine
            </p>
          </div>

          <div class="text-center">
            <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <CalendarIcon class="w-8 h-8 text-primary-600" />
            </div>
            <h3 class="text-xl font-semibold mb-4">Réservation facile</h3>
            <p class="text-gray-600">
              Réservez vos cours en quelques clics selon vos disponibilités
            </p>
          </div>

          <div class="text-center">
            <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <CreditCardIcon class="w-8 h-8 text-primary-600" />
            </div>
            <h3 class="text-xl font-semibold mb-4">Paiement sécurisé</h3>
            <p class="text-gray-600">
              Payments sécurisés avec Stripe, remboursement en cas d'annulation
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="py-24 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
          <div>
            <div class="text-4xl font-bold text-primary-600 mb-2">{{ platformStats.coaches }}+</div>
            <div class="text-gray-600">Coaches certifiés</div>
          </div>
          <div>
            <div class="text-4xl font-bold text-primary-600 mb-2">{{ platformStats.students }}+</div>
            <div class="text-gray-600">Élèves satisfaits</div>
          </div>
          <div>
            <div class="text-4xl font-bold text-primary-600 mb-2">{{ platformStats.lessons }}+</div>
            <div class="text-gray-600">Cours dispensés</div>
          </div>
          <div>
            <div class="text-4xl font-bold text-primary-600 mb-2">{{ platformStats.locations }}+</div>
            <div class="text-gray-600">Lieux disponibles</div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 bg-primary-600 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-6">
          Prêt à commencer votre aventure équestre ?
        </h2>
        <p class="text-xl mb-8 text-primary-100">
          Rejoignez des milliers d'élèves qui ont déjà trouvé leur coach idéal
        </p>
        <NuxtLink to="/register" class="btn-primary bg-white text-primary-600 hover:bg-gray-100">
          S'inscrire gratuitement
        </NuxtLink>
      </div>
    </section>
  </div>
</template>

<script setup>
import { 
  UserGroupIcon, 
  CalendarIcon, 
  CreditCardIcon 
} from '@heroicons/vue/24/outline'

// Stats de la plateforme (à charger depuis l'API plus tard)
const platformStats = ref({
  coaches: 150,
  students: 2500,
  lessons: 8500,
  locations: 45
})

// Si l'utilisateur est connecté, rediriger vers le dashboard
const authStore = useAuthStore()
watchEffect(() => {
  if (authStore.isAuthenticated) {
    navigateTo('/dashboard')
  }
})

// SEO
useHead({
  title: 'BookYourCoach - Trouvez votre coach équestre idéal',
  meta: [
    {
      name: 'description',
      content: 'Plateforme de réservation de cours équestres avec des coaches certifiés. Réservez facilement vos leçons d\'équitation en ligne.'
    }
  ]
})
</script>
