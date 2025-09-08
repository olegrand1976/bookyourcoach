<template>
  <div class="min-h-screen bg-background p-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-primary mb-4">
          🎨 Palette de Couleurs Harmonisée
        </h1>
        <p class="text-lg text-secondary">
          Couleurs communes entre l'application web et mobile
        </p>
      </div>

      <!-- Couleurs principales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="card">
          <h3 class="text-lg font-semibold text-primary mb-4">Couleurs Principales</h3>
          <div class="space-y-3">
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 rounded-lg bg-primary"></div>
              <span class="text-sm text-secondary">Primary Brown (#8B4513)</span>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 rounded-lg bg-secondary"></div>
              <span class="text-sm text-secondary">Light Brown (#D2B48C)</span>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 rounded-lg" style="background-color: #FFD700;"></div>
              <span class="text-sm text-secondary">Gold (#FFD700)</span>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 rounded-lg" style="background-color: #F5F5DC;"></div>
              <span class="text-sm text-secondary">Cream (#F5F5DC)</span>
            </div>
          </div>
        </div>

        <div class="card">
          <h3 class="text-lg font-semibold text-primary mb-4">Couleurs d'État</h3>
          <div class="space-y-3">
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 rounded-lg bg-success"></div>
              <span class="text-sm text-secondary">Success (#10B981)</span>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 rounded-lg bg-error"></div>
              <span class="text-sm text-secondary">Error (#EF4444)</span>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 rounded-lg bg-warning"></div>
              <span class="text-sm text-secondary">Warning (#F59E0B)</span>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 rounded-lg bg-info"></div>
              <span class="text-sm text-secondary">Info (#3B82F6)</span>
            </div>
          </div>
        </div>

        <div class="card">
          <h3 class="text-lg font-semibold text-primary mb-4">Couleurs de Texte</h3>
          <div class="space-y-3">
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 rounded-lg bg-text-primary"></div>
              <span class="text-sm text-secondary">Text Primary (#111827)</span>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 rounded-lg bg-text-secondary"></div>
              <span class="text-sm text-secondary">Text Secondary (#6B7280)</span>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 rounded-lg bg-text-tertiary"></div>
              <span class="text-sm text-secondary">Text Tertiary (#9CA3AF)</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Boutons de démonstration -->
      <div class="card mb-8">
        <h3 class="text-lg font-semibold text-primary mb-4">Boutons</h3>
        <div class="flex flex-wrap gap-4">
          <button class="btn-primary">Bouton Principal</button>
          <button class="btn-secondary">Bouton Secondaire</button>
          <button class="btn-outline">Bouton Outline</button>
        </div>
      </div>

      <!-- Badges de démonstration -->
      <div class="card mb-8">
        <h3 class="text-lg font-semibold text-primary mb-4">Badges</h3>
        <div class="flex flex-wrap gap-4">
          <span class="badge badge-primary">Primary</span>
          <span class="badge badge-secondary">Secondary</span>
          <span class="badge badge-success">Success</span>
          <span class="badge badge-error">Error</span>
        </div>
      </div>

      <!-- Alertes de démonstration -->
      <div class="space-y-4 mb-8">
        <div class="alert alert-success">
          <strong>Succès !</strong> Cette alerte utilise les couleurs harmonisées.
        </div>
        <div class="alert alert-error">
          <strong>Erreur !</strong> Cette alerte utilise les couleurs harmonisées.
        </div>
        <div class="alert alert-warning">
          <strong>Attention !</strong> Cette alerte utilise les couleurs harmonisées.
        </div>
        <div class="alert alert-info">
          <strong>Information !</strong> Cette alerte utilise les couleurs harmonisées.
        </div>
      </div>

      <!-- Champs de saisie de démonstration -->
      <div class="card mb-8">
        <h3 class="text-lg font-semibold text-primary mb-4">Champs de Saisie</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-secondary mb-2">Email</label>
            <input type="email" class="input" placeholder="votre@email.com" />
          </div>
          <div>
            <label class="block text-sm font-medium text-secondary mb-2">Mot de passe</label>
            <input type="password" class="input" placeholder="Votre mot de passe" />
          </div>
        </div>
      </div>

      <!-- Cartes de démonstration -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="card hover-lift">
          <h4 class="text-lg font-semibold text-primary mb-2">Carte Interactive</h4>
          <p class="text-secondary mb-4">
            Cette carte utilise les couleurs harmonisées et a un effet de survol.
          </p>
          <button class="btn-primary">Action</button>
        </div>
        <div class="card hover-lift">
          <h4 class="text-lg font-semibold text-primary mb-2">Carte avec Badge</h4>
          <p class="text-secondary mb-4">
            Cette carte inclut un badge et utilise les couleurs harmonisées.
          </p>
          <div class="flex items-center justify-between">
            <span class="badge badge-success">Actif</span>
            <button class="btn-secondary">Voir</button>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="text-center text-secondary">
        <p class="text-sm">
          Cette page démontre l'harmonisation des couleurs entre l'application web et mobile.
        </p>
        <p class="text-xs mt-2">
          Les couleurs sont définies dans <code class="bg-surface-variant px-2 py-1 rounded">app-colors.css</code>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
// Page de démonstration des couleurs harmonisées
</script>

<style scoped>
/* Styles spécifiques à cette page si nécessaire */
</style>
