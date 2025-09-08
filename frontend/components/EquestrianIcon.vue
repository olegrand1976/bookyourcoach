<template>
  <span :class="iconClass" :style="{ fontSize: size + 'px' }">
    {{ getIconEmoji(icon) }}
  </span>
</template>

<script setup>
const props = defineProps({
  icon: {
    type: String,
    required: true
  },
  size: {
    type: Number,
    default: 16
  }
})

const iconClass = computed(() => {
  return 'inline-block'
})

const getIconEmoji = (icon) => {
  const icons = {
    trophy: '🏆',
    helmet: '⛑️',
    horse: '🐎',
    saddle: '🛡️',
    horseshoe: '🔧',
    user: '👤',
    users: '👥',
    calendar: '📅',
    clock: '⏰',
    star: '⭐',
    heart: '❤️',
    check: '✅',
    cross: '❌',
    warning: '⚠️',
    info: 'ℹ️'
  }
  return icons[icon] || '❓'
}
</script>