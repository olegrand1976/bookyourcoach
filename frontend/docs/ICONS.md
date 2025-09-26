# 🎨 Guide des Icônes Font Awesome

Ce guide explique comment utiliser le système d'icônes Font Awesome dans l'application.

## 🛠️ Installation et Configuration

Font Awesome est déjà configuré dans l'application via :
- Plugin : `plugins/fontawesome.client.ts`
- Composant utilitaire : `components/Icon.vue`
- CSS : Ajouté dans `nuxt.config.ts`

## 🧩 Utilisation du Composant Icon

### Syntaxe de base
```vue
<Icon name="building" />
<Icon name="users" class="text-blue-500" />
<Icon name="settings" class="text-lg mr-2" />
```

### Props disponibles
- `name` (string, requis) : Nom de l'icône
- `class` (string, optionnel) : Classes CSS personnalisées
- `size` (string, optionnel, défaut: 'lg') : Taille de l'icône

## 📚 Icônes Disponibles

### Interface Générale
| Nom | Icône Font Awesome | Usage |
|-----|-------------------|-------|
| `building` | `fa-building` | Entreprises, clubs |
| `info` | `fa-clipboard-list` | Informations générales |
| `location` | `fa-map-marker-alt` | Adresses, localisation |
| `settings` | `fa-cog` | Configuration, paramètres |
| `lightbulb` | `fa-lightbulb` | Idées, calculs |
| `users` | `fa-users` | Groupes, participants |
| `note` | `fa-file-alt` | Notes, documentation |

### Actions
| Nom | Icône Font Awesome | Usage |
|-----|-------------------|-------|
| `plus` | `fa-plus` | Ajouter |
| `minus` | `fa-minus` | Supprimer |
| `trash` | `fa-trash` | Supprimer définitivement |
| `edit` | `fa-edit` | Modifier |
| `save` | `fa-save` | Sauvegarder |
| `sync` | `fa-sync-alt` | Synchroniser, récurrent |

### Temps et Planning
| Nom | Icône Font Awesome | Usage |
|-----|-------------------|-------|
| `clock` | `fa-clock` | Horaires |
| `calendar` | `fa-calendar-alt` | Calendrier |
| `calendar-day` | `fa-calendar-day` | Jour spécifique |
| `schedule` | `fa-calendar-check` | Planning |

### Activités Sportives
| Nom | Icône Font Awesome | Usage |
|-----|-------------------|-------|
| `horse` | `fa-horse` | Équitation |
| `swimmer` | `fa-swimmer` | Natation |
| `dumbbell` | `fa-dumbbell` | Fitness, musculation |
| `football` | `fa-futbol` | Sports collectifs |
| `martial-arts` | `fa-fist-raised` | Arts martiaux |
| `dance` | `fa-music` | Danse |
| `tennis` | `fa-table-tennis` | Tennis |
| `gymnastics` | `fa-child` | Gymnastique |
| `running` | `fa-running` | Course, activités |

### Communication
| Nom | Icône Font Awesome | Usage |
|-----|-------------------|-------|
| `phone` | `fa-phone` | Téléphone |
| `email` | `fa-envelope` | Email |
| `globe` | `fa-globe` | Site web |
| `bell` | `fa-bell` | Notifications |

### Statut et Feedback
| Nom | Icône Font Awesome | Usage |
|-----|-------------------|-------|
| `success` | `fa-check-circle` | Succès |
| `error` | `fa-times-circle` | Erreur |
| `warning` | `fa-exclamation-triangle` | Attention |
| `info-circle` | `fa-info-circle` | Information |
| `spinner` | `fa-spinner` | Chargement |

### Financier
| Nom | Icône Font Awesome | Usage |
|-----|-------------------|-------|
| `euro` | `fa-euro-sign` | Prix en euros |
| `money` | `fa-money-bill-wave` | Argent |
| `credit-card` | `fa-credit-card` | Paiement |

## 🎨 Exemples d'Usage

### Titre de section
```vue
<h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
  <Icon name="settings" class="text-xl mr-2 text-gray-600" />
  Configuration des cours
</h2>
```

### Bouton avec icône
```vue
<button class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
  <Icon name="plus" class="mr-1" />
  Ajouter
</button>
```

### Affichage d'activité
```vue
<div class="flex items-center">
  <Icon :name="activity.icon" class="text-lg mr-2 text-blue-600" />
  <span>{{ activity.name }}</span>
</div>
```

### Calcul automatique
```vue
<div class="font-medium text-gray-700 mb-1 flex items-center">
  <Icon name="lightbulb" class="mr-1 text-yellow-500" />
  Calcul automatique
</div>
```

## 🔧 Personnalisation

### Couleurs
Utilisez les classes Tailwind CSS pour personnaliser les couleurs :
```vue
<Icon name="building" class="text-blue-600" />
<Icon name="trash" class="text-red-500" />
<Icon name="success" class="text-green-600" />
```

### Tailles
```vue
<Icon name="settings" class="text-sm" />
<Icon name="settings" class="text-lg" />
<Icon name="settings" class="text-xl" />
<Icon name="settings" class="text-4xl" />
```

## 🆕 Ajouter de Nouvelles Icônes

1. **Ajouter l'import** dans `plugins/fontawesome.client.ts` :
```typescript
import { faNewIcon } from '@fortawesome/free-solid-svg-icons'
```

2. **Ajouter à la bibliothèque** :
```typescript
library.add(faNewIcon)
```

3. **Mapper dans Icon.vue** :
```typescript
const iconMapping = {
  'new-icon': 'new-icon',
  // ...
}
```

4. **Utiliser** :
```vue
<Icon name="new-icon" />
```

## 📝 Migration des Emoji

Les emoji suivants ont été remplacés :
- 🏢 → `building`
- 📋 → `info`
- 📍 → `location`
- ⚙️ → `settings`
- 💡 → `lightbulb`
- 👥 → `users`
- 📝 → `note`
- ➕ → `plus`
- 🔄 → `sync`
- 🗑️ → `trash`
- 📅 → `calendar-day`
- 🏃‍♀️ → `running`

## 🧪 Tests

Pour tester les icônes :
1. Accédez à `/club/profile`
2. Vérifiez que toutes les icônes s'affichent correctement
3. Sélectionnez différentes activités pour voir les icônes sportives
4. Utilisez les fonctionnalités (ajouter, supprimer) pour tester les icônes d'action
