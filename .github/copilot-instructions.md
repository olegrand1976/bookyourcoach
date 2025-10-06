# BookYourCoach - AI Coding Agent Instructions

## Project Overview

BookYourCoach (activibe) is a full-stack sports coaching platform for managing equestrian/sports clubs, lessons, teachers, and students. Multi-tenant architecture supporting clubs, teachers (coaches), students, and admins.

**Stack:** Laravel 11 (PHP 8.3) + Nuxt.js 3 + Flutter + MySQL + Neo4j + Docker

## Architecture & Data Flow

### Multi-Role System (Critical)

-   **User roles:** `admin`, `teacher`, `student`, `club` (stored in `users.role` column)
-   **Role relationships:** Users can belong to multiple clubs via pivot tables
    -   `club_user` - Club managers/staff
    -   `club_teachers` - Teachers affiliated with clubs
    -   `club_students` - Students enrolled in clubs
-   **Auth pattern:** All role checks use string comparison: `$user->role === 'admin'`
-   **Middleware:** `AdminMiddleware`, `TeacherMiddleware`, etc. check `role` field directly

### Database Architecture

-   **Primary DB:** MySQL for relational data (users, lessons, clubs, transactions)
-   **Graph DB:** Neo4j for complex relationship analysis via `Neo4jService` and `Neo4jSyncService`
-   **Dual approach:** All critical data lives in MySQL; Neo4j is sync'd for analytics only
-   **Key models:** `User`, `Club`, `Teacher`, `Student`, `Lesson`, `CourseSlot`, `Discipline`
-   **Pivot pattern:** Heavy use of pivot tables for many-to-many (clubs-users, lessons-students)

### Service Layer Pattern

Services in `app/Services/` handle business logic:

-   `Neo4jService.php` - Graph DB operations (Cypher queries)
-   `GoogleCalendarService.php` - Calendar sync integration
-   `TeacherAssignmentService.php` - Automated teacher-student matching
-   `StripeService.php` - Payment processing
-   `AI/*` - Predictive analysis services

### Authentication (Environment-Specific)

**Critical:** Auth behavior differs by environment - check `app()->environment()`:

-   **Local:** Simple token auth without CSRF/XSRF cookies (`AuthControllerSimple.php`)
-   **Production:** Laravel Sanctum SPA with cookie-based sessions
-   **Frontend detection:** `frontend/stores/auth.ts` checks `apiBase` for localhost
-   **Never mix:** Don't use `withCredentials: true` or XSRF tokens in local environment

## Development Workflows

### Docker Commands (Primary Development)

**Always use scripts** in `scripts/` directory:

```bash
./scripts/docker-maintenance.sh start|stop|restart|rebuild|clean|logs|status
./scripts/deploy.sh local|dev|prod
./scripts/test-all.sh [login|api|docker|frontend]
```

**Service URLs:**

-   Frontend: http://localhost:3000
-   Backend API: http://localhost:8080
-   phpMyAdmin: http://localhost:8082
-   Neo4j Browser: http://localhost:7474
-   MySQL: localhost:3308

### Testing Strategy

-   **PHPUnit 12** with PHP 8+ attributes (use `#[Test]` not `@test`)
-   **303 unit tests** covering models, services, middleware
-   **Test pattern:** Factory-based with `actingAsAdmin()` helper for auth
-   **Auth in tests:** Create Sanctum token and set Bearer header manually
-   **Run tests:** `php artisan test` or `./scripts/test-all.sh`
-   **Critical config:** `phpunit.xml` uses `DB_DATABASE=activibe_test`

### Migration Management

67+ migrations in sequence - **never modify old migrations**. Key tables:

-   Base: `users`, `clubs`, `teachers`, `students`, `lessons`
-   Pivots: `club_user`, `club_teachers`, `club_students`, `lesson_student`
-   Features: `disciplines`, `club_open_slots`, `subscriptions`, `google_calendar_tokens`
-   Always run: `php artisan migrate` after pulling or in Docker with `docker-compose exec backend php artisan migrate`

