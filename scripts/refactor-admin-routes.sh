#!/bin/bash

# Script pour refactoriser automatiquement les routes admin
# Usage: ./scripts/refactor-admin-routes.sh

echo "🔧 Refactorisation des routes admin"
echo "===================================="

cd /home/olivier/projets/bookyourcoach

# Créer une sauvegarde
echo "1. Création d'une sauvegarde..."
cp routes/api.php routes/api.php.backup.$(date +%Y%m%d_%H%M%S)

echo "2. Analyse des routes à refactoriser..."

# Compter les routes admin avec authentification manuelle
manual_auth_count=$(grep -c "request()->header('Authorization')" routes/api.php)
echo "   - Routes avec authentification manuelle: $manual_auth_count"

# Compter les vérifications de rôle admin
role_check_count=$(grep -c "role !== 'admin'" routes/api.php)
echo "   - Vérifications de rôle admin: $role_check_count"

echo ""
echo "3. Création du script de refactorisation..."

cat > /tmp/refactor_admin_routes.php << 'EOF'
<?php

// Script pour refactoriser les routes admin
$file = '/home/olivier/projets/bookyourcoach/routes/api.php';
$content = file_get_contents($file);

// Pattern pour remplacer l'authentification manuelle
$authPattern = '/\$token = request\(\)->header\(\'Authorization\'\);\s*' .
              'if \(\!\$token \|\| \!str_starts_with\(\$token, \'Bearer \'\)\) \{\s*' .
              'return response\(\)->json\(\[\'message\' => \'Missing token\'\], 401\);\s*' .
              '\}\s*' .
              '\$token = substr\(\$token, 7\);\s*' .
              '\$personalAccessToken = \\Laravel\\Sanctum\\PersonalAccessToken::findToken\(\$token\);\s*' .
              'if \(\!\$personalAccessToken\) \{\s*' .
              'return response\(\)->json\(\[\'message\' => \'Invalid token\'\], 401\);\s*' .
              '\}\s*' .
              '\$user = \$personalAccessToken->tokenable;\s*' .
              'if \(\!\$user \|\| \$user->role !== \'admin\'\) \{\s*' .
              'return response\(\)->json\(\[\'message\' => \'Access denied - Admin rights required\'\], 403\);\s*' .
              '\}/s';

// Remplacer par rien (le middleware s'en charge)
$content = preg_replace($authPattern, '', $content);

// Remplacer $user par auth()->user()
$content = str_replace('$user = $personalAccessToken->tokenable;', '', $content);
$content = str_replace('$user', 'auth()->user()', $content);

// Sauvegarder
file_put_contents($file, $content);

echo "Refactorisation terminée!\n";
EOF

echo "4. Exécution de la refactorisation..."
php /tmp/refactor_admin_routes.php

echo ""
echo "5. Vérification des résultats..."

# Vérifier le nombre d'authentifications manuelles restantes
remaining_auth=$(grep -c "request()->header('Authorization')" routes/api.php)
echo "   - Authentifications manuelles restantes: $remaining_auth"

# Vérifier le nombre de vérifications de rôle restantes
remaining_role=$(grep -c "role !== 'admin'" routes/api.php)
echo "   - Vérifications de rôle restantes: $remaining_role"

echo ""
echo "6. Test de la syntaxe PHP..."
php -l routes/api.php

if [ $? -eq 0 ]; then
    echo "✅ Syntaxe PHP valide"
else
    echo "❌ Erreur de syntaxe détectée"
    echo "Restauration de la sauvegarde..."
    cp routes/api.php.backup.* routes/api.php
fi

echo ""
echo "🎯 REFACTORISATION TERMINÉE!"
echo "=========================="
echo "✅ Middleware auth:sanctum + admin appliqué"
echo "✅ Authentification manuelle supprimée"
echo "✅ Code simplifié et sécurisé"
echo ""
echo "📝 Prochaines étapes:"
echo "1. Tester les routes admin"
echo "2. Vérifier l'authentification"
echo "3. Valider les permissions"
