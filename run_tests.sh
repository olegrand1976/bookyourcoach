#!/bin/bash

echo "🧪 EXÉCUTION DES TESTS UNITAIRES ET D'INTÉGRATION"
echo "================================================="
echo ""

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les résultats
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2${NC}"
    else
        echo -e "${RED}❌ $2${NC}"
    fi
}

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "composer.json" ]; then
    echo -e "${RED}❌ Erreur: Ce script doit être exécuté depuis la racine du projet Laravel${NC}"
    exit 1
fi

echo -e "${BLUE}📋 VÉRIFICATION DE L'ENVIRONNEMENT${NC}"
echo "----------------------------------------"

# Vérifier que PHP est installé
if command -v php &> /dev/null; then
    echo -e "${GREEN}✅ PHP installé: $(php --version | head -n1)${NC}"
else
    echo -e "${RED}❌ PHP n'est pas installé${NC}"
    exit 1
fi

# Vérifier que Composer est installé
if command -v composer &> /dev/null; then
    echo -e "${GREEN}✅ Composer installé: $(composer --version | head -n1)${NC}"
else
    echo -e "${RED}❌ Composer n'est pas installé${NC}"
    exit 1
fi

# Vérifier que les dépendances sont installées
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}⚠️  Installation des dépendances Composer...${NC}"
    composer install --no-interaction
    print_result $? "Installation des dépendances Composer"
fi

echo ""
echo -e "${BLUE}🔧 PRÉPARATION DES TESTS${NC}"
echo "------------------------"

# Créer la base de données de test si elle n'existe pas
echo -e "${YELLOW}📊 Configuration de la base de données de test...${NC}"
php artisan config:clear
php artisan cache:clear

# Exécuter les migrations de test
echo -e "${YELLOW}🗄️  Exécution des migrations de test...${NC}"
php artisan migrate --env=testing --force
print_result $? "Migrations de test"

echo ""
echo -e "${BLUE}🧪 EXÉCUTION DES TESTS${NC}"
echo "---------------------"

# Tests unitaires des modèles
echo -e "${YELLOW}📝 Tests unitaires des modèles...${NC}"
php artisan test tests/Unit/Models/StudentTest.php --verbose
print_result $? "Tests du modèle Student"

php artisan test tests/Unit/Models/TeacherTest.php --verbose
print_result $? "Tests du modèle Teacher"

php artisan test tests/Unit/Models/LessonTest.php --verbose
print_result $? "Tests du modèle Lesson"

echo ""

# Tests d'intégration des API
echo -e "${YELLOW}🌐 Tests d'intégration des API...${NC}"
php artisan test tests/Feature/Api/StudentApiTest.php --verbose
print_result $? "Tests API des étudiants"

php artisan test tests/Feature/Api/TeacherApiTest.php --verbose
print_result $? "Tests API des enseignants"

php artisan test tests/Feature/Api/LessonApiTest.php --verbose
print_result $? "Tests API des cours"

echo ""

# Tests frontend (si Node.js est disponible)
if command -v npm &> /dev/null && [ -d "frontend" ]; then
    echo -e "${YELLOW}🎨 Tests frontend...${NC}"
    cd frontend
    
    # Vérifier que les dépendances sont installées
    if [ ! -d "node_modules" ]; then
        echo -e "${YELLOW}📦 Installation des dépendances Node.js...${NC}"
        npm install
    fi
    
    # Exécuter les tests unitaires frontend
    npm run test:unit
    print_result $? "Tests unitaires frontend"
    
    cd ..
else
    echo -e "${YELLOW}⚠️  Node.js non disponible, tests frontend ignorés${NC}"
fi

echo ""
echo -e "${BLUE}📊 RÉSUMÉ DES TESTS${NC}"
echo "-------------------"

# Compter les tests réussis et échoués
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Exécuter tous les tests et compter les résultats
TEST_OUTPUT=$(php artisan test --verbose 2>&1)
TOTAL_TESTS=$(echo "$TEST_OUTPUT" | grep -c "✓\|✗")
PASSED_TESTS=$(echo "$TEST_OUTPUT" | grep -c "✓")
FAILED_TESTS=$(echo "$TEST_OUTPUT" | grep -c "✗")

echo -e "${GREEN}✅ Tests réussis: $PASSED_TESTS${NC}"
echo -e "${RED}❌ Tests échoués: $FAILED_TESTS${NC}"
echo -e "${BLUE}📈 Total des tests: $TOTAL_TESTS${NC}"

if [ $FAILED_TESTS -eq 0 ]; then
    echo ""
    echo -e "${GREEN}🎉 TOUS LES TESTS SONT PASSÉS !${NC}"
    echo -e "${GREEN}Le système élèves, enseignants et cours fonctionne correctement.${NC}"
    exit 0
else
    echo ""
    echo -e "${RED}⚠️  CERTAINS TESTS ONT ÉCHOUÉ${NC}"
    echo -e "${YELLOW}Vérifiez les erreurs ci-dessus pour identifier les problèmes.${NC}"
    exit 1
fi