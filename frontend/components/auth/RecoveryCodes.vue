<template>
  <div class="space-y-4">
    <div class="bg-amber-50 border-l-4 border-amber-400 rounded-lg p-4 text-sm text-amber-800">
      Conservez ces codes en lieu sûr (gestionnaire de mots de passe, papier). Chacun permet
      <strong>une seule</strong> connexion si vous perdez votre téléphone. Ils ne seront plus affichés.
    </div>
    <ul class="grid grid-cols-2 gap-2 font-mono text-sm bg-gray-50 border border-gray-200 rounded-lg p-4" data-testid="recovery-codes">
      <li v-for="c in codes" :key="c" class="text-center text-gray-900">{{ c }}</li>
    </ul>
    <div class="flex gap-3">
      <button type="button" class="flex-1 px-4 py-2 border-2 border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50" @click="copyCodes">
        {{ copied ? 'Copié ✓' : 'Copier' }}
      </button>
      <button type="button" class="flex-1 px-4 py-2 border-2 border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50" @click="downloadCodes">
        Télécharger (.txt)
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{ codes: string[] }>()

const copied = ref(false)

const codesAsText = () => props.codes.join('\n')

const copyCodes = async () => {
  try {
    await navigator.clipboard.writeText(codesAsText())
    copied.value = true
  } catch {
    copied.value = false
  }
}

const downloadCodes = () => {
  const blob = new Blob([`Codes de récupération activibe\n\n${codesAsText()}\n`], { type: 'text/plain' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'activibe-codes-recuperation.txt'
  link.click()
  URL.revokeObjectURL(url)
}
</script>
