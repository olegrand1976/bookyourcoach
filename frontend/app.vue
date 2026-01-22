<template>
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
</template>

<script setup>
// Configuration globale de l'application
useHead({
  title: 'Acti\'Vibe',
  titleTemplate: (titleChunk) => {
    return titleChunk ? `${titleChunk} - Acti'Vibe` : ' Acti\'Vibe '
  },
  meta: [
    { charset: 'utf-8' },
    { name: 'viewport', content: 'width=device-width, initial-scale=1' },
    { hid: 'description', name: 'description', content: 'Plateforme de réservation de cours avec des coaches certifiés - Acti\'Vibe' }
  ],
  link: [
    { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' }
  ]
})
</script>
