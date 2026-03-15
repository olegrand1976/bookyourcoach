# Documentation technique – BookYourCoach

**Dernière mise à jour :** Mars 2025

---

## 1. Vue d’ensemble

BookYourCoach est une plateforme de gestion de cours et de clubs sportifs : backend API Laravel, frontend Nuxt, application mobile Flutter, bases MySQL et Neo4j, conteneurisation Docker.

---

## 2. Stack technique

| Couche | Technologie | Version |
|--------|-------------|---------|
| **Backend** | PHP | 8.2+ |
| | Laravel | 12.x |
| | MySQL | 8.0 |
| | Redis | 7-alpine |
| | Neo4j | Latest (analytics) |
| **Tests backend** | PHPUnit | 11.x |
| **Frontend** | Nuxt.js | 3.x |
| | Vue.js | 3.x |
| | Tailwind CSS | 6.x |
| **Tests frontend** | Vitest, Playwright | - |
| **Mobile** | Flutter / Dart | Latest |
| **Infra** | Docker / Docker Compose | - |
| **CI/CD** | GitHub Actions | - |

**Bibliothèques principales :** Laravel Sanctum, Stripe PHP SDK, Google API Client, laudis/neo4j-php-client, DomPDF, SimpleSoftwareIO/QrCode, rlanvin/php-rrule.

---

## 3. Architecture

- **Multi-tenant :** isolation des données par club.
- **API-first :** fonctionnalités exposées via API REST.
- **Séparation :** Controllers → Services → Models.

```
Frontend (Nuxt 3) / Mobile (Flutter)
         │
         ▼
API REST (Laravel 12) — Middleware (auth, rôles) — Controllers — Services
         │
    ┌────┴────┐
    ▼         ▼
 MySQL      Neo4j
 (principal) (analytics)
    │
    ▼
  Redis (cache, sessions, queues)
```

---

## 4. Structure du projet

```
bookyourcoach/
├── app/
│   ├── Console/Commands/
│   ├── Http/Controllers/Api/     # Contrôleurs API
│   ├── Http/Middleware/
│   ├── Models/                   # Modèles Eloquent
│   ├── Services/                # Logique métier (dont AI/)
│   ├── Notifications/
│   └── Jobs/
├── config/
├── database/migrations, factories, seeders/
├── docker/                      # Nginx, PHP
├── docs/
├── frontend/                    # Nuxt 3
│   ├── components/, composables/, stores/
│   └── pages/                   # Routes (admin, club, teacher, student)
├── mobile/                      # Flutter
├── routes/api.php
├── scripts/                     # test-all.sh, docker-maintenance.sh, deploy.sh
└── tests/Unit, tests/Feature/
```

---

## 5. Modèles de données (principaux)

| Modèle | Table | Rôle |
|--------|--------|------|
| User | users | Comptes (admin, club, teacher, student) |
| Club | clubs | Clubs sportifs |
| Teacher | teachers | Enseignants (user_id, spécialités, taux horaire) |
| Student | students | Étudiants (user_id, infos médicales) |
| Lesson | lessons | Cours (club, teacher, student(s), créneau, statut, paiement) |
| SubscriptionTemplate | subscription_templates | Modèles d’abonnements (club) |
| Subscription | subscriptions | Abonnements (lien template ↔ étudiants) |
| SubscriptionInstance | subscription_instances | Instances (cours utilisés/restants, dates) |
| ClubOpenSlot | club_open_slots | Créneaux ouverts récurrents |
| RecurringSlot | recurring_slots | Créneaux récurrents réservés |
| CourseType | course_types | Types de cours (par club/discipline) |
| Location | locations | Lieux |
| Payment | payments | Paiements |
| Notification | notifications | Notifications in-app |
| LessonReplacement | lesson_replacements | Demandes de remplacement |
| AuditLog | audit_logs | Logs d’audit |
| GoogleCalendarToken | google_calendar_tokens | Tokens OAuth Google |
| VolunteerLetterSend | volunteer_letter_sends | Historique lettres bénévolat |
| + pivot : club_user, club_teachers, club_students, lesson_student, etc. |

