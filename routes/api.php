<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\ClubController;
use App\Http\Controllers\Api\ClubDashboardController;
use App\Http\Controllers\Api\ClubOpenSlotController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SubscriptionTemplateController;
use App\Http\Controllers\Api\AdminDashboardController;

// Health check endpoint pour Docker healthcheck
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toDateTimeString(),
    ], 200);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', function (Request $request) {
            return response()->json([
                'user' => $request->user(),
            ]);
        });
        Route::get('/debug-user', function (Request $request) {
            return response()->json([
                'user' => $request->user(),
                'role' => $request->user()->role,
                'isAuthenticated' => Auth::check(),
            ]);
        });
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Routes AdminDashboardController (priorité sur AdminController pour certaines routes)
    Route::get('/dashboard', [AdminDashboardController::class, 'dashboard']);
    Route::get('/users', [AdminDashboardController::class, 'users']); // Utilise AdminDashboardController::users au lieu de AdminController::getUsers
    Route::put('/users/{id}/status', [AdminDashboardController::class, 'updateUserStatus']);
    
    // Routes AdminController (autres routes admin)
    Route::get('/stats', [AdminController::class, 'getStats']);
    Route::get('/users/{id}', [AdminController::class, 'getUser']);
    Route::post('/users', [AdminController::class, 'createUser']);
    Route::put('/users/{id}', [AdminController::class, 'updateUser']);
    Route::patch('/users/{id}/role', [AdminController::class, 'updateUserRole']);
    Route::post('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);
    Route::post('/users/{id}/create-club', [AdminController::class, 'createClubForUser']);
    Route::get('/activities', [AdminController::class, 'getActivities']);
    Route::get('/settings', [AdminController::class, 'getAllSettings']);
    Route::put('/settings', [AdminController::class, 'updateAllSettings']);
    Route::get('/settings/{type}', [AdminController::class, 'getSettings']);
    Route::put('/settings/{type}', [AdminController::class, 'updateSettings']);
    Route::get('/system-status', [AdminController::class, 'getSystemStatus']);
    Route::post('/clear-cache', [AdminController::class, 'clearCache']);
    Route::get('/audit-logs', [AdminController::class, 'getAuditLogs']);
    Route::get('/clubs', [AdminController::class, 'getClubs']);
    Route::post('/clubs', [AdminController::class, 'createClub']);
    Route::get('/clubs/{id}', [AdminController::class, 'getClub']);
    Route::put('/clubs/{id}', [AdminController::class, 'updateClub']);
    Route::delete('/clubs/{id}', [AdminController::class, 'deleteClub']);
    Route::post('/clubs/{id}/toggle-status', [AdminController::class, 'toggleClubStatus']);
    Route::post('/clubs/upload-logo', [AdminController::class, 'uploadLogo']);
    
    // Routes pour les rapports de paie
    Route::prefix('payroll')->group(function () {
        Route::get('/reports', [App\Http\Controllers\Api\PayrollController::class, 'getReports']);
        Route::post('/generate', [App\Http\Controllers\Api\PayrollController::class, 'generate']);
        Route::get('/reports/{year}/{month}', [App\Http\Controllers\Api\PayrollController::class, 'getReportDetails']);
        Route::get('/export/{year}/{month}/csv', [App\Http\Controllers\Api\PayrollController::class, 'exportCsv']);
    });
});

