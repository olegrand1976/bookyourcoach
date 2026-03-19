---
name: auth-adaptive
description: Enforces the adaptive authentication strategy from AUTH_SOLUTION.md (local token vs production Sanctum SPA). Use when modifying auth flow, AuthControllerSimple.php, frontend/stores/auth.ts, or api.client.ts, or when the user mentions authentication, Sanctum, or environment-specific auth.
---

# Authentification adaptative

## Source de vérité (à lire avant toute modif)

1. **`docs/AUTH_SOLUTION.md`** (à la racine du dépôt) — stratégie complète.
2. **Règle Cursor** `.cursor/rules/Security-Environment-Strategy.mdc` — interdictions (withCredentials, headers) local vs prod.

## Règle impérative

Ne pas modifier `AuthControllerSimple.php`, `frontend/stores/auth.ts` ou `frontend/plugins/api.client.ts` sans **deux branches** explicites : comportement **local** vs **production**, alignés sur `AUTH_SOLUTION.md`.

## Rappel ultra-court

| Environnement | Frontend (résumé) |
|---------------|---------------------|
| Local | `withCredentials: false`, pas de `X-Requested-With: XMLHttpRequest`, Bearer token |
| Production | `withCredentials: true`, `X-Requested-With: XMLHttpRequest`, cookies session |

Détection : `app()->environment('local')` (backend) ; `apiBase` contient `localhost` / `127.0.0.1` (frontend).

## Fichiers typiques

| Fichier | Rôle |
|---------|------|
| `docs/AUTH_SOLUTION.md` | Référence officielle |
| `app/Http/Controllers/Api/AuthControllerSimple.php` | Login / logout selon env |
| `frontend/stores/auth.ts` | Credentials & headers selon env |
| `frontend/plugins/api.client.ts` | Client HTTP selon env |

## Checklist

- [ ] Relire la section pertinente de `AUTH_SOLUTION.md`.
- [ ] Local : pas de `withCredentials: true` pour le flux principal.
- [ ] Production : `withCredentials: true` + `X-Requested-With: XMLHttpRequest` pour les appels authentifiés.
