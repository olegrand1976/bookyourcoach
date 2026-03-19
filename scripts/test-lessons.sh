#!/usr/bin/env bash
# Lance les tests liés aux leçons (annulation, certificats médicaux).
# Usage: ./scripts/test-lessons.sh
#
# Prérequis: PHP avec driver SQLite (php-sqlite3) ou lancer via Docker :
#   ./scripts/run-phpunit.sh tests/Unit/Models/LessonTest.php tests/Feature/Api/StudentLessonCancellationTest.php tests/Feature/Api/ClubCancellationCertificateTest.php

set -e
cd "$(dirname "$0")/.."

TESTS=(
  tests/Unit/Models/LessonTest.php
  tests/Feature/Api/StudentLessonCancellationTest.php
  tests/Feature/Api/ClubCancellationCertificateTest.php
)

if ! php -m 2>/dev/null | grep -q pdo_sqlite; then
  echo "Driver SQLite absent. Lancement via Docker..."
  exec docker compose run --rm -v "$(pwd):/var/www/html" backend sh -c \
    "composer install -q --no-interaction && php artisan test ${TESTS[*]}"
fi

php artisan test "${TESTS[@]}"
