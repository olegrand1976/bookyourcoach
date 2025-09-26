# 🎨 Convention des Couleurs de Boutons

Guide professionnel pour l'utilisation cohérente des couleurs de boutons dans l'application.

## 🎯 Principe de Base
Chaque type d'action a une couleur dédiée pour une **compréhension intuitive** de l'interface.

## 📋 Convention des Couleurs

### ✅ Actions de Création
**Couleur :** `emerald` (vert émeraude)
```css
bg-emerald-600 hover:bg-emerald-700 text-white
```
**Usage :** Ajouter, Créer, Nouveau, Inscrire
**Exemples :**
- "Ajouter un enseignant"
- "Nouveau cours"
- "Créer un planning"
- "Inscrire un élève"

### 💾 Actions de Sauvegarde/Confirmation
**Couleur :** `blue` (bleu)
```css
bg-blue-600 hover:bg-blue-700 text-white
```
**Usage :** Sauvegarder, Confirmer, Valider, Connexion
**Exemples :**
- "Sauvegarder"
- "Confirmer"
- "Se connecter"
- "Valider"

### ✏️ Actions de Modification
**Couleur :** `amber` (ambre/orange)
```css
bg-amber-600 hover:bg-amber-700 text-white
```
**Usage :** Modifier, Éditer, Mettre à jour
**Exemples :**
- "Modifier le profil"
- "Éditer"
- "Mettre à jour"

### 🗑️ Actions de Suppression
**Couleur :** `red` (rouge)
```css
bg-red-600 hover:bg-red-700 text-white
```
**Usage :** Supprimer, Désactiver, Annuler (destructif)
**Exemples :**
- "Supprimer"
- "Désactiver"
- "Annuler la réservation"

### ❌ Actions d'Annulation
**Couleur :** `gray` (gris)
```css
bg-gray-500 hover:bg-gray-600 text-white
```
**Usage :** Annuler (non-destructif), Fermer, Retour
**Exemples :**
- "Annuler" (dans modal)
- "Fermer"
- "Retour"

### 📅 Actions de Planning
**Couleur :** `indigo` (indigo)
```css
bg-indigo-600 hover:bg-indigo-700 text-white
```
**Usage :** Planning, Calendrier, Programme, Horaires
**Exemples :**
- "Planning"
- "Voir le calendrier"
- "Programmer"
- "Horaires"

### 🔄 Actions de Synchronisation
**Couleur :** `cyan` (cyan)
```css
bg-cyan-600 hover:bg-cyan-700 text-white
```
**Usage :** Synchroniser, Récurrent, Actualiser, Rafraîchir
**Exemples :**
- "Synchroniser"
- "Récurrent"
- "Actualiser"
- "Rafraîchir"

### ⚙️ Actions de Configuration
**Couleur :** `slate` (slate/gris foncé)
```css
bg-slate-600 hover:bg-slate-700 text-white
```
**Usage :** Paramètres, Configuration, Options
**Exemples :**
- "Paramètres"
- "Configuration"
- "Options"
- "Préférences"

### 📊 Actions d'Analyse
**Couleur :** `purple` (violet)
```css
bg-purple-600 hover:bg-purple-700 text-white
```
**Usage :** Dashboard, Statistiques, Rapports, Analyse
**Exemples :**
- "Dashboard"
- "Statistiques"
- "Rapport"
- "Analyse"

### 👁️ Actions de Visualisation
**Couleur :** `teal` (teal)
```css
bg-teal-600 hover:bg-teal-700 text-white
```
**Usage :** Voir, Détails, Consulter, Afficher
**Exemples :**
- "Voir les détails"
- "Consulter"
- "Afficher"
- "Détails"

## 🎨 Variantes et États

### Boutons Secondaires
Pour les actions moins importantes, utiliser des variantes plus claires :
```css
bg-emerald-100 text-emerald-800 hover:bg-emerald-200
bg-blue-100 text-blue-800 hover:bg-blue-200
```

### Boutons Désactivés
```css
bg-gray-300 text-gray-500 cursor-not-allowed
```

### Boutons Outline
```css
border-2 border-emerald-600 text-emerald-600 hover:bg-emerald-600 hover:text-white
```

## 📏 Tailles Standard

### Petit
```css
px-3 py-1 text-sm rounded
```

### Normal
```css
px-4 py-2 text-base rounded-lg
```

### Grand
```css
px-6 py-3 text-lg rounded-xl
```

## 🎯 Exemples d'Application

### Modal de Confirmation
```html
<!-- Confirmer (bleu) -->
<button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
  Confirmer
</button>

<!-- Annuler (gris) -->
<button class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
  Annuler
</button>
```

### Formulaire de Profil
```html
<!-- Sauvegarder (bleu) -->
<button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
  Sauvegarder
</button>

<!-- Modifier (ambre) -->
<button class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg">
  Modifier
</button>
```

### Liste d'Actions
```html
<!-- Ajouter (vert émeraude) -->
<button class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg">
  Ajouter un cours
</button>

<!-- Planning (indigo) -->
<button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg">
  Planning
</button>

<!-- Supprimer (rouge) -->
<button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
  Supprimer
</button>
```

## 🧪 Migration

### Avant
```css
bg-green-500 hover:bg-green-600  → bg-emerald-600 hover:bg-emerald-700
bg-yellow-500 hover:bg-yellow-600 → bg-amber-600 hover:bg-amber-700
bg-purple-500 hover:bg-purple-600 → bg-indigo-600 hover:bg-indigo-700 (si planning)
                                  → bg-purple-600 hover:bg-purple-700 (si analyse)
```

## ✅ Checklist de Validation

- [ ] Les couleurs correspondent au type d'action
- [ ] Les états hover sont définis
- [ ] La lisibilité est assurée (contraste suffisant)
- [ ] La cohérence est respectée dans toute l'application
- [ ] Les icônes correspondent aux couleurs
