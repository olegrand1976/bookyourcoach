# Tests PHPUnit (local)

`phpunit.xml` utilise **SQLite** (`database/testing.sqlite`). Il faut l’extension PHP **pdo_sqlite** sur l’interpréteur qui exécute les tests.

## Sans SQLite sur la machine hôte (Docker Compose)

Service **`php-test`** (profil `test`) : image `php:8.3-cli` + `pdo_sqlite` + Composer.

```bash
# Une fois (build)
docker compose --profile test build php-test

# Toute la suite (crée testing.sqlite si besoin, installe vendor dans le container si absent)
composer test:docker

# Suite récurrence uniquement (équivalent à composer test:recurring)
composer test:recurring:docker

# Commande brute — ne pas mettre « … » comme argument (PHPUnit chercherait un fichier nommé littéralement « … »)
docker compose --profile test run --rm php-test sh -c 'mkdir -p database && touch database/testing.sqlite && php artisan test --testsuite=Unit'

# Exemple avec chemins réels (copier-coller valide)
docker compose --profile test run --rm php-test sh -c 'mkdir -p database && touch database/testing.sqlite && php artisan test tests/Unit/Models/LessonTest.php'
```

## Avec PHP sur l’hôte

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
