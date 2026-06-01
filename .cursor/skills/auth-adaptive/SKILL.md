---
name: auth-adaptive
description: Auth adaptative AUTH_SOLUTION.md (local token vs prod Sanctum SPA). Pour AuthControllerSimple, auth.ts, api.client.ts.
---

# Auth adaptative

Lire **`docs/AUTH_SOLUTION.md`** et **`.cursor/rules/Security-Environment-Strategy.mdc`** avant toute modif.

| | Local | Prod |
|---|--------|------|
| FE | `withCredentials: false`, Bearer, pas `X-Requested-With` | `withCredentials: true`, `X-Requested-With: XMLHttpRequest` |
| BE | Token `createToken`, `environment('local')` | Sanctum SPA session |

Fichiers : `AuthControllerSimple.php`, `frontend/stores/auth.ts`, `frontend/plugins/api.client.ts`. Deux branches `isLocal` obligatoires.
