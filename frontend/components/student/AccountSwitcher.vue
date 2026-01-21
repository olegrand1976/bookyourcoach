<template>
  <div v-if="linkedAccounts.length > 1" class="mb-6">
    <div class="bg-white rounded-lg shadow-md p-4 border border-gray-200">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="bg-purple-100 p-2 rounded-lg">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Compte actif
            </label>
            <select
              v-model="selectedAccountId"
              @change="switchAccount"
              :disabled="switching"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <option
                v-for="account in linkedAccounts"
                :key="account.id"
                :value="account.id"
              >
                {{ account.name }} {{ account.is_primary ? '(Principal)' : '' }}
                <span v-if="account.email"> - {{ account.email }}</span>
              </option>
            </select>
          </div>
        </div>
        <div v-if="switching" class="ml-4">
          <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'

const props = defineProps({
  // Props optionnelles pour personnaliser le comportement
})

const emit = defineEmits(['account-switched'])

const { $api } = useNuxtApp()

const linkedAccounts = ref([])
const selectedAccountId = ref(null)
const switching = ref(false)

// Charger les comptes liés
const loadLinkedAccounts = async () => {
  try {
    const response = await $api.get('/student/linked-accounts')
    
    if (response.data.success && response.data.data) {
      linkedAccounts.value = response.data.data
      
      // Définir le compte actif actuel
      const activeAccount = linkedAccounts.value.find(acc => acc.is_active)
      if (activeAccount) {
        selectedAccountId.value = activeAccount.id
      } else if (linkedAccounts.value.length > 0) {
        selectedAccountId.value = linkedAccounts.value[0].id
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement des comptes liés:', error)
  }
}

// Changer de compte
const switchAccount = async () => {
  if (!selectedAccountId.value || switching.value) return
  
  switching.value = true
  try {
    const response = await $api.post(`/student/switch-account/${selectedAccountId.value}`)
    
    if (response.data.success) {
      console.log('✅ Compte changé avec succès:', response.data.data)
      
      // Émettre l'événement pour que le parent puisse recharger les données
      emit('account-switched', response.data.data)
      
      // Recharger la page pour mettre à jour toutes les données
      // Ou utiliser router.reload() si disponible
      window.location.reload()
    }
  } catch (error) {
    console.error('Erreur lors du changement de compte:', error)
    alert(error.response?.data?.message || 'Erreur lors du changement de compte')
    
    // Recharger les comptes pour réinitialiser la sélection
    await loadLinkedAccounts()
  } finally {
    switching.value = false
  }
}

// Charger les comptes au montage
onMounted(() => {
  loadLinkedAccounts()
})
</script>
