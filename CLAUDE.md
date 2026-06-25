# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Projet

BookYourCoach — plateforme multi-tenant de réservation de cours de sport (clubs, enseignants, élèves). Monorepo : API **Laravel 12** (`app/`), front **Nuxt 3** (`frontend/`), mobile **Flutter** (`mobile/`). Stockage : **MySQL** (relationnel), **Redis** (cache/queues), **Neo4j** (analytics/relations).

Communication et commits en **français**.

## Commandes

### Tests backend (PHPUnit / SQLite)
Préférer le runner Docker `php-test` (profil `test`) — le PHP de l'hôte n'a souvent pas `pdo_sqlite` (`could not find driver`).

```bash
composer test:docker           # suite complète, docker-compose.yml
composer test:docker:local     # suite complète, docker-compose.local.yml
composer test:recurring:docker # sous-ensemble récurrence
composer test                  # local, seulement si pdo_sqlite présent sur l'hôte

# Un seul test / filtre, via le service php-test :
docker compose --profile test run --rm php-test sh -c \
  'mkdir -p database && touch database/testing.sqlite && php vendor/bin/phpunit --filter=NomDuTest'
```
Build initial du runner : `docker compose --profile test build php-test`.

### Lint backend
```bash
vendor/bin/pint            # Laravel Pint (formatage PSR-12)
```

### Tests frontend (`frontend/`)
```bash
npm run test               # vitest --run
npm run test:e2e           # Playwright
npm run test:e2e:auth      # sous-ensemble auth
```

### Dev / environnement (Docker)
```bash
./start_local.sh                         # rebuild + up (docker-compose.local.yml)
./scripts/docker-maintenance.sh start    # démarrer les services
make queue                               # queue worker Laravel (foreground)
make queue-d                             # queue worker en arrière-plan
```
Services locaux : front `:3000`, API `:8080`, phpMyAdmin `:8082`, Neo4j `:7474`.

## Architecture

### Rôles & multi-tenant
- Rôles dans `User` : `admin`, `club`, `teacher`, `student` (constantes `User::ROLE_*`, helpers `isAdmin()/isClub()/isTeacher()/isStudent()`).
- Middlewares de route alias dans [bootstrap/app.php](bootstrap/app.php) : `admin`, `teacher`, `student`, `club`, `active.student`.
- **Cloisonnement par `club_id`** : tout modèle portant `club_id` doit être filtré (`->where('club_id', …)` ou global scope) sur **chaque** lecture/écriture. Un nouveau contrôleur/service sur une entité de club doit vérifier ce scope avant merge. Voir [.cursor/rules/Multi-Tenant-Data-Isolation.mdc](.cursor/rules/Multi-Tenant-Data-Isolation.mdc).

### Contrat API
Toutes les réponses suivent `{ "success": bool, "data": ?, "message": "..." }`. Erreurs : `success: false` + `message` + statut 4xx/5xx. Create/update passent par un **FormRequest** (`authorize` + `rules`) ; l'exposition de modèle passe par une **JsonResource** (jamais le modèle brut). Routes dans `routes/api.php` (+ `admin.php`, `api_simple.php`). Le middleware `ForceJsonResponse` + CORS est appliqué à tout le groupe `api`. Voir [.cursor/rules/API-Design-System-Contracts.mdc](.cursor/rules/API-Design-System-Contracts.mdc).

### Authentification — local ≠ production (NE PAS uniformiser)
Comportement volontairement divergent ; le « corriger » casse l'auth.
- **Local** : token JSON Bearer (`createToken`), front `withCredentials: false`, sans `X-Requested-With`.
- **Prod** : session Sanctum SPA, front `withCredentials: true`, `X-Requested-With: XMLHttpRequest`.

Interdit en local : `withCredentials: true`, `X-Requested-With`, ou supprimer la branche `isLocal`. Fichiers : `AuthControllerSimple.php`, `frontend/stores/auth.ts`, `frontend/plugins/api.client.ts`. Détail : [docs/AUTH_SOLUTION.md](docs/AUTH_SOLUTION.md).

### Abonnements & récurrences (cœur métier)
- `SubscriptionTemplate` (offre) → `SubscriptionInstance` (souscription d'un élève) → leçons.
- Récurrences validées sur **26 semaines** : `RecurringSlotValidator::VALIDATION_WEEKS = 26` vérifie chaque occurrence (capacité `ClubOpenSlot`, dispo enseignant, conflits). `expires_at` d'un slot récurrent = `min(fin abonnement, début + 6 mois)`.
- En cas de conflit : **ne pas bloquer sans alternative** — renvoyer `conflicts` (`type`, `date`, `message`), et au besoin proposer via `GeminiService` / `ClubPlanningController::suggestOptimalSlot`.
- Services clés : `RecurringSlotValidator`, `RecurringSlotService`, `RecurrenceCreationService` (`app/Services/`). Voir [.cursor/rules/Planning-Recurrence-Logic.mdc](.cursor/rules/Planning-Recurrence-Logic.mdc).

### Frontend (Nuxt 3)
State via **Pinia** (`frontend/stores/auth.ts`, `studentScope.ts`). Pages par rôle sous `frontend/pages/{admin,club,teacher,student}/`. Client API : `frontend/plugins/api.client.ts`.

## Conventions de travail

- **Périmètre strict** : ne lire/modifier que les fichiers liés à la tâche, pas de refactor périphérique. Si >2 fichiers impactés → proposer un plan textuel et attendre validation.
- Diff minimal, pas de répétition du contexte.
- Erreur terminal : 1 retry max, puis stopper.
- Index documentaire complet : [docs/INDEX.md](docs/INDEX.md), [docs/CURSOR_PROJECT.md](docs/CURSOR_PROJECT.md). Les règles `.cursor/rules/*.mdc` font foi sur leurs domaines.
