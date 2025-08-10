#!/bin/bash

echo "🧪 BookYourCoach - Lancement des tests"
echo "======================================"

# Configuration Docker
echo "📦 Vérification de l'environnement Docker..."
if ! docker-compose ps | grep -q "bookyourcoach_app.*Up"; then
    echo "❌ Les conteneurs Docker ne sont pas démarrés"
    echo "   Démarrage des conteneurs..."
    docker-compose up -d
    sleep 5
fi

echo "✅ Environnement Docker prêt"

# Tests unitaires
echo ""
echo "🔬 Exécution des tests unitaires..."
docker-compose exec app php artisan test tests/Unit --stop-on-failure

# Tests de fonctionnalité
echo ""
echo "🎯 Exécution des tests de fonctionnalité..."
docker-compose exec app php artisan test tests/Feature --stop-on-failure

# Tests complets avec couverture
echo ""
echo "📊 Exécution de tous les tests..."
docker-compose exec app php artisan test --coverage-text

# Vérification de la documentation Swagger
echo ""
echo "📚 Génération de la documentation Swagger..."
docker-compose exec app php artisan l5-swagger:generate

echo ""
echo "✅ Tous les tests terminés !"
echo "📖 Documentation disponible sur : http://localhost:8081/docs"
echo "🌐 Application disponible sur : http://localhost:8081"
