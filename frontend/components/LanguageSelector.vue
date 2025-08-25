<template>
  <div class="relative">
    <button @click="toggleDropdown" 
            class="flex items-center space-x-2 px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors text-equestrian-cream">
      <span class="text-sm font-medium">{{ currentLocale.name }}</span>
      <ChevronDownIcon class="w-4 h-4" :class="{ 'rotate-180': isOpen }" />
    </button>
    
    <div v-if="isOpen" 
         class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl py-2 z-50 border border-equestrian-gold/20">
      <button v-for="locale in availableLocales" 
              :key="locale.code"
              @click="switchLanguage(locale.code)"
              class="flex items-center w-full px-4 py-2 text-sm text-equestrian-darkBrown hover:bg-equestrian-cream transition-colors"
              :class="{ 'bg-equestrian-cream font-medium': $i18n.locale === locale.code }">
        <span class="mr-3">{{ getFlagEmoji(locale.code) }}</span>
        <span>{{ locale.name }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const { $i18n } = useNuxtApp()
const isOpen = ref(false)

const availableLocales = computed(() => $i18n.locales)
const currentLocale = computed(() => 
  availableLocales.value.find(locale => locale.code === $i18n.locale) || availableLocales.value[0]
)

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
}

const switchLanguage = (code) => {
  $i18n.setLocale(code)
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
