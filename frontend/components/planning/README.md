# Composants Planning

Organisation modulaire des composants liés au planning des cours.

## 📂 Structure

```
components/planning/
├── NewLessonModal.vue    # Modale de création de cours
└── README.md             # Ce fichier

composables/planning/
├── useCourseTypeFiltering.ts  # Logique de filtrage des types de cours
└── useLessonModal.ts          # Gestion de l'état de la modale
```

## 🎯 NewLessonModal.vue

### Description
Composant autonome pour créer un nouveau cours. Gère l'affichage du formulaire, la validation et la soumission.

### Props

| Prop | Type | Requis | Description |
|------|------|--------|-------------|
| `show` | Boolean | ✅ | Afficher/masquer la modale |
| `lessonData` | Object | ✅ | Données du cours (date, time, slot) |
| `clubDisciplines` | Array | ❌ | IDs des disciplines du club |
| `teachers` | Array | ❌ | Liste des enseignants disponibles |
| `students` | Array | ❌ | Liste des élèves du club |

### Events

| Event | Payload | Description |
|-------|---------|-------------|
| `close` | - | Fermeture de la modale |
| `submit` | lessonData (Object) | Soumission du formulaire |
| `edit-slot` | - | Demande d'édition du créneau |

### Utilisation

```vue
<script setup>
import NewLessonModal from '~/components/planning/NewLessonModal.vue'
import { useLessonModal } from '~/composables/planning/useLessonModal'

const { showModal, modalData, openModal, closeModal } = useLessonModal()

const handleSubmit = async (lessonData) => {
  // Appeler l'API pour créer le cours
  const { $api } = useNuxtApp()
  await $api.post('/lessons', lessonData)
  closeModal()
}
</script>

<template>
  <NewLessonModal
    :show="showModal"
    :lesson-data="modalData"
    :club-disciplines="clubProfile.disciplines"
    :teachers="clubTeachers"
    :students="clubStudents"
    @close="closeModal"
    @submit="handleSubmit"
    @edit-slot="editSlot"
  />
</template>
```

## 🔧 Composables

### useCourseTypeFiltering

**Fonctionnalité** : Filtre les types de cours selon les disciplines du club.

**Fonctions** :
- `filterCourseTypesByClubDisciplines(slotCourseTypes, clubDisciplineIds)` : Filtre les types d'un créneau
- `courseTypeMatchesClub(courseType, clubDisciplineIds)` : Vérifie si un type correspond au club
- `filterAllCourseTypesForClub(allCourseTypes, clubDisciplineIds)` : Filtre tous les types pour un club

**Utilisation** :
```ts
import { useCourseTypeFiltering } from '~/composables/planning/useCourseTypeFiltering'

const { filterCourseTypesByClubDisciplines } = useCourseTypeFiltering()

const filteredTypes = filterCourseTypesByClubDisciplines(
  slot.course_types,
  [1, 2, 5] // IDs des disciplines du club
)
```

### useLessonModal

**Fonctionnalité** : Gère l'état de la modale de création de cours.

**État** :
- `showModal` : Boolean - Afficher/masquer la modale
- `modalData` : Object - Données du cours en cours de création

**Fonctions** :
- `openModal(date, time, slot)` : Ouvre la modale
- `closeModal()` : Ferme la modale
- `resetModal()` : Réinitialise complètement la modale

**Utilisation** :
```ts
import { useLessonModal } from '~/composables/planning/useLessonModal'

const { showModal, modalData, openModal, closeModal } = useLessonModal()

// Ouvrir la modale
openModal('2025-10-21', '09:00', slotData)

// Fermer la modale
closeModal()
```

## 🎨 Principes de design

### Séparation des responsabilités
- **Composants** : Gèrent l'affichage et l'interaction utilisateur
- **Composables** : Contiennent la logique métier réutilisable
- **Page** : Orchestre les composants et gère le state global

### Filtrage des types de cours

Le filtrage se fait en **double sécurité** :

1. **Backend** (`ClubOpenSlotController`) : Filtre déjà les types par disciplines du club
2. **Frontend** (`NewLessonModal` + `useCourseTypeFiltering`) : Re-filtre pour garantir la cohérence

Cela assure qu'aucun type de cours non autorisé ne soit affiché, même en cas d'incohérence des données.

### Gestion des erreurs

Le composant affiche des alertes claires quand :
- ❌ Aucun type de cours n'est disponible
- ❌ Aucun enseignant n'est configuré

Il propose des actions pour résoudre le problème (ex: "Configurer le créneau").

## 🧪 Tests recommandés

1. **Test d'affichage** : La modale s'ouvre et affiche les bonnes données
2. **Test de filtrage** : Seuls les types du club sont affichés
3. **Test de validation** : Le formulaire ne peut pas être soumis sans les champs requis
4. **Test de soumission** : Les données sont correctement formatées et envoyées
5. **Test d'erreur** : Les messages d'alerte s'affichent correctement

## 📝 Améliorations futures

- [ ] Validation des horaires (empêcher les chevauchements)
- [ ] Auto-suggestion de créneaux disponibles
- [ ] Prévisualisation du cours dans le planning avant création
- [ ] Support du drag & drop pour créer des cours
- [ ] Création de cours récurrents (série)

## 🐛 Debug

Pour activer les logs de debug, ouvrez la console du navigateur (F12). Les logs sont préfixés par :
- `🎯 [NewLessonModal]` : Logs du composant
- `✅ [useCourseTypeFiltering]` : Logs du filtrage
- `📖 [useLessonModal]` : Logs de gestion de la modale

## 📚 Ressources

- [Vue 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)
- [Nuxt 3 Composables](https://nuxt.com/docs/guide/directory-structure/composables)
- [TypeScript](https://www.typescriptlang.org/docs/)

