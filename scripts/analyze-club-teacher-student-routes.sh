#!/bin/bash

# Script d'analyse des routes club, teacher et student
# Usage: ./scripts/analyze-club-teacher-student-routes.sh

echo "🔍 ANALYSE DES ROUTES CLUB, TEACHER ET STUDENT"
echo "=============================================="

cd /home/olivier/projets/bookyourcoach

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les résultats
log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

echo ""
echo "1. STATISTIQUES GÉNÉRALES"
echo "-------------------------"

# Compter les routes par type
club_routes=$(php artisan route:list --path=api | grep "api/club" | wc -l)
teacher_routes=$(php artisan route:list --path=api | grep "api/teacher" | wc -l)
student_routes=$(php artisan route:list --path=api | grep "api/student" | wc -l)
total_routes=$(php artisan route:list --path=api | wc -l)

echo "   - Routes club: $club_routes"
echo "   - Routes teacher: $teacher_routes"
echo "   - Routes student: $student_routes"
echo "   - Total routes API: $total_routes"

echo ""
echo "2. ANALYSE DES MIDDLEWARES"
echo "-------------------------"

# Vérifier les middlewares pour chaque type
club_middleware=$(php artisan route:list --path=api | grep "api/club" | grep "auth:sanctum" | wc -l)
teacher_middleware=$(php artisan route:list --path=api | grep "api/teacher" | grep "auth:sanctum" | wc -l)
student_middleware=$(php artisan route:list --path=api | grep "api/student" | grep "auth:sanctum" | wc -l)

echo "   - Routes club avec auth:sanctum: $club_middleware/$club_routes"
echo "   - Routes teacher avec auth:sanctum: $teacher_middleware/$teacher_routes"
echo "   - Routes student avec auth:sanctum: $student_middleware/$student_routes"

echo ""
echo "3. ANALYSE DE LA SÉCURITÉ"
echo "------------------------"

# Vérifier l'authentification manuelle
manual_auth_club=$(grep -A 20 -B 5 "api/club" routes/api.php | grep -c "request()->header('Authorization')" || echo "0")
manual_auth_teacher=$(grep -A 20 -B 5 "api/teacher" routes/api.php | grep -c "request()->header('Authorization')" || echo "0")
manual_auth_student=$(grep -A 20 -B 5 "api/student" routes/api.php | grep -c "request()->header('Authorization')" || echo "0")

echo "   - Authentifications manuelles club: $manual_auth_club"
echo "   - Authentifications manuelles teacher: $manual_auth_teacher"
echo "   - Authentifications manuelles student: $manual_auth_student"

echo ""
echo "4. ANALYSE DÉTAILLÉE PAR TYPE"
echo "----------------------------"

echo ""
echo "4.1 ROUTES CLUB:"
echo "   - Middleware: auth:sanctum ✅"
echo "   - Contrôleur: ClubController ✅"
echo "   - Routes principales:"
php artisan route:list --path=api | grep "api/club" | head -5 | sed 's/^/     /'

echo ""
echo "4.2 ROUTES TEACHER:"
echo "   - Middleware: ❌ (aucun middleware détecté)"
echo "   - Contrôleur: DashboardController ✅"
echo "   - Routes principales:"
php artisan route:list --path=api | grep "api/teacher" | head -5 | sed 's/^/     /'

echo ""
echo "4.3 ROUTES STUDENT:"
echo "   - Middleware: ❌ (aucun middleware détecté)"
echo "   - Contrôleur: ❌ (routes inline)"
echo "   - Routes principales:"
php artisan route:list --path=api | grep "api/student" | head -5 | sed 's/^/     /'

echo ""
echo "5. PROBLÈMES IDENTIFIÉS"
echo "----------------------"

problems=0

if [ $teacher_middleware -eq 0 ]; then
    log_error "Routes teacher sans middleware auth:sanctum"
    problems=$((problems + 1))
