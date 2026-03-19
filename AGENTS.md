# Instructions pour agents (Cursor, etc.) — BookYourCoach

## Projet

Plateforme multi-tenant de clubs sportifs : **Laravel 12** (API), **Nuxt 3** (frontend), **MySQL**, **Redis**, **Neo4j** (analytique), **Flutter** (mobile).

## Règles critiques

1. **Auth local ≠ production** — Lire `docs/AUTH_SOLUTION.md` avant toute modification de `AuthControllerSimple.php`, `frontend/stores/auth.ts`, `frontend/plugins/api.client.ts`. En local : pas de `withCredentials: true` pour le flux principal ; en prod : Sanctum SPA avec cookies + `X-Requested-With: XMLHttpRequest`. Détail : `.cursor/rules/Security-Environment-Strategy.mdc`.

2. **Multi-tenant** — Données isolées par `club_id` sur les modèles concernés (voir `.cursor/rules/Multi-Tenant-Data-Isolation.mdc`).

3. **API** — Réponses JSON `{ success, data, message }` ; préférer FormRequests et Resources (voir `.cursor/rules/API-Design-System-Contracts.mdc`).

4. **Planning** — Récurrences validées sur **26 semaines** (voir `.cursor/rules/Planning-Recurrence-Logic.mdc`).

## Point d’entrée configuration IA

Voir **`docs/CURSOR_PROJECT.md`** pour l’index des règles `.cursor/` et des commandes.

## Langue

Réponses en **français** si l’équipe le demande ; code et commentaires techniques en anglais ou français selon conventions du fichier existant.
