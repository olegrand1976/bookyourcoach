# Guide des Couleurs Harmonisées

Ce guide explique comment utiliser les couleurs harmonisées entre l'application web et mobile.

## Vue d'ensemble

Les couleurs sont définies dans deux fichiers principaux :
- **Web** : `frontend/assets/css/app-colors.css`
- **Mobile** : `mobile/lib/constants/app_colors.dart`

## Couleurs Principales

### Couleurs Équestres
- **Primary Brown** : `#8B4513` - Couleur principale de l'application
- **Dark Brown** : `#5D2F0A` - Couleur foncée pour les accents
- **Light Brown** : `#D2B48C` - Couleur claire pour les éléments secondaires
- **Gold** : `#FFD700` - Couleur dorée pour les éléments spéciaux
- **Cream** : `#F5F5DC` - Couleur de fond douce

### Couleurs d'État
- **Success** : `#10B981` - Vert pour les succès
- **Error** : `#EF4444` - Rouge pour les erreurs
- **Warning** : `#F59E0B` - Orange pour les avertissements
- **Info** : `#3B82F6` - Bleu pour les informations

### Couleurs de Texte
- **Text Primary** : `#111827` - Texte principal
- **Text Secondary** : `#6B7280` - Texte secondaire
- **Text Tertiary** : `#9CA3AF` - Texte tertiaire

## Utilisation Web (CSS)

### Variables CSS
```css
:root {
  --color-primary-brown: #8B4513;
  --color-dark-brown: #5D2F0A;
  --color-light-brown: #D2B48C;
  --color-gold: #FFD700;
  --color-cream: #F5F5DC;
  /* ... autres couleurs */
}
```

### Classes Utilitaires
```html
<!-- Couleurs de texte -->
<span class="text-primary">Texte principal</span>
<span class="text-secondary">Texte secondaire</span>
<span class="text-accent">Texte accent</span>

<!-- Couleurs de fond -->
<div class="bg-primary">Fond principal</div>
<div class="bg-secondary">Fond secondaire</div>
<div class="bg-surface">Fond surface</div>

<!-- Couleurs de bordure -->
<div class="border-primary">Bordure principale</div>
<div class="border-accent">Bordure accent</div>
```

### Composants Prédéfinis
```html
<!-- Boutons -->
<button class="btn-primary">Bouton Principal</button>
<button class="btn-secondary">Bouton Secondaire</button>
<button class="btn-outline">Bouton Outline</button>

<!-- Cartes -->
<div class="card">Contenu de la carte</div>

<!-- Champs de saisie -->
<input type="text" class="input" placeholder="Votre texte" />

<!-- Badges -->
<span class="badge badge-primary">Primary</span>
<span class="badge badge-success">Success</span>

<!-- Alertes -->
<div class="alert alert-success">Message de succès</div>
<div class="alert alert-error">Message d'erreur</div>
```

## Utilisation Mobile (Flutter)

### Classe AppColors
```dart
import 'package:activibe/constants/app_colors.dart';

// Utilisation des couleurs
Container(
  color: AppColors.primaryBrown,
  child: Text(
    'Texte',
    style: TextStyle(color: AppColors.white),
  ),
)
```

### Thème AppTheme
```dart
import 'package:activibe/constants/app_colors.dart';

// Utilisation du thème complet
MaterialApp(
  theme: AppTheme.lightTheme,
  home: MyHomePage(),
)
```

### Couleurs Disponibles
```dart
// Couleurs principales
AppColors.primaryBrown
AppColors.darkBrown
AppColors.lightBrown
AppColors.gold
AppColors.cream

// Couleurs d'état
AppColors.success
AppColors.error
AppColors.warning
AppColors.info

// Couleurs de texte
AppColors.textPrimary
AppColors.textSecondary
AppColors.textTertiary

// Couleurs de fond
AppColors.background
AppColors.surface
AppColors.surfaceVariant
```

