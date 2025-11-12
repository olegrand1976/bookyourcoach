<template>
  <div class="relative" ref="containerRef">
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <div class="relative">
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
      </div>
      
      <input
        ref="inputRef"
        v-model="searchQuery"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown.enter.prevent="selectFirstResult"
        @keydown.down.prevent="navigateDown"
        @keydown.up.prevent="navigateUp"
        @keydown.escape="closeResults"
        type="text"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :class="[
          'w-full pl-10 pr-4 py-2 border rounded-md focus:ring-2 focus:ring-blue-500',
          error ? 'border-red-500 bg-red-50' : 'border-gray-300',
          disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'
        ]"
      />
      
      <button
        v-if="selectedItem && !disabled"
        @click="clearSelection"
        type="button"
        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>
    
    <!-- Résultats -->
    <div
      v-if="showResults && filteredResults.length > 0"
      class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
    >
      <div
        v-for="(item, index) in filteredResults"
        :key="getItemIdFn(item)"
        @click="selectItem(item)"
        @mouseenter="highlightedIndex = index"
        :class="[
          'px-4 py-2 cursor-pointer transition-colors',
          index === highlightedIndex ? 'bg-blue-50 text-blue-900' : 'text-gray-900 hover:bg-gray-50'
        ]"
      >
        <slot name="item" :item="item">
          {{ getItemLabelFn(item) }}
        </slot>
      </div>
    </div>
    
    <!-- Message aucun résultat -->
    <div
      v-if="showResults && searchQuery && filteredResults.length === 0 && !isLoading"
      class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg px-4 py-2 text-sm text-gray-500"
    >
      Aucun résultat trouvé
    </div>
    
    <!-- Loading -->
    <div
      v-if="isLoading"
      class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg px-4 py-2 text-sm text-gray-500 flex items-center justify-center"
    >
      <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600 mr-2"></div>
      Recherche...
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'

interface Props {
  modelValue: any
  items: any[]
  label?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  error?: boolean
  getItemLabel?: (item: any) => string
  getItemId?: (item: any) => any
  filterFunction?: (item: any, query: string) => boolean
  isLoading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Rechercher...',
  required: false,
  disabled: false,
  error: false,
  isLoading: false,
  getItemLabel: undefined,
  getItemId: undefined,
  filterFunction: undefined
})

// Fonctions par défaut (ne peuvent pas référencer props dans les valeurs par défaut)
function defaultGetItemLabel(item: any): string {
  if (typeof item === 'string' || typeof item === 'number') {
    return String(item)
  }
  return item.name || item.label || String(item.id || '')
}

function defaultGetItemId(item: any): any {
  return item.id || item
}

function defaultFilterFunction(item: any, query: string, getLabelFn: (item: any) => string): boolean {
  const label = getLabelFn(item).toLowerCase()
  return label.includes(query.toLowerCase())
}

const emit = defineEmits<{
  'update:modelValue': [value: any]
}>()

const containerRef = ref<HTMLElement>()
const inputRef = ref<HTMLInputElement>()
const searchQuery = ref('')
const showResults = ref(false)
const highlightedIndex = ref(-1)

// Helpers pour utiliser les fonctions par défaut ou celles passées en props
const getItemLabelFn = computed(() => props.getItemLabel || defaultGetItemLabel)
const getItemIdFn = computed(() => props.getItemId || defaultGetItemId)
const filterFunctionFn = computed(() => props.filterFunction || ((item: any, query: string) => defaultFilterFunction(item, query, getItemLabelFn.value)))

// Trouver l'item sélectionné pour afficher son label
const selectedItem = computed(() => {
  if (!props.modelValue) return null
  return props.items.find(item => getItemIdFn.value(item) === props.modelValue) || null
})

// Affichage dans l'input
watch(selectedItem, (item) => {
  if (item) {
    searchQuery.value = getItemLabelFn.value(item)
  } else {
    searchQuery.value = ''
  }
}, { immediate: true })

// Résultats filtrés
const filteredResults = computed(() => {
  if (!searchQuery.value || searchQuery.value.length < 2) {
    return props.items.slice(0, 10) // Limiter à 10 résultats par défaut
  }
  
  return props.items
    .filter(item => filterFunctionFn.value(item, searchQuery.value))
    .slice(0, 10)
})

// Gestion des événements
function handleInput() {
  showResults.value = true
  highlightedIndex.value = -1
  
  // Si on efface la recherche, réinitialiser la sélection
  if (!searchQuery.value) {
    emit('update:modelValue', null)
  }
}

function handleFocus() {
  showResults.value = true
}

function handleBlur(event: FocusEvent) {
  // Ne pas fermer si on clique dans les résultats
  const relatedTarget = event.relatedTarget as HTMLElement
  if (relatedTarget && containerRef.value?.contains(relatedTarget)) {
    return
  }
  
  setTimeout(() => {
    showResults.value = false
    highlightedIndex.value = -1
    
    // Réinitialiser la recherche si rien n'est sélectionné
    if (!props.modelValue && searchQuery.value) {
      searchQuery.value = ''
    } else if (selectedItem.value) {
      searchQuery.value = getItemLabelFn.value(selectedItem.value)
    }
  }, 200)
}

function selectItem(item: any) {
  const itemId = getItemIdFn.value(item)
  emit('update:modelValue', itemId)
  showResults.value = false
  highlightedIndex.value = -1
  searchQuery.value = getItemLabelFn.value(item)
}

function selectFirstResult() {
  if (filteredResults.value.length > 0) {
    selectItem(filteredResults.value[0])
  }
}

function navigateDown() {
  if (highlightedIndex.value < filteredResults.value.length - 1) {
    highlightedIndex.value++
  }
}

function navigateUp() {
  if (highlightedIndex.value > 0) {
    highlightedIndex.value--
  }
}

function closeResults() {
  showResults.value = false
  highlightedIndex.value = -1
}

function clearSelection() {
  emit('update:modelValue', null)
  searchQuery.value = ''
  showResults.value = false
  inputRef.value?.focus()
}

// Fermer au clic en dehors
function handleClickOutside(event: MouseEvent) {
  if (containerRef.value && !containerRef.value.contains(event.target as Node)) {
    showResults.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

