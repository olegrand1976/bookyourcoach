#!/bin/bash

# Script de test du système de paie
# Utilise localhost:3308 pour se connecter au container MySQL Docker

set -e

echo "🔧 Configuration de la connexion à la base de données Docker..."
export DB_CONNECTION=mysql
export DB_HOST=127.0.0.1
export DB_PORT=3308
export DB_DATABASE=book_your_coach_local
export DB_USERNAME=activibe_user
export DB_PASSWORD=activibe_password
export DB_SOCKET=""  # Force l'utilisation de TCP au lieu d'un socket Unix

echo "📦 Exécution de la migration..."
php artisan migrate --path=database/migrations/2025_11_17_214233_add_commission_fields_to_subscription_instances_table.php

echo ""
echo "🌱 Création des données de test..."
php artisan db:seed --class=PayrollTestDataSeeder

echo ""
echo "📊 Génération du rapport de paie pour Novembre 2025..."
echo "=========================================="
php artisan payroll:generate --year=2025 --month=11 --output=json

echo ""
echo ""
echo "📋 Comparaison avec les résultats attendus :"
echo "=========================================="
echo ""
echo "Résultats attendus pour prof_alpha :"
echo "  - total_commissions_type1: 105.00 € (100×0.70 + 50×0.70)"
echo "  - total_commissions_type2: 40.00 € (80×0.50)"
echo "  - total_a_payer: 145.00 €"
echo ""
echo "Résultats attendus pour prof_beta :"
echo "  - total_commissions_type1: 70.00 € (100×0.70)"
echo "  - total_commissions_type2: 0.00 €"
echo "  - total_a_payer: 70.00 €"
echo ""
echo "✅ Test terminé !"
