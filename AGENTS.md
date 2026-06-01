# Instructions agents — BookYourCoach

**Stack** : Laravel 12 API, Nuxt 3, MySQL, Redis, Neo4j, Flutter. Multi-tenant (`club_id`).

## Règles critiques

1. **Auth** — `docs/AUTH_SOLUTION.md` ; local ≠ prod (`.cursor/rules/Security-Environment-Strategy.mdc`).
2. **Multi-tenant** — `club_id` / scopes (`.cursor/rules/Multi-Tenant-Data-Isolation.mdc`).
3. **API** — `{ success, data, message }` (`.cursor/rules/API-Design-System-Contracts.mdc`).
4. **Planning** — 26 semaines (`.cursor/rules/Planning-Recurrence-Logic.mdc`).

## Communication

Réponses **concises** (français) : diff minimal, pas de répétition du contexte. Détail : `.cursor/rules/Concise-Communication.mdc`.

## Index

`docs/CURSOR_PROJECT.md` — règles `.cursor/`, commandes test.
