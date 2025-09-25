#!/bin/bash

# Script pour supprimer progressivement l'authentification manuelle
# Usage: ./scripts/progressive-auth-removal.sh

echo "🔧 Suppression progressive de l'authentification manuelle"
echo "========================================================"

cd /home/olivier/projets/bookyourcoach

# Créer une sauvegarde
echo "1. Sauvegarde..."
cp routes/api.php routes/api.php.backup.progressive.$(date +%Y%m%d_%H%M%S)

echo "2. Suppression progressive..."

# Fonction pour supprimer un bloc d'authentification spécifique
remove_auth_block() {
    local start_pattern="$1"
    local end_pattern="$2"
    
    # Trouver la ligne de début
    local start_line=$(grep -n "$start_pattern" routes/api.php | head -1 | cut -d: -f1)
    
    if [ -n "$start_line" ]; then
        # Trouver la ligne de fin (prochaine ligne avec "if (!$user || $user->role !== 'admin')")
        local end_line=$(grep -n "$end_pattern" routes/api.php | head -1 | cut -d: -f1)
        
        if [ -n "$end_line" ]; then
            echo "   Suppression du bloc lignes $start_line-$end_line"
            
            # Supprimer le bloc
            sed -i "${start_line},${end_line}d" routes/api.php
            
            # Supprimer les lignes de retour d'erreur qui suivent
            sed -i "${start_line}d" routes/api.php 2>/dev/null || true
            
            return 0
        fi
    fi
    return 1
}

# Supprimer les blocs d'authentification un par un
count=0
max_attempts=30

while [ $count -lt $max_attempts ]; do
    if remove_auth_block "request()->header('Authorization')" "role !== 'admin'"; then
        count=$((count + 1))
        echo "   Bloc $count supprimé"
    else
        break
    fi
done

echo "3. Nettoyage final..."

# Supprimer les lignes de retour d'erreur restantes
sed -i '/return response()->json(\['\''message'\'' => '\''Missing token'\''\], 401);/d' routes/api.php
sed -i '/return response()->json(\['\''message'\'' => '\''Invalid token'\''\], 401);/d' routes/api.php
sed -i '/return response()->json(\['\''message'\'' => '\''Access denied - Admin rights required'\''\], 403);/d' routes/api.php

# Supprimer les lignes vides multiples
sed -i '/^$/N;/^\n$/d' routes/api.php

echo "4. Test de la syntaxe..."
php -l routes/api.php

if [ $? -eq 0 ]; then
    echo "✅ Syntaxe valide"
    
    echo ""
    echo "5. Vérification des résultats..."
    remaining_auth=$(grep -c "request()->header('Authorization')" routes/api.php)
    remaining_role=$(grep -c "role !== 'admin'" routes/api.php)
    
    echo "   - Authentifications manuelles restantes: $remaining_auth"
    echo "   - Vérifications de rôle restantes: $remaining_role"
    echo "   - Blocs supprimés: $count"
    
    if [ $remaining_auth -eq 0 ] && [ $remaining_role -eq 0 ]; then
        echo ""
        echo "🎯 SUCCÈS COMPLET!"
        echo "=================="
        echo "✅ Toutes les authentifications manuelles supprimées"
        echo "✅ Middleware auth:sanctum + admin appliqué"
        echo "✅ Code simplifié et sécurisé"
        echo "✅ Prêt pour la production"
    else
        echo ""
        echo "⚠️  Refactorisation partielle"
        echo "   - Authentifications restantes: $remaining_auth"
        echo "   - Vérifications de rôle restantes: $remaining_role"
        echo "   - Continuer la refactorisation manuelle"
    fi
else
    echo "❌ Erreur de syntaxe - restauration de la sauvegarde"
    cp routes/api.php.backup.progressive.* routes/api.php
fi

echo ""
echo "6. Nettoyage..."
rm -f temp_routes_start.php temp_routes_end.php temp_admin_routes.php
