# Tests PHPUnit (local)

`phpunit.xml` utilise **SQLite** (`database/testing.sqlite`). Il faut l’extension PHP :

```bash
# Debian/Ubuntu
sudo apt install php8.3-sqlite3

mkdir -p database && touch database/testing.sqlite
composer test:recurring
```

## Suite ciblée « validation récurrence »

- `RecurringSlotValidatorLessonOverlapTest` — chevauchement `lessons` en UTC vs heures club
- `RecurringSlotValidatorRecurringIntervalTest` — phase `recurring_interval` / `start_date`
- `SubscriptionRecurringSlotTest` — scope `lessonLikeTimeWindow` (≤ 120 min)

Sur **GitHub Actions**, le workflow `.github/workflows/phpunit.yml` exécute `composer test:recurring` sur `ubuntu-latest` avec `pdo_sqlite`.
