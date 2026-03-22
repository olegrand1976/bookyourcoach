# Revue — validation récurrence (POST `/api/lessons`)

## Objectif

Éviter les 422 « Conflits détectés sur N occurrence(s) » lorsque le planning club montre un créneau libre, tout en conservant les garde-fous métier (26 semaines, élève + enseignant).

## Correctifs livrés (résumé)

| Zone | Problème | Correction |
|------|----------|------------|
| `Lesson` | `whereDate` + `whereTime` vs datetimes UTC | Chevauchement d’intervalles via `occurrenceUtcBounds()` + requête `start_time` / `end_time` |
| `SubscriptionRecurringSlot` | Fenêtres club (ex. 14h–17h) ≤ 300 min | `MAX_LESSON_LIKE_WINDOW_MINUTES` = **120** |
| `SubscriptionRecurringSlot` | Conflit chaque mercredi sans tenir compte de `recurring_interval` | `subscriptionRecurringSlotFiresOnDate()` aligné sur `LegacyRecurringSlotService` |
| `checkSlotCapacity` | Comptage récurrences | Filtre par occurrence réelle le jour J |
| Observabilité | Diagnostic prod difficile | `RECURRING_VALIDATION_LOG_CONFLICTS` + logs `[RecurringAvailability]` |

## Points d’attention

- **Multi-tenant** : les contrôles enseignant/élève ne filtrent pas par `club_id` sur `Lesson` ; la cohérence repose sur les IDs issus du contrôleur / policies.
- **RGPD** : les logs détaillés contiennent des IDs et horaires — durée de conservation et accès aux logs à cadrer.
- **Charge logs** : activer `RECURRING_VALIDATION_LOG_CONFLICTS` uniquement en diagnostic (défaut `false`).

## Tests

- `composer test:recurring` (voir `docs/TESTING_PHPUNIT.md`).
- CI : `.github/workflows/phpunit.yml`.

## Fichiers clés

- `app/Services/RecurringSlotValidator.php`
- `app/Models/SubscriptionRecurringSlot.php`
- `config/bookyourcoach.php`
- `app/Http/Controllers/Api/LessonController.php` (log 422 récurrence)
