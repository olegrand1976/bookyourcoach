---
name: bookyourcoach-standards
description: Standards Laravel/Nuxt/API BookYourCoach. Contrôleurs, FormRequests, stores, réponses JSON.
---

# Standards BookYourCoach

Contrats API détaillés : **`.cursor/rules/API-Design-System-Contracts.mdc`**.

- **Laravel** : FormRequest, JsonResource, logique dans `app/Services/`, contrôleurs fins.
- **Nuxt** : `<script setup>`, Pinia, composants dans `frontend/components/`.
- **Routes** : préfixes par rôle (`/api/club/…`, `/api/student/…`).

Checklist : FormRequest + Service + Resource ; format `{ success, data, message }` ; scope `club_id` si entité club.
