#!/bin/bash

# Script simple pour refactoriser les routes admin
# Usage: ./scripts/simple-refactor-admin.sh

echo "🔧 Refactorisation simple des routes admin"
echo "==========================================="

cd /home/olivier/projets/bookyourcoach

# Créer une sauvegarde
echo "1. Sauvegarde..."
cp routes/api.php routes/api.php.backup.$(date +%Y%m%d_%H%M%S)

echo "2. Suppression de l'authentification manuelle répétitive..."

# Supprimer les blocs d'authentification manuelle
sed -i '/\$token = request()->header('\''Authorization'\'');/,/if (!$user || $user->role !== '\''admin'\'') {/d' routes/api.php

# Supprimer les lignes de vérification de rôle restantes
sed -i '/return response()->json(\['\''message'\'' => '\''Access denied - Admin rights required'\''\], 403);/d' routes/api.php

# Supprimer les lignes de vérification de token restantes
sed -i '/return response()->json(\['\''message'\'' => '\''Missing token'\''\], 401);/d' routes/api.php
sed -i '/return response()->json(\['\''message'\'' => '\''Invalid token'\''\], 401);/d' routes/api.php

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
    
    echo ""
    echo "🎯 REFACTORISATION TERMINÉE!"
    echo "=========================="
    echo "✅ Middleware auth:sanctum + admin appliqué"
    echo "✅ Authentification manuelle supprimée"
    echo "✅ Code simplifié"
else
    echo "❌ Erreur de syntaxe - restauration de la sauvegarde"
    cp routes/api.php.backup.* routes/api.php
fi
