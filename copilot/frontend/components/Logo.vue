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

<script setup lang="ts">
interface Props {
    size?: 'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md'
})

const settings = useSettings()
const showFallback = ref(false)

const logoUrl = computed(() => settings.settings.logo_url || '/logo.svg')
const platformName = computed(() => settings.settings.platform_name || 'BookYourCoach')

const onImageError = () => {
    showFallback.value = true
}

// Charger les paramètres au montage
onMounted(() => {
    settings.loadSettings()
})
</script>
