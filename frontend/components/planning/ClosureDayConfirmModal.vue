<template>
  <div
    v-if="show"
    class="fixed inset-0 z-[60] overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="closure-confirm-title"
    @keydown.esc="cancel"
  >
    <div class="flex min-h-screen items-center justify-center px-4 py-8">
      <div class="fixed inset-0 bg-black/50" aria-hidden="true" @click="cancel" />

      <form
        class="relative w-full max-w-md rounded-xl bg-white shadow-xl overflow-hidden"
        @submit.prevent="submit"
      >
        <div class="px-6 py-4 border-b bg-gradient-to-r from-amber-500 to-orange-600 text-white">
          <h2 id="closure-confirm-title" class="text-lg font-bold">
            {{ action === 'close' ? 'Jour de congés' : 'Annuler le congé' }}
          </h2>
          <p class="text-sm opacity-90 mt-0.5">{{ dateLabel }}</p>
        </div>

        <div class="p-6 space-y-4">
          <p class="text-sm text-gray-800 whitespace-pre-line">{{ message }}</p>

          <fieldset class="space-y-3">
            <legend class="text-sm font-medium text-gray-700 mb-2">Confirmez avec</legend>
            <div class="flex gap-2">
              <button
                v-for="option in methods"
                :key="option.value"
                type="button"
                class="flex-1 px-3 py-2 text-sm rounded-lg border"
                :class="method === option.value
                  ? 'border-orange-600 bg-orange-50 text-orange-800 font-medium'
                  : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                :aria-pressed="method === option.value"
                :disabled="submitting"
                @click="selectMethod(option.value)"
              >
                {{ option.label }}
              </button>
            </div>

            <div v-if="method === 'password'">
              <label for="closure-confirm-password" class="block text-sm text-gray-700 mb-1">Mot de passe</label>
              <input
                id="closure-confirm-password"
                ref="secretInput"
                v-model="password"
                type="password"
                autocomplete="current-password"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500"
                :disabled="submitting"
              />
            </div>
            <div v-else>
              <label for="closure-confirm-code" class="block text-sm text-gray-700 mb-1">
                Code à 6 chiffres de votre application
              </label>
              <input
                id="closure-confirm-code"
                ref="secretInput"
                v-model="code"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="7"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm tracking-widest focus:border-orange-500 focus:ring-orange-500"
                :disabled="submitting"
              />
            </div>
          </fieldset>

          <p v-if="errorMessage" class="text-sm text-red-700 bg-red-50 border border-red-100 rounded-lg px-3 py-2" role="alert">
            {{ errorMessage }}
          </p>
        </div>

        <div class="px-6 py-4 border-t flex justify-end gap-2 bg-gray-50">
          <button
            type="button"
            class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100"
            :disabled="submitting"
            @click="cancel"
          >
            Annuler
          </button>
          <button
            type="submit"
            class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-orange-600 hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!canSubmit || submitting"
          >
            {{ submitting ? 'Vérification…' : 'Confirmer' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import type { ClosureConfirmation } from '~/composables/planning/useClosureDayGuard'

type Method = ClosureConfirmation['method']

const props = defineProps<{
  show: boolean
  action: 'close' | 'open'
  dateLabel: string
  message: string
  submitting: boolean
  errorMessage: string
}>()

const emit = defineEmits<{
  (e: 'confirm', confirmation: ClosureConfirmation): void
  (e: 'cancel'): void
}>()

const methods: { value: Method; label: string }[] = [
  { value: 'password', label: 'Mot de passe' },
  { value: 'totp', label: 'Code 2FA' },
]

const method = ref<Method>('password')
const password = ref('')
const code = ref('')
const secretInput = ref<HTMLInputElement | null>(null)

function focusSecret() {
  nextTick(() => secretInput.value?.focus())
}

// Champs vidés à chaque ouverture : un secret ne doit pas survivre à la modale.
watch(
  () => props.show,
  (open) => {
    password.value = ''
    code.value = ''
    if (open) focusSecret()
  },
  { immediate: true }
)

// Refus du serveur : un code 2FA ne sert qu'une fois, on le fait ressaisir.
watch(
  () => props.errorMessage,
  (message) => {
    if (!message) return
    code.value = ''
    focusSecret()
  }
)

function selectMethod(value: Method) {
  method.value = value
  focusSecret()
}

const canSubmit = computed(() =>
  method.value === 'password'
    ? password.value.length > 0
    : /^\d{6}$/.test(code.value.replace(/\s+/g, ''))
)

function submit() {
  if (!canSubmit.value || props.submitting) return
  emit(
    'confirm',
    method.value === 'password'
      ? { method: 'password', password: password.value }
      : { method: 'totp', code: code.value }
  )
}

function cancel() {
  if (props.submitting) return
  emit('cancel')
}
</script>
