/**
 * Évite le 500 sur GET /_nuxt/builds/meta/<uuid>.json (metadata de build Nuxt/Nitro).
 * Retourne un JSON minimal pour que le client ne casse pas.
 */
export default defineEventHandler(() => {
  return { id: '' }
})