Route::middleware(['auth:sanctum', 'teacher'])->prefix('teacher')->group(function () {
    Route::get('/dashboard', [TeacherController::class, 'dashboard']);
    Route::get('/dashboard-simple', [TeacherController::class, 'dashboardSimple']);
    Route::get('/profile', [TeacherController::class, 'getProfile']); // Profil de l'enseignant
    Route::put('/profile', [TeacherController::class, 'updateProfile']); // Mise à jour du profil
    Route::get('/lessons', [App\Http\Controllers\Api\LessonController::class, 'index']);
    Route::post('/lessons', [App\Http\Controllers\Api\LessonController::class, 'store']);
    Route::put('/lessons/{id}', [App\Http\Controllers\Api\LessonController::class, 'update']);
    Route::delete('/lessons/{id}', [App\Http\Controllers\Api\LessonController::class, 'destroy']);
    Route::get('/lesson-replacements', [App\Http\Controllers\Api\LessonReplacementController::class, 'index']);
    Route::post('/lesson-replacements', [App\Http\Controllers\Api\LessonReplacementController::class, 'store']);
    Route::post('/lesson-replacements/{id}/respond', [App\Http\Controllers\Api\LessonReplacementController::class, 'respond']);
    Route::delete('/lesson-replacements/{id}', [App\Http\Controllers\Api\LessonReplacementController::class, 'cancel']);
    Route::get('/teachers', [App\Http\Controllers\Api\TeacherController::class, 'index']); // Liste des autres enseignants
    Route::get('/students', [App\Http\Controllers\Api\TeacherController::class, 'getStudents']); // Liste des élèves
    Route::get('/students/{id}', [App\Http\Controllers\Api\TeacherController::class, 'getStudent']); // Détails d'un élève
    Route::get('/clubs', [App\Http\Controllers\Api\TeacherController::class, 'getClubs']); // Liste des clubs
    Route::get('/earnings', [App\Http\Controllers\Api\TeacherController::class, 'getEarnings']); // Revenus de l'enseignant
    
    // Notifications
    Route::get('/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
});

Route::middleware(['auth:sanctum', 'student'])->prefix('student')->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard']);
    
    // Profil
    Route::get('/profile', [StudentController::class, 'getProfile']);
    Route::put('/profile', [StudentController::class, 'updateProfile']);
    
    // Clubs de l'élève
    Route::get('/clubs', [StudentController::class, 'getClubs']);
    
    // Statistiques du dashboard
    Route::get('/dashboard/stats', [App\Http\Controllers\Api\Student\DashboardController::class, 'getStats']);
    
    // Cours disponibles
    Route::get('/available-lessons', [App\Http\Controllers\Api\Student\DashboardController::class, 'getAvailableLessons']);
    
    // Historique des cours
    Route::get('/lesson-history', [App\Http\Controllers\Api\Student\DashboardController::class, 'getLessonHistory']);
    
    // Réservations
    Route::get('/bookings', [App\Http\Controllers\Api\Student\DashboardController::class, 'getBookings']);
    Route::post('/bookings', [App\Http\Controllers\Api\Student\DashboardController::class, 'createBooking']);
    Route::put('/bookings/{id}/cancel', [App\Http\Controllers\Api\Student\DashboardController::class, 'cancelBooking']);
    
    // Préférences
    Route::get('/disciplines', [App\Http\Controllers\Api\Student\PreferencesController::class, 'getDisciplines']);
    Route::get('/preferences/advanced', [App\Http\Controllers\Api\Student\PreferencesController::class, 'getPreferences']);
    Route::post('/preferences/advanced', [App\Http\Controllers\Api\Student\PreferencesController::class, 'addPreference']);
    Route::put('/preferences/advanced', [App\Http\Controllers\Api\Student\PreferencesController::class, 'updatePreferences']);
    Route::delete('/preferences/advanced', [App\Http\Controllers\Api\Student\PreferencesController::class, 'removePreference']);
    
    // Abonnements
    Route::get('/subscriptions/available', [App\Http\Controllers\Api\StudentSubscriptionController::class, 'availableSubscriptions']);
    Route::get('/subscriptions', [App\Http\Controllers\Api\StudentSubscriptionController::class, 'mySubscriptions']);
    Route::post('/subscriptions/create-checkout-session', [App\Http\Controllers\Api\StudentSubscriptionController::class, 'createCheckoutSession']);
    Route::post('/subscriptions', [App\Http\Controllers\Api\StudentSubscriptionController::class, 'subscribe']);
    Route::post('/subscriptions/{instanceId}/renew', [App\Http\Controllers\Api\StudentSubscriptionController::class, 'renew']);
    
    // Paiement à la séance
    Route::post('/payments/create-intent', [App\Http\Controllers\Api\StripeWebhookController::class, 'createPaymentIntent']);
    Route::post('/payments/create-lesson-checkout', [App\Http\Controllers\Api\PaymentController::class, 'createLessonCheckoutSession']);
});