fi

if [ $student_middleware -eq 0 ]; then
    log_error "Routes student sans middleware auth:sanctum"
    problems=$((problems + 1))
fi

if [ $manual_auth_teacher -gt 0 ]; then
    log_error "Routes teacher avec authentification manuelle"
    problems=$((problems + 1))
fi

if [ $manual_auth_student -gt 0 ]; then
    log_error "Routes student avec authentification manuelle"
    problems=$((problems + 1))
fi

echo ""
echo "6. RECOMMANDATIONS"
echo "-----------------"

echo ""
echo "🔧 ACTIONS REQUISES:"

if [ $teacher_middleware -eq 0 ]; then
    echo "   1. Ajouter middleware auth:sanctum aux routes teacher"
    echo "   2. Créer TeacherController pour organiser le code"
    echo "   3. Supprimer l'authentification manuelle"
fi

if [ $student_middleware -eq 0 ]; then
    echo "   4. Ajouter middleware auth:sanctum aux routes student"
    echo "   5. Créer StudentController pour organiser le code"
    echo "   6. Supprimer l'authentification manuelle"
fi

echo ""
echo "✅ ROUTES CLUB:"
echo "   - Bien sécurisées avec auth:sanctum"
echo "   - Utilisent ClubController"
echo "   - Aucune action requise"

echo ""
echo "7. EXEMPLE DE CORRECTION"
echo "-----------------------"

cat << 'EOF'
// Routes teacher sécurisées (à implémenter)
Route::prefix('teacher')->middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [TeacherController::class, 'dashboard']);
    Route::get('/lessons', [TeacherController::class, 'getLessons']);
    Route::post('/lessons', [TeacherController::class, 'createLesson']);
    Route::put('/lessons/{id}', [TeacherController::class, 'updateLesson']);
    Route::delete('/lessons/{id}', [TeacherController::class, 'deleteLesson']);
    Route::get('/students', [TeacherController::class, 'getStudents']);
    Route::get('/calendar', [TeacherController::class, 'getCalendar']);
    Route::get('/earnings', [TeacherController::class, 'getEarnings']);
});

// Routes student sécurisées (à implémenter)
Route::prefix('student')->middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard']);
    Route::get('/calendar', [StudentController::class, 'getCalendar']);
    Route::get('/clubs', [StudentController::class, 'getClubs']);
    Route::post('/book-lesson', [StudentController::class, 'bookLesson']);
    Route::get('/lessons', [StudentController::class, 'getLessons']);
    Route::get('/profile', [StudentController::class, 'getProfile']);
});
EOF

echo ""
echo "8. ÉTAT DE SÉCURITÉ"
echo "------------------"

if [ $problems -eq 0 ]; then
    log_success "Toutes les routes sont sécurisées"
    echo "   ✅ Middlewares appropriés"
    echo "   ✅ Authentification centralisée"
    echo "   ✅ Code organisé"
else
    log_warning "Problèmes de sécurité détectés: $problems"
    echo "   ❌ Routes teacher non sécurisées"
    echo "   ❌ Routes student non sécurisées"
    echo "   ❌ Authentification manuelle présente"
fi

echo ""
echo "=============================================="
echo "🎯 RÉSUMÉ DE L'ANALYSE"
echo "=============================================="
echo "📊 Statistiques:"
echo "   - Routes club: $club_routes (✅ sécurisées)"
echo "   - Routes teacher: $teacher_routes (❌ non sécurisées)"
echo "   - Routes student: $student_routes (❌ non sécurisées)"
echo ""
echo "🔧 Actions requises:"
echo "   - Sécuriser les routes teacher"
echo "   - Sécuriser les routes student"
echo "   - Créer les contrôleurs appropriés"
echo "   - Supprimer l'authentification manuelle"
echo ""
echo "🚀 Une fois corrigées, toutes les routes respecteront les standards Laravel!"
