<template>
  <div class="student-nav">
    <!-- Mobile menu button -->
    <div class="lg:hidden">
      <button 
        @click="mobileMenuOpen = !mobileMenuOpen"
        class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
      >
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </div>

    <!-- Desktop sidebar -->
    <div class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 lg:pt-16 lg:pb-0 lg:bg-white lg:border-r lg:border-gray-200">
      <div class="flex-1 flex flex-col min-h-0">
        <nav class="flex-1 px-2 py-4 space-y-1">
          <NuxtLink 
            v-for="item in navigationItems" 
            :key="item.name"
            :to="item.href"
            :class="[
              'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors',
              isActiveRoute(item.href) 
                ? 'bg-blue-100 text-blue-900' 
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
            ]"
          >
            <component 
              :is="item.icon" 
              :class="[
                'mr-3 flex-shrink-0 h-5 w-5',
                isActiveRoute(item.href) ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500'
              ]"
            />
            {{ item.name }}
          </NuxtLink>
        </nav>
      </div>
    </div>

    <!-- Mobile menu -->
    <div v-if="mobileMenuOpen" class="lg:hidden">
      <div class="fixed inset-0 z-40 flex">
        <div class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="mobileMenuOpen = false"></div>
        <div class="relative flex-1 flex flex-col max-w-xs w-full bg-white">
          <div class="absolute top-0 right-0 -mr-12 pt-2">
            <button 
              @click="mobileMenuOpen = false"
              class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
            >
              <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
            <nav class="mt-5 px-2 space-y-1">
              <NuxtLink 
                v-for="item in navigationItems" 
                :key="item.name"
                :to="item.href"
                @click="mobileMenuOpen = false"
                :class="[
                  'group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors',
                  isActiveRoute(item.href) 
                    ? 'bg-blue-100 text-blue-900' 
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                ]"
              >
                <component 
                  :is="item.icon" 
                  :class="[
                    'mr-4 flex-shrink-0 h-6 w-6',
                    isActiveRoute(item.href) ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500'
                  ]"
                />
                {{ item.name }}
              </NuxtLink>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

// State
const mobileMenuOpen = ref(false)

// Navigation items
const navigationItems = [
  {
    name: 'Tableau de bord',
    href: '/student/dashboard',
    icon: 'svg'
  },
  {
    name: 'Leçons disponibles',
    href: '/student/lessons',
    icon: 'svg'
  },
  {
    name: 'Mes réservations',
    href: '/student/bookings',
    icon: 'svg'
  },
  {
    name: 'Mes préférences',
    href: '/student/preferences',
    icon: 'svg'
  },
  {
    name: 'Historique',
    href: '/student/history',
    icon: 'svg'
  },
  {
    name: 'Enseignants',
    href: '/student/teachers',
    icon: 'svg'
  }
]

// Methods
const isActiveRoute = (href: string) => {
  const route = useRoute()
  return route.path === href
}
</script>

<style scoped>
.student-nav {
  @apply relative;
}
</style>
