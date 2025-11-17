#!/bin/bash

# Script pour lancer tous les tests et générer un résumé détaillé
# Usage: ./run-tests.sh [options]
# Options:
#   --filter=pattern    Filtrer les tests par pattern
#   --stop-on-failure   Arrêter au premier échec
#   --coverage          Générer un rapport de couverture

set -e

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# Variables
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$SCRIPT_DIR"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
OUTPUT_FILE="/tmp/test_results_${TIMESTAMP}.txt"
JSON_OUTPUT_FILE="/tmp/test_results_${TIMESTAMP}.json"

# Fonction pour afficher un en-tête
print_header() {
    echo ""
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${CYAN}  $1${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
    echo ""
}

# Fonction pour afficher une section
print_section() {
    echo ""
    echo -e "${BLUE}─── $1 ───${NC}"
}

# Fonction pour formater un nombre avec couleur
format_number() {
    local num=$1
    local color=$2
    if [ "$num" -gt 0 ]; then
        echo -e "${color}$num${NC}"
    else
        echo -e "${GREEN}$num${NC}"
    fi
}

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "$PROJECT_DIR/artisan" ]; then
    echo -e "${RED}Erreur: Ce script doit être exécuté depuis la racine du projet Laravel${NC}"
    exit 1
fi

# Aller dans le répertoire du projet
cd "$PROJECT_DIR"

print_header "Lancement de tous les tests"

# Construire la commande de test avec format JSON pour un meilleur parsing
TEST_CMD="php artisan test --testdox"
if [ "$1" != "" ]; then
    TEST_CMD="$TEST_CMD $@"
fi

echo -e "${YELLOW}Commande: $TEST_CMD${NC}"
echo ""

# Lancer les tests et capturer la sortie
if $TEST_CMD 2>&1 | tee "$OUTPUT_FILE"; then
    TEST_EXIT_CODE=0
else
    TEST_EXIT_CODE=${PIPESTATUS[0]}
fi

print_header "Analyse des résultats"

# Extraire les statistiques globales depuis la dernière ligne de résumé
# Format attendu: "Tests: XX passed, YY failed, ZZ skipped"
LAST_LINE=$(tail -20 "$OUTPUT_FILE" | grep -E "Tests:|passed|failed|skipped" | tail -1)

TOTAL_TESTS=$(grep -oP '\d+(?=\s+test)' "$OUTPUT_FILE" | tail -1 || echo "0")
PASSED_TESTS=$(grep -oP '\d+(?=\s+passed)' "$OUTPUT_FILE" | tail -1 || echo "0")
FAILED_TESTS=$(grep -oP '\d+(?=\s+failed)' "$OUTPUT_FILE" | tail -1 || echo "0")
SKIPPED_TESTS=$(grep -oP '\d+(?=\s+skipped)' "$OUTPUT_FILE" | tail -1 || echo "0")
WARNINGS=$(grep -oP '\d+(?=\s+warning)' "$OUTPUT_FILE" | tail -1 || echo "0")

# Si on n'a pas trouvé les stats, essayer un autre format
if [ "$TOTAL_TESTS" = "0" ] && [ "$PASSED_TESTS" = "0" ]; then
    # Format alternatif: "OK (XX tests, YY assertions)"
    TOTAL_TESTS=$(grep -oP 'OK\s+\((\d+)\s+test' "$OUTPUT_FILE" | grep -oP '\d+' | head -1 || echo "0")
    if [ "$TOTAL_TESTS" = "0" ]; then
        # Compter les tests depuis la sortie testdox
        TOTAL_TESTS=$(grep -c "✓\|✗\|⊘" "$OUTPUT_FILE" 2>/dev/null || echo "0")
    fi
fi

# Extraire les tests échoués de manière plus précise
FAILED_TEST_LIST=()
while IFS= read -r line; do
    if echo "$line" | grep -qE "✗|FAILED|ERROR"; then
        # Extraire le nom du test
        TEST_NAME=$(echo "$line" | sed -E 's/.*✗\s*//' | sed -E 's/.*FAILED\s*//' | sed -E 's/.*ERROR\s*//' | sed -E 's/\s*\(.*//' | xargs)
        if [ ! -z "$TEST_NAME" ]; then
            FAILED_TEST_LIST+=("$TEST_NAME")
        fi
    fi