---

## 6. API REST

Base : `/api`. Authentification : `Authorization: Bearer {token}` (Sanctum). Réponses JSON : `{ "success": true|false, "data": ..., "message": "..." }`.

### Santé et public

- `GET /api/health`
- `GET /api/activity-types`
- `GET /api/disciplines`, `GET /api/disciplines/{id}`, `GET /api/disciplines/by-activity/{activityTypeId}`
- `GET /api/clubs/public`

### Auth

- `POST /api/auth/register`, `POST /api/auth/login`, `POST /api/auth/forgot-password`, `POST /api/auth/reset-password`
- Avec `auth:sanctum` : `POST /api/auth/logout`, `GET /api/auth/user`, `PUT /api/auth/profile`

### Admin (`auth:sanctum` + `admin`)

- Dashboard : `GET /api/admin/dashboard`, `GET /api/admin/users`, `PUT /api/admin/users/{id}/status`
- Stats / config : `GET /api/admin/stats`, `GET /api/admin/activities`, `GET|PUT /api/admin/settings`, `GET /api/admin/system-status`, `POST /api/admin/clear-cache`
- Users : `GET|POST /api/admin/users`, `GET|PUT /api/admin/users/{id}`, `PATCH /api/admin/users/{id}/role`, `POST /api/admin/users/{id}/toggle-status`, `POST /api/admin/users/{id}/create-club`
- Clubs : `GET|POST /api/admin/clubs`, `GET|PUT|DELETE /api/admin/clubs/{id}`, `POST /api/admin/clubs/{id}/toggle-status`, `POST /api/admin/clubs/upload-logo`
- Étudiants liés : `GET /api/admin/students/{studentId}/linked`, `POST /api/admin/students/{studentId}/link`, `DELETE /api/admin/students/{studentId}/unlink/{linkedStudentId}`, `GET /api/admin/students/available-for-linking`
- Paie globale : `GET /api/admin/payroll/reports`, `POST /api/admin/payroll/generate`, `GET /api/admin/payroll/reports/{year}/{month}`, `GET /api/admin/payroll/export/{year}/{month}/csv`
- `GET /api/admin/audit-logs`

### Club (`auth:sanctum` + `club`)

- Dashboard / profil : `GET /api/club/dashboard`, `GET|PUT /api/club/profile`, `GET /api/club/qr-code`, `GET /api/club/diagnose-columns`, `GET /api/club/custom-specialties`
- Enseignants : `GET|POST /api/club/teachers`, `PUT|DELETE /api/club/teachers/{teacherId}`, `POST /api/club/teachers/{teacherId}/resend-invitation`
- Étudiants : `GET /api/club/students`, `POST /api/club/students`, `GET /api/club/students/{studentId}/history`, `PATCH /api/club/students/{studentId}/toggle-status`, `POST /api/club/students/{studentId}/resend-invitation`, `PUT /api/club/students/{studentId}`, `DELETE` (remove) via ClubController
- Abonnements : `GET|POST /api/club/subscriptions`, `GET|PUT|DELETE /api/club/subscriptions/{id}`, `POST /api/club/subscriptions/assign`, `POST /api/club/subscriptions/recalculate`, `POST /api/club/subscriptions/{instanceId}/close`, `PUT /api/club/subscriptions/{instanceId}/est-legacy`, `PUT /api/club/subscriptions/instances/{instanceId}`, `GET /api/club/subscriptions/instances/{instanceId}/history`, `GET /api/club/subscription-instances/{instanceId}/future-lessons`, `GET /api/club/students/{studentId}/subscriptions`, `POST /api/club/subscriptions/{instanceId}/renew`
- Modèles d’abonnements : `GET|POST /api/club/subscription-templates`, `PUT|DELETE /api/club/subscription-templates/{id}`
- Créneaux ouverts : `GET|POST /api/club/open-slots`, `GET|PUT|DELETE /api/club/open-slots/{id}`, `PUT /api/club/open-slots/{id}/course-types`
- Créneaux récurrents : `GET /api/club/recurring-slots`, `GET /api/club/recurring-slots/{id}`, `POST /api/club/recurring-slots/{id}/release`, `POST /api/club/recurring-slots/{id}/reactivate`
- Planning : `POST /api/club/planning/suggest-optimal-slot`, `POST /api/club/planning/check-availability`, `GET /api/club/planning/statistics`
- Bénévolat : `POST /api/club/volunteer-letters/send/{teacherId}`, `POST /api/club/volunteer-letters/send-all`, `GET /api/club/volunteer-letters/history`
- IA : `GET /api/club/predictive-analysis`, `GET /api/club/predictive-analysis/alerts`
- Notifications : `GET /api/club/notifications`, `GET /api/club/notifications/unread-count`, `POST /api/club/notifications/{id}/read`, `POST /api/club/notifications/read-all`
- Paie club : `GET /api/club/payroll/reports`, `POST /api/club/payroll/generate`, `GET /api/club/payroll/reports/{year}/{month}`, `POST /api/club/payroll/reports/{year}/{month}/reload`, `GET|PUT /api/club/payroll/reports/{year}/{month}/teachers/{teacherId}/payments`, `GET /api/club/payroll/export/{year}/{month}/csv`

