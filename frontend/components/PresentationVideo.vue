<template>
  <div class="absolute inset-0 overflow-hidden">
    <video
      ref="videoRef"
      :key="currentLocale"
      class="absolute inset-0 h-full w-full object-cover"
      autoplay
      muted
      loop
      playsinline
      :poster="posterSrc"
      aria-hidden="true"
    >
      <source :src="videoSrc" type="video/mp4" />
    </video>

    <div
      class="absolute inset-0 bg-gradient-to-br from-blue-900/85 to-purple-900/85"
      aria-hidden="true"
    />

    <div
      v-if="showLanguageSelector"
      class="absolute right-4 top-4 z-30 flex gap-2 sm:right-6 sm:top-6"
      role="group"
      aria-label="Langue de la vidéo"
    >
      <button
        v-for="locale in locales"
        :key="locale.code"
        type="button"
        class="rounded-full px-3 py-1.5 text-xs font-semibold transition-all sm:text-sm"
        :class="currentLocale === locale.code
          ? 'bg-white text-blue-900 shadow-lg'
          : 'bg-white/15 text-white backdrop-blur-sm hover:bg-white/25'"
        :aria-pressed="currentLocale === locale.code"
        @click="selectLocale(locale.code)"
      >
        {{ locale.label }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
type PresentationLocale = 'fr' | 'en' | 'nl'

const props = withDefaults(defineProps<{
  modelValue?: PresentationLocale
  showLanguageSelector?: boolean
}>(), {
  modelValue: 'fr',
  showLanguageSelector: true,
})

const emit = defineEmits<{
  'update:modelValue': [value: PresentationLocale]
}>()

const locales: { code: PresentationLocale; label: string }[] = [
  { code: 'fr', label: 'FR' },
  { code: 'en', label: 'EN' },
  { code: 'nl', label: 'NL' },
]

const videoRef = ref<HTMLVideoElement | null>(null)

const currentLocale = computed({
  get: () => props.modelValue,
  set: (value: PresentationLocale) => emit('update:modelValue', value),
})

const videoSrc = computed(() => `/videos/presentation-${currentLocale.value}.mp4`)
const posterSrc = computed(() => `/videos/poster-${currentLocale.value}.jpg`)

function selectLocale(code: PresentationLocale) {
  currentLocale.value = code
}

function detectBrowserLocale(): PresentationLocale {
  if (!process.client) {
    return 'fr'
  }

  const lang = navigator.language.slice(0, 2).toLowerCase()
  if (lang === 'en' || lang === 'nl') {
    return lang
  }

  return 'fr'
}

onMounted(() => {
  if (props.modelValue === 'fr') {
    currentLocale.value = detectBrowserLocale()
  }

  videoRef.value?.play().catch(() => {})
})

watch(currentLocale, async () => {
  await nextTick()
  videoRef.value?.load()
  videoRef.value?.play().catch(() => {})
})
</script>
