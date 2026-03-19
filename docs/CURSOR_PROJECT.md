# Configuration Cursor / IA — BookYourCoach

Ce document sert de **point d’entrée** pour les règles d’assistance au code (Cursor, équivalents).

## Emplacement des règles

| Emplacement | Rôle |
|-------------|------|
| `.cursor/rules/*.mdc` | Règles du projet (contexte stack, auth local/prod, multi-tenant, API, planning 26 sem.) |
| `.cursor/skills/*/SKILL.md` | Compétences réutilisables (auth adaptative, standards, Neo4j) |
| `.cursor/agents/*.md` | Profils « experts » pour délégation / sous-agents |
| `.cursor/commands/*.md` | Commandes slash (ex. lancer les tests) |

## Références produit / technique obligatoires

- **Authentification** : `docs/AUTH_SOLUTION.md` — stratégie **différenciée** local (token, pas de cookies) vs production (Sanctum SPA, cookies). Ne pas « uniformiser » sans lire ce document.
- **Sanctum production** : `docs/PRODUCTION_SANCTUM_CONFIG.md`
- **Tests** : commande Cursor `test` → `./scripts/test-all.sh` ; `phpunit` → `./scripts/run-phpunit.sh` (voir `.cursor/commands/`)

## Index des règles Cursor (résumé)

- `BookYourCoach-Fullstack-Context.mdc` — stack Laravel / Nuxt / Neo4j / Flutter, rôles, `club_id`, abonnements.
- `Security-Environment-Strategy.mdc` — **ne pas** activer `withCredentials` en local ni imposer `X-Requested-With` en local.
- `Multi-Tenant-Data-Isolation.mdc` — isolation par `club_id` sur le code PHP API.
- `API-Design-System-Contracts.mdc` — format `{ success, data, message }`, FormRequests, Resources.
- `Planning-Recurrence-Logic.mdc` — récurrences validées sur 26 semaines.

## Plans et docs produit

Les brouillons de plans peuvent vivre sous `.cursor/plans/`. Les **décisions** pérennes doivent être reflétées dans `docs/` ou dans les issues / PR du dépôt.

## Fichiers exclus de l’indexation Cursor

Voir `.cursorignore` à la racine du dépôt (vendor, node_modules, artefacts lourds).
