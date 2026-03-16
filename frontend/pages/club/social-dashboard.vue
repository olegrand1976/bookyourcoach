<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
            </svg>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Posts réseaux sociaux</h1>
            <p class="text-sm text-gray-500">Activibe CM — Planning du mois</p>
          </div>
        </div>
        <button
          type="button"
          @click="generatePlanning"
          :disabled="generating || loading"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg font-medium hover:from-purple-600 hover:to-pink-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md"
        >
          <svg v-if="generating" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>{{ generating ? 'Génération...' : 'Générer le planning du mois' }}</span>
        </button>
      </div>

      <div v-if="loading && posts.length === 0" class="flex flex-col items-center justify-center py-24">
        <div class="w-14 h-14 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin" />
        <p class="mt-4 text-gray-600">Chargement du planning...</p>
      </div>

      <div v-else-if="!loading && posts.length === 0" class="bg-white rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
        </svg>
        <h2 class="text-xl font-bold text-gray-800 mb-2">Aucun post ce mois-ci</h2>
        <p class="text-gray-600 mb-6 max-w-md mx-auto">
          Cliquez sur « Générer le planning du mois » pour créer 8 posts (texte + image) avec l’IA Activibe CM.
        </p>
        <button
          type="button"
          @click="generatePlanning"
          :disabled="generating"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg font-medium hover:from-purple-600 hover:to-pink-600 disabled:opacity-50"
        >
          {{ generating ? 'Génération...' : 'Générer le planning' }}
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <SocialPostCard
          v-for="post in posts"
          :key="post.id"
          :post="post"
          @updated="onPostUpdated"
          @regenerated="onPostRegenerated"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

definePageMeta({
  middleware: ['auth']
})

const { $api } = useNuxtApp()

const posts = ref([])
const loading = ref(true)
const generating = ref(false)

async function fetchPlanning () {
  loading.value = true
  try {
    const now = new Date()
    const res = await $api.get('/club/social-generator/planning', {
      params: { year: now.getFullYear(), month: now.getMonth() + 1 }
    })
    if (res.data?.success && Array.isArray(res.data?.data?.posts)) {
      posts.value = res.data.data.posts
    } else {
      posts.value = []
    }
  } catch (err) {
    console.error('Erreur chargement planning:', err)
    posts.value = []
  } finally {
    loading.value = false
  }
}

async function generatePlanning () {
  generating.value = true
  try {
    const res = await $api.post('/club/social-generator/planning/generate')
    if (res.data?.success && Array.isArray(res.data?.data?.posts)) {
      posts.value = res.data.data.posts
    }
  } catch (err) {
    console.error('Erreur génération planning:', err)
  } finally {
    generating.value = false
  }
}

function onPostUpdated (updatedPost) {
  const i = posts.value.findIndex(p => p.id === updatedPost.id)
  if (i !== -1) {
    posts.value = [...posts.value.slice(0, i), updatedPost, ...posts.value.slice(i + 1)]
  }
}

function onPostRegenerated (updatedPost) {
  onPostUpdated(updatedPost)
}

onMounted(() => {
  fetchPlanning()
})
</script>