## Bonnes Pratiques

### 1. Cohérence
- Utilisez toujours les couleurs définies dans les fichiers de couleurs
- Évitez les couleurs codées en dur dans le code

### 2. Accessibilité
- Vérifiez le contraste entre le texte et le fond
- Utilisez les couleurs d'état appropriées

### 3. Responsive Design
- Les couleurs s'adaptent automatiquement au mode sombre
- Utilisez les classes responsive pour les différentes tailles d'écran

### 4. Maintenance
- Modifiez les couleurs uniquement dans les fichiers de couleurs
- Testez les changements sur les deux plateformes

## Exemples d'Utilisation

### Page de Connexion
```html
<!-- Web -->
<div class="min-h-screen bg-background">
  <div class="card">
    <h1 class="text-2xl font-bold text-primary">Connexion</h1>
    <input type="email" class="input" placeholder="Email" />
    <button class="btn-primary w-full">Se connecter</button>
  </div>
</div>
```

```dart
// Mobile
Scaffold(
  backgroundColor: AppColors.background,
  body: Card(
    child: Column(
      children: [
        Text(
          'Connexion',
          style: TextStyle(
            fontSize: 24,
            fontWeight: FontWeight.bold,
            color: AppColors.textPrimary,
          ),
        ),
        TextField(
          decoration: InputDecoration(
            hintText: 'Email',
            border: OutlineInputBorder(
              borderSide: BorderSide(color: AppColors.border),
            ),
          ),
        ),
        ElevatedButton(
          onPressed: () {},
          child: Text('Se connecter'),
        ),
      ],
    ),
  ),
)
```

### Page des Préférences
```html
<!-- Web -->
<div class="card">
  <h2 class="text-xl font-semibold text-primary mb-4">Mes Préférences</h2>
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <span class="text-secondary">Équitation</span>
      <span class="badge badge-success">Actif</span>
    </div>
    <div class="flex items-center justify-between">
      <span class="text-secondary">Natation</span>
      <span class="badge badge-primary">Préféré</span>
    </div>
  </div>
</div>
```

```dart
// Mobile
Card(
  child: Column(
    children: [
      Text(
        'Mes Préférences',
        style: TextStyle(
          fontSize: 20,
          fontWeight: FontWeight.w600,
          color: AppColors.textPrimary,
        ),
      ),
      ListTile(
        title: Text('Équitation'),
        trailing: Chip(
          label: Text('Actif'),
          backgroundColor: AppColors.success,
        ),
      ),
      ListTile(
        title: Text('Natation'),
        trailing: Chip(
          label: Text('Préféré'),
          backgroundColor: AppColors.primaryBrown,
        ),
      ),
    ],
  ),
)
```

## Dépannage

### Problèmes Courants

1. **Couleurs non appliquées**
   - Vérifiez que les fichiers CSS sont bien chargés
   - Vérifiez que les imports sont corrects

2. **Incohérence entre web et mobile**
   - Vérifiez que les valeurs hexadécimales sont identiques
   - Vérifiez que les noms des couleurs correspondent

3. **Problèmes de contraste**
   - Utilisez un outil de vérification de contraste
   - Ajustez les couleurs si nécessaire

### Outils de Développement

- **Web** : Inspecteur de navigateur pour vérifier les CSS
- **Mobile** : Flutter Inspector pour vérifier les widgets
- **Contraste** : WebAIM Contrast Checker

## Mise à Jour

Pour mettre à jour les couleurs :

1. Modifiez les valeurs dans `app-colors.css` (web)
2. Modifiez les valeurs dans `app_colors.dart` (mobile)
3. Testez sur les deux plateformes
4. Mettez à jour la documentation si nécessaire

## Support

Pour toute question ou problème :
- Vérifiez d'abord ce guide
- Consultez la page de démonstration `/colors-demo`
- Testez avec les exemples fournis
