<template>
    <div class="flex items-center">
        <img :src="logoUrl" :alt="platformName" :class="[
            'h-auto',
            size === 'sm' ? 'w-24' : size === 'lg' ? 'w-40' : 'w-32'
        ]" @error="onImageError" />
        <!-- Fallback si l'image ne charge pas -->
        <span v-if="showFallback" :class="[
            'font-serif font-bold text-primary-600',
            size === 'sm' ? 'text-lg' : size === 'lg' ? 'text-3xl' : 'text-2xl'
        ]">
            {{ platformName }}
        </span>
    </div>
</template>

<script setup>
const props = defineProps({
    size: {
        type: String,
        default: 'md',
        validator: value => ['sm', 'md', 'lg'].includes(value)
    }
})

const { settings, loadSettings } = useSettings()
const showFallback = ref(false)

const logoUrl = computed(() => settings.value.logo_url || '/logo-activibe.svg')
const platformName = computed(() => settings.value.platform_name || 'Acti\'Vibe')

const onImageError = () => {
    showFallback.value = true
}

// Charger les paramètres au montage
onMounted(() => {
    loadSettings()
})
</script>
