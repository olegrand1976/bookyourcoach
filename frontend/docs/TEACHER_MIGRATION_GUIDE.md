# 📘 Guide de Migration - Pages Enseignant

## ✅ Pages Migrées

### 1. teacher/index.vue - ✅ TERMINÉ
**Modifications apportées** :
- Remplacement des emojis par des icônes SVG
- Application des gradients du Design System :
  - Dashboard : `from-blue-500 to-indigo-600` (Bleu/Indigo - Enseignant)
  - Planning : `from-orange-500 to-red-600` (Orange/Rouge - Planning)
  - Élèves : `from-emerald-500 to-teal-600` (Vert/Teal - Élèves)
  - Revenus : `from-purple-500 to-pink-600` (Violet/Rose - Premium/Analyse)
  - QR Code : `from-purple-500 to-pink-600` (Violet/Rose - Premium)
  - Profil : `from-blue-500 to-indigo-600` (Bleu/Indigo - Enseignant)
- Ajout de cartes avec bordures hover
- Amélioration des statistiques rapides avec gradients textuels

### 2. teacher/dashboard.vue - ✅ TERMINÉ  
**Modifications apportées** :
- Ajout de la sélection de clubs avec design moderne
- Boutons avec gradients pour les actions principales
- Filtrage des cours par club
- Affichage des revenus mensuels
- Bouton "Voir le calendrier" avec gradient violet-rose
- Statistiques adaptées selon le club sélectionné

### 3. teacher/qr-code.vue - ✅ TERMINÉ
**Modifications apportées** :
- Header avec gradient Violet/Rose (QR Code = Premium)
- Bouton "Régénérer" avec gradient `from-purple-500 to-pink-600`
- Ajout d'icône de rechargement avec animation
- Amélioration visuelle de l'icône QR Code dans le header

## 🔄 Pages À Migrer

### 4. teacher/profile.vue
**Modifications nécessaires** :
```vue
<!-- Boutons à mettre à jour -->

<!-- ❌ AVANT (à remplacer) -->
<button class="bg-green-500 hover:bg-green-600">Ajouter</button>

<!-- ✅ APRÈS (Design System) -->
<button class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors shadow-sm hover:shadow-md">
  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
  </svg>
  <span>Ajouter</span>
</button>

<!-- Bouton Sauvegarder (bleu) -->
<button class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-sm hover:shadow-md">
  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
  </svg>
  <span>Enregistrer</span>
</button>

<!-- Bouton Modifier (ambre) -->
<button class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors shadow-sm hover:shadow-md">
  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
  </svg>
  <span>Modifier</span>
</button>
```

### 5. teacher/schedule.vue
**Modifications nécessaires** :
```vue
<!-- Boutons Planning avec gradient Orange/Rouge -->
<button class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-orange-500 to-red-600 text-white rounded-lg hover:from-orange-600 hover:to-red-700 transition-all duration-200 shadow-sm hover:shadow-md">
  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
  </svg>
  <span>Voir le planning</span>
</button>

<!-- Bouton Ajouter créneau (emerald) -->
<button class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors shadow-sm hover:shadow-md">
  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
  </svg>
  <span>Ajouter un créneau</span>
</button>

<!-- Bouton Synchroniser Google Calendar (cyan) -->
<button class="inline-flex items-center px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition-colors shadow-sm hover:shadow-md">
  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
  </svg>
  <span>Synchroniser</span>
</button>
```

### 6. teacher/students.vue
**Modifications nécessaires** :
```vue
<!-- Actions avec icônes + tooltips selon Claude.md -->

<!-- Bouton Voir (teal) avec tooltip -->
<button 
  class="inline-flex items-center justify-center w-9 h-9 bg-teal-100 text-teal-700 rounded-lg hover:bg-teal-200 transition-colors"
  title="Voir les détails de l'élève"
>
  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
  </svg>
</button>

<!-- Bouton Modifier (amber) avec tooltip -->
<button 
  class="inline-flex items-center justify-center w-9 h-9 bg-amber-100 text-amber-700 rounded-lg hover:bg-amber-200 transition-colors"
  title="Modifier l'élève"
>
  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
  </svg>
</button>

<!-- Bouton Supprimer (red) avec tooltip -->
<button 
  class="inline-flex items-center justify-center w-9 h-9 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors"
  title="Supprimer l'élève"
>
  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
  </svg>
</button>
```

