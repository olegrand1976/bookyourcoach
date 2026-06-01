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
- **Tests Docker SQLite** : service `php-test` (profil `test`) disponible dans `docker-compose.yml` **et** `docker-compose.local.yml`. Utiliser en priorité :
  - `composer test:docker`
  - `composer test:docker:local`
  - ou `docker compose --profile test run --rm php-test ...`

## Index des règles Cursor (résumé)

- `Concise-Communication.mdc` — **toujours actif** : réponses courtes, diffs minimaux, pas de duplication du contexte.
- `BookYourCoach-Fullstack-Context.mdc` — **toujours actif** : stack et concepts métiers (version condensée).
- `Security-Environment-Strategy.mdc` — auth local vs prod (globs auth ; **ne pas** `withCredentials` en local).
- `Multi-Tenant-Data-Isolation.mdc` — `club_id` / scopes (globs `app/**`).
- `API-Design-System-Contracts.mdc` — `{ success, data, message }`, FormRequests, Resources.
- `Planning-Recurrence-Logic.mdc` — 26 semaines (globs planning / récurrence).
- `Testing-Docker-SQLite.mdc` — tests via `php-test` Docker (globs tests / compose).

Les règles hors `alwaysApply` ne sont injectées que sur les fichiers correspondant aux **globs** — pour limiter les tokens en entrée.

## Plans et docs produit

Les brouillons de plans peuvent vivre sous `.cursor/plans/`. Les **décisions** pérennes doivent être reflétées dans `docs/` ou dans les issues / PR du dépôt.

## Fichiers exclus de l’indexation Cursor

Voir `.cursorignore` à la racine du dépôt (vendor, node_modules, artefacts lourds).
