#!/bin/bash

# Script de démarrage pour l'application complète
echo "🚀 Démarrage de BookYourCoach (Backend + Frontend)"

# Créer les répertoires nécessaires
mkdir -p frontend/node_modules

# Construire et démarrer les services
echo "📦 Construction des containers..."
docker-compose build

echo "🔄 Démarrage des services..."
docker-compose up -d

# Attendre que MySQL soit prêt
echo "⏳ Attente de la base de données..."
sleep 10

# Installation des dépendances frontend
echo "📚 Installation des dépendances frontend..."
docker-compose exec frontend npm install

# Migrations Laravel
echo "🔧 Exécution des migrations..."
docker-compose exec app php artisan migrate --force

# Générer les clés et liens symboliques
echo "🔑 Configuration de Laravel..."
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan storage:link

# Générer la documentation API
echo "📖 Génération de la documentation API..."
docker-compose exec app php artisan l5-swagger:generate

echo "✅ Application démarrée avec succès!"
echo ""
echo "🌐 Accès aux services:"
echo "   - Frontend (Nuxt.js): http://localhost:3000"
echo "   - API (Laravel): http://localhost:8081"
echo "   - PhpMyAdmin: http://localhost:8082"
echo "   - Documentation API: http://localhost:8081/api/documentation"
echo ""
echo "📊 Identifiants par défaut:"
echo "   - Base de données: bookyourcoach / password"
echo "   - PhpMyAdmin: root / root_password"