### Teacher (`auth:sanctum` + `teacher`)

- `GET /api/teacher/dashboard`, `GET /api/teacher/dashboard-simple`, `GET|PUT /api/teacher/profile`
- Cours : `GET|POST /api/teacher/lessons`, `PUT|DELETE /api/teacher/lessons/{id}`
- Remplacements : `GET /api/teacher/lesson-replacements`, `POST /api/teacher/lesson-replacements`, `POST /api/teacher/lesson-replacements/{id}/respond`, `DELETE /api/teacher/lesson-replacements/{id}`
- `GET /api/teacher/teachers`, `GET /api/teacher/students`, `GET /api/teacher/students/{id}`, `GET /api/teacher/clubs`, `GET /api/teacher/earnings`
- Notifications : index, unread-count, markAsRead, markAllAsRead

### Student (`auth:sanctum` + `student` + `active.student`)

- `GET /api/student/dashboard`, `GET /api/student/dashboard/stats`, `GET|PUT /api/student/profile`
- Clubs : `GET /api/student/clubs`
- Cours : `GET /api/student/available-lessons`, `GET /api/student/lesson-history`, `GET /api/student/bookings`, `POST /api/student/bookings`, `PUT /api/student/bookings/{id}/cancel`
- Préférences : `GET /api/student/disciplines`, `GET|POST|PUT|DELETE /api/student/preferences/advanced`
- Abonnements : `GET /api/student/subscriptions/available`, `GET /api/student/subscriptions`, `POST /api/student/subscriptions/create-checkout-session`, `POST /api/student/subscriptions`, `POST /api/student/subscriptions/{instanceId}/renew`
- Comptes liés : `GET /api/student/linked-accounts`, `POST /api/student/switch-account/{studentId}`, `GET /api/student/active-account`

### Cours (auth:sanctum, tous rôles concernés)

- `GET /api/lessons/slot-occupants`, `GET|POST /api/lessons`, `GET|PUT|DELETE /api/lessons/{id}`, `PUT /api/lessons/{id}/subscription`, `POST /api/lessons/{id}/cancel-with-future`

### Commun

- `GET /api/course-types` (auth)
- QR Code : `GET /api/qr-code/user/{userId}`, `GET /api/qr-code/club/{clubId}`, `POST /api/qr-code/club/{clubId}/regenerate`, `POST /api/qr-code/scan`
- Stripe : `POST /api/stripe/webhook`
- Debug : `GET /api/debug/course-types-filtering`, `GET /api/debug/slot/{id}`

---

## 7. Authentification et sécurité

- **Sanctum :** tokens pour l’API, domaine stateful pour le front (SANCTUM_STATEFUL_DOMAINS, SESSION_DOMAIN).
- **Middleware :** `admin`, `club`, `teacher`, `student`, `active.student`.
- **Sécurité :** validation (Form Requests), CSRF pour SPA, mots de passe hashés, audit logs, isolation par `club_id` où applicable.

---

## 8. Services principaux

| Service | Rôle |
|---------|------|
| Neo4jService / Neo4jSyncService / Neo4jAnalysisService | Sync MySQL → Neo4j, analyses graphe, métriques |
| GoogleCalendarService | OAuth2, export/import calendrier |
| StripeService | Paiements, webhooks, abonnements, remboursements |
| QrCodeService | Génération / scan QR (utilisateurs, clubs) |
| RecurringSlotValidator | Vérification disponibilité sur 26 semaines, conflits |
| RecurringSlotService / LegacyRecurringSlotService | Gestion créneaux récurrents |
| AI/GeminiService | Appels Gemini |
| AI/PredictiveAnalysisService | Analyse prédictive, alertes |
| TeacherAssignmentService | Attribution enseignants |
| CommissionCalculationService | Commissions enseignants |
| NotificationService | Notifications in-app / email |

---

## 9. Base de données

- **MySQL :** base principale (tables listées en §5). Config : `DB_*` dans `.env`.
- **Redis :** cache, sessions, files de queues. Config : `REDIS_*`, `CACHE_DRIVER`, `SESSION_DRIVER`, `QUEUE_CONNECTION`.
- **Neo4j :** analytics. Config : `NEO4J_*`.

---

## 10. Tests

- **Backend :** PHPUnit 11, `phpunit.xml`, suites Unit et Feature.
- **Frontend :** Vitest (unit), Playwright (e2e).
- **Script projet :** `./scripts/test-all.sh` (login, api, docker, etc.).

Commandes utiles :

```bash
php artisan test
php artisan test --testsuite=Unit
php artisan test --coverage
./scripts/test-all.sh
```

---

## 11. Déploiement et scripts

- **Docker :** `docker-compose.yml` (app, mysql, redis, neo4j, nginx, etc.). Développement : `./scripts/docker-maintenance.sh start|stop|rebuild|logs`.
- **Déploiement :** `./scripts/deploy.sh local|dev|prod`. Détails production : voir [PRODUCTION_DEPLOYMENT.md](PRODUCTION_DEPLOYMENT.md).
- **CI/CD :** GitHub Actions (tests, sécurité, build, déploiement). Voir [GITHUB_ACTIONS_CONFIG.md](GITHUB_ACTIONS_CONFIG.md) si besoin.

---

## 12. Frontend (Nuxt 3)

- **Pages principales :** `index`, `login`, `register`, `reset-password`, `dashboard`, `profile`.
- **Admin :** `admin/index`, `admin/users`, `admin/settings`, `admin/payroll`, `admin/graph-analysis`, `admin/contracts`.
- **Club :** `club/dashboard`, `club/profile`, `club/teachers`, `club/students`, `club/planning`, `club/subscriptions`, `club/subscription-templates`, `club/open-slots`, `club/recurring-slots`, `club/payroll`, `club/volunteer-letter`, `club/qr-code`, `club/space`, etc.
- **Teacher :** `teacher/dashboard`, `teacher/profile`, `teacher/schedule`, `teacher/earnings`, `teacher/settings`, `teacher/qr-code`.
- **Student :** `student/dashboard`, `student/bookings`, `student/schedule`, `student/subscriptions`, `student/lessons`, `student/preferences`, `student/profile`.

---

## 13. Références

- [Documentation fonctionnelle](DOCUMENTATION_FONCTIONNELLE.md)
- [Index de la documentation](INDEX.md)
- [Déploiement production](PRODUCTION_DEPLOYMENT.md)
- [Configuration GitHub Actions](GITHUB_ACTIONS_CONFIG.md)
