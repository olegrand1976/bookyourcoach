<template>
  <div v-if="store.hasMultipleStudents" class="mb-6">
    <div class="bg-white rounded-lg shadow-md p-4 border border-gray-200">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center min-w-0 flex-1">
          <div class="bg-blue-100 p-2 rounded-lg shrink-0">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div class="ml-3 min-w-0 flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Affichage
            </label>
            <select
              :value="store.activeStudentId === null ? 'all' : store.activeStudentId"
              @change="onChange"
              class="w-full min-h-[44px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
            >
              <option value="all">Vue globale (tous les élèves)</option>
              <option
                v-for="account in store.linkedAccounts"
                :key="account.id"
                :value="account.id"
              >
                {{ account.name }}{{ account.is_primary ? ' (principal)' : '' }}
              </option>
            </select>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const store = useStudentScopeStore()
const emit = defineEmits<{ (e: 'scope-changed'): void }>()

const onChange = (event: Event) => {
  const value = (event.target as HTMLSelectElement).value
  if (value === 'all') {
    store.setGlobalView()
  } else {
    store.setScope(parseInt(value, 10))
  }
  emit('scope-changed')
}
</script>