done < <(grep -E "✗|FAILED|ERROR" "$OUTPUT_FILE")

# Extraire les tests ignorés
SKIPPED_TEST_LIST=()
while IFS= read -r line; do
    if echo "$line" | grep -qE "⊘|Skipped|skipped"; then
        TEST_NAME=$(echo "$line" | sed -E 's/.*⊘\s*//' | sed -E 's/.*Skipped:\s*//' | sed -E 's/\s*\(.*//' | xargs)
        if [ ! -z "$TEST_NAME" ]; then
            SKIPPED_TEST_LIST+=("$TEST_NAME")
        fi
    fi
done < <(grep -E "⊘|Skipped|skipped" "$OUTPUT_FILE")

# Analyser par catégorie de test
print_section "Résultats par type de test"

# Compter les tests Unit
UNIT_PASSED=$(grep -E "Tests\\Unit.*✓" "$OUTPUT_FILE" | wc -l || echo "0")
UNIT_FAILED=$(grep -E "Tests\\Unit.*✗" "$OUTPUT_FILE" | wc -l || echo "0")
UNIT_SKIPPED=$(grep -E "Tests\\Unit.*⊘" "$OUTPUT_FILE" | wc -l || echo "0")

# Compter les tests Feature
FEATURE_PASSED=$(grep -E "Tests\\Feature.*✓" "$OUTPUT_FILE" | wc -l || echo "0")
FEATURE_FAILED=$(grep -E "Tests\\Feature.*✗" "$OUTPUT_FILE" | wc -l || echo "0")
FEATURE_SKIPPED=$(grep -E "Tests\\Feature.*⊘" "$OUTPUT_FILE" | wc -l || echo "0")

# Analyser par modèle/service en comptant les lignes de test
print_section "Résultats par modèle/service"

# Club
CLUB_PASSED=$(grep -E "ClubTest.*✓" "$OUTPUT_FILE" | wc -l || echo "0")
CLUB_FAILED=$(grep -E "ClubTest.*✗" "$OUTPUT_FILE" | wc -l || echo "0")
CLUB_SKIPPED=$(grep -E "ClubTest.*⊘" "$OUTPUT_FILE" | wc -l || echo "0")

# Teacher
TEACHER_PASSED=$(grep -E "TeacherTest.*✓" "$OUTPUT_FILE" | wc -l || echo "0")
TEACHER_FAILED=$(grep -E "TeacherTest.*✗" "$OUTPUT_FILE" | wc -l || echo "0")
TEACHER_SKIPPED=$(grep -E "TeacherTest.*⊘" "$OUTPUT_FILE" | wc -l || echo "0")

# Student
STUDENT_PASSED=$(grep -E "StudentTest.*✓" "$OUTPUT_FILE" | wc -l || echo "0")
STUDENT_FAILED=$(grep -E "StudentTest.*✗" "$OUTPUT_FILE" | wc -l || echo "0")
STUDENT_SKIPPED=$(grep -E "StudentTest.*⊘" "$OUTPUT_FILE" | wc -l || echo "0")

# Subscription (tous les tests liés aux abonnements)
SUBSCRIPTION_PASSED=$(grep -E "(SubscriptionTest|SubscriptionInstanceTest|SubscriptionTemplateTest|SubscriptionRecurringSlotTest).*✓" "$OUTPUT_FILE" | wc -l || echo "0")
SUBSCRIPTION_FAILED=$(grep -E "(SubscriptionTest|SubscriptionInstanceTest|SubscriptionTemplateTest|SubscriptionRecurringSlotTest).*✗" "$OUTPUT_FILE" | wc -l || echo "0")
SUBSCRIPTION_SKIPPED=$(grep -E "(SubscriptionTest|SubscriptionInstanceTest|SubscriptionTemplateTest|SubscriptionRecurringSlotTest).*⊘" "$OUTPUT_FILE" | wc -l || echo "0")

