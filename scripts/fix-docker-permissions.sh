#!/bin/bash

# =============================================================================
# Script de correction des permissions Docker pour BookYourCoach
# =============================================================================

echo "🔧 Correction des permissions Docker pour BookYourCoach"
echo ""

# Vérifier que nous sommes dans un projet Laravel
if [ ! -f "artisan" ]; then
    echo "❌ Ce script doit être exécuté dans le répertoire racine du projet Laravel"
    echo "💡 Naviguez vers le répertoire du projet et relancez le script"
    exit 1
fi

echo "✅ Projet Laravel détecté"
echo ""

# Vérifier que Docker Compose est en cours d'exécution
if ! docker compose ps | grep -q "activibe-backend"; then
    echo "❌ Le conteneur activibe-backend n'est pas en cours d'exécution"
    echo "💡 Lancez d'abord: docker compose up -d"
    exit 1
fi

echo "✅ Conteneur Docker détecté"
echo ""

# Afficher les permissions actuelles
echo "📋 Permissions actuelles du dossier storage:"
docker exec activibe-backend ls -la /var/www/html/storage/logs/
echo ""

# Corriger les permissions
echo "🔧 Correction des permissions..."

# Dossier storage complet
echo "   - Correction du dossier storage complet..."
docker exec activibe-backend chown -R www-data:www-data /var/www/html/storage/
docker exec activibe-backend chmod -R 775 /var/www/html/storage/

# Dossier bootstrap/cache
echo "   - Correction du dossier bootstrap/cache..."
docker exec activibe-backend chown -R www-data:www-data /var/www/html/bootstrap/cache/
docker exec activibe-backend chmod -R 775 /var/www/html/bootstrap/cache/

echo ""
echo "✅ Permissions corrigées"
echo ""

# Vérifier les nouvelles permissions
echo "📋 Nouvelles permissions:"
docker exec activibe-backend ls -la /var/www/html/storage/logs/
echo ""

# Tester les logs
echo "🧪 Test des logs..."
docker exec activibe-backend php artisan tinker --execute="Log::info('Test de permissions - ' . now());" > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo "✅ Test de logging réussi"
    echo ""
    echo "📝 Dernière entrée de log:"
    docker exec activibe-backend tail -1 /var/www/html/storage/logs/laravel.log
else
    echo "❌ Test de logging échoué"
    echo "💡 Vérifiez les logs pour plus de détails"
fi

echo ""
echo "🎉 Script terminé avec succès !"
echo ""
echo "💡 Ce script peut être relancé si vous rencontrez des problèmes de permissions"
echo "💡 Les permissions sont automatiquement corrigées pour:"
echo "   - /var/www/html/storage/ (logs, cache, sessions)"
echo "   - /var/www/html/bootstrap/cache/ (cache de configuration)"
