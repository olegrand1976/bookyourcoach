<template>
  <div class="address-form">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Rue</label>
        <input 
          v-model="localAddress.street" 
          type="text" 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :placeholder="streetPlaceholder"
          @input="updateAddress"
        >
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Numéro</label>
        <input 
          v-model="localAddress.street_number" 
          type="text" 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :placeholder="numberPlaceholder"
          @input="updateAddress"
        >
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
        <input 
          v-model="localAddress.postal_code" 
          type="text" 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :placeholder="postalCodePlaceholder"
          @input="updateAddress"
        >
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
        <input 
          v-model="localAddress.city" 
          type="text" 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :placeholder="cityPlaceholder"
          @input="updateAddress"
        >
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
        <input 
          v-model="localAddress.country" 
          type="text" 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :placeholder="countryPlaceholder"
          @input="updateAddress"
        >
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      street: '',
      street_number: '',
      postal_code: '',
      city: '',
      country: 'France'
    })
  },
  streetPlaceholder: {
    type: String,
    default: 'Nom de la rue'
  },
  numberPlaceholder: {
    type: String,
    default: 'Numéro'
  },
  postalCodePlaceholder: {
    type: String,
    default: 'Code postal'
  },
  cityPlaceholder: {
    type: String,
    default: 'Ville'
  },
  countryPlaceholder: {
    type: String,
    default: 'Pays'
  }
})

const emit = defineEmits(['update:modelValue'])

const localAddress = ref({ ...props.modelValue })

const updateAddress = () => {
  emit('update:modelValue', { ...localAddress.value })
}

watch(() => props.modelValue, (newValue) => {
  localAddress.value = { ...newValue }
}, { deep: true })

onMounted(() => {
  localAddress.value = { ...props.modelValue }
})
</script>

<style scoped>
.address-form {
  @apply w-full;
}
</style>
