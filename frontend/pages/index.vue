<template>
  <div>
    <!-- Hero Section -->
    <section class="bg-stable-gradient text-white relative overflow-hidden" data-testid="home-hero">
      <!-- Motif de fond -->
      <div class="absolute inset-0 bg-horse-pattern opacity-10"></div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 relative z-10">
        <div class="text-center">
          <!-- Logo -->
          <div class="flex justify-center mb-8">
            <div class="bg-white/10 backdrop-blur-sm p-6 rounded-xl">
              <Logo size="lg" />
            </div>
          </div>

          <h1 class="text-4xl md:text-6xl font-bold mb-6 font-serif">
            {{ settings.settings.platform_name }}
          </h1>
          <p class="text-xl md:text-2xl mb-8 text-equestrian-cream">
            {{ $t('home.hero.tagline') }}
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <NuxtLink to="/register" data-testid="cta-register"
              class="btn-primary bg-equestrian-cream text-equestrian-darkBrown hover:bg-white hover:shadow-lg transform hover:scale-105 transition-all duration-200 font-semibold">
              🏇 {{ $t('home.hero.ctaStart') }}
            </NuxtLink>
            <NuxtLink to="/teachers" data-testid="cta-discover"
              class="btn-secondary border-equestrian-cream text-equestrian-cream hover:bg-equestrian-cream hover:text-equestrian-darkBrown transition-all duration-200 font-semibold">
              🐎 {{ $t('home.hero.ctaDiscover') }}
            </NuxtLink>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="py-24 bg-equestrian-cream" data-testid="home-features">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl font-bold text-equestrian-darkBrown mb-4 font-serif">
            {{ $t('home.features.title', { platform: settings.settings.platform_name }) }}
          </h2>
          <p class="text-xl text-equestrian-brown max-w-3xl mx-auto">
            {{ $t('home.features.subtitle') }}
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="text-center bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300">
            <div
              class="w-20 h-20 bg-gradient-to-br from-equestrian-leather to-equestrian-gold rounded-full flex items-center justify-center mx-auto mb-6">
              <EquestrianIcon name="trophy" :size="40" class="text-white" />
            </div>
            <h3 class="text-xl font-semibold mb-4 text-equestrian-darkBrown">{{ $t('home.features.items.certified.title') }}</h3>
            <p class="text-equestrian-brown">
              {{ $t('home.features.items.certified.desc') }}
            </p>
          </div>

          <div class="text-center bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300">
            <div
              class="w-20 h-20 bg-gradient-to-br from-equestrian-leather to-equestrian-gold rounded-full flex items-center justify-center mx-auto mb-6">
              <CalendarIcon class="w-10 h-10 text-white" />
            </div>
            <h3 class="text-xl font-semibold mb-4 text-equestrian-darkBrown">{{ $t('home.features.items.flexible.title') }}</h3>
            <p class="text-equestrian-brown">
              {{ $t('home.features.items.flexible.desc') }}
            </p>
          </div>

          <div class="text-center bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300">
            <div
              class="w-20 h-20 bg-gradient-to-br from-equestrian-leather to-equestrian-gold rounded-full flex items-center justify-center mx-auto mb-6">
              <EquestrianIcon name="helmet" :size="40" class="text-white" />
            </div>
            <h3 class="text-xl font-semibold mb-4 text-equestrian-darkBrown">{{ $t('home.features.items.safety.title') }}</h3>
            <p class="text-equestrian-brown">
              {{ $t('home.features.items.safety.desc') }}
            </p>
          </div>
        </div>
      </div>
    </section>
    <!-- Stats Section -->
    <section class="py-24 bg-gradient-to-r from-equestrian-brown to-equestrian-leather text-white" data-testid="home-stats">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold mb-4 font-serif">{{ $t('home.stats.title') }}</h2>
          <EquestrianIcon name="horseshoe" :size="60" class="text-equestrian-gold mx-auto" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
          <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
            <div class="text-4xl font-bold text-equestrian-gold mb-2">{{ platformStats.coaches }}+</div>
            <div class="text-equestrian-cream flex items-center justify-center gap-2">
              <EquestrianIcon name="horse" :size="20" />
              {{ $t('home.stats.coachesLabel') }}
            </div>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
            <div class="text-4xl font-bold text-equestrian-gold mb-2">{{ platformStats.students }}+</div>
            <div class="text-equestrian-cream">{{ $t('home.stats.studentsLabel') }}</div>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
            <div class="text-4xl font-bold text-equestrian-gold mb-2">{{ platformStats.lessons }}+</div>
            <div class="text-equestrian-cream">{{ $t('home.stats.lessonsLabel') }}</div>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
            <div class="text-4xl font-bold text-equestrian-gold mb-2">{{ platformStats.locations }}+</div>
            <div class="text-equestrian-cream flex items-center justify-center gap-2">
              <EquestrianIcon name="saddle" :size="20" />
              {{ $t('home.stats.locationsLabel') }}
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 bg-equestrian-forest text-white relative overflow-hidden" data-testid="home-cta">
      <div class="absolute inset-0 bg-horse-pattern opacity-5"></div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <EquestrianIcon name="jump" :size="80" class="text-equestrian-gold mx-auto mb-8" />

        <h2 class="text-3xl md:text-4xl font-bold mb-6 font-serif">
          {{ $t('home.cta.title') }}
        </h2>
        <p class="text-xl mb-8 text-green-100 max-w-2xl mx-auto">
          {{ $t('home.cta.subtitle') }}
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <NuxtLink to="/register" data-testid="cta-register-bottom"
            class="btn-primary bg-equestrian-gold text-equestrian-darkBrown hover:bg-equestrian-cream hover:shadow-lg transform hover:scale-105 transition-all duration-200 font-semibold text-lg px-8 py-4">
            🏆 {{ $t('home.cta.ctaSignup') }}
          </NuxtLink>
          <NuxtLink to="/about"
            class="btn-secondary border-equestrian-gold text-equestrian-gold hover:bg-equestrian-gold hover:text-equestrian-darkBrown transition-all duration-200 font-semibold text-lg px-8 py-4">
            📖 {{ $t('home.cta.ctaAbout') }}
          </NuxtLink>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import {
  CalendarIcon
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
const settings = useSettings()

watchEffect(() => {
  if (authStore.isAuthenticated) {
    navigateTo('/dashboard')
  }
})

// Charger les paramètres au montage
onMounted(() => {
  settings.loadSettings()
})

// SEO
useHead({
  title: computed(() => `${settings.settings.platform_name} - Trouvez votre instructeur équestre idéal`),
  meta: [
    {
      name: 'description',
      content: 'Plateforme de réservation de cours équestres avec des instructeurs certifiés. Réservez facilement vos leçons d\'équitation en ligne. Dressage, obstacle, cross-country.'
    },
    {
      name: 'keywords',
      content: 'équitation, cours équestre, instructeur, cheval, dressage, obstacle, centre équestre, réservation'
    }
  ]
})
</script>
