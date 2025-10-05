# 🎨 Exemples de Boutons - Convention Appliquée

Ce document montre des exemples concrets des nouvelles couleurs appliquées dans l'application.

## ✅ Actions de Création - `emerald`

### Avant / Après
```html
<!-- AVANT -->
<button class="bg-green-500 hover:bg-green-600">Ajouter</button>
<button class="bg-blue-600 hover:bg-blue-700">Ajouter un enseignant</button>

<!-- APRÈS -->
<button class="bg-emerald-600 hover:bg-emerald-700 text-white">Ajouter</button>
<button class="bg-emerald-600 hover:bg-emerald-700 text-white">Ajouter un enseignant</button>
```

### Exemples dans l'application
```html
<!-- Profile.vue - Ajouter période -->
<button class="bg-emerald-600 text-white px-3 py-1 rounded text-sm hover:bg-emerald-700">
  <Icon name="plus" class="mr-1" />
  Ajouter
</button>

<!-- Dashboard.vue - Nouveau cours -->
<button class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">
  Nouveau cours
</button>

<!-- Space.vue - Actions rapides -->
<button class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">
  Ajouter un Enseignant
</button>
```

## 💾 Actions de Sauvegarde - `blue`

### Exemples
```html
<!-- Profile.vue - Sauvegarder -->
<button class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
  Enregistrer les modifications
</button>

<!-- Login.vue - Connexion -->
<button class="bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
  Se connecter
</button>
```

## 🔄 Actions de Synchronisation - `cyan`

### Avant / Après
```html
<!-- AVANT -->
<button class="bg-blue-500 hover:bg-blue-600">Récurrent</button>
<button class="bg-green-600 hover:bg-green-700">Ouvrir créneaux</button>

<!-- APRÈS -->
<button class="bg-cyan-600 hover:bg-cyan-700 text-white">Récurrent</button>
<button class="bg-cyan-600 hover:bg-cyan-700 text-white">Ouvrir créneaux</button>
```

### Exemples dans l'application
```html
<!-- Profile.vue - Récurrent -->
<button class="bg-cyan-600 text-white px-3 py-1 rounded text-sm hover:bg-cyan-700">
  <Icon name="sync" class="mr-1" />
  Récurrent
</button>

<!-- Planning.vue - Ouvrir créneaux -->
<button class="bg-cyan-600 text-white px-4 py-2 rounded-lg hover:bg-cyan-700">
  Ouvrir créneaux
</button>

<!-- Dashboard.vue - Réessayer -->
<button class="bg-cyan-100 text-cyan-800 px-4 py-2 rounded-md hover:bg-cyan-200">
  Réessayer
</button>
```

## 📅 Actions de Planning - `indigo`

### Exemples
```html
<!-- Dashboard.vue - Planning -->
<button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
  <svg class="w-4 h-4" ...>...</svg>
  <span>Planning</span>
</button>

<!-- Space.vue - Programmer un cours -->
<button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
  Programmer un Cours
</button>
```

## 📊 Actions d'Analyse - `purple`

### Exemples
```html
<!-- Space.vue - Finances -->
<button class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
  Voir les Finances
</button>

<!-- Dashboard.vue - Statistiques -->
<button class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
  Statistiques
</button>
```

## 🗑️ Actions de Suppression - `red`

### Exemples
```html
<!-- Profile.vue - Désactiver -->
<button class="bg-red-100 text-red-700 hover:bg-red-200 px-2 py-1 text-xs rounded">
  Désactiver
</button>

<!-- Modal de confirmation -->
<button class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
  Supprimer définitivement
</button>
```

## ❌ Actions d'Annulation - `gray`

### Exemples
```html
<!-- Modals - Annuler -->
<button class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
  Annuler
</button>

<!-- Formulaires - Retour -->
<button class="border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
  Annuler
</button>
```

## ✏️ Actions de Modification - `amber`

### Exemples
```html
<!-- Profil - Modifier -->
<button class="bg-amber-600 text-white px-4 py-2 rounded-lg hover:bg-amber-700">
  Modifier le profil
</button>

<!-- Liste - Éditer -->
<button class="bg-amber-100 text-amber-800 px-3 py-1 rounded text-sm hover:bg-amber-200">
  Éditer
</button>
```

## 👁️ Actions de Visualisation - `teal`

### Exemples
```html
<!-- Détails -->
<button class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700">
  Voir les détails
</button>

<!-- Consulter -->
<button class="bg-teal-100 text-teal-800 px-3 py-1 rounded text-sm hover:bg-teal-200">
  Consulter
</button>
```

## 🎯 Combinaisons Courantes

### Modal avec Actions
```html
<div class="flex justify-end space-x-3">
  <!-- Annuler (gris) -->
  <button class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
    Annuler
  </button>
  
  <!-- Confirmer (bleu) -->
  <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
    Confirmer
  </button>
</div>
```

### Formulaire Standard
```html
<div class="flex justify-between">
  <!-- Modifier (ambre) -->
  <button class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg">
    Modifier
  </button>
  
  <!-- Sauvegarder (bleu) -->
  <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
    Sauvegarder
  </button>
</div>
```

### Actions de Liste
```html
<div class="flex space-x-2">
  <!-- Ajouter (émeraude) -->
  <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded text-sm">
    Ajouter
  </button>
  
  <!-- Synchroniser (cyan) -->
  <button class="bg-cyan-600 hover:bg-cyan-700 text-white px-3 py-1 rounded text-sm">
    Synchroniser
  </button>
  
  <!-- Supprimer (rouge) -->
  <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
    Supprimer
  </button>
</div>
```

## ✅ Validation Visuelle

Lors du test, vérifiez que :

1. **Cohérence** : Même couleur pour même type d'action
2. **Contraste** : Texte lisible sur tous les boutons
3. **États hover** : Transition fluide vers couleur plus foncée
4. **Icônes** : Correspondance avec la couleur du bouton
5. **Accessibilité** : Respect des contrastes WCAG

## 🔧 Outils de Développement

### Classes utilitaires créées
```css
/* Classes personnalisées suggérées */
.btn-create { @apply bg-emerald-600 hover:bg-emerald-700 text-white; }
.btn-save { @apply bg-blue-600 hover:bg-blue-700 text-white; }
.btn-edit { @apply bg-amber-600 hover:bg-amber-700 text-white; }
.btn-delete { @apply bg-red-600 hover:bg-red-700 text-white; }
.btn-cancel { @apply bg-gray-500 hover:bg-gray-600 text-white; }
.btn-plan { @apply bg-indigo-600 hover:bg-indigo-700 text-white; }
.btn-sync { @apply bg-cyan-600 hover:bg-cyan-700 text-white; }
.btn-analyze { @apply bg-purple-600 hover:bg-purple-700 text-white; }
.btn-view { @apply bg-teal-600 hover:bg-teal-700 text-white; }
```
