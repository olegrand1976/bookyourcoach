---
name: bookyourcoach-standards
description: Enforces backend/frontend/API code standards for BookYourCoach. Use when adding or modifying Laravel controllers, FormRequests, API routes, Nuxt components, Pinia stores, or when the user mentions code standards, validation, or API responses.
---

# Standards de code BookYourCoach

## Détail API & contrats (référence longue)

Pour le format JSON, erreurs HTTP, FormRequests, Resources et conventions contrôleurs : **lire d’abord**  
**`.cursor/rules/API-Design-System-Contracts.mdc`** (globs : `app/Http/Controllers/Api/**`, Requests, Resources).

Ce skill résume la checklist ; la rule porte le détail contractuel.

## Backend (Laravel) — rappel

- **Validation** : **FormRequests** (éviter la validation inline lourde dans le contrôleur).
- **Réponses** : **API Resources** pour les payloads exposés au client.
- **Métier** : **Services** dans `app/Services/` ; contrôleurs fins.

## Frontend (Nuxt) — rappel

- **Composition API** + `<script setup>`.
- Composants réutilisables dans `frontend/components/`.
- **Pinia** pour l’état global (pas Vuex).

## API — rappel format

Réponses : `{ "success": true|false, "data": ..., "message": "..." }` (voir rule API ci-dessus pour les cas d’erreur et HTTP).

Routes : préfixes par rôle, ex. `/api/club/...`, `/api/student/...`.

## Checklist rapide

- [ ] Contrôleur : FormRequest + Service + Resource (ou alignement explicite avec le code existant du fichier)
- [ ] Contrats API : conforme à `API-Design-System-Contracts.mdc`
- [ ] Route : préfixe cohérent avec le rôle
- [ ] Nuxt : `<script setup>` + Pinia si état partagé
- [ ] Nouveau composable partagé → `frontend/composables/` ; composant UI → `frontend/components/`
