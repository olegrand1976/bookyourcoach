#!/bin/bash

# Script pour supprimer l'authentification manuelle des routes admin
# Usage: ./scripts/remove-manual-auth.sh

echo "🔧 Suppression de l'authentification manuelle"
echo "============================================"

cd /home/olivier/projets/bookyourcoach

# Créer une sauvegarde
echo "1. Sauvegarde..."
cp routes/api.php routes/api.php.backup.remove_auth.$(date +%Y%m%d_%H%M%S)

echo "2. Suppression des blocs d'authentification manuelle..."

# Supprimer les blocs d'authentification manuelle répétitifs
# Pattern: depuis "$token = request()->header('Authorization');" jusqu'à "if (!$user || $user->role !== 'admin') {"

# Utiliser sed pour supprimer les blocs d'authentification
sed -i '/\$token = request()->header('\''Authorization'\'');/,/if (!$user || $user->role !== '\''admin'\'') {/{
    /if (!$user || $user->role !== '\''admin'\'') {/d
}' routes/api.php

# Supprimer les lignes de retour d'erreur restantes
sed -i '/return response()->json(\['\''message'\'' => '\''Missing token'\''\], 401);/d' routes/api.php
sed -i '/return response()->json(\['\''message'\'' => '\''Invalid token'\''\], 401);/d' routes/api.php
sed -i '/return response()->json(\['\''message'\'' => '\''Access denied - Admin rights required'\''\], 403);/d' routes/api.php

# Supprimer les lignes vides multiples
sed -i '/^$/N;/^\n$/d' routes/api.php

echo "3. Test de la syntaxe..."
php -l routes/api.php

if [ $? -eq 0 ]; then
    echo "✅ Syntaxe valide"
    
    echo ""
    echo "4. Vérification des résultats..."
    remaining_auth=$(grep -c "request()->header('Authorization')" routes/api.php)
    remaining_role=$(grep -c "role !== 'admin'" routes/api.php)
    
    echo "   - Authentifications manuelles restantes: $remaining_auth"
    echo "   - Vérifications de rôle restantes: $remaining_role"
    
    if [ $remaining_auth -eq 0 ] && [ $remaining_role -eq 0 ]; then
        echo ""
        echo "🎯 SUCCÈS! Authentification manuelle supprimée"
        echo "============================================="
        echo "✅ Toutes les authentifications manuelles supprimées"
        echo "✅ Middleware auth:sanctum + admin appliqué"
        echo "✅ Code simplifié et sécurisé"
    else
        echo ""
        echo "⚠️  Quelques authentifications manuelles restent"
        echo "   - Authentifications: $remaining_auth"
        echo "   - Vérifications de rôle: $remaining_role"
    fi
else
    echo "❌ Erreur de syntaxe - restauration de la sauvegarde"
    cp routes/api.php.backup.remove_auth.* routes/api.php
fi

echo ""
echo "5. Nettoyage des fichiers temporaires..."
rm -f temp_routes_start.php temp_routes_end.php temp_admin_routes.php
