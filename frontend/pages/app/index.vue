<template>
  <div class="min-h-screen bg-white">
    <section class="relative flex h-screen items-center justify-center overflow-hidden">
      <PresentationVideo v-model="locale" />

      <div class="relative z-20 mx-auto max-w-7xl px-4 text-center text-white sm:px-6 lg:px-8">
        <div class="mb-8 animate-fade-in">
          <h1 class="mb-6 text-5xl font-extrabold leading-tight md:text-7xl">
            {{ copy.title }}
            <span class="block bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">
              {{ copy.highlight }}
            </span>
          </h1>
          <p class="mx-auto mb-8 max-w-3xl text-xl leading-relaxed text-gray-200 md:text-2xl">
            {{ copy.description }}
          </p>
        </div>

        <div class="mb-12 flex flex-col items-center justify-center gap-4 sm:flex-row">
          <NuxtLink
            to="/register"
            class="group flex items-center gap-2 rounded-full bg-gradient-to-r from-cyan-500 to-blue-600 px-10 py-4 text-lg font-bold text-white shadow-2xl transition-all hover:scale-105 hover:from-cyan-600 hover:to-blue-700"
          >
            <span>{{ copy.ctaPrimary }}</span>
            <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </NuxtLink>
          <NuxtLink
            to="/login"
            class="rounded-full border-2 border-white/30 bg-white/10 px-10 py-4 text-lg font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20"
          >
            {{ copy.ctaSecondary }}
          </NuxtLink>
        </div>

        <div class="mx-auto grid max-w-3xl grid-cols-3 gap-8 border-t border-white/20 pt-8">
          <div v-for="stat in copy.stats" :key="stat.label">
            <div class="text-4xl font-bold text-cyan-400">{{ stat.value }}</div>
            <div class="mt-1 text-sm text-gray-300">{{ stat.label }}</div>
          </div>
        </div>
      </div>

      <div class="absolute bottom-8 left-1/2 z-20 -translate-x-1/2 animate-bounce">
        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
        </svg>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
type AppLocale = 'fr' | 'en' | 'nl'

definePageMeta({
  layout: 'minimal',
})

const locale = ref<AppLocale>('fr')

const content: Record<AppLocale, {
  title: string
  highlight: string
  description: string
  ctaPrimary: string
  ctaSecondary: string
  stats: { value: string; label: string }[]
}> = {
  fr: {
    title: 'Natation & Fitness,',
    highlight: 'réservez vos cours en un clic',
    description: 'La plateforme de référence pour réserver vos cours avec les meilleurs clubs et coachs.',
    ctaPrimary: 'Commencer gratuitement',
    ctaSecondary: 'Connexion',
    stats: [
      { value: '2 500+', label: 'Élèves actifs' },
      { value: '150+', label: 'Clubs partenaires' },
      { value: '50 000+', label: 'Cours réservés' },
    ],
  },
  en: {
    title: 'Swimming & Fitness,',
    highlight: 'book your classes in one click',
    description: 'The leading platform to book classes with the best clubs and coaches.',
    ctaPrimary: 'Start for free',
    ctaSecondary: 'Sign in',
    stats: [
      { value: '2,500+', label: 'Active students' },
      { value: '150+', label: 'Partner clubs' },
      { value: '50,000+', label: 'Classes booked' },
    ],
  },
  nl: {
    title: 'Zwemmen & Fitness,',
    highlight: 'boek je lessen met één klik',
    description: 'Het platform om lessen te boeken bij de beste clubs en coaches.',
    ctaPrimary: 'Gratis beginnen',
    ctaSecondary: 'Inloggen',
    stats: [
      { value: '2.500+', label: 'Actieve leerlingen' },
      { value: '150+', label: 'Partnerclubs' },
      { value: '50.000+', label: 'Geboekte lessen' },
    ],
  },
}

const copy = computed(() => content[locale.value])

useHead({
  title: 'Présentation',
})
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(20px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 1s ease-out;
}
</style>
