<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">🔍 Debug API - Filtrage des Types de Cours</h1>
        <p class="mt-2 text-sm text-gray-600">
          Vérification du filtrage des types de cours par club
        </p>
      </div>

      <!-- Boutons d'action -->
      <div class="mb-6 flex gap-4">
        <button
          @click="loadDebugData"
          :disabled="loading"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ loading ? 'Chargement...' : '🔄 Recharger' }}
        </button>
        
        <button
          @click="navigateTo('/club/planning')"
          class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700"
        >
          ← Retour au planning
        </button>
      </div>

      <!-- Erreur -->
      <div v-if="error" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded">
        <p class="text-red-700 font-semibold">❌ Erreur</p>
        <p class="text-red-600 text-sm mt-1">{{ error }}</p>
      </div>

      <!-- Résumé -->
      <div v-if="debugData && debugData.summary" class="mb-6 bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">📊 Résumé</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="p-4 bg-blue-50 rounded">
            <div class="text-sm text-gray-600">Rôle utilisateur</div>
            <div class="text-2xl font-bold text-blue-600">{{ debugData.summary.user_role }}</div>
          </div>
          <div class="p-4 bg-purple-50 rounded">
            <div class="text-sm text-gray-600">Disciplines club</div>
            <div class="text-2xl font-bold text-purple-600">{{ debugData.summary.disciplines_count }}</div>
          </div>
          <div class="p-4 bg-yellow-50 rounded">
            <div class="text-sm text-gray-600">Types totaux</div>
            <div class="text-2xl font-bold text-yellow-600">{{ debugData.summary.all_course_types_count }}</div>
          </div>
          <div class="p-4 rounded" :class="debugData.summary.filtering_working ? 'bg-green-50' : 'bg-red-50'">
            <div class="text-sm text-gray-600">Types filtrés</div>
            <div class="text-2xl font-bold" :class="debugData.summary.filtering_working ? 'text-green-600' : 'text-red-600'">
              {{ debugData.summary.filtered_course_types_count }}
            </div>
          </div>
        </div>
        
        <!-- Status du filtrage -->
        <div class="mt-4 p-4 rounded" :class="debugData.summary.filtering_working ? 'bg-green-50 border-l-4 border-green-500' : 'bg-red-50 border-l-4 border-red-500'">
          <p class="font-semibold" :class="debugData.summary.filtering_working ? 'text-green-700' : 'text-red-700'">
            {{ debugData.summary.filtering_working ? '✅ Filtrage actif' : '❌ Filtrage non actif' }}
          </p>
        </div>
      </div>

      <!-- Problèmes détectés -->
      <div v-if="debugData && debugData.issues" class="mb-6 bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">🔎 Problèmes détectés</h2>
        <ul class="space-y-2">
          <li v-for="(issue, index) in debugData.issues" :key="index" 
              class="p-3 rounded"
              :class="issue.startsWith('✅') ? 'bg-green-50 text-green-700' : issue.startsWith('⚠️') ? 'bg-yellow-50 text-yellow-700' : 'bg-red-50 text-red-700'">
            {{ issue }}
          </li>
        </ul>
      </div>

      <!-- Informations Club -->
      <div v-if="debugData && debugData.club" class="mb-6 bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">🏢 Informations Club</h2>
        <div class="space-y-3">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <span class="text-sm font-semibold text-gray-700">ID:</span>
              <span class="ml-2">{{ debugData.club.id }}</span>
            </div>
            <div>
              <span class="text-sm font-semibold text-gray-700">Nom:</span>
              <span class="ml-2">{{ debugData.club.name }}</span>
            </div>
          </div>
          
          <div class="p-4 bg-gray-50 rounded">
            <div class="text-sm font-semibold text-gray-700 mb-2">Disciplines (brut):</div>
            <pre class="text-xs">{{ debugData.club.disciplines_raw }}</pre>
            <div class="mt-2 text-sm">
              <span class="font-semibold">Type:</span> {{ debugData.club.disciplines_type }}
              <span class="ml-4 font-semibold">Est un array:</span> 
              <span :class="debugData.club.disciplines_is_array ? 'text-green-600' : 'text-red-600'">
                {{ debugData.club.disciplines_is_array ? 'Oui ✓' : 'Non ✗' }}
              </span>
            </div>
          </div>
          
          <div class="p-4 bg-blue-50 rounded">
            <div class="text-sm font-semibold text-gray-700 mb-2">Disciplines (parsées):</div>
            <pre class="text-xs">{{ JSON.stringify(debugData.club.disciplines_parsed, null, 2) }}</pre>
          </div>
        </div>
      </div>

      <!-- Types de cours -->
      <div v-if="debugData" class="mb-6 bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">📚 Types de cours</h2>
        
        <div class="grid md:grid-cols-2 gap-6">
          <!-- Tous les types -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-3">
              Tous les types ({{ debugData.all_course_types?.length || 0 }})
            </h3>
            <div class="max-h-96 overflow-y-auto space-y-2">
              <div v-for="type in debugData.all_course_types" :key="type.id"
                   class="p-3 bg-gray-50 rounded text-sm">
                <div class="font-semibold">{{ type.name }}</div>
                <div class="text-gray-600">
                  ID: {{ type.id }} | Discipline: {{ type.discipline_id || 'Générique' }} | 
                  {{ type.duration_minutes }}min
                </div>
              </div>
            </div>
          </div>
          
          <!-- Types filtrés -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-3">
              Types filtrés pour le club ({{ debugData.filtered_course_types?.length || 0 }})
            </h3>
            <div class="max-h-96 overflow-y-auto space-y-2">
              <div v-for="type in debugData.filtered_course_types" :key="type.id"
                   class="p-3 bg-green-50 rounded text-sm">
                <div class="font-semibold text-green-900">{{ type.name }}</div>
                <div class="text-green-700">
                  ID: {{ type.id }} | Discipline: {{ type.discipline_id || 'Générique' }} | 
                  {{ type.duration_minutes }}min
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Créneaux -->
      <div v-if="debugData && debugData.open_slots" class="mb-6 bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">📅 Créneaux ouverts ({{ debugData.open_slots.length }})</h2>
        
        <div class="space-y-4">
          <div v-for="slot in debugData.open_slots" :key="slot.id" 
               class="p-4 border rounded-lg">
            <div class="flex justify-between items-start mb-3">
              <div>
                <h4 class="font-semibold text-lg">Créneau #{{ slot.id }}</h4>
                <p class="text-sm text-gray-600">
                  {{ getDayName(slot.day_of_week) }} {{ slot.start_time }} - {{ slot.end_time }}
                </p>
                <p class="text-sm text-gray-600">
                  Discipline: {{ slot.discipline_name || 'Aucune' }} (ID: {{ slot.discipline_id || 'N/A' }})
                </p>
              </div>
              <div class="text-right">
                <div class="text-sm">
                  <span class="font-semibold">Avant:</span> {{ slot.course_types_count_before_filter }}
                </div>
                <div class="text-sm" 
                     :class="slot.course_types_count_before_filter !== slot.course_types_count_after_filter ? 'text-green-600 font-bold' : ''">
                  <span class="font-semibold">Après:</span> {{ slot.course_types_count_after_filter }}
                </div>
              </div>
            </div>

            <!-- Types avant filtrage -->
            <div class="mb-3">
              <div class="text-sm font-semibold text-gray-700 mb-2">Types AVANT filtrage:</div>
              <div class="flex flex-wrap gap-2">
                <span v-for="ct in slot.course_types_before_filter" :key="ct.id"
                      class="px-2 py-1 text-xs rounded"
                      :class="ct.should_be_filtered ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'">
                  {{ ct.name }} (disc:{{ ct.discipline_id || 'N/A' }})
                  {{ ct.should_be_filtered ? '❌' : '✓' }}
                </span>
              </div>
            </div>

            <!-- Types après filtrage -->
            <div>
              <div class="text-sm font-semibold text-gray-700 mb-2">Types APRÈS filtrage:</div>
              <div class="flex flex-wrap gap-2">
                <span v-for="ct in slot.course_types_after_filter" :key="ct.id"
                      class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                  {{ ct.name }} (disc:{{ ct.discipline_id || 'N/A' }}) ✓
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- JSON brut -->
      <details class="mb-6 bg-white rounded-lg shadow p-6">
        <summary class="text-xl font-bold text-gray-900 cursor-pointer">📄 Données JSON brutes</summary>
        <pre class="mt-4 p-4 bg-gray-50 rounded text-xs overflow-x-auto">{{ JSON.stringify(debugData, null, 2) }}</pre>
      </details>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

definePageMeta({
  middleware: ['auth'],
  layout: 'default'
})

const debugData = ref(null)
const loading = ref(false)
const error = ref(null)

const getDayName = (day) => {
  const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
  return days[day] || day
}

const loadDebugData = async () => {
  try {
    loading.value = true
    error.value = null
    
    const { $api } = useNuxtApp()
    const response = await $api.get('/debug/course-types-filtering')
    
    if (response.data.success) {
      debugData.value = response.data.data
      console.log('🔍 Debug data loaded:', debugData.value)
    } else {
      error.value = response.data.error || 'Erreur inconnue'
    }
  } catch (e) {
    console.error('❌ Erreur chargement debug:', e)
    error.value = e.message || 'Erreur lors du chargement'
  } finally {
    loading.value = false
  }
}

// Charger au montage
onMounted(() => {
  loadDebugData()
})

useHead({
  title: 'Debug API | activibe'
})
</script>

