---
name: bookyourcoach-standards
description: Enforces backend/frontend/API code standards for BookYourCoach. Use when adding or modifying Laravel controllers, FormRequests, API routes, Nuxt components, Pinia stores, or when the user mentions code standards, validation, or API responses.
---

# Standards de code BookYourCoach

Respecte ces conventions pour garder la cohérence backend / frontend.

## Backend (Laravel)

- **Validation** : Toujours utiliser des **FormRequests** pour valider les entrées (pas de `$request->validate()` direct dans le contrôleur).
- **Réponses JSON** : Utiliser des **Resources** (API Resources) pour formater les réponses (pas de tableaux associatifs bruts).
- **Logique métier** : Déléguer la logique complexe à des **Services** (ex. `RecurringSlotValidator`, services dans `app/Services/`). Les contrôleurs restent fins : validation → appel service → retour Resource.

## Frontend (Nuxt)

- **Composition** : Utiliser la **Composition API** avec **&lt;script setup&gt;** (pas d’Options API).
- **Composants** : Composants réutilisables dans `frontend/components/` ; les pages composent ces composants.
- **État** : **Pinia** pour la gestion du store (pas Vuex).

## API

- **Routes** : Préfixe par rôle : `/api/{role}/` (ex. `/api/club/teachers`, `/api/student/bookings`).
- **Format de réponse** : Toutes les réponses JSON doivent suivre :
  ```json
  {
    "success": true,
    "data": { ... },
    "message": "Message optionnel"
  }
  ```
  En cas d’erreur : `success: false`, `data` null ou détail d’erreur, `message` explicite.

## Checklist rapide

- [ ] Contrôleur : FormRequest + Service + Resource
- [ ] Route : préfixe `/api/{role}/`
- [ ] Réponse : `{ success, data, message }`
- [ ] Composant Nuxt : `<script setup>` + Pinia si état partagé
- [ ] Nouveau composant réutilisable → `frontend/components/`
