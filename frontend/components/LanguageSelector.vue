<template>
  <div class="relative">
    <button @click="toggleDropdown" 
            class="flex items-center space-x-2 px-3 py-2 rounded-lg bg-white/10 bg-blue-600:bg-white/20 transition-colors text-gray-100">
      <span class="text-sm font-medium">{{ currentLocale.name }}</span>
      <ChevronDownIcon class="w-4 h-4" :class="{ 'rotate-180': isOpen }" />
    </button>
    
    <div v-if="isOpen" 
         class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl py-2 z-50 border border-blue-500/20">
      <button v-for="locale in availableLocales" 
              :key="locale.code"
              @click="switchLanguage(locale.code)"
              class="flex items-center w-full px-4 py-2 text-sm text-gray-900 hover:bg-gray-50 transition-colors"
              :class="{ 'bg-gray-50 font-medium': currentLocale.code === locale.code }">
        <span class="mr-3">{{ getFlagEmoji(locale.code) }}</span>
        <span>{{ locale.name }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const isOpen = ref(false)
const currentLocale = ref({ code: 'fr', name: 'Français' })

// Locales disponibles (sans i18n pour le moment)
const availableLocales = ref([
  { code: 'fr', name: 'Français' },
  { code: 'en', name: 'English' },
  { code: 'nl', name: 'Nederlands' },
  { code: 'de', name: 'Deutsch' }
])

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
}

const switchLanguage = (code) => {
  const locale = availableLocales.value.find(l => l.code === code)
  if (locale) {
    currentLocale.value = locale
    // Pour le moment, on ne fait que changer l'affichage
    // Plus tard, on pourra réactiver i18n
  }
  isOpen.value = false
}

const getFlagEmoji = (code) => {
  const flags = {
    fr: '🇫🇷',
    en: '🇺🇸',
    nl: '🇳🇱',
    de: '🇩🇪',
    it: '🇮🇹',
    es: '🇪🇸',
    pt: '🇵🇹',
    hu: '🇭🇺',
    pl: '🇵🇱',
    zh: '🇨🇳',
    ja: '🇯🇵',
    sv: '🇸🇪',
    no: '🇳🇴',
    fi: '🇫🇮',
    da: '🇩🇰'
  }
  return flags[code] || '🌐'
}

// Fermer le dropdown quand on clique ailleurs
onMounted(() => {
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.relative')) {
      isOpen.value = false
    }
  })
})
</script>