// Webhook Stripe (route publique, sans authentification)
Route::post('/stripe/webhook', [App\Http\Controllers\Api\StripeWebhookController::class, 'handleWebhook']);

// Routes publiques
Route::get('/activity-types', function() {
    return response()->json([
        'success' => true,
        'data' => [
            ['id' => 1, 'name' => 'Équitation', 'icon' => 'horse', 'description' => 'Sports équestres et monte à cheval'],
            ['id' => 2, 'name' => 'Natation', 'icon' => 'swimmer', 'description' => 'Sports aquatiques et natation'],
            ['id' => 3, 'name' => 'Fitness', 'icon' => 'dumbbell', 'description' => 'Musculation et remise en forme'],
            ['id' => 4, 'name' => 'Sports collectifs', 'icon' => 'futbol', 'description' => 'Football, basketball, volleyball'],
            ['id' => 5, 'name' => 'Arts martiaux', 'icon' => 'fist-raised', 'description' => 'Karaté, judo, taekwondo'],
            ['id' => 6, 'name' => 'Danse', 'icon' => 'music', 'description' => 'Danse classique, moderne, hip-hop'],
            ['id' => 7, 'name' => 'Tennis', 'icon' => 'table-tennis', 'description' => 'Tennis de table et tennis'],
            ['id' => 8, 'name' => 'Gymnastique', 'icon' => 'child', 'description' => 'Gymnastique artistique et rythmique'],
        ]
    ]);
});

// Disciplines - Route publique (utilisée par le profil club)
Route::get('/disciplines', [App\Http\Controllers\Api\DisciplineController::class, 'index']);
Route::get('/disciplines/{id}', [App\Http\Controllers\Api\DisciplineController::class, 'show']);
Route::get('/disciplines/by-activity/{activityTypeId}', [App\Http\Controllers\Api\DisciplineController::class, 'byActivityType']);

// Clubs - Route publique pour l'inscription (liste des clubs actifs)
Route::get('/clubs/public', function() {
    $clubs = \App\Models\Club::where('is_active', true)
        ->select('id', 'name', 'city', 'postal_code')
        ->orderBy('name')
        ->get();
    
    return response()->json([
        'success' => true,
        'data' => $clubs
    ]);
});

