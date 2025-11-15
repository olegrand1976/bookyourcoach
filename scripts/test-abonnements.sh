#!/bin/bash

# Script de test rapide pour la gestion des abonnements
# Usage: ./scripts/test-abonnements.sh

echo "🧪 Tests de Gestion des Abonnements"
echo "===================================="
echo ""

# Couleurs pour l'affichage
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Compteurs
TESTS_PASSED=0
TESTS_FAILED=0
TESTS_TOTAL=0

# Fonction pour afficher un test
test_result() {
    TESTS_TOTAL=$((TESTS_TOTAL + 1))
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ Test $2: PASSÉ${NC}"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "${RED}❌ Test $2: ÉCHOUÉ${NC}"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
}

echo "📋 Tests Critiques (Priorité 1)"
echo "-------------------------------"
echo ""

# Test 1: Vérifier que les routes existent
echo "Test 1: Vérification des routes API..."
php artisan route:list | grep -q "subscription-templates" && test_result 0 "Routes API" || test_result 1 "Routes API"

# Test 2: Vérifier que les modèles existent
echo "Test 2: Vérification des modèles..."
php artisan tinker --execute="echo App\Models\SubscriptionTemplate::count();" > /dev/null 2>&1 && test_result 0 "Modèles Eloquent" || test_result 1 "Modèles Eloquent"

# Test 3: Vérifier que les migrations sont à jour
echo "Test 3: Vérification des migrations..."
php artisan migrate:status | grep -q "Ran" && test_result 0 "Migrations" || test_result 1 "Migrations"

echo ""
echo "📊 Résumé des Tests"
echo "-------------------"
echo -e "Total: ${TESTS_TOTAL}"
echo -e "${GREEN}Réussis: ${TESTS_PASSED}${NC}"
echo -e "${RED}Échoués: ${TESTS_FAILED}${NC}"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ Tous les tests de base sont passés !${NC}"
    echo ""
    echo "📝 Prochaines étapes :"
    echo "1. Consulter le plan de test complet : docs/PLAN_TEST_ABONNEMENTS.md"
    echo "2. Exécuter les tests manuels dans l'ordre de priorité"
    echo "3. Vérifier les logs Laravel après chaque test"
    exit 0
else
    echo -e "${RED}❌ Certains tests ont échoué. Vérifiez les erreurs ci-dessus.${NC}"
    exit 1
fi

