<template>
    <svg :class="iconClass" :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        :stroke-width="strokeWidth" stroke-linecap="round" stroke-linejoin="round">
        <!-- Icône Cheval -->
        <path v-if="icon === 'horse'"
            d="M8 2s3-1 6 2c2 3-1 7-4 8l-3 3v6h-2v-6l-3-3c-3-1-6-5-4-8 3-3 6-2 6-2z M16 8c1-2 3-3 5-2 1 1 0 3-1 4-1 0-2-1-3-2z" />

        <!-- Icône Selle -->
        <path v-if="icon === 'saddle'"
            d="M4 12c0-2 2-4 4-4h8c2 0 4 2 4 4v2c0 1-1 2-2 2H6c-1 0-2-1-2-2v-2z M8 8V6c0-1 1-2 2-2h4c1 0 2 1 2 2v2 M6 16l-2 4 M18 16l2 4" />

        <!-- Icône Casque -->
        <path v-if="icon === 'helmet'"
            d="M12 2C8 2 5 5 5 9v4c0 1 1 2 2 2h10c1 0 2-1 2-2V9c0-4-3-7-7-7z M5 13h2 M17 13h2 M12 7v2" />

        <!-- Icône Trophée -->
        <path v-if="icon === 'trophy'"
            d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6 M18 9h1.5a2.5 2.5 0 0 0 0-5H18 M8 20h8 M8 20v-2c0-2 2-4 4-4s4 2 4 4v2 M6 9v6c0 2 2 4 4 4h4c2 0 4-2 4-4V9H6z" />

        <!-- Icône Fer à cheval -->
        <path v-if="icon === 'horseshoe'"
            d="M7 12v5a3 3 0 0 0 3 3h0a3 3 0 0 0 3-3v-5 M17 12v5a3 3 0 0 1-3 3h0a3 3 0 0 1-3-3v-5 M5 12C5 8.5 8 6 12 6s7 2.5 7 6" />

        <!-- Icône Obstacle -->
        <path v-if="icon === 'jump'" d="M2 18h20 M4 18v-4l4-4 4 4 4-4 4 4v4 M6 14V8 M10 10V6 M14 10V6 M18 14V8" />
    </svg>
</template>

<script setup>
const props = defineProps({
    icon: {
        type: String,
        required: true,
        validator: (value) => ['horse', 'saddle', 'helmet', 'trophy', 'horseshoe', 'jump'].includes(value)
    },
    size: {
        type: [String, Number],
        default: 24
    },
    strokeWidth: {
        type: [String, Number],
        default: 2
    },
    class: {
        type: String,
        default: ''
    }
})

const iconClass = computed(() => {
    return `equestrian-icon ${props.class}`
})
</script>

<style scoped>
.equestrian-icon {
    display: inline-block;
    vertical-align: middle;
}
</style>
