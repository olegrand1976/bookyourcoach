<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">
          Test d'intégration Fontsource Inter ✅
        </h1>
        <p class="text-lg text-gray-600">
          Cette page teste tous les poids de la police Inter installée localement via Fontsource.
        </p>
      </div>

      <!-- Tests des poids de police -->
      <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Poids de police disponibles</h2>
        
        <div class="space-y-4">
          <!-- Weight 400 (Regular) -->
          <div class="border-l-4 border-blue-500 pl-4 py-2">
            <p class="text-sm text-gray-500 mb-1">font-weight: 400 (Regular)</p>
            <p class="text-2xl font-normal">
              The quick brown fox jumps over the lazy dog 🦊
            </p>
            <p class="text-xl font-normal">
              Portez ce vieux whisky au juge blond qui fume 🥃
            </p>
          </div>

          <!-- Weight 500 (Medium) -->
          <div class="border-l-4 border-green-500 pl-4 py-2">
            <p class="text-sm text-gray-500 mb-1">font-weight: 500 (Medium)</p>
            <p class="text-2xl font-medium">
              The quick brown fox jumps over the lazy dog 🦊
            </p>
            <p class="text-xl font-medium">
              Portez ce vieux whisky au juge blond qui fume 🥃
            </p>
          </div>

          <!-- Weight 600 (Semibold) -->
          <div class="border-l-4 border-yellow-500 pl-4 py-2">
            <p class="text-sm text-gray-500 mb-1">font-weight: 600 (Semibold)</p>
            <p class="text-2xl font-semibold">
              The quick brown fox jumps over the lazy dog 🦊
            </p>
            <p class="text-xl font-semibold">
              Portez ce vieux whisky au juge blond qui fume 🥃
            </p>
          </div>

          <!-- Weight 700 (Bold) -->
          <div class="border-l-4 border-red-500 pl-4 py-2">
            <p class="text-sm text-gray-500 mb-1">font-weight: 700 (Bold)</p>
            <p class="text-2xl font-bold">
              The quick brown fox jumps over the lazy dog 🦊
            </p>
            <p class="text-xl font-bold">
              Portez ce vieux whisky au juge blond qui fume 🥃
            </p>
          </div>
        </div>
      </div>

      <!-- Test des tailles -->
      <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Tailles de texte</h2>
        
        <div class="space-y-4">
          <p class="text-xs font-medium text-gray-600">text-xs: Très petit texte (12px)</p>
          <p class="text-sm font-medium text-gray-600">text-sm: Petit texte (14px)</p>
          <p class="text-base font-medium text-gray-700">text-base: Texte normal (16px)</p>
          <p class="text-lg font-medium text-gray-800">text-lg: Texte large (18px)</p>
          <p class="text-xl font-semibold text-gray-900">text-xl: Texte extra-large (20px)</p>
          <p class="text-2xl font-semibold text-gray-900">text-2xl: Titre H3 (24px)</p>
          <p class="text-3xl font-bold text-gray-900">text-3xl: Titre H2 (30px)</p>
          <p class="text-4xl font-bold text-gray-900">text-4xl: Titre H1 (36px)</p>
        </div>
      </div>

      <!-- Test des caractères spéciaux -->
      <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Caractères spéciaux et accents</h2>
        
        <div class="space-y-3">
          <p class="text-lg font-normal">Français: àâäæçéèêëîïôœùûüÿ ÀÂÄÆÇÉÈÊËÎÏÔŒÙÛÜŸ</p>
          <p class="text-lg font-medium">Chiffres: 0123456789</p>
          <p class="text-lg font-semibold">Symboles: €$£¥@#&amp;*+=()[]{}|\/</p>
          <p class="text-lg font-bold">Ponctuation: .,;:!?¡¿«»"'‹›</p>
        </div>
      </div>

      <!-- Informations techniques -->
      <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-8 text-white">
        <h2 class="text-2xl font-bold mb-4">✅ Informations techniques</h2>
        <div class="space-y-2 text-sm">
          <p>📦 Package: @fontsource/inter</p>
          <p>⚡ Chargement: Local (pas de requête externe)</p>
          <p>🎨 Poids installés: 400, 500, 600, 700</p>
          <p>🌍 Unicode: Latin, Cyrillique, Grec, Vietnamien</p>
          <p>💾 Format: WOFF2 (compression optimale)</p>
          <p>⏱️ Performance: Instantané (0ms de latence réseau)</p>
        </div>
      </div>

      <!-- Bouton retour -->
      <div class="mt-8 text-center">
        <NuxtLink 
          to="/" 
          class="inline-flex items-center px-6 py-3 bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow font-medium text-gray-700 hover:text-gray-900"
        >
          ← Retour à l'accueil
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: false // Pas de layout pour cette page de test
})

useHead({
  title: 'Test Fonts - Inter via Fontsource',
  meta: [
    { name: 'description', content: 'Page de test pour vérifier l\'intégration de la police Inter via Fontsource' }
  ]
})
</script>

<style scoped>
/* Force l'utilisation de la police Inter pour être sûr */
* {
  font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
}
</style>
