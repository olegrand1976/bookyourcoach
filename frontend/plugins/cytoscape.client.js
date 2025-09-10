export default defineNuxtPlugin(() => {
  // Configuration pour les imports dynamiques de Cytoscape
  if (process.client) {
    // Précharger les modules Cytoscape côté client
    import('cytoscape').then(cytoscape => {
      // Enregistrer globalement si nécessaire
      window.cytoscape = cytoscape.default
    })
    
    import('cytoscape-cose-bilkent').then(coseBilkent => {
      window.coseBilkent = coseBilkent.default
    })
  }
})