# Planning
PLANNING_PASSED=$(grep -E "(ClubPlanningControllerTest|Planning).*✓" "$OUTPUT_FILE" | wc -l || echo "0")
PLANNING_FAILED=$(grep -E "(ClubPlanningControllerTest|Planning).*✗" "$OUTPUT_FILE" | wc -l || echo "0")
PLANNING_SKIPPED=$(grep -E "(ClubPlanningControllerTest|Planning).*⊘" "$OUTPUT_FILE" | wc -l || echo "0")

# Services
SERVICE_PASSED=$(grep -E "(ServiceTest|LegacyRecurringSlotServiceTest).*✓" "$OUTPUT_FILE" | wc -l || echo "0")
SERVICE_FAILED=$(grep -E "(ServiceTest|LegacyRecurringSlotServiceTest).*✗" "$OUTPUT_FILE" | wc -l || echo "0")
SERVICE_SKIPPED=$(grep -E "(ServiceTest|LegacyRecurringSlotServiceTest).*⊘" "$OUTPUT_FILE" | wc -l || echo "0")

# Commands
COMMAND_PASSED=$(grep -E "(CommandTest|ConsumePastLessonsCommandTest).*✓" "$OUTPUT_FILE" | wc -l || echo "0")
COMMAND_FAILED=$(grep -E "(CommandTest|ConsumePastLessonsCommandTest).*✗" "$OUTPUT_FILE" | wc -l || echo "0")
COMMAND_SKIPPED=$(grep -E "(CommandTest|ConsumePastLessonsCommandTest).*⊘" "$OUTPUT_FILE" | wc -l || echo "0")

# Afficher le résumé global
print_header "Résumé global"

printf "%-20s %15s\n" "Métrique" "Valeur"
echo "────────────────────────────────────────────────────────────"
printf "%-20s %15s\n" "Total de tests" "$(format_number "$TOTAL_TESTS" "$CYAN")"
printf "%-20s %15s\n" "Tests réussis" "$(format_number "$PASSED_TESTS" "$GREEN")"
printf "%-20s %15s\n" "Tests échoués" "$(format_number "$FAILED_TESTS" "$RED")"
printf "%-20s %15s\n" "Tests ignorés" "$(format_number "$SKIPPED_TESTS" "$YELLOW")"
if [ "$WARNINGS" != "0" ]; then
    printf "%-20s %15s\n" "Avertissements" "$(format_number "$WARNINGS" "$YELLOW")"
fi

# Afficher les résultats par catégorie
print_section "Par type de test"

printf "%-20s %12s %12s %12s\n" "Catégorie" "Réussis" "Échoués" "Ignorés"
echo "────────────────────────────────────────────────────────────────────────────"
printf "%-20s %12s %12s %12s\n" "Unit" \
    "$(format_number "$UNIT_PASSED" "$GREEN")" \
    "$(format_number "$UNIT_FAILED" "$RED")" \
    "$(format_number "$UNIT_SKIPPED" "$YELLOW")"
printf "%-20s %12s %12s %12s\n" "Feature" \
    "$(format_number "$FEATURE_PASSED" "$GREEN")" \
    "$(format_number "$FEATURE_FAILED" "$RED")" \
    "$(format_number "$FEATURE_SKIPPED" "$YELLOW")"

# Afficher les résultats par modèle/service
print_section "Par modèle/service"

printf "%-35s %12s %12s %12s\n" "Modèle/Service" "Réussis" "Échoués" "Ignorés"
echo "────────────────────────────────────────────────────────────────────────────────────────────"
printf "%-35s %12s %12s %12s\n" "Club" \
    "$(format_number "$CLUB_PASSED" "$GREEN")" \
    "$(format_number "$CLUB_FAILED" "$RED")" \
    "$(format_number "$CLUB_SKIPPED" "$YELLOW")"
printf "%-35s %12s %12s %12s\n" "Teacher" \
    "$(format_number "$TEACHER_PASSED" "$GREEN")" \
    "$(format_number "$TEACHER_FAILED" "$RED")" \
    "$(format_number "$TEACHER_SKIPPED" "$YELLOW")"
