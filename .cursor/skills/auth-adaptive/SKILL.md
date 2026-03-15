---
name: auth-adaptive
description: Enforces the adaptive authentication strategy from AUTH_SOLUTION.md (local token vs production Sanctum SPA). Use when modifying auth flow, AuthControllerSimple.php, frontend/stores/auth.ts, or api.client.ts, or when the user mentions authentication, Sanctum, or environment-specific auth.
---

# Authentification adaptative (AUTH_SOLUTION.md)

Applique strictement la stratégie définie dans [docs/AUTH_SOLUTION.md](docs/AUTH_SOLUTION.md).

## Règle impérative

**Ne jamais modifier** `app/Http/Controllers/Api/AuthControllerSimple.php` ni `frontend/stores/auth.ts` sans vérifier et respecter la distinction d'environnement ci-dessous.

## Local

- **Backend** : Auth simple par token Sanctum (`createToken('...')->plainTextToken`), pas de session/cookies côté API.
- **Frontend** :
  - `withCredentials: false` (pas de cookies de session).
  - Pas d'en-tête `X-Requested-With: XMLHttpRequest`.
  - Token renvoyé dans la réponse JSON et stocké (cookie ou mémoire) pour les requêtes suivantes via `Authorization: Bearer <token>`.

Détection : `app()->environment('local')` (backend) ou `apiBase` contient `localhost` / `127.0.0.1` (frontend).

## Production

- **Backend** : Authentification Sanctum SPA (session + cookies).
- **Frontend** :
  - `withCredentials: true` (envoi des cookies de session).
  - En-tête obligatoire : `X-Requested-With: XMLHttpRequest`.

Détection : environnement non-`local` (backend) ou `apiBase` ne contenant pas `localhost`/`127.0.0.1` (frontend).

## Fichiers concernés

| Fichier | Rôle |
|--------|------|
| `docs/AUTH_SOLUTION.md` | Référence officielle de la stratégie |
| `app/Http/Controllers/Api/AuthControllerSimple.php` | Branche local vs production côté login/logout |
| `frontend/stores/auth.ts` | Branche local vs production pour login (credentials, headers) |
| `frontend/plugins/api.client.ts` | `withCredentials` et `X-Requested-With` selon environnement |

## Checklist avant modification

Avant toute modification des fichiers listés ci-dessus :

- [ ] Relire la section concernée de `docs/AUTH_SOLUTION.md`.
- [ ] Vérifier que le code distingue bien local et production (pas de comportement unique qui casserait l’un des deux).
- [ ] En local : pas de `withCredentials: true` ni de dépendance aux cookies de session Sanctum pour le flux principal.
- [ ] En production : `withCredentials: true` et `X-Requested-With: XMLHttpRequest` présents pour les appels API authentifiés.
