# Cursor — BookYourCoach

## Structure

| Chemin | Rôle |
|--------|------|
| `.cursor/rules/00-Core.mdc` | Stack, workflow, concision (**always**) |
| `.cursor/rules/*.mdc` | Règles métier par glob (auth, API, planning…) |
| `.cursor/skills/*/SKILL.md` | Compétences on-demand (auth, standards, Neo4j) |
| `.cursor/agents/*.md` | Sous-agents experts |
| `.cursor/commands/*.md` | `/test`, `/phpunit` |

## Docs clés

- Auth : `docs/AUTH_SOLUTION.md` — local token vs prod Sanctum SPA
- Prod Sanctum : `docs/PRODUCTION_SANCTUM_CONFIG.md`
- Tests : `composer test:docker` ou `/test` → `./scripts/test-all.sh`

## Règles par glob

| Fichier | Glob |
|---------|------|
| `Security-Environment-Strategy.mdc` | auth FE/BE |
| `Multi-Tenant-Data-Isolation.mdc` | `app/**/*.php` |
| `API-Design-System-Contracts.mdc` | controllers/requests/resources API |
| `Planning-Recurrence-Logic.mdc` | services planning/récurrence |
| `Testing-Docker-SQLite.mdc` | `tests/**`, compose test |

Hors `alwaysApply` → injectées seulement sur fichiers matchés (économie tokens).

## Exclusions index

`.cursorignore` : vendor, node_modules, builds, `.env*`, logs, dumps, médias lourds.
