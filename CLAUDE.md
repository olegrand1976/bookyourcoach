# CLAUDE.md

Guide pour Claude Code (claude.ai/code) sur ce dépôt.

## Projet

BookYourCoach — plateforme multi-tenant de réservation de cours de sport (clubs, enseignants, élèves). Monorepo : API **Laravel 12** (`app/`), front **Nuxt 3** (`frontend/`), mobile **Flutter** (`mobile/`). Stockage : **MySQL**, **Redis** (cache/queues), **Neo4j** (analytics).

Communication et commits en **français**.

## Commandes

### Tests backend (PHPUnit / SQLite)
Passer par le runner Docker `php-test` (profil `test`) : le PHP de l'hôte n'a souvent pas `pdo_sqlite` (`could not find driver`).

```bash
composer test:docker           # suite complète (docker-compose.yml)
composer test:docker:local     # suite complète (docker-compose.local.yml)
composer test:recurring:docker # sous-ensemble récurrence

# Un seul test / filtre :
docker compose --profile test run --rm php-test sh -c \
  'mkdir -p database && touch database/testing.sqlite && php vendor/bin/phpunit --filter=NomDuTest'
```
Build initial du runner : `docker compose --profile test build php-test`.

### Lint backend
`vendor/bin/pint` (Laravel Pint, PSR-12).

### Tests frontend (`frontend/`)
```bash
npm run test               # vitest --run
npm run test:e2e           # Playwright (aussi :auth, :club)
```

### Dev (Docker)
```bash
./start_local.sh                         # rebuild + up (docker-compose.local.yml)
./scripts/docker-maintenance.sh start    # démarrer les services
make queue / make queue-d / make queue-stop   # worker de queue Laravel
```
Front `:3000`, API `:8080`, phpMyAdmin `:8082`, Neo4j `:7474`.

## Architecture

### Rôles & multi-tenant
- Rôles `User::ROLE_{ADMIN,CLUB,TEACHER,STUDENT}`, helpers `isAdmin()/isClub()/isTeacher()/isStudent()` (passent par `hasRole()`).
- **Compte double club + enseignant** : `User::hasDualProfile()`, `DualProfileService`. Le front envoie l'en-tête `X-Active-Role` (`frontend/plugins/api.client.ts`) ; le middleware `ApplyActiveRole` (groupe `api`, juste après l'auth, avant rôle et 2FA) appelle `$user->actAs()`. L'en-tête ne donne aucun droit : un rôle non détenu est ignoré.
- **Foyer élève** : un même email sur plusieurs fiches = un seul compte (`students.user_id` partagé). Un élève qui a déjà son compte le garde : les deux comptes sont **fusionnés** (`users.household_root_id`) et chaque connexion voit tout le foyer via `User::getHouseholdStudentIds()`. Logique dans `FamilyLinkService` (`attachToAccount`, `mergeAccounts`, `separateAccount` ← `POST /club/students/{id}/separate-account`) et `StudentController::update` (email modifié sur un compte partagé → 409 puis `email_scope: household|student`). Cloisonnement : rattacher/fusionner exige que **tous** les élèves des foyers concernés soient inscrits dans le club, sinon code d'invitation famille.
- Alias de middlewares ([bootstrap/app.php](bootstrap/app.php)) : `admin`, `teacher`, `student`, `club`, `active.student`, `2fa`, `force.json`.
- **Cloisonnement par `club_id`** : tout modèle portant `club_id` doit être filtré sur **chaque** lecture/écriture. À vérifier sur tout nouveau contrôleur/service. Voir [.cursor/rules/Multi-Tenant-Data-Isolation.mdc](.cursor/rules/Multi-Tenant-Data-Isolation.mdc).

### Contrat API
- Réponses `{ "success": bool, "data": ?, "message": "..." }` ; erreurs : `success: false` + statut 4xx/5xx.
- Create/update via **FormRequest** ; exposition via **JsonResource** (jamais le modèle brut).
- Toutes les routes actives sont dans `routes/api.php` (groupes `admin`, `teacher`, `club` sous `auth:sanctum` + `2fa`). `routes/admin.php` et `routes/api_simple.php` ne sont **pas chargés**.
- Groupe `api` : CORS + `ForceJsonResponse` + `ApplyActiveRole`. Voir [.cursor/rules/API-Design-System-Contracts.mdc](.cursor/rules/API-Design-System-Contracts.mdc).

### Authentification — local ≠ production (NE PAS uniformiser)
Divergence volontaire ; la « corriger » casse l'auth.
- **Local** : token Bearer (`createToken`), front `withCredentials: false`, sans `X-Requested-With`.
- **Prod** : session Sanctum SPA, `withCredentials: true`, `X-Requested-With: XMLHttpRequest`.

Interdit en local : `withCredentials: true`, `X-Requested-With`, ou supprimer la branche `isLocal`. Fichiers : `AuthControllerSimple.php`, `frontend/stores/auth.ts`, `frontend/plugins/api.client.ts`. Détail : [docs/AUTH_SOLUTION.md](docs/AUTH_SOLUTION.md).

**2FA** obligatoire pour club/admin (middleware `2fa`, `TwoFactorService`, appareils de confiance `TrustedDevice`) ; confirmation des congés club par mot de passe ou 2FA. Voir [docs/TWO_FACTOR_AUTH.md](docs/TWO_FACTOR_AUTH.md). Traçage des connexions (IP réelle, GeoLite2) : [docs/TRACABILITE_CONNEXIONS.md](docs/TRACABILITE_CONNEXIONS.md).

### Abonnements (cœur métier)
- `SubscriptionTemplate` (offre) → `SubscriptionInstance` (souscription, éventuellement familiale : plusieurs élèves, pool partagé) → cours via le pivot `subscription_lessons` (`UNIQUE(lesson_id)` : un cours ne débite qu'**un** abonnement).
- Deux compteurs distincts : `lessons_used` / `remaining_consumed` (cours passés débités + `manual_lessons_used` + annulations tardives) vs `remaining_bookable` (inclut les cours futurs attachés). Recalcul central idempotent : `SubscriptionInstance::recalculateLessonsUsed()`. Détail : [docs/SUBSCRIPTION_REMAINING_LESSONS.md](docs/SUBSCRIPTION_REMAINING_LESSONS.md).
- Les cours tombant un **jour de congé club** (`ClubClosureDay`, `ClubClosureDayService`) ne sont pas décomptés.
- Abonnement plein/épuisé : bascule sur l'abonnement suivant (rollover) ; une déduction peut être forcée depuis la modification du cours.
- Services : `SubscriptionRebalanceService` (cohérence cours ↔ abonnements), `SubscriptionProjectionService` (planification de l'avenir), `SubscriptionCounterRepairService`, `SubscriptionExpiryWatchService` (veille).
- Artisan utiles : `subscriptions:consume-past-lessons`, `subscriptions:link-existing-lessons`, `recurring-slots:generate-lessons`, `recurring-slots:prolong`, `recurring-slots:expire-subscriptions`.

### Récurrences
- Horizon **26 semaines** (`SubscriptionRecurringSlot::RECURRENCE_WEEKS`, repris par `RecurringSlotValidator::VALIDATION_WEEKS`), borné par `expires_at` de l'abonnement. Chaque occurrence est vérifiée (capacité `ClubOpenSlot`, dispo enseignant, conflits).
- Conflit : **ne pas bloquer sans alternative** — renvoyer `conflicts` (`type`, `date`, `message`), au besoin proposer via `GeminiService` / `ClubPlanningController::suggestOptimalSlot`.
- La génération ne doit jamais surbooker un abonnement ; une série épuisée passe à l'abonnement suivant.
- Services : `RecurringSlotValidator`, `RecurringSlotService`, `RecurrenceCreationService`, `LegacyRecurringSlotService` (anciennes séries), `ProlongRecurringSlotsService`. Voir [.cursor/rules/Planning-Recurrence-Logic.mdc](.cursor/rules/Planning-Recurrence-Logic.mdc).

### Frontend (Nuxt 3)
Pinia (`frontend/stores/auth.ts`, `studentScope.ts`), pages par rôle sous `frontend/pages/{admin,club,teacher,student}/`, composables dans `frontend/composables/`, client API `frontend/plugins/api.client.ts`.

## Conventions de travail

- **Périmètre strict** : ne lire/modifier que les fichiers liés à la tâche, pas de refactor périphérique. Si >2 fichiers impactés → plan textuel et attendre validation.
- Diff minimal, pas de répétition du contexte.
- Erreur terminal : 1 retry max, puis stopper.
- Docs : [docs/INDEX.md](docs/INDEX.md). Les règles `.cursor/rules/*.mdc` font foi sur leurs domaines.
