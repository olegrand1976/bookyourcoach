<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="link-child-title"
    @click.self="close"
  >
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
      <div class="flex items-start justify-between mb-4">
        <div>
          <h2 id="link-child-title" class="text-xl font-semibold text-gray-900">
            Rattacher un enfant
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Saisissez le code d'invitation transmis par le club.
          </p>
        </div>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded p-1"
          aria-label="Fermer"
          @click="close"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label for="invite-code-input" class="block text-sm font-medium text-gray-700 mb-1">
            Code d'invitation
          </label>
          <input
            id="invite-code-input"
            ref="codeInput"
            v-model="rawCode"
            type="text"
            inputmode="text"
            autocomplete="off"
            autocapitalize="characters"
            spellcheck="false"
            maxlength="14"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg text-center font-mono tracking-widest text-lg uppercase focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            :class="{ 'border-red-400 focus:ring-red-500 focus:border-red-500': errorMessage }"
            placeholder="ABCD-EFGH-IJ"
            aria-describedby="invite-code-help"
            :aria-invalid="errorMessage ? 'true' : 'false'"
            @input="onInput"
          />
          <p id="invite-code-help" class="text-xs text-gray-500 mt-2">
            Demandez ce code à votre club. Il est valable 30 jours.
          </p>
        </div>

        <div
          v-if="errorMessage"
          role="alert"
          class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800"
        >
          {{ errorMessage }}
        </div>

        <div
          v-if="successMessage"
          role="status"
          class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-800"
        >
          {{ successMessage }}
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
          <button
            type="button"
            class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            :disabled="submitting"
            @click="close"
          >
            Annuler
          </button>
          <button
            type="submit"
            class="px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="submitting || !canSubmit"
          >
            <span v-if="submitting">Rattachement…</span>
            <span v-else>Rattacher l'enfant</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { useStudentScopeStore } from '~/stores/studentScope'

const props = defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'linked', child: { id: number; name: string }): void
}>()

const { $api } = useNuxtApp()
const scopeStore = useStudentScopeStore()

const rawCode = ref('')
const submitting = ref(false)
const errorMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)
const codeInput = ref<HTMLInputElement | null>(null)

const normalizedCode = computed(() =>
  rawCode.value.toUpperCase().replace(/[^A-Z0-9]/g, '')
)

const canSubmit = computed(() => normalizedCode.value.length >= 6 && normalizedCode.value.length <= 14)

const close = () => {
  if (submitting.value) return
  emit('update:modelValue', false)
}

watch(
  () => props.modelValue,
  async (open) => {
    if (open) {
      rawCode.value = ''
      errorMessage.value = null
      successMessage.value = null
      await nextTick()
      codeInput.value?.focus()
    }
  }
)

const onInput = () => {
  errorMessage.value = null
  const filtered = rawCode.value.toUpperCase().replace(/[^A-Z0-9\-\s]/g, '')
  rawCode.value = filtered
}

const submit = async () => {
  if (!canSubmit.value || submitting.value) return

  submitting.value = true
  errorMessage.value = null
  successMessage.value = null

  try {
    const response = await $api.post<{
      success: boolean
      message?: string
      data?: { id: number; name: string }
    }>('/student/family/link-child', { invite_code: normalizedCode.value })

    if (response.data?.success && response.data.data) {
      successMessage.value = response.data.message || 'Enfant rattaché avec succès.'
      emit('linked', response.data.data)
      await scopeStore.loadLinkedAccounts()
      setTimeout(() => emit('update:modelValue', false), 1200)
    } else {
      errorMessage.value = response.data?.message || 'Erreur inconnue.'
    }
  } catch (err: any) {
    const status = err?.response?.status
    const message = err?.response?.data?.message
    if (status === 429) {
      errorMessage.value = 'Trop de tentatives. Réessayez dans quelques minutes.'
    } else {
      errorMessage.value = message || 'Erreur lors du rattachement.'
    }
  } finally {
    submitting.value = false
  }
}
</script>