Route::middleware(['auth:sanctum', 'club'])->prefix('club')->group(function () {
    Route::get('/dashboard', [ClubDashboardController::class, 'dashboard']);
    Route::get('/qr-code', function(Request $request) {
        $user = $request->user();
        $club = $user->getFirstClub();
        if (!$club) {
            return response()->json(['success' => false, 'message' => 'Club non trouvé'], 404);
        }
        return app(\App\Http\Controllers\Api\QrCodeController::class)->getClubQrCode($club->id);
    });
    Route::get('/diagnose-columns', [ClubController::class, 'diagnoseColumns']); // Diagnostic
    Route::get('/profile', [ClubController::class, 'getProfile']);
    Route::put('/profile', [ClubController::class, 'updateProfile']);
    Route::get('/custom-specialties', [ClubController::class, 'getCustomSpecialties']);
    Route::get('/teachers', [ClubController::class, 'getTeachers']);
    Route::post('/teachers', [ClubController::class, 'createTeacher']);
    Route::put('/teachers/{teacherId}', [ClubController::class, 'updateTeacher']);
    Route::delete('/teachers/{teacherId}', [ClubController::class, 'deleteTeacher']);
    Route::post('/teachers/{teacherId}/resend-invitation', [ClubController::class, 'resendTeacherInvitation']);
    Route::get('/students', [ClubController::class, 'getStudents']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::get('/students/{studentId}/history', [StudentController::class, 'history']);
    Route::patch('/students/{studentId}/toggle-status', [StudentController::class, 'toggleStatus']);
    Route::post('/students/{studentId}/resend-invitation', [StudentController::class, 'resendInvitation']);
    Route::put('/students/{studentId}', [StudentController::class, 'update']);
    Route::delete('/students/{studentId}', [ClubController::class, 'removeStudent']);
    // Abonnements du club
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    Route::post('/subscriptions', [SubscriptionController::class, 'store']);
    Route::get('/subscriptions/{id}', [SubscriptionController::class, 'show']);
    Route::put('/subscriptions/{id}', [SubscriptionController::class, 'update']);
    Route::delete('/subscriptions/{id}', [SubscriptionController::class, 'destroy']);
    // Créneaux ouverts
    Route::get('/open-slots', [ClubOpenSlotController::class, 'index']);
    Route::post('/open-slots', [ClubOpenSlotController::class, 'store']);
    Route::get('/open-slots/{id}', [ClubOpenSlotController::class, 'show']);
    Route::put('/open-slots/{id}', [ClubOpenSlotController::class, 'update']);
    Route::delete('/open-slots/{id}', [ClubOpenSlotController::class, 'destroy']);
    // Lettres de volontariat
    Route::post('/volunteer-letters/send/{teacherId}', [\App\Http\Controllers\Api\VolunteerLetterController::class, 'sendToTeacher']);
    Route::post('/volunteer-letters/send-all', [\App\Http\Controllers\Api\VolunteerLetterController::class, 'sendToAll']);
    Route::get('/volunteer-letters/history', [\App\Http\Controllers\Api\VolunteerLetterController::class, 'history']);
    // Gestion des types de cours pour les créneaux
    Route::put('/open-slots/{id}/course-types', [ClubOpenSlotController::class, 'updateCourseTypes']);
    
    // Gestion des créneaux récurrents (réservations d'abonnements)
    Route::get('/recurring-slots', [\App\Http\Controllers\Api\RecurringSlotController::class, 'index']);
    Route::get('/recurring-slots/{id}', [\App\Http\Controllers\Api\RecurringSlotController::class, 'show']);
    Route::post('/recurring-slots/{id}/release', [\App\Http\Controllers\Api\RecurringSlotController::class, 'release']);
    Route::post('/recurring-slots/{id}/reactivate', [\App\Http\Controllers\Api\RecurringSlotController::class, 'reactivate']);
    
    // Planning avancé (suggestions, statistiques, vérifications)
    Route::post('/planning/suggest-optimal-slot', [App\Http\Controllers\Api\ClubPlanningController::class, 'suggestOptimalSlot']);
    Route::post('/planning/check-availability', [App\Http\Controllers\Api\ClubPlanningController::class, 'checkAvailability']);
    Route::get('/planning/statistics', [App\Http\Controllers\Api\ClubPlanningController::class, 'getStatistics']);
    // Modèles d'abonnements
    Route::get('/subscription-templates', [App\Http\Controllers\Api\SubscriptionTemplateController::class, 'index']);
    Route::post('/subscription-templates', [App\Http\Controllers\Api\SubscriptionTemplateController::class, 'store']);
    Route::put('/subscription-templates/{id}', [App\Http\Controllers\Api\SubscriptionTemplateController::class, 'update']);
    Route::delete('/subscription-templates/{id}', [App\Http\Controllers\Api\SubscriptionTemplateController::class, 'destroy']);
    
    // Abonnements (créés depuis les modèles)
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    Route::post('/subscriptions', [SubscriptionController::class, 'store']);
    // Routes spécifiques AVANT les routes génériques avec {id}
    Route::post('/subscriptions/assign', [SubscriptionController::class, 'assignToStudent']);
    Route::post('/subscriptions/recalculate', [SubscriptionController::class, 'recalculateAll']);
    Route::post('/subscriptions/{instanceId}/close', [SubscriptionController::class, 'close']);
    Route::put('/subscriptions/{instanceId}/est-legacy', [SubscriptionController::class, 'updateEstLegacy']);
    Route::put('/subscriptions/instances/{instanceId}', [SubscriptionController::class, 'updateInstance']);
    Route::get('/subscriptions/instances/{instanceId}/history', [SubscriptionController::class, 'getInstanceHistory']);
    Route::get('/subscription-instances/{instanceId}/future-lessons', [SubscriptionController::class, 'getFutureLessons']);
    Route::get('/subscriptions/{id}', [SubscriptionController::class, 'show']);
    Route::delete('/subscriptions/{id}', [SubscriptionController::class, 'destroy']);
    // Route spécifique AVANT les routes génériques students/{id}
    Route::get('/students/{studentId}/subscriptions', [SubscriptionController::class, 'studentSubscriptions']);
    Route::post('/subscriptions/{instanceId}/renew', [SubscriptionController::class, 'renew']);
    // Analyse prédictive IA
    Route::get('/predictive-analysis', [App\Http\Controllers\Api\PredictiveAnalysisController::class, 'getAnalysis']);
    Route::get('/predictive-analysis/alerts', [App\Http\Controllers\Api\PredictiveAnalysisController::class, 'getCriticalAlerts']);
    
    // Notifications
    Route::get('/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    
    // Routes pour les rapports de paie du club
    Route::prefix('payroll')->group(function () {
        Route::get('/reports', [App\Http\Controllers\Api\ClubPayrollController::class, 'getReports']);
        Route::post('/generate', [App\Http\Controllers\Api\ClubPayrollController::class, 'generate']);
        Route::get('/reports/{year}/{month}', [App\Http\Controllers\Api\ClubPayrollController::class, 'getReportDetails']);
        Route::post('/reports/{year}/{month}/reload', [App\Http\Controllers\Api\ClubPayrollController::class, 'reloadReport']);
        Route::get('/reports/{year}/{month}/teachers/{teacherId}/payments', [App\Http\Controllers\Api\ClubPayrollController::class, 'getTeacherPaymentsDetails']);
        Route::put('/reports/{year}/{month}/teachers/{teacherId}/payments', [App\Http\Controllers\Api\ClubPayrollController::class, 'updatePayments']);
        Route::get('/export/{year}/{month}/csv', [App\Http\Controllers\Api\ClubPayrollController::class, 'exportCsv']);
    });
});

// Routes pour les types de cours - accessibles à tous les utilisateurs authentifiés
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/course-types', [App\Http\Controllers\Api\CourseTypeController::class, 'index']);
});

