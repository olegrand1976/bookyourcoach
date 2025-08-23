#!/bin/bash

# Script de démarrage pour BookYourCoach

echo "🚀 Démarrage de BookYourCoach..."

# Construire et démarrer les conteneurs
echo "📦 Construction et démarrage des conteneurs Docker..."
docker-compose up -d --build

# Attendre que la base de données soit prête
echo "⏳ Attente que la base de données soit prête..."
sleep 15

# Installation des dépendances
echo "📥 Installation des dépendances Composer..."
docker-compose exec app composer install --optimize-autoloader

# Génération de la clé d'application
echo "🔑 Génération de la clé d'application..."
docker-compose exec app php artisan key:generate

# Configuration du cache
echo "💾 Configuration du cache..."
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache

# Exécution des migrations
echo "🗄️ Exécution des migrations..."
docker-compose exec app php artisan migrate --force

# Création du lien de stockage
echo "🔗 Création du lien de stockage..."
docker-compose exec app php artisan storage:link

# Configuration des permissions
echo "🔒 Configuration des permissions..."
docker-compose exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

echo "✅ BookYourCoach est maintenant disponible !"
echo ""
echo "📱 Application: http://localhost:8000"
echo "🗃️ PHPMyAdmin: http://localhost:8080"
echo "   - Utilisateur: bookyourcoach"
echo "   - Mot de passe: password"
echo ""
echo "🔧 Commandes utiles:"
echo "   - Arrêter: docker-compose down"
echo "   - Logs: docker-compose logs -f"
echo "   - Console Laravel: docker-compose exec app php artisan tinker"
echo "   - Tests: docker-compose exec app php artisan test"
