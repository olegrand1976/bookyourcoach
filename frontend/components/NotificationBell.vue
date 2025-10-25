<template>
  <div class="relative">
    <!-- Bouton cloche -->
    <button
      @click="togglePanel"
      class="relative p-2 rounded-lg hover:bg-gray-100 transition-colors"
      :class="{ 'bg-gray-100': showPanel }"
    >
      <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
      
      <!-- Badge nombre non lu -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center animate-pulse"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Panel des notifications -->
    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-1"
    >
      <div
        v-if="showPanel"
        class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-50 max-h-[600px] flex flex-col"
      >
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
          <button
            v-if="unreadCount > 0"
            @click="markAllAsRead"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
          >
            Tout marquer comme lu
          </button>
        </div>

        <!-- Liste des notifications -->
        <div class="flex-1 overflow-y-auto">
          <div v-if="loading" class="p-8 text-center text-gray-500">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mx-auto"></div>
            <p class="mt-2">Chargement...</p>
          </div>

          <div v-else-if="notifications.length === 0" class="p-8 text-center text-gray-500">
            <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <p class="mt-4 font-medium">Aucune notification</p>
            <p class="text-sm">Vous êtes à jour !</p>
          </div>

          <div v-else class="divide-y divide-gray-200">
            <div
              v-for="notification in notifications"
              :key="notification.id"
              @click="markAsRead(notification)"
              class="p-4 hover:bg-gray-50 cursor-pointer transition-colors"
              :class="{ 'bg-blue-50': !notification.read }"
            >
              <div class="flex items-start">
                <!-- Icône -->
                <div class="flex-shrink-0 mt-1">
                  <div
                    class="w-10 h-10 rounded-full flex items-center justify-center"
                    :class="getNotificationColor(notification.type)"
                  >
                    <span class="text-xl">{{ getNotificationIcon(notification.type) }}</span>
                  </div>
                </div>

                <!-- Contenu -->
                <div class="ml-3 flex-1">
                  <p class="text-sm font-medium text-gray-900">
                    {{ notification.title }}
                  </p>
                  <p class="text-sm text-gray-600 mt-1">
                    {{ notification.message }}
                  </p>
                  <p class="text-xs text-gray-400 mt-2">
                    {{ notification.time_ago }}
                  </p>
                </div>

                <!-- Badge non lu -->
                <div v-if="!notification.read" class="ml-2 flex-shrink-0">
                  <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Overlay pour fermer -->
    <div
      v-if="showPanel"
      @click="showPanel = false"
      class="fixed inset-0 z-40"
    ></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

const { $api } = useNuxtApp()

// State
const showPanel = ref(false)
const loading = ref(false)
const notifications = ref<any[]>([])
const unreadCount = ref(0)
let pollInterval: NodeJS.Timeout | null = null

// Methods
async function loadNotifications() {
  try {
    loading.value = true
    const response = await $api.get('/teacher/notifications')
    notifications.value = response.data.data || []
  } catch (error) {
    console.error('❌ Erreur chargement notifications:', error)
  } finally {
    loading.value = false
  }
}

async function loadUnreadCount() {
  try {
    const response = await $api.get('/teacher/notifications/unread-count')
    unreadCount.value = response.data.count || 0
  } catch (error) {
    console.error('❌ Erreur comptage notifications:', error)
  }
}

async function markAsRead(notification: any) {
  if (notification.read) return

  try {
    await $api.post(`/teacher/notifications/${notification.id}/read`)
    notification.read = true
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  } catch (error) {
    console.error('❌ Erreur marquage notification:', error)
  }
}

async function markAllAsRead() {
  try {
    await $api.post('/teacher/notifications/read-all')
    notifications.value.forEach(n => n.read = true)
    unreadCount.value = 0
  } catch (error) {
    console.error('❌ Erreur marquage toutes notifications:', error)
  }
}

function togglePanel() {
  showPanel.value = !showPanel.value
  if (showPanel.value && notifications.value.length === 0) {
    loadNotifications()
  }
}

function getNotificationIcon(type: string): string {
  const icons: Record<string, string> = {
    'replacement_request': '🔔',
    'replacement_accepted': '✅',
    'replacement_rejected': '❌',
    'replacement_cancelled': '🚫',
    'club_replacement_accepted': 'ℹ️'
  }
  return icons[type] || '📬'
}

function getNotificationColor(type: string): string {
  const colors: Record<string, string> = {
    'replacement_request': 'bg-orange-100',
    'replacement_accepted': 'bg-green-100',
    'replacement_rejected': 'bg-red-100',
    'replacement_cancelled': 'bg-gray-100',
    'club_replacement_accepted': 'bg-blue-100'
  }
  return colors[type] || 'bg-gray-100'
}

function startPolling() {
  // Vérifier le nombre de notifications non lues toutes les 30 secondes
  pollInterval = setInterval(() => {
    loadUnreadCount()
  }, 30000)
}

function stopPolling() {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

// Lifecycle
onMounted(() => {
  loadUnreadCount()
  startPolling()
})

onUnmounted(() => {
  stopPolling()
})
</script>

