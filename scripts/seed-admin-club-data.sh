#!/bin/bash

# Script pour charger les données de test pour le club de l'admin
# Usage: ./scripts/seed-admin-club-data.sh

set -e

echo "🎯 Chargement des données de test pour le club de l'admin..."
echo ""

# Vérifier si on est dans le bon répertoire
if [ ! -f "artisan" ]; then
    echo "❌ Erreur: Ce script doit être exécuté depuis la racine du projet"
    exit 1
fi

# Vérifier si docker-compose.local.yml existe
if [ ! -f "docker-compose.local.yml" ]; then
    echo "❌ Erreur: docker-compose.local.yml introuvable"
    exit 1
fi

# Exécuter le seeder
echo "📦 Exécution du seeder AdminClubTestDataSeeder..."
docker compose -f docker-compose.local.yml exec backend php artisan db:seed --class=AdminClubTestDataSeeder

echo ""
echo "✅ Données de test chargées avec succès !"
echo ""
echo "📊 Résumé:"
echo "   - Club: ACTI'VIBE (admin: b.murgo1976@gmail.com)"
echo "   - 5 enseignants"
echo "   - 12 élèves"
echo "   - 147 cours (4 semaines)"
echo ""
echo "🔗 Accès:"
echo "   - Frontend: http://localhost:3000"
echo "   - Backend API: http://localhost:8080/api"
