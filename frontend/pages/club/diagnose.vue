<template>
  <div class="min-h-screen bg-gray-50 p-4 md:p-8">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">🔍 Diagnostic des Colonnes Club</h1>
        <p class="text-gray-600">Vérification de la structure de la table clubs</p>
      </div>

      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
        <p class="mt-4 text-gray-600">Chargement du diagnostic...</p>
      </div>

      <div v-else-if="error" class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Erreur</h3>
            <p class="text-sm text-red-700 mt-1">{{ error }}</p>
          </div>
        </div>
      </div>

      <div v-else-if="diagnostic">
        <!-- Résumé -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Total Colonnes</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">{{ diagnostic.total_columns }}</div>
          </div>
          <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Champs Légaux Présents</div>
            <div class="text-3xl font-bold text-green-600 mt-2">
              {{ diagnostic.legal_fields_existing }} / 10
            </div>
          </div>
          <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Status</div>
            <div class="text-2xl font-bold mt-2" :class="diagnostic.legal_fields_existing === 10 ? 'text-green-600' : 'text-orange-600'">
              {{ diagnostic.legal_fields_existing === 10 ? '✅ Complet' : '⚠️ Incomplet' }}
            </div>
          </div>
        </div>

        <!-- Champs Légaux -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
          <h2 class="text-xl font-bold text-gray-900 mb-4">📋 État des Champs Légaux</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div
              v-for="(status, field) in diagnostic.legal_fields_status"
              :key="field"
              class="flex items-center justify-between p-3 border rounded-lg"
              :class="status === 'EXISTS' ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'"
            >
              <span class="font-mono text-sm">{{ field }}</span>
              <span v-if="status === 'EXISTS'" class="text-green-600 font-semibold">✅ Existe</span>
              <span v-else class="text-red-600 font-semibold">❌ Manquant</span>
            </div>
          </div>
        </div>

        <!-- Données Actuelles du Club -->
        <div v-if="diagnostic.current_club_data" class="bg-white rounded-lg shadow-lg p-6 mb-6">
          <h2 class="text-xl font-bold text-gray-900 mb-4">💾 Données Actuelles de Votre Club</h2>
          <div class="space-y-2">
            <div
              v-for="(data, field) in diagnostic.current_club_data"
              :key="field"
              class="flex items-start justify-between p-3 border-b"
            >
              <span class="font-mono text-sm text-gray-600 w-1/3">{{ field }}</span>
              <div class="w-2/3 text-right">
                <div v-if="typeof data === 'object'">
                  <div class="text-sm" :class="data.is_empty ? 'text-gray-400' : 'text-gray-900'">
                    {{ data.value || '(vide)' }}
                  </div>
                  <div class="text-xs text-gray-500">Type: {{ data.type }}</div>
                </div>
                <div v-else class="text-sm text-red-500">{{ data }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Toutes les Colonnes -->
        <div class="bg-white rounded-lg shadow-lg p-6">
          <h2 class="text-xl font-bold text-gray-900 mb-4">🗂️ Toutes les Colonnes de la Table</h2>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
            <div
              v-for="column in diagnostic.all_columns"
              :key="column"
              class="p-2 bg-gray-50 border border-gray-200 rounded text-sm font-mono"
            >
              {{ column }}
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex gap-4">
          <button
            @click="loadDiagnostic"
            class="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700 transition-all duration-200 shadow-md hover:shadow-lg"
          >
            🔄 Rafraîchir
          </button>
          <NuxtLink
            to="/club/profile"
            class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
          >
            ← Retour au Profil
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

definePageMeta({
  middleware: ['auth']
})

const diagnostic = ref(null)
const loading = ref(true)
const error = ref(null)

async function loadDiagnostic() {
  loading.value = true
  error.value = null
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/diagnose-columns')
    
    if (response.data.success) {
      diagnostic.value = response.data
      console.log('📊 Diagnostic:', diagnostic.value)
    } else {
      error.value = response.data.error || 'Erreur inconnue'
    }
  } catch (err) {
    console.error('❌ Erreur diagnostic:', err)
    error.value = err.message || 'Erreur lors du chargement du diagnostic'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadDiagnostic()
})
</script>