printf "%-35s %12s %12s %12s\n" "Student" \
    "$(format_number "$STUDENT_PASSED" "$GREEN")" \
    "$(format_number "$STUDENT_FAILED" "$RED")" \
    "$(format_number "$STUDENT_SKIPPED" "$YELLOW")"
printf "%-35s %12s %12s %12s\n" "Subscription" \
    "$(format_number "$SUBSCRIPTION_PASSED" "$GREEN")" \
    "$(format_number "$SUBSCRIPTION_FAILED" "$RED")" \
    "$(format_number "$SUBSCRIPTION_SKIPPED" "$YELLOW")"
printf "%-35s %12s %12s %12s\n" "Planning" \
    "$(format_number "$PLANNING_PASSED" "$GREEN")" \
    "$(format_number "$PLANNING_FAILED" "$RED")" \
    "$(format_number "$PLANNING_SKIPPED" "$YELLOW")"
printf "%-35s %12s %12s %12s\n" "Services" \
    "$(format_number "$SERVICE_PASSED" "$GREEN")" \
    "$(format_number "$SERVICE_FAILED" "$RED")" \
    "$(format_number "$SERVICE_SKIPPED" "$YELLOW")"
printf "%-35s %12s %12s %12s\n" "Commands" \
    "$(format_number "$COMMAND_PASSED" "$GREEN")" \
    "$(format_number "$COMMAND_FAILED" "$RED")" \
    "$(format_number "$COMMAND_SKIPPED" "$YELLOW")"

# Afficher les tests échoués
if [ "$FAILED_TESTS" -gt 0 ] || [ ${#FAILED_TEST_LIST[@]} -gt 0 ]; then
    print_section "Tests échoués"
    
    if [ ${#FAILED_TEST_LIST[@]} -gt 0 ]; then
        echo -e "${RED}Les tests suivants ont échoué:${NC}"
        for test_name in "${FAILED_TEST_LIST[@]}"; do
            echo -e "  ${RED}✗${NC} $test_name"
        done
    else
        # Fallback: afficher les lignes contenant FAILED ou ERROR
        echo -e "${RED}Détails des échecs:${NC}"
        grep -E "✗|FAILED|ERROR" "$OUTPUT_FILE" | head -30 | while IFS= read -r line; do
            echo -e "  ${RED}✗${NC} $line"
        done
    fi
fi

# Afficher les tests ignorés si présents
if [ "$SKIPPED_TESTS" -gt 0 ] || [ ${#SKIPPED_TEST_LIST[@]} -gt 0 ]; then
    print_section "Tests ignorés"
    
    if [ ${#SKIPPED_TEST_LIST[@]} -gt 0 ]; then
        echo -e "${YELLOW}Les tests suivants ont été ignorés:${NC}"
        for test_name in "${SKIPPED_TEST_LIST[@]}"; do
            echo -e "  ${YELLOW}⊘${NC} $test_name"
        done
    else
        echo -e "${YELLOW}Nombre de tests ignorés: $SKIPPED_TESTS${NC}"
    fi
fi

# Résumé final
print_header "Résumé final"

if [ "$FAILED_TESTS" -eq 0 ] && [ ${#FAILED_TEST_LIST[@]} -eq 0 ]; then
    echo -e "${GREEN}✓ Tous les tests sont passés avec succès!${NC}"
    FINAL_EXIT_CODE=0
else
    echo -e "${RED}✗ $FAILED_TESTS test(s) ont échoué${NC}"
    FINAL_EXIT_CODE=1
fi

if [ "$SKIPPED_TESTS" -gt 0 ]; then
    echo -e "${YELLOW}⊘ $SKIPPED_TESTS test(s) ont été ignorés${NC}"
fi

# Calculer le pourcentage de réussite
if [ "$TOTAL_TESTS" -gt 0 ]; then
    SUCCESS_RATE=$(echo "scale=1; ($PASSED_TESTS * 100) / $TOTAL_TESTS" | bc)
    echo -e "${CYAN}Taux de réussite: ${SUCCESS_RATE}%${NC}"
fi

echo ""
echo -e "${CYAN}Fichiers de sortie:${NC}"
echo "  - Résultats complets: $OUTPUT_FILE"

exit $FINAL_EXIT_CODE