## Code Conventions

### API Routes Pattern

-   **Prefix groups:** `/api/auth`, `/api/admin`, `/api/teacher`, `/api/student`, `/api/clubs`
-   **Middleware stacking:** `['auth:sanctum', 'admin']` for role-based routes
-   **Controller namespace:** `App\Http\Controllers\Api\*` for API endpoints
-   **Response format:** Always return JSON: `response()->json(['data' => $data], 200)`

### Frontend Architecture (Nuxt 3)

-   **Auto-imports:** Nuxt auto-imports composables, utils, components (ignore TypeScript errors)
-   **Stores:** Pinia stores in `frontend/stores/` (`auth.ts`, `club.ts`, etc.)
-   **API plugin:** `frontend/plugins/api.client.ts` configures Axios with environment-aware settings
-   **SSR-safe:** Use `process.client` checks before accessing browser APIs
-   **Icons:** FontAwesome via `@fortawesome/vue-fontawesome` + local Fontsource fonts

### Mobile App (Flutter)

-   **Structure:** `mobile/lib/{screens,services,models,providers,widgets}`
-   **Auth:** Laravel Sanctum tokens stored via secure storage
-   **API base:** Configured per environment in `mobile/lib/config/`
-   **Testing:** Use provided test accounts (see `mobile/README.md`)

## Common Pitfalls & Fixes

### CORS/500 Errors

If login returns 500 or CORS errors:

1. Check backend migrations: `docker-compose exec backend php artisan migrate:status`
2. Run missing migrations: `docker-compose exec backend php artisan migrate`
3. Verify CORS config in `config/cors.php` includes `localhost:3000`
4. Check backend logs: `./scripts/docker-maintenance.sh logs`

### Auth Loop Issues

If experiencing redirect loops:

-   Verify environment detection in `frontend/stores/auth.ts`
-   Local must NOT send `withCredentials: true` or XSRF headers
-   Check token storage: cookies for persistence, localStorage as fallback
-   Ensure middleware matches route definitions in `routes/api.php`

### Neo4j Integration

-   Neo4j is **optional** - app works without it
-   Sync operations are queued/async via `Neo4jSyncService`
-   Never block user operations waiting for Neo4j writes
-   Check connection in `.env`: `NEO4J_HOST`, `NEO4J_PORT`, `NEO4J_USERNAME`, `NEO4J_PASSWORD`

## Key Files Reference

-   **Backend entry:** `app/Http/Controllers/Api/*Controller.php`
-   **Models:** `app/Models/{User,Club,Teacher,Student,Lesson}.php`
-   **Routes:** `routes/{api,admin,web}.php`
-   **Frontend auth:** `frontend/stores/auth.ts`, `frontend/plugins/api.client.ts`
-   **Docker config:** `docker-compose.yml`, `docker-compose.local.yml`, `docker-compose.dev.yml`
-   **Docs hub:** `docs/INDEX.md` links to all documentation

## External Integrations

-   **Stripe:** Payment processing via `StripeService.php` (API keys in `.env`)
-   **Google Calendar:** OAuth2 sync for lessons (`GoogleCalendarService.php`)
-   **QR Codes:** Generated for users/clubs via `QrCodeService.php`
-   **L5-Swagger:** API documentation at `/api/documentation`

## When Stuck

1. Check relevant doc: `docs/{AUTH_SOLUTION,BACKEND_CONNECTION_GUIDE,TECHNICAL_DOCUMENTATION}.md`
2. Review script helpers: `scripts/README.md`
3. Test account credentials in `frontend/README.md` and `mobile/README.md`
4. Verify Docker health: `./scripts/docker-maintenance.sh status`
5. Check test suite for usage examples: `tests/Feature/Api/*Test.php`
