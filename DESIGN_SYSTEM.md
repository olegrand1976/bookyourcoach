# 🎨 Design System - Acti'Vibe

**Date**: 25 octobre 2025  
**Version**: 1.0

---

## 📋 Table des Matières

1. [Typographie](#typographie)
2. [Palette de Couleurs](#palette-de-couleurs)
3. [Boutons](#boutons)
4. [Espacement](#espacement)
5. [Ombres et Élévations](#ombres-et-élévations)
6. [Guidelines](#guidelines)

---

## 📝 Typographie

### Police Principale : **Inter**

La police **Inter** est utilisée dans toute l'application via **@fontsource**.

**Configuration** : `frontend/nuxt.config.ts`

```typescript
css: [
  '@fontsource/inter/400.css',  // Regular
  '@fontsource/inter/500.css',  // Medium
  '@fontsource/inter/600.css',  // Semi-Bold
  '@fontsource/inter/700.css'   // Bold
]
```

### Avantages de @fontsource

✅ **Performance optimale** : Fonts hébergées localement (pas de requête externe)  
✅ **Contrôle total** : Sélection précise des poids de police  
✅ **Pas de blocage** : Pas de dépendance à Google Fonts  
✅ **RGPD-friendly** : Pas de tracking externe

### Hiérarchie Typographique

| Élément | Classe Tailwind | Poids | Taille |
|---------|----------------|-------|--------|
| **H1 - Titre principal** | `text-3xl font-bold` | 700 | 30px |
| **H2 - Sous-titre** | `text-2xl font-semibold` | 600 | 24px |
| **H3 - Section** | `text-xl font-semibold` | 600 | 20px |
| **H4 - Carte** | `text-lg font-semibold` | 600 | 18px |
| **Body - Texte normal** | `text-base font-normal` | 400 | 16px |
| **Small - Texte secondaire** | `text-sm` | 400 | 14px |
| **Caption - Légende** | `text-xs` | 400 | 12px |

### Utilisation

```vue
<h1 class="text-3xl font-bold text-gray-900">Titre Principal</h1>
<h2 class="text-2xl font-semibold text-gray-800">Sous-titre</h2>
<p class="text-base text-gray-600">Texte normal</p>
<span class="text-sm text-gray-500">Texte secondaire</span>
```

---

## 🎨 Palette de Couleurs

### Couleurs Primaires

```css
/* Orange/Rouge - Action principale (Planning, CTA) */
from-orange-500 to-red-600

/* Violet/Rose - QR Code, Fonctionnalités premium */
from-purple-500 to-pink-600

/* Bleu/Indigo - Enseignants, Professionnels */
from-blue-500 to-indigo-600

/* Vert/Teal - Élèves, Succès, Validation */
from-emerald-500 to-teal-600
```

### Couleurs Secondaires

| Couleur | Usage | Classes |
|---------|-------|---------|
| **Gris** | Texte, Bordures, Fonds | `gray-50` à `gray-900` |
| **Rouge** | Erreurs, Danger | `red-600`, `red-700` |
| **Jaune** | Avertissements | `yellow-500`, `yellow-600` |
| **Vert** | Succès, Validation | `emerald-600`, `emerald-700` |

### Utilisation des Couleurs

```vue
<!-- Texte -->
<p class="text-gray-900">Texte principal</p>
<p class="text-gray-600">Texte secondaire</p>
<p class="text-gray-500">Texte tertiaire</p>

<!-- Arrière-plans -->
<div class="bg-gray-50">Fond clair</div>
<div class="bg-white">Fond blanc</div>
<div class="bg-gray-100">Fond gris léger</div>
```

---

## 🔘 Boutons

### Classes de Boutons Unifiées

Toutes les classes sont définies dans `frontend/assets/css/buttons.css`

#### Boutons avec Gradients (Actions principales)

```vue
<!-- Planning (Orange → Rouge) -->
<button class="btn-planning">
  <svg class="btn-icon">...</svg>
  <span>Planning</span>
</button>

<!-- QR Code (Violet → Rose) -->
<button class="btn-qr-code">
  <svg class="btn-icon">...</svg>
  <span>QR Code</span>
</button>

<!-- Enseignant (Bleu → Indigo) -->
<button class="btn-teacher">
  <svg class="btn-icon">...</svg>
  <span>Enseignant</span>
</button>

<!-- Élève (Vert → Teal) -->
<button class="btn-student">
  <svg class="btn-icon">...</svg>
  <span>Élève</span>
</button>
```

#### Boutons Standards

```vue
<!-- Succès -->
<button class="btn-success">Enregistrer</button>

<!-- Danger -->
<button class="btn-danger">Supprimer</button>

<!-- Secondaire -->
<button class="btn-secondary">Annuler</button>

<!-- Outline -->
<button class="btn-outline">Plus d'options</button>
```

#### Tailles de Boutons

```vue
<!-- Petit -->
<button class="btn-planning btn-sm">Planning</button>

<!-- Normal (par défaut) -->
<button class="btn-planning">Planning</button>

<!-- Grand -->
<button class="btn-planning btn-lg">Planning</button>
```

#### Icônes dans les Boutons

```vue
<button class="btn-planning">
  <!-- Icône normale -->
  <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <!-- paths... -->
  </svg>
  <span>Texte</span>
</button>

<!-- Grande icône -->
<button class="btn-planning">
  <svg class="btn-icon-lg">...</svg>
  <span>Texte</span>
</button>
```

### Anatomie d'un Bouton

```
┌─────────────────────────────────┐
│  [Icône]  Texte du Bouton       │
│   4x4     space-x-2              │
│  (16px)   padding: 16px 16px    │
│           font: medium (500)     │
│           rounded-lg (8px)       │
│           shadow-sm + hover:md   │
└─────────────────────────────────┘
```

---

## 📏 Espacement

### Système d'Espacement Tailwind

| Classe | Valeur | Usage |
|--------|--------|-------|
| `space-x-1` | 4px | Espacement serré (icône + texte mini) |
| `space-x-2` | 8px | **Standard pour boutons** |
| `space-x-3` | 12px | Espacement moyen |
| `space-x-4` | 16px | Espacement large (header) |
| `space-y-4` | 16px | **Standard pour listes** |
| `space-y-6` | 24px | Espacement entre sections |
| `space-y-8` | 32px | Espacement entre blocs |

### Padding

| Classe | Valeur | Usage |
|--------|--------|-------|
| `p-4` | 16px | Padding carte (petit) |
| `p-6` | 24px | **Padding carte standard** |
| `p-8` | 32px | Padding carte (grand) |
| `px-4 py-2` | 16px 8px | **Padding bouton standard** |

### Margin

```vue
<!-- Espacement entre sections -->
<div class="mb-8">Section 1</div>
<div class="mb-8">Section 2</div>

<!-- Espacement dans un conteneur -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  Contenu
</div>
```

---

## 🎭 Ombres et Élévations

### Ombres Tailwind

| Classe | Usage |
|--------|-------|
| `shadow-sm` | Boutons, cartes légères |
| `shadow-md` | **Cartes standard** |
| `shadow-lg` | **Cartes importantes** |
| `shadow-xl` | Modales, popups |
| `shadow-2xl` | Éléments flottants |

### Hover States

```vue
<!-- Carte avec élévation au survol -->
<div class="shadow-lg hover:shadow-xl transition-shadow">
  Contenu
</div>

<!-- Bouton avec ombre au survol -->
<button class="shadow-sm hover:shadow-md">
  Action
</button>
```

---

## 📐 Layout & Grilles

### Conteneur Principal

```vue
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  <!-- Contenu centré avec padding responsive -->
</div>
```

### Grille de Statistiques

```vue
<!-- 1 colonne mobile, 2 tablette, 4 desktop -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
  <div class="bg-white rounded-xl shadow-lg p-6">Stat 1</div>
  <div class="bg-white rounded-xl shadow-lg p-6">Stat 2</div>
  <div class="bg-white rounded-xl shadow-lg p-6">Stat 3</div>
  <div class="bg-white rounded-xl shadow-lg p-6">Stat 4</div>
</div>
```

### Flex Header

```vue
<div class="flex items-center justify-between">
  <div>
    <h1 class="text-3xl font-bold">Titre</h1>
    <p class="text-gray-600">Sous-titre</p>
  </div>
  <div class="flex items-center space-x-4">
    <!-- Boutons d'action -->
  </div>
</div>
```

---

## ✅ Guidelines

### 1. Cohérence

**❌ À éviter** :
```vue
<!-- Styles en ligne différents partout -->
<button class="bg-indigo-600 px-4 py-2">Button 1</button>
<button class="bg-orange-500 px-3 py-1.5">Button 2</button>
<button style="background: red">Button 3</button>
```

**✅ À faire** :
```vue
<!-- Utiliser les classes unifiées -->
<button class="btn-planning">Button 1</button>
<button class="btn-qr-code">Button 2</button>
<button class="btn-danger">Button 3</button>
```

### 2. Réutilisabilité

**Créer des composants** pour les patterns répétés :

```vue
<!-- components/ActionButtons.vue -->
<template>
  <div class="flex items-center space-x-4">
    <button @click="$emit('planning')" class="btn-planning">
      <svg class="btn-icon">...</svg>
      <span>Planning</span>
    </button>
    <button @click="$emit('qr')" class="btn-qr-code">
      <svg class="btn-icon">...</svg>
      <span>QR Code</span>
    </button>
  </div>
</template>
```

### 3. Accessibilité

```vue
<!-- Toujours inclure du texte avec les icônes -->
<button class="btn-planning">
  <svg class="btn-icon" aria-hidden="true">...</svg>
  <span>Planning</span>
</button>

<!-- Contraste suffisant (WCAG AA minimum) -->
<p class="text-gray-900">Texte principal (contraste élevé)</p>
<p class="text-gray-600">Texte secondaire (contraste moyen)</p>
```

### 4. Responsive Design

```vue
<!-- Adapter les layouts -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
  <!-- Mobile: 1 colonne -->
  <!-- Tablette: 2 colonnes -->
  <!-- Desktop: 4 colonnes -->
</div>

<!-- Cacher/montrer des éléments -->
<div class="hidden lg:block">Desktop only</div>
<div class="lg:hidden">Mobile/Tablet only</div>
```

### 5. Performance

```css
/* ✅ Utiliser les transitions pour les interactions */
.btn-planning {
  @apply transition-all duration-200;
}

/* ❌ Éviter les animations complexes partout */
/* ❌ Ne pas abuser des ombres */
```

---

## 🚀 Migration Checklist

Pour uniformiser une page existante :

- [ ] Remplacer les styles en ligne par des classes unifiées
- [ ] Utiliser `btn-*` pour tous les boutons
- [ ] Utiliser `btn-icon` pour les icônes
- [ ] Vérifier l'espacement (`space-x-2` pour les boutons)
- [ ] Vérifier les ombres (`shadow-sm hover:shadow-md`)
- [ ] Vérifier la hiérarchie typographique
- [ ] Tester le responsive (mobile, tablette, desktop)
- [ ] Vérifier le contraste (accessibilité)

---

## 📚 Ressources

### Documentation

- **Tailwind CSS** : https://tailwindcss.com/docs
- **@fontsource/inter** : https://fontsource.org/fonts/inter
- **Heroicons** : https://heroicons.com/

### Outils

- **Contrast Checker** : https://webaim.org/resources/contrastchecker/
- **Tailwind Color Generator** : https://uicolors.app/create
- **Gradient Generator** : https://hypercolor.dev/

---

**Dernière mise à jour** : 25 octobre 2025  
**Maintenu par** : Équipe Acti'Vibe

