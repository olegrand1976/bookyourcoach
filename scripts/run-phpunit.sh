#!/usr/bin/env bash
# Lance PHPUnit dans le conteneur backend avec les dépendances de dev (phpunit).
# Usage: ./scripts/run-phpunit.sh [arguments phpunit...]
# Exemple: ./scripts/run-phpunit.sh tests/Feature/Api/StudentFamilyLinksTest.php

set -e
cd "$(dirname "$0")/.."

# Monter le projet et installer les deps dev puis lancer phpunit
docker compose run --rm -v "$(pwd):/var/www/html" backend sh -c \
  "composer install --no-interaction && php vendor/bin/phpunit $*"