### 7. teacher/earnings.vue
**Modifications nécessaires** :
```vue
<!-- Header avec gradient Violet/Rose (Analyse) -->
<div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl shadow-lg p-6">
  <div class="flex items-center space-x-4">
    <div class="p-3 bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg shadow-md">
      <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
      </svg>
    </div>
    <div>
      <h1 class="text-3xl font-bold text-gray-900">Mes Revenus</h1>
      <p class="text-gray-600">Statistiques et analyses financières</p>
    </div>
  </div>
</div>

<!-- Boutons d'export/analyse -->
<button class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors shadow-sm hover:shadow-md">
  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
  </svg>
  <span>Exporter</span>
</button>
```

### 8. teacher/settings.vue
**Modifications nécessaires** :
```vue
<!-- Bouton Sauvegarder (bleu) -->
<button class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-sm hover:shadow-md font-medium">
  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
  </svg>
  <span>Enregistrer les paramètres</span>
</button>

<!-- Bouton Annuler (gray) -->
<button class="inline-flex items-center px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors shadow-sm hover:shadow-md font-medium">
  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
  </svg>
  <span>Annuler</span>
</button>
```

## 📋 Checklist de Migration

Pour chaque page, vérifier :

- [ ] **Remplacement des emojis** par des icônes SVG
- [ ] **Application des gradients** selon le type d'action :
  - 🟦 Bleu/Indigo : Enseignant, Dashboard
  - 🟧 Orange/Rouge : Planning, CTA
  - 🟩 Vert/Teal : Élèves, Succès, Ajouter
  - 🟪 Violet/Rose : QR Code, Revenus, Premium, Analyse
  - 🔵 Bleu uni : Sauvegarder
  - 🟡 Ambre : Modifier
  - 🔴 Rouge : Supprimer
  - ⚫ Gris : Annuler
  - 🔷 Cyan : Synchroniser
  - 🟢 Teal : Visualiser

- [ ] **Boutons avec icônes** : utiliser `space-x-2` pour l'espacement icône/texte
- [ ] **Tooltips** : ajouter `title=""` sur les boutons icône seuls
- [ ] **Classes de transition** : `transition-colors`, `transition-all duration-200`
- [ ] **Ombres** : `shadow-sm hover:shadow-md`
- [ ] **Padding standard** : `px-4 py-2` pour boutons normaux
- [ ] **Rounded** : `rounded-lg` pour les boutons et cartes

## 🎯 Conventions Globales

### Espacement
- **Entre boutons** : `space-x-2` ou `space-x-3`
- **Padding cartes** : `p-6` (standard) ou `p-8` (grande)
- **Margin sections** : `mb-8`

### Typographie
- **H1** : `text-3xl font-bold text-gray-900`
- **H2** : `text-2xl font-semibold text-gray-800`
- **H3** : `text-xl font-semibold text-gray-900`
- **Body** : `text-gray-600` ou `text-gray-700`

### États hover
- **Cartes** : `hover:shadow-xl transition-all duration-300`
- **Boutons** : `hover:bg-{couleur}-700` (pour bg-{couleur}-600)
- **Transform** : `transform hover:-translate-y-1` pour les cartes

## 🚀 Avantages de la Migration

1. **Cohérence visuelle** : Même design sur toutes les pages
2. **Accessibilité** : Icônes + texte, tooltips, contrastes WCAG
3. **Performance** : Classes Tailwind optimisées
4. **Maintenance** : Code unifié et réutilisable
5. **UX** : Meilleure ergonomie avec actions visuelles claires

---

**Dernière mise à jour** : Décembre 2024  
**Statut** : 3/8 pages migrées

