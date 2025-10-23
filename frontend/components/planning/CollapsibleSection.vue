<template>
  <div class="collapsible-section bg-white rounded-lg shadow-md mb-6">
    <!-- En-tête cliquable -->
    <div 
      @click="toggleExpanded"
      class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50 transition-colors border-b"
      :class="{ 'rounded-b-lg border-b-0': !isExpanded }"
    >
      <div class="flex items-center space-x-3">
        <!-- Icône -->
        <span class="text-2xl">{{ icon }}</span>
        
        <!-- Titre et badge -->
        <div>
          <h2 class="text-xl font-semibold text-gray-800">{{ title }}</h2>
          <span v-if="count !== null" class="text-sm text-gray-500">{{ count }} élément(s)</span>
        </div>
      </div>
      
      <div class="flex items-center space-x-3">
        <!-- Slot pour boutons d'action (ex: Ajouter) -->
        <slot name="actions"></slot>
        
        <!-- Icône expand/collapse -->
        <svg 
          class="w-6 h-6 text-gray-600 transition-transform duration-200"
          :class="{ 'rotate-180': isExpanded }"
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
    </div>
    
    <!-- Contenu pliable -->
    <transition
      name="collapse"
      @enter="enter"
      @after-enter="afterEnter"
      @leave="leave"
    >
      <div v-show="isExpanded" class="section-content">
        <div class="p-4">
          <slot></slot>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  icon: {
    type: String,
    default: '📋'
  },
  count: {
    type: Number,
    default: null
  },
  defaultExpanded: {
    type: Boolean,
    default: true
  }
})

const isExpanded = ref(props.defaultExpanded)

const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value
}

// Animations CSS
const enter = (element) => {
  element.style.height = 'auto'
  const height = getComputedStyle(element).height
  element.style.height = '0'
  requestAnimationFrame(() => {
    element.style.height = height
  })
}

const afterEnter = (element) => {
  element.style.height = 'auto'
}

const leave = (element) => {
  element.style.height = getComputedStyle(element).height
  requestAnimationFrame(() => {
    element.style.height = '0'
  })
}
</script>

<style scoped>
.section-content {
  overflow: hidden;
  transition: height 0.3s ease;
}

.collapse-enter-active,
.collapse-leave-active {
  transition: height 0.3s ease;
}

.collapse-enter-from,
.collapse-leave-to {
  height: 0;
}
</style>




