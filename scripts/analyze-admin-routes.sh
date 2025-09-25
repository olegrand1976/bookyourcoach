#!/bin/bash

# Script pour analyser et corriger les routes admin
# Usage: ./scripts/analyze-admin-routes.sh

echo "🔍 Analyse des routes admin"
echo "=========================="

cd /home/olivier/projets/bookyourcoach

echo ""
echo "1. Nombre total de routes admin:"
php artisan route:list --path=api | grep "api/admin" | wc -l

echo ""
echo "2. Routes admin actuelles:"
php artisan route:list --path=api | grep "api/admin" | head -10

echo ""
echo "3. Vérification de l'authentification manuelle:"
grep -n "request()->header('Authorization')" routes/api.php | wc -l

echo ""
echo "4. Vérification des vérifications de rôle admin:"
grep -n "role !== 'admin'" routes/api.php | wc -l

echo ""
echo "5. Structure du groupe admin (lignes 1222-2254):"
echo "   - Début: ligne 1222"
echo "   - Fin: ligne 2254"
echo "   - Total: $((2254 - 1222 + 1)) lignes"

echo ""
echo "6. Middleware AdminMiddleware disponible:"
if [ -f "app/Http/Middleware/AdminMiddleware.php" ]; then
    echo "   ✅ AdminMiddleware.php existe"
else
    echo "   ❌ AdminMiddleware.php manquant"
fi

echo ""
echo "7. Configuration middleware dans bootstrap/app.php:"
grep -n "admin.*AdminMiddleware" bootstrap/app.php

echo ""
echo "🎯 RECOMMANDATIONS:"
echo "=================="
echo "1. Remplacer Route::prefix('admin')->group() par:"
echo "   Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group()"
echo ""
echo "2. Supprimer toute l'authentification manuelle répétitive"
echo ""
echo "3. Utiliser auth()->user() au lieu de \$personalAccessToken->tokenable"
echo ""
echo "4. Simplifier le code de ~1000 lignes à ~200 lignes"
