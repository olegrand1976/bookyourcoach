<template>
  <font-awesome-icon :icon="iconName" :class="className" />
</template>

<script setup lang="ts">
interface Props {
  name: string
  class?: string
  size?: string
}

const props = withDefaults(defineProps<Props>(), {
  class: '',
  size: 'lg'
})

// Mapping des noms simplifiés vers les noms Font Awesome
const iconMapping = {
  // Interface générale
  'building': 'building',
  'info': 'clipboard-list',
  'location': 'map-marker-alt',
  'settings': 'cog',
  'lightbulb': 'lightbulb',
  'users': 'users',
  'note': 'file-alt',
  'plus': 'plus',
  'minus': 'minus',
  'trash': 'trash',
  'edit': 'edit',
  'save': 'save',
  'cancel': 'times',
  'check': 'check',
  'sync': 'sync-alt',
  'clock': 'clock',
  'calendar': 'calendar-alt',
  'calendar-day': 'calendar-alt', // Utiliser calendar-alt
  'schedule': 'calendar-alt', // Utiliser calendar-alt
  
  // Activités sportives
  'horse': 'horse',
  'swimmer': 'swimmer',
  'dumbbell': 'dumbbell',
  'football': 'futbol',
  'martial-arts': 'fist-raised',
  'dance': 'music',
  'tennis': 'table-tennis',
  'gymnastics': 'child',
  'running': 'running',
  
  // Interface utilisateur
  'chevron-left': 'chevron-left',
  'chevron-right': 'chevron-right',
  'chevron-up': 'chevron-up',
  'chevron-down': 'chevron-down',
  'arrow-left': 'arrow-left',
  'arrow-right': 'arrow-right',
  'bars': 'bars',
  'search': 'search',
  
  // Communication
  'phone': 'phone',
  'email': 'envelope',
  'globe': 'globe',
  'bell': 'bell',
  'comment': 'comment',
  
  // Statut et notifications
  'info-circle': 'info-circle',
  'warning': 'exclamation-triangle',
  'success': 'check-circle',
  'error': 'times-circle',
  'spinner': 'spinner',
  
  // Financier
  'euro': 'euro-sign',
  'money': 'money-bill-wave',
  'credit-card': 'credit-card',
  
  // Navigation et interface
  'home': 'home',
  'dashboard': 'chart-line',
  'profile': 'user',
  'logout': 'sign-out-alt',
  'login': 'sign-in-alt',
}

const iconName = computed(() => {
  return iconMapping[props.name] || props.name
})

const className = computed(() => {
  let classes = props.class
  
  // Ajouter une taille par défaut si pas spécifiée
  if (!classes.includes('fa-') && props.size) {
    classes += ` fa-${props.size}`
  }
  
  return classes
})
</script>