// Routes QR Code - accessibles aux utilisateurs authentifiés
Route::middleware(['auth:sanctum'])->prefix('qr-code')->group(function () {
    Route::get('/user/{userId}', [App\Http\Controllers\Api\QrCodeController::class, 'getUserQrCode']);
    Route::get('/club/{clubId}', [App\Http\Controllers\Api\QrCodeController::class, 'getClubQrCode']);
    Route::post('/club/{clubId}/regenerate', [App\Http\Controllers\Api\QrCodeController::class, 'regenerateClubQrCode']);
    Route::post('/scan', [App\Http\Controllers\Api\QrCodeController::class, 'scanQrCode']);
});

// Routes pour les cours (lessons) - accessibles aux clubs, enseignants et étudiants
Route::middleware(['auth:sanctum'])->group(function () {
    // IMPORTANT: Les routes spécifiques AVANT les routes avec paramètres dynamiques {id}
    Route::get('/lessons/slot-occupants', [App\Http\Controllers\Api\LessonController::class, 'getSlotOccupants']);
    
    Route::get('/lessons', [App\Http\Controllers\Api\LessonController::class, 'index']);
    Route::post('/lessons', [App\Http\Controllers\Api\LessonController::class, 'store']);
    Route::get('/lessons/{id}', [App\Http\Controllers\Api\LessonController::class, 'show']);
    Route::put('/lessons/{id}', [App\Http\Controllers\Api\LessonController::class, 'update']);
    Route::put('/lessons/{id}/subscription', [App\Http\Controllers\Api\LessonController::class, 'updateSubscription']);
    Route::delete('/lessons/{id}', [App\Http\Controllers\Api\LessonController::class, 'destroy']);
    Route::post('/lessons/{id}/cancel-with-future', [App\Http\Controllers\Api\LessonController::class, 'cancelWithFuture']);
});

// Routes de debug (accessibles à tous les utilisateurs authentifiés)
Route::middleware(['auth:sanctum'])->prefix('debug')->group(function () {
    Route::get('/course-types-filtering', [App\Http\Controllers\Api\DebugController::class, 'checkCourseTypesFiltering']);
    Route::get('/slot/{id}', [App\Http\Controllers\Api\DebugController::class, 'checkSlot']);
});
