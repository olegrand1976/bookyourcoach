#!/bin/bash

# Script pour lancer les tests du flux de création de cours
# Usage: ./scripts/test-lesson-flow.sh

set -e

COMPOSE_FILE="docker-compose.local.yml"
CONTAINER="backend"

echo "🧪 Tests du flux de création de cours - BookYourCoach"
echo "=================================================="
echo ""

# Vérifier que le conteneur est actif
if ! docker compose -f "$COMPOSE_FILE" ps | grep -q "$CONTAINER.*Up"; then
    echo "❌ Le conteneur backend n'est pas démarré"
    echo "Démarrez-le avec: ./scripts/docker-maintenance.sh start"
    exit 1
fi

# Installer les dépendances si nécessaire
if ! docker compose -f "$COMPOSE_FILE" exec "$CONTAINER" sh -c "test -d vendor"; then
    echo "📦 Installation des dépendances Composer..."
    docker compose -f "$COMPOSE_FILE" exec "$CONTAINER" composer install --no-interaction --prefer-dist
fi

echo "🔧 Préparation de la base de données de test (SQLite)..."
docker compose -f "$COMPOSE_FILE" exec "$CONTAINER" sh -c "mkdir -p database && touch database/database.sqlite"
docker compose -f "$COMPOSE_FILE" exec "$CONTAINER" php artisan migrate:fresh --env=testing --force --quiet
echo "✅ Base de données migrée"

echo ""
echo "🧪 Exécution des tests du flux de création de cours..."
echo ""

# Exécuter les tests
docker compose -f "$COMPOSE_FILE" exec "$CONTAINER" ./vendor/bin/phpunit \
    --filter=LessonCreationFlowTest \
    --testdox \
    --colors=always

EXIT_CODE=$?

echo ""
if [ $EXIT_CODE -eq 0 ]; then
    echo "✅ Tous les tests sont passés avec succès!"
else
    echo "❌ Certains tests ont échoué (code: $EXIT_CODE)"
fi

echo ""
echo "📊 Pour exécuter tous les tests:"
echo "   docker compose -f $COMPOSE_FILE exec $CONTAINER ./vendor/bin/phpunit"
echo ""
echo "📖 Documentation:"
echo "   docs/LESSON_CREATION_FLOW.md"

exit $EXIT_CODE
