<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\CourseTypeController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\AppSettingController;
use App\Http\Controllers\Api\FileUploadController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\Student\DashboardController;
use App\Http\Controllers\Api\Student\PreferencesController;
use App\Http\Controllers\Api\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Api\ClubController;
use App\Http\Controllers\Api\FinancialDashboardController;
use App\Http\Controllers\Api\ClubSettingsController;
use App\Http\Controllers\Api\GraphAnalyticsController;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (sans middleware)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Routes pour les activités et disciplines (publiques)
Route::get('/activity-types', function() {
    $activities = App\Models\ActivityType::where('is_active', true)->get();
    return response()->json([
        'success' => true,
        'data' => $activities
    ]);
});

Route::get('/disciplines', function(Request $request) {
    $query = App\Models\Discipline::query();
    
    if ($request->has('activity_type_id')) {
        $query->where('activity_type_id', $request->activity_type_id);
    }
    
    $disciplines = $query->get();
    return response()->json([
        'success' => true,
        'data' => $disciplines
    ]);
});

Route::get('/test-search', function(Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'Route de test fonctionne',
        'query' => $request->query('query', 'aucun')
    ]);
});

Route::get('/search-users', function(Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'Route de test fonctionne',
        'query' => $request->query('query', 'aucun')
    ]);
});

// Routes pour les QR codes et ajout d'utilisateurs existants
Route::get('/qr-code/{user}', function(User $user) {
    $qrService = new App\Services\QrCodeService();
    return response()->json([
        'success' => true,
        'data' => $qrService->createQrData($user)
    ]);
});

Route::get('/qr-code/club/{club}', function(App\Models\Club $club) {
    $qrService = new App\Services\QrCodeService();
    return response()->json([
        'success' => true,
        'data' => $qrService->createClubQrData($club)
    ]);
});

Route::post('/qr-code/scan', function(Request $request) {
    $request->validate([
        'qr_code' => 'required|string'
    ]);

    $qrService = new App\Services\QrCodeService();
    
    // Try to find user first
    $user = $qrService->findUserByQrCode($request->qr_code);
    if ($user) {
        return response()->json([
            'success' => true,
            'type' => 'user',
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'profile' => $user->role === 'teacher' ? $user->teacher : $user->student
            ]
        ]);
    }

    // Try to find club
    $club = $qrService->findClubByQrCode($request->qr_code);
    if ($club) {
        return response()->json([
            'success' => true,
            'type' => 'club',
            'data' => [
                'club_id' => $club->id,
                'name' => $club->name,
                'email' => $club->email,
                'phone' => $club->phone,
                'address' => $club->address,
                'city' => $club->city,
                'description' => $club->description
            ]
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'QR code invalide'
    ], 404);
});

Route::post('/club/add-existing-teacher', function(Request $request) {
    try {
        $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'allowed_disciplines' => 'nullable|array',
            'restricted_disciplines' => 'nullable|array',
            'hourly_rate' => 'nullable|numeric|min:0',
            'contract_type' => 'required|string|in:volunteer,student,article_17,freelance,salaried',
        ]);

        $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $club = $user->clubs()->first();
        if (!$club) {
            return response()->json(['error' => 'Club not found'], 404);
        }

        $teacher = App\Models\Teacher::where('user_id', $request->teacher_id)->first();
        if (!$teacher) {
            return response()->json(['error' => 'Teacher profile not found'], 404);
        }

        // Vérifier si l'enseignant n'est pas déjà dans ce club
        if ($club->teachers()->where('teacher_id', $teacher->id)->exists()) {
            return response()->json(['error' => 'Teacher already in this club'], 409);
        }

        // Ajouter l'enseignant au club
        $club->teachers()->attach($teacher->id, [
            'allowed_disciplines' => json_encode($request->allowed_disciplines ?: []),
            'restricted_disciplines' => json_encode($request->restricted_disciplines ?: []),
            'hourly_rate' => $request->hourly_rate ?: $teacher->hourly_rate,
            'contract_type' => $request->contract_type,
            'is_active' => true,
            'joined_at' => now()
        ]);

        return response()->json([
            'message' => 'Enseignant ajouté au club avec succès',
            'teacher' => $teacher->load('user'),
            'club' => $club
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Erreur lors de l\'ajout de l\'enseignant',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::post('/club/add-existing-student', function(Request $request) {
    try {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'level' => 'nullable|string',
            'goals' => 'nullable|string',
            'medical_info' => 'nullable|string',
            'preferred_disciplines' => 'nullable|array'
        ]);

        $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $club = $user->clubs()->first();
        if (!$club) {
            return response()->json(['error' => 'Club not found'], 404);
        }

        $student = App\Models\Student::where('user_id', $request->student_id)->first();
        if (!$student) {
            return response()->json(['error' => 'Student profile not found'], 404);
        }

        // Vérifier si l'élève n'est pas déjà dans ce club
        if ($club->students()->where('student_id', $student->id)->exists()) {
            return response()->json(['error' => 'Student already in this club'], 409);
        }

        // Ajouter l'élève au club
        $club->students()->attach($student->id, [
            'level' => $request->level ?: $student->level,
            'goals' => $request->goals ?: $student->goals,
            'medical_info' => $request->medical_info ?: $student->medical_info,
            'preferred_disciplines' => json_encode($request->preferred_disciplines ?: []),
            'is_active' => true,
            'joined_at' => now()
        ]);

        return response()->json([
            'message' => 'Élève ajouté au club avec succès',
            'student' => $student->load('user'),
            'club' => $club
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Erreur lors de l\'ajout de l\'élève',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::get('/teacher/clubs', function() {
    try {
        $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $teacher = $user->teacher;
        if (!$teacher) {
            return response()->json(['error' => 'Teacher profile not found'], 404);
        }

        $clubs = $teacher->clubs()->wherePivot('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => $clubs
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Erreur lors du chargement des clubs',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Routes pour rejoindre un club via QR code
Route::post('/join-club', function(Request $request) {
    try {
        $request->validate([
            'qr_code' => 'required|string',
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:teacher,student',
            'level' => 'nullable|in:debutant,intermediaire,avance,expert',
            'goals' => 'nullable|string',
            'medical_info' => 'nullable|string',
            'preferred_disciplines' => 'nullable|array',
            'allowed_disciplines' => 'nullable|array',
            'restricted_disciplines' => 'nullable|array',
            'hourly_rate' => 'nullable|numeric'
        ]);

        $qrService = new App\Services\QrCodeService();
        $club = $qrService->findClubByQrCode($request->qr_code);

        if (!$club) {
            return response()->json([
                'success' => false,
                'message' => 'QR code de club invalide'
            ], 404);
        }

        $user = App\Models\User::find($request->user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        // Check if user is already in this club
        if ($request->role === 'teacher') {
            if ($club->teachers()->where('teacher_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous êtes déjà membre de ce club'
                ], 400);
            }

            // Attach teacher to club
            $club->teachers()->attach($user->id, [
                'allowed_disciplines' => json_encode($request->allowed_disciplines ?? []),
                'restricted_disciplines' => json_encode($request->restricted_disciplines ?? []),
                'hourly_rate' => $request->hourly_rate,
                'is_active' => true,
                'joined_at' => now()
            ]);
        } else {
            if ($club->students()->where('student_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous êtes déjà membre de ce club'
                ], 400);
            }

            // Attach student to club
            $club->students()->attach($user->id, [
                'level' => $request->level,
                'goals' => $request->goals,
                'medical_info' => $request->medical_info,
                'preferred_disciplines' => json_encode($request->preferred_disciplines ?? []),
                'is_active' => true,
                'joined_at' => now()
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Vous avez rejoint le club avec succès',
            'data' => [
                'club' => [
                    'id' => $club->id,
                    'name' => $club->name,
                    'email' => $club->email,
                    'phone' => $club->phone,
                    'address' => $club->address,
                    'city' => $club->city
                ],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ]
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'ajout au club: ' . $e->getMessage()
        ], 500);
    }
});

// Routes pour les spécialités personnalisées des clubs
Route::post('/club/custom-specialty', function(Request $request) {
    try {
        $request->validate([
            'activity_id' => 'required|exists:activity_types,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:15|max:180',
            'base_price' => 'nullable|numeric|min:0',
            'skill_levels' => 'nullable|array',
            'min_participants' => 'nullable|integer|min:1',
            'max_participants' => 'nullable|integer|min:1',
            'equipment_required' => 'nullable|array'
        ]);

        // Get the club from the authenticated user
        $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $club = $user->clubs()->first();
        if (!$club) {
            return response()->json(['error' => 'Club not found'], 404);
        }

        // Create the custom specialty
        $specialty = App\Models\ClubCustomSpecialty::create([
            'club_id' => $club->id,
            'activity_type_id' => $request->activity_id,
            'name' => $request->name,
            'description' => $request->description,
            'duration_minutes' => $request->duration_minutes ?? 60,
            'base_price' => $request->base_price ?? 0,
            'skill_levels' => $request->skill_levels ?? ['debutant'],
            'min_participants' => $request->min_participants ?? 1,
            'max_participants' => $request->max_participants ?? 8,
            'equipment_required' => $request->equipment_required ?? [],
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Spécialité personnalisée créée avec succès',
            'data' => $specialty->load('activityType')
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création de la spécialité: ' . $e->getMessage()
        ], 500);
    }
});

Route::get('/club/custom-specialties', function() {
    try {
        $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $club = $user->clubs()->first();
        if (!$club) {
            return response()->json(['error' => 'Club not found'], 404);
        }

        $specialties = $club->customSpecialties()->with('activityType')->get();

        return response()->json([
            'success' => true,
            'data' => $specialties
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des spécialités: ' . $e->getMessage()
        ], 500);
    }
});

// Activer/Désactiver une spécialité personnalisée
Route::patch('/club/custom-specialty/{specialty}/toggle', function (Request $request, $specialtyId) {
    try {
        $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $club = $user->clubs()->first();
        if (!$club) {
            return response()->json(['error' => 'Club not found'], 404);
        }

        $specialty = App\Models\ClubCustomSpecialty::where('id', $specialtyId)
            ->where('club_id', $club->id)
            ->first();

        if (!$specialty) {
            return response()->json(['error' => 'Specialty not found'], 404);
        }

        $specialty->is_active = !$specialty->is_active;
        $specialty->save();

        return response()->json([
            'success' => true,
            'message' => $specialty->is_active ? 'Spécialité activée' : 'Spécialité désactivée',
            'data' => $specialty
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la modification: ' . $e->getMessage()
        ], 500);
    }
});

// Mettre à jour une spécialité personnalisée
Route::put('/club/custom-specialty/{specialty}', function (Request $request, $specialtyId) {
    try {
        $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
        $club = $user->clubs()->first();

        $specialty = App\Models\ClubCustomSpecialty::where('id', $specialtyId)
            ->where('club_id', $club->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:15',
            'base_price' => 'required|numeric|min:0',
            'skill_levels' => 'required|array',
            'min_participants' => 'required|integer|min:1',
            'max_participants' => 'required|integer|min:1',
            'equipment_required' => 'nullable|array',
        ]);

        $specialty->update($validated);

        return response()->json(['success' => true, 'data' => $specialty->fresh()]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['success' => false, 'message' => 'Spécialité non trouvée ou non autorisée.'], 404);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['success' => false, 'message' => 'Données invalides.', 'errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()], 500);
    }
});

// Inscription enseignant (auto-inscription)
Route::post('/auth/register-teacher', function(Request $request) {
    try {
        // Validation
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'specializations' => 'nullable|array',
            'experience_years' => 'nullable|integer|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'bio' => 'nullable|string|max:1000'
        ]);

        // Créer l'utilisateur
        $user = App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => 'teacher',
            'status' => 'active',
            'is_active' => true,
            'can_act_as_teacher' => true,
            'can_act_as_student' => false,
            'is_admin' => false
        ]);

        // Créer le profil enseignant (sans club_id pour l'auto-inscription)
        $teacherProfile = App\Models\Teacher::create([
            'user_id' => $user->id,
            'club_id' => null, // Pas de club pour l'auto-inscription
            'specialties' => $request->specializations ?: [],
            'experience_years' => $request->experience_years ?: 0,
            'hourly_rate' => $request->hourly_rate ?: 50,
            'bio' => $request->bio ?: '',
            'is_available' => true,
            'rating' => 0,
            'total_lessons' => 0
        ]);

        return response()->json([
            'message' => 'Enseignant inscrit avec succès',
            'user' => $user,
            'teacherProfile' => $teacherProfile
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Erreur lors de l\'inscription',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Route pour récupérer l'utilisateur authentifié (avec middleware admin personnalisé)
Route::get('/auth/user-test', function(Request $request) {
    // Le middleware 'admin' s'occupe de l'authentification et de la vérification du rôle
    $user = $request->user();
    
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    $userData = $user->toArray();
    $userData['can_act_as_teacher'] = $user->canActAsTeacher();
    $userData['can_act_as_student'] = $user->canActAsStudent();
    $userData['is_admin'] = $user->isAdmin();
    
    return response()->json([
        'user' => $userData
    ]);
})->middleware('admin');

// Route temporaire pour mettre à jour le profil utilisateur (sans auth)
Route::put('/profile-test', function(Request $request) {
    $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    // Mettre à jour les données utilisateur
    $user->update([
        'name' => $request->name,
        'phone' => $request->phone,
        'date_of_birth' => $request->date_of_birth,
    ]);
    
    return response()->json([
        'message' => 'Profil mis à jour avec succès',
        'user' => $user->fresh()
    ]);
});

// Route temporaire pour récupérer le profil du club (sans auth)
Route::get('/club/profile-test', function() {
    $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    $club = $user->clubs()->first();
    if (!$club) {
        return response()->json(['error' => 'Club not found'], 404);
    }
    
    // Charger les activités et disciplines du club
    $club->load(['activityTypes', 'disciplines']);
    
    return response()->json([
        'user' => $user->toArray(),
        'club' => $club->toArray()
    ]);
});

// Route temporaire pour mettre à jour le profil du club (sans auth)
Route::put('/club/profile-test', function(Request $request) {
    $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    $club = $user->clubs()->first();
    if (!$club) {
        return response()->json(['error' => 'Club not found'], 404);
    }
    
    // Mettre à jour les données du club
    $club->update([
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'website' => $request->website,
        'description' => $request->description,
        'address' => $request->address,
        'city' => $request->city,
        'postal_code' => $request->postal_code,
        'country' => $request->country,
        'is_active' => $request->is_active !== false
    ]);
    
    // Mettre à jour les activités du club
    if ($request->has('activity_types')) {
        $club->activityTypes()->sync($request->activity_types);
    }
    
    // Mettre à jour les disciplines du club
    if ($request->has('disciplines')) {
        $club->disciplines()->sync($request->disciplines);
    }
    
    return response()->json([
        'message' => 'Profil du club mis à jour avec succès',
        'club' => $club->fresh()
    ]);
});

// Route temporaire pour tester les étudiants du club (sans auth)
Route::get('/club/students-test', function() {
    $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    $club = $user->clubs()->first();
    if (!$club) {
        return response()->json(['error' => 'Club not found'], 404);
    }
    
    $students = $club->users()->wherePivot('role', 'student')->get(['users.id', 'users.name', 'users.email', 'users.phone']);
    
    return response()->json([
        'club_id' => $club->id,
        'total_students' => $students->count(),
        'students' => $students->toArray()
    ]);
});

// Route temporaire pour tester la création d'étudiant (sans auth)
Route::post('/club/students-test', function(Request $request) {
    try {
        $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        $club = $user->clubs()->first();
        if (!$club) {
            return response()->json(['error' => 'Club not found'], 404);
        }
        
        // Validation simple
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email'
        ]);
        
        // Créer l'utilisateur étudiant
        $student = App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone ?? '',
            'role' => 'student',
            'status' => 'active',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);
        
        // Créer le profil étudiant
        $studentProfile = App\Models\Student::create([
            'user_id' => $student->id,
            'level' => $request->level ?: null,
            'goals' => $request->goals ?: null,
            'medical_info' => $request->medical_info ?: null,
            'club_id' => $club->id
        ]);
        
        // Associer l'étudiant au club via la relation pivot
        $club->users()->attach($student->id, [
            'role' => 'student',
            'is_admin' => false,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return response()->json([
            'message' => 'Étudiant créé avec succès',
            'student' => $student,
            'studentProfile' => $studentProfile
        ], 201);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Erreur lors de la création',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Route temporaire pour créer un étudiant (sans auth)
Route::post('/club/students', function(Request $request) {
    $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    $club = $user->clubs()->first();
    if (!$club) {
        return response()->json(['error' => 'Club not found'], 404);
    }
    
    // Valider les données requises
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'phone' => 'nullable|string|max:20',
        'level' => 'nullable|string|in:debutant,intermediaire,avance,expert',
        'goals' => 'nullable|string',
        'medical_info' => 'nullable|string',
        'disciplines' => 'nullable|array',
        'disciplines.*' => 'exists:disciplines,id'
    ]);
    
    // Créer l'utilisateur étudiant
    $student = App\Models\User::create([
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'role' => 'student',
        'status' => 'active',
        'password' => bcrypt('password123'), // Mot de passe temporaire
        'email_verified_at' => now()
    ]);
    
    // Créer le profil étudiant
    $studentProfile = App\Models\Student::create([
        'user_id' => $student->id,
        'level' => $request->level ?: null,
        'goals' => $request->goals ?: null,
        'medical_info' => $request->medical_info ?: null,
        'club_id' => $club->id
    ]);
    
    // Associer l'étudiant au club via la relation pivot
    $club->users()->attach($student->id, [
        'role' => 'student',
        'is_admin' => false,
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    // Associer les disciplines si fournies
    if ($request->has('disciplines') && is_array($request->disciplines)) {
        $studentProfile->disciplines()->sync($request->disciplines);
    }
    
    return response()->json([
        'message' => 'Étudiant créé avec succès',
        'student' => $student->load('studentProfile.disciplines')
    ], 201);
});

// Route temporaire pour tester la création d'enseignant (sans auth)
Route::post('/club/teachers-test', function(Request $request) {
    try {
        $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        $club = $user->clubs()->first();
        if (!$club) {
            return response()->json(['error' => 'Club not found'], 404);
        }
        
        // Validation simple
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email'
        ]);
        
        // Créer l'utilisateur enseignant
        $teacher = App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone ?? '',
            'role' => 'teacher',
            'status' => 'active',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);
        
        // Créer le profil enseignant
        $teacherProfile = App\Models\Teacher::create([
            'user_id' => $teacher->id,
            'club_id' => $club->id,
            'specialties' => $request->specializations ?: [],
            'experience_years' => $request->experience_years ?: 0,
            'hourly_rate' => $request->hourly_rate ?: 50,
            'bio' => $request->bio ?: '',
            'is_available' => true,
            'rating' => 0,
            'total_lessons' => 0
        ]);
        
        // Associer l'enseignant au club via la relation pivot
        $club->users()->attach($teacher->id, [
            'role' => 'teacher',
            'is_admin' => false,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return response()->json([
            'message' => 'Enseignant créé avec succès',
            'teacher' => $teacher,
            'teacherProfile' => $teacherProfile
        ], 201);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Erreur lors de la création',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Route temporaire pour créer un enseignant (sans auth)
Route::post('/club/teachers', function(Request $request) {
    $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    $club = $user->clubs()->first();
    if (!$club) {
        return response()->json(['error' => 'Club not found'], 404);
    }

    DB::beginTransaction();
    try {
        // Valider les données requises
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'specializations' => 'nullable|array',
            'experience_years' => 'nullable|integer|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'bio' => 'nullable|string',
            'contract_type' => 'required|string|in:volunteer,student,article_17,freelance,salaried',
        ]);
        
        // Créer l'utilisateur enseignant
        $teacherUser = App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => 'teacher',
            'status' => 'active',
            'password' => bcrypt('password123'), // Mot de passe temporaire
            'email_verified_at' => now()
        ]);
        
        // Créer le profil enseignant (sans club_id pour être compatible multi-club)
        $teacherProfile = App\Models\Teacher::create([
            'user_id' => $teacherUser->id,
            'club_id' => null, 
            'specialties' => $request->specializations ?: [],
            'experience_years' => $request->experience_years ?: 0,
            'hourly_rate' => $request->hourly_rate ?: 50,
            'bio' => $request->bio ?: '',
            'is_available' => true,
            'rating' => 0,
            'total_lessons' => 0
        ]);
        
        // Associer l'enseignant au club via la table pivot `club_teachers`
        $club->teachers()->attach($teacherProfile->id, [
            'allowed_disciplines' => json_encode($request->specializations ?: []),
            'restricted_disciplines' => json_encode([]),
            'hourly_rate' => $request->hourly_rate ?: $teacherProfile->hourly_rate,
            'contract_type' => $request->contract_type,
            'is_active' => true,
            'joined_at' => now()
        ]);

        DB::commit();
        
        return response()->json([
            'message' => 'Enseignant créé et ajouté au club avec succès',
            'teacher' => $teacherUser->load('teacherProfile')
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Erreur lors de la création de l\'enseignant',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Route temporaire pour lister les étudiants avec leurs IDs (sans auth)
Route::get('/club/students-list', function() {
    $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    $club = $user->clubs()->first();
    if (!$club) {
        return response()->json(['error' => 'Club not found'], 404);
    }
    
    $students = App\Models\Student::where('club_id', $club->id)
        ->with('user')
        ->get(['id', 'user_id'])
        ->map(function($student) {
            return [
                'student_id' => $student->id,
                'user_id' => $student->user_id,
                'name' => $student->user->name,
                'email' => $student->user->email
            ];
        });
    
    return response()->json([
        'students' => $students
    ]);
});

// Route temporaire pour tester la création d'enseignant (sans auth)
Route::post('/club/lessons-test', function(Request $request) {
    try {
        $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        $club = $user->clubs()->first();
        if (!$club) {
            return response()->json(['error' => 'Club not found'], 404);
        }
        
        // Validation simple
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'student_id' => 'required|exists:students,id',
            'start_time' => 'required|date',
            'duration' => 'required|integer|min:30',
            'price' => 'required|numeric|min:0'
        ]);
        
        // Utiliser des valeurs par défaut pour les champs optionnels
        $courseTypeId = $request->course_type_id ?: 1; // Premier type de cours
        $locationId = $request->location_id ?: 1; // Premier lieu
        
        // Calculer l'heure de fin
        $startTime = \Carbon\Carbon::parse($request->start_time);
        $endTime = $startTime->copy()->addMinutes($request->duration);
        
        // Créer le cours
        $lesson = App\Models\Lesson::create([
            'teacher_id' => $request->teacher_id,
            'student_id' => $request->student_id,
            'course_type_id' => $courseTypeId,
            'location_id' => $locationId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'price' => $request->price,
            'status' => 'pending',
            'notes' => $request->notes ?: ''
        ]);
        
        // Associer l'étudiant au cours
        $lesson->students()->attach($request->student_id, [
            'status' => 'pending',
            'price' => $request->price,
            'notes' => $request->notes ?: ''
        ]);
        
        return response()->json([
            'message' => 'Cours créé avec succès',
            'lesson' => $lesson
        ], 201);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Erreur lors de la création',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Route temporaire pour créer un cours (sans auth)
Route::post('/club/lessons', function(Request $request) {
    $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    $club = $user->clubs()->first();
    if (!$club) {
        return response()->json(['error' => 'Club not found'], 404);
    }
    
    // Valider les données requises
    $request->validate([
        'teacher_id' => 'required|exists:teachers,id',
        'student_id' => 'required|exists:students,id',
        'course_type_id' => 'required|exists:course_types,id',
        'location_id' => 'required|exists:locations,id',
        'start_time' => 'required|date',
        'duration' => 'required|integer|min:30',
        'price' => 'required|numeric|min:0',
        'notes' => 'nullable|string'
    ]);
    
    // Vérifier que l'enseignant appartient au club
    $teacher = App\Models\Teacher::where('id', $request->teacher_id)
        ->where('club_id', $club->id)
        ->first();
    
    if (!$teacher) {
        return response()->json(['error' => 'Teacher not found in this club'], 404);
    }
    
    // Vérifier que l'étudiant appartient au club
    $student = App\Models\Student::where('id', $request->student_id)
        ->where('club_id', $club->id)
        ->first();
    
    if (!$student) {
        return response()->json(['error' => 'Student not found in this club'], 404);
    }
    
    // Calculer l'heure de fin
    $startTime = \Carbon\Carbon::parse($request->start_time);
    $endTime = $startTime->copy()->addMinutes($request->duration);
    
    // Créer le cours
    $lesson = App\Models\Lesson::create([
        'teacher_id' => $request->teacher_id,
        'student_id' => $request->student_id,
        'course_type_id' => $request->course_type_id,
        'location_id' => $request->location_id,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'price' => $request->price,
        'status' => 'pending',
        'notes' => $request->notes
    ]);
    
    // Associer l'étudiant au cours via la relation many-to-many
    $lesson->students()->attach($request->student_id, [
        'status' => 'pending',
        'price' => $request->price,
        'notes' => $request->notes
    ]);
    
    return response()->json([
        'message' => 'Cours créé avec succès',
        'lesson' => $lesson->load(['teacher.user', 'student.user', 'courseType', 'location'])
    ], 201);
});

// Route temporaire pour uploader des documents médicaux d'un étudiant (sans auth)
Route::post('/club/students/{studentId}/medical-documents', function(Request $request, $studentId) {
    $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    $club = $user->clubs()->first();
    if (!$club) {
        return response()->json(['error' => 'Club not found'], 404);
    }
    
    $student = App\Models\Student::where('id', $studentId)->where('club_id', $club->id)->first();
    if (!$student) {
        return response()->json(['error' => 'Student not found'], 404);
    }
    
    $documents = [];
    
    if ($request->has('documents')) {
        foreach ($request->documents as $index => $documentData) {
            if (isset($documentData['file'])) {
                $file = $documentData['file'];
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('medical_documents', $fileName, 'public');
                
                $documents[] = App\Models\StudentMedicalDocument::create([
                    'student_id' => $student->id,
                    'document_type' => $documentData['document_type'],
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'expiry_date' => $documentData['expiry_date'] ?: null,
                    'renewal_frequency' => $documentData['renewal_frequency'] ?: null,
                    'notes' => $documentData['notes'] ?: null,
                    'is_active' => true
                ]);
            }
        }
    }
    
    return response()->json([
        'message' => 'Documents médicaux uploadés avec succès',
        'documents' => $documents
    ], 201);
});

// Route temporaire pour le profil utilisateur (sans auth)
Route::get('/profile-test', function() {
    $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    $userData = $user->toArray();
    $userData['can_act_as_teacher'] = $user->canActAsTeacher();
    $userData['can_act_as_student'] = $user->canActAsStudent();
    $userData['is_admin'] = $user->isAdmin();
    
    // Ajouter les données du club si c'est un utilisateur club
    if ($user->role === 'club') {
        $club = $user->clubs()->first();
        if ($club) {
            $userData['club'] = $club->toArray();
        }
    }
    
    return response()->json([
        'user' => $userData
    ]);
});

// Route temporaire pour le dashboard club (sans auth)
Route::get('/club/dashboard-test', function() {
    $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
    $club = $user->clubs()->first();
    
    $teacherUserIds = $club->users()->wherePivot('role', 'teacher')->pluck('users.id');
    $clubTeachers = App\Models\Teacher::whereIn('user_id', $teacherUserIds)->get();
    $teacherIds = $clubTeachers->pluck('id')->toArray();
    
    $totalLessons = App\Models\Lesson::whereIn('teacher_id', $teacherIds)->count();
    $completedLessons = App\Models\Lesson::whereIn('teacher_id', $teacherIds)->where('status', 'completed')->count();
    $pendingLessons = App\Models\Lesson::whereIn('teacher_id', $teacherIds)->where('status', 'pending')->count();
    $confirmedLessons = App\Models\Lesson::whereIn('teacher_id', $teacherIds)->where('status', 'confirmed')->count();
    $cancelledLessons = App\Models\Lesson::whereIn('teacher_id', $teacherIds)->where('status', 'cancelled')->count();
    
    $totalRevenue = App\Models\Payment::whereHas('lesson', function($query) use ($teacherIds) {
        $query->whereIn('teacher_id', $teacherIds);
    })->where('status', 'succeeded')->sum('amount');
    
    // Calculs supplémentaires
    $monthlyRevenue = App\Models\Payment::whereHas('lesson', function($query) use ($teacherIds) {
        $query->whereIn('teacher_id', $teacherIds);
    })->where('status', 'succeeded')
    ->whereMonth('created_at', now()->month)
    ->whereYear('created_at', now()->year)
    ->sum('amount');
    
    // Calcul du prix moyen basé sur les prix des cours, pas sur les revenus
    $averageLessonPrice = App\Models\Lesson::whereIn('teacher_id', $teacherIds)->avg('price') ?? 0;
    $averageLessonPrice = round($averageLessonPrice, 2);
    
    // Calcul du taux d'occupation (plus logique)
    $occupiedLessons = $completedLessons + $confirmedLessons; // Cours occupés
    $occupancyRate = $totalLessons > 0 ? round(($occupiedLessons / $totalLessons) * 100, 1) : 0;
    
    $recentTeachers = $clubTeachers->sortByDesc('created_at')->take(5)->values()->map(function ($teacher) {
        $user = $teacher->user;
        return [
            'id' => $teacher->id,
            'name' => $user ? $user->name : 'Enseignant ' . $teacher->id,
            'email' => $user ? $user->email : null,
            'phone' => $user ? $user->phone : null,
            'role' => 'teacher'
        ];
    });
    
    $recentStudents = $club->users()->wherePivot('role', 'student')
        ->orderBy('club_user.created_at', 'desc')
        ->take(5)
        ->get()
        ->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'phone' => $student->phone,
                'role' => 'student'
            ];
        });
    
    $recentLessons = App\Models\Lesson::whereIn('teacher_id', $teacherIds)
        ->with(['teacher.user', 'student.user', 'location'])
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get()
        ->map(function ($lesson) {
            $teacherName = $lesson->teacher && $lesson->teacher->user ? $lesson->teacher->user->name : 'Enseignant ' . $lesson->teacher_id;
            $studentName = $lesson->student && $lesson->student->user ? $lesson->student->user->name : 'Étudiant ' . $lesson->student_id;
            $locationName = $lesson->location ? $lesson->location->name : 'Lieu non défini';
            
            return [
                'id' => $lesson->id,
                'teacher_name' => $teacherName,
                'student_name' => $studentName,
                'location' => $locationName,
                'status' => $lesson->status,
                'created_at' => $lesson->created_at->format('d/m/Y H:i'),
                'start_time' => $lesson->start_time ? $lesson->start_time->format('d/m/Y H:i') : null,
                'end_time' => $lesson->end_time ? $lesson->end_time->format('d/m/Y H:i') : null
            ];
        });
    
    return response()->json([
        'success' => true,
        'data' => [
            'club' => [
                'id' => $club->id,
                'name' => $club->name,
                'email' => $club->email,
                'phone' => $club->phone,
                'address' => $club->address,
                'city' => $club->city,
                'postal_code' => $club->postal_code,
                'country' => $club->country,
                'description' => $club->description,
                'status' => $club->status,
                'is_active' => $club->is_active
            ],
            'stats' => [
                'total_teachers' => $clubTeachers->count(),
                'total_students' => $club->users()->wherePivot('role', 'student')->count(),
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'pending_lessons' => $pendingLessons,
                'confirmed_lessons' => $confirmedLessons,
                'cancelled_lessons' => $cancelledLessons,
                'total_revenue' => (float) $totalRevenue,
                'monthly_revenue' => (float) $monthlyRevenue,
                'average_lesson_price' => (float) $averageLessonPrice,
                'occupancy_rate' => (float) $occupancyRate
            ],
            'recentTeachers' => $recentTeachers,
            'recentStudents' => $recentStudents,
            'recentLessons' => $recentLessons
        ],
        'message' => 'Données du dashboard récupérées avec succès'
    ]);
});

// App settings publiques (pour le rebranding frontend)
Route::get('/app-settings/public', [AppSettingController::class, 'index']);

// Stripe webhook (pas d'authentification nécessaire)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // User routes
    Route::apiResource('users', UserController::class);

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'currentUserProfile']);
    Route::put('/profile', [ProfileController::class, 'updateCurrentUserProfile']);
    Route::apiResource('profiles', ProfileController::class);

    // Lesson routes  
    Route::apiResource('lessons', LessonController::class);

    // Course Type routes
    Route::apiResource('course-types', CourseTypeController::class);

    // Location routes
    Route::apiResource('locations', LocationController::class);

    // Payment routes
    Route::apiResource('payments', PaymentController::class);
    Route::post('/stripe/create-payment-intent', [StripeWebhookController::class, 'createPaymentIntent']);

    // App Settings routes (admin only for write operations)
    Route::apiResource('app-settings', AppSettingController::class);
    Route::post('/app-settings/{appSetting}/activate', [AppSettingController::class, 'activate']);

    // Teacher routes
    Route::get('/teachers', [UserController::class, 'teachers']);
    Route::get('/teachers/{id}/availability', [UserController::class, 'teacherAvailability']);
    Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'getDashboardData'])->middleware('teacher');

    // Student routes
    Route::get('/students', [UserController::class, 'students']);
    Route::get('/students/{id}/lessons', [LessonController::class, 'studentLessons']);

    // Student Dashboard
    Route::get('/student/dashboard/stats', [DashboardController::class, 'getStats']);

    // Student routes
    Route::prefix('student')->middleware('student')->group(function () {
        Route::get('/available-lessons', [DashboardController::class, 'getAvailableLessons']);
        Route::get('/bookings', [DashboardController::class, 'getBookings']);
        Route::post('/bookings', [DashboardController::class, 'createBooking']);
        Route::put('/bookings/{id}/cancel', [DashboardController::class, 'cancelBooking']);
        Route::get('/available-teachers', [DashboardController::class, 'getAvailableTeachers']);
        Route::get('/teachers/{id}/lessons', [DashboardController::class, 'getTeacherLessons']);
        Route::get('/stats', [DashboardController::class, 'getStats']);
        Route::get('/search-lessons', [DashboardController::class, 'searchLessons']);
        Route::get('/lesson-history', [DashboardController::class, 'getLessonHistory']);
        Route::post('/bookings/{id}/rate', [DashboardController::class, 'rateLesson']);
        Route::get('/favorite-teachers', [DashboardController::class, 'getFavoriteTeachers']);
        Route::post('/favorite-teachers/{id}/toggle', [DashboardController::class, 'toggleFavoriteTeacher']);
        Route::get('/teachers', [DashboardController::class, 'getTeachers']);
        Route::get('/preferences', [DashboardController::class, 'getPreferences']);
        Route::post('/preferences', [DashboardController::class, 'savePreferences']);
        
        // Nouvelles routes pour les préférences avancées
        Route::get('/disciplines', [PreferencesController::class, 'getDisciplines']);
        Route::get('/preferences/advanced', [PreferencesController::class, 'getPreferences']);
        Route::put('/preferences/advanced', [PreferencesController::class, 'updatePreferences']);
        Route::post('/preferences/advanced', [PreferencesController::class, 'addPreference']);
        Route::delete('/preferences/advanced', [PreferencesController::class, 'removePreference']);
        Route::get('/disciplines/{id}/course-types', [PreferencesController::class, 'getCourseTypesByDiscipline']);
    });

    // Teacher routes
    Route::prefix('teacher')->middleware('teacher')->group(function () {
        Route::get('/lessons', [TeacherDashboardController::class, 'getLessons']);
        Route::post('/lessons', [TeacherDashboardController::class, 'createLesson']);
        Route::put('/lessons/{id}', [TeacherDashboardController::class, 'updateLesson']);
        Route::delete('/lessons/{id}', [TeacherDashboardController::class, 'deleteLesson']);
        Route::get('/availabilities', [TeacherDashboardController::class, 'getAvailabilities']);
        Route::post('/availabilities', [TeacherDashboardController::class, 'createAvailability']);
        Route::put('/availabilities/{id}', [TeacherDashboardController::class, 'updateAvailability']);
        Route::delete('/availabilities/{id}', [TeacherDashboardController::class, 'deleteAvailability']);
        Route::get('/stats', [TeacherDashboardController::class, 'getStats']);
        Route::get('/students', [TeacherDashboardController::class, 'getStudents']);
    });

    // Club routes
    Route::prefix('club')->middleware('club')->group(function () {
        Route::get('/dashboard', [ClubController::class, 'dashboard']);
        Route::get('/teachers', [ClubController::class, 'teachers']);
        Route::get('/students', [ClubController::class, 'students']);
        Route::post('/teachers', [ClubController::class, 'addTeacher']);
        Route::post('/students', [ClubController::class, 'addStudent']);
        Route::put('/profile', [ClubController::class, 'updateClub']);
        Route::get('/profile', [ClubController::class, 'getClubProfile']);
        
        // Dashboard financier
        Route::get('/financial/overview', [FinancialDashboardController::class, 'getOverview']);
        Route::get('/financial/revenue-by-discipline', [FinancialDashboardController::class, 'getRevenueByDiscipline']);
        Route::get('/financial/revenue-by-period', [FinancialDashboardController::class, 'getRevenueByPeriod']);
        Route::get('/financial/ancillary-revenue', [FinancialDashboardController::class, 'getAncillaryRevenue']);
        Route::get('/financial/profitability', [FinancialDashboardController::class, 'getProfitabilityAnalysis']);
        
        // Paramètres du club
        Route::get('/settings', [ClubSettingsController::class, 'index']);
        Route::get('/settings/category/{category}', [ClubSettingsController::class, 'getByCategory']);
        Route::put('/settings/{featureKey}', [ClubSettingsController::class, 'update']);
        Route::put('/settings/bulk', [ClubSettingsController::class, 'bulkUpdate']);
        Route::get('/settings/available-features', [ClubSettingsController::class, 'getAvailableFeatures']);
        Route::post('/settings/reset', [ClubSettingsController::class, 'resetToDefaults']);
        
        // Analyses graphiques Neo4j
        Route::get('/graph/dashboard', [GraphAnalyticsController::class, 'getDashboard']);
        Route::get('/graph/network-stats', [GraphAnalyticsController::class, 'getNetworkStats']);
        Route::get('/graph/top-teachers', [GraphAnalyticsController::class, 'getTopTeachers']);
        Route::get('/graph/skills-network', [GraphAnalyticsController::class, 'getSkillsNetwork']);
        Route::get('/graph/student-progress', [GraphAnalyticsController::class, 'getStudentProgress']);
        Route::get('/graph/recommendations', [GraphAnalyticsController::class, 'getRecommendations']);
        Route::post('/graph/teacher-matching', [GraphAnalyticsController::class, 'getTeacherMatching']);
        Route::post('/graph/teacher-performance', [GraphAnalyticsController::class, 'getTeacherPerformance']);
        Route::post('/graph/predict-success', [GraphAnalyticsController::class, 'predictStudentSuccess']);
        Route::get('/graph/visualization', [GraphAnalyticsController::class, 'getGraphVisualization']);
        Route::post('/graph/sync', [GraphAnalyticsController::class, 'syncAllData']);
        Route::get('/graph/status', [GraphAnalyticsController::class, 'getStatus']);
    });

    // Upload de fichiers
    Route::post('/upload/avatar', [FileUploadController::class, 'uploadAvatar'])->name('upload.avatar');
    Route::post('/upload/certificate', [FileUploadController::class, 'uploadCertificate'])->name('upload.certificate');
    Route::delete('/upload/{path}', [FileUploadController::class, 'deleteFile'])->where('path', '.*')->name('upload.delete');

    // Upload de logo (admin seulement)
    Route::post('/upload/logo', [FileUploadController::class, 'uploadLogo'])->name('upload.logo');

});

// Administration (avec middleware admin personnalisé - évite Sanctum qui cause SIGSEGV)
// Le middleware 'admin' gère l'authentification via token et vérifie le rôle admin
    Route::prefix('admin')->middleware('admin')->group(function () {
    // SIMPLE TEST ROUTE
    Route::get('/test', function () {
        return response()->json(['success' => true, 'message' => 'Admin test route is working.']);
    });

        // Dashboard
        Route::get('/stats', [AdminController::class, 'getStats']);
        Route::get('/activities', [AdminController::class, 'getActivities']);

        // Users management
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::patch('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);

    // Contract Settings management - PLACED BEFORE GENERIC ROUTE
    Route::get('/settings/contracts', function (Illuminate\Http\Request $request) {
            // Le middleware 'admin' s'occupe de l'authentification et de la vérification du rôle
            $settings = App\Models\AppSetting::where('key', 'contract_parameters')->first();
            
            if (!$settings) {
                // Retourner les valeurs par défaut si aucun paramètre n'est sauvegardé
                $defaultSettings = [
                    'volunteer' => [
                        'active' => true,
                        'annual_ceiling' => 3900,
                        'daily_ceiling' => 42.31,
                        'mileage_allowance' => 0.4,
                        'max_annual_mileage' => 2000,
                    ],
                    'student' => [
                        'active' => false,
                        'annual_ceiling' => 0,
                        'daily_ceiling' => 0,
                    ],
                    'article17' => [
                        'active' => false,
                        'annual_ceiling' => 0,
                        'daily_ceiling' => 0,
                    ],
                    'freelance' => [
                        'active' => false,
                        'annual_ceiling' => 0,
                        'daily_ceiling' => 0,
                    ],
                    'salaried' => [
                        'active' => false,
                        'annual_ceiling' => 0,
                        'daily_ceiling' => 0,
                    ],
                ];
                return response()->json(['success' => true, 'data' => $defaultSettings]);
            }
            
            return response()->json(['success' => true, 'data' => json_decode($settings->value, true)]);
        });

    Route::put('/settings/contracts', function (Illuminate\Http\Request $request) {
            // Le middleware 'admin' s'occupe de l'authentification et de la vérification du rôle
            
            // Validation pour tous les types de contrats
            $validated = $request->validate([
                'volunteer.active' => 'boolean',
                'volunteer.annual_ceiling' => 'nullable|numeric|min:0',
                'volunteer.daily_ceiling' => 'nullable|numeric|min:0',
                'volunteer.mileage_allowance' => 'nullable|numeric|min:0',
                'volunteer.max_annual_mileage' => 'nullable|integer|min:0',
                
                'student.active' => 'boolean',
                'student.annual_ceiling' => 'nullable|numeric|min:0',
                'student.daily_ceiling' => 'nullable|numeric|min:0',
                
                'article17.active' => 'boolean',
                'article17.annual_ceiling' => 'nullable|numeric|min:0',
                'article17.daily_ceiling' => 'nullable|numeric|min:0',
                
                'freelance.active' => 'boolean',
                'freelance.annual_ceiling' => 'nullable|numeric|min:0',
                'freelance.daily_ceiling' => 'nullable|numeric|min:0',
                
                'salaried.active' => 'boolean',
                'salaried.annual_ceiling' => 'nullable|numeric|min:0',
                'salaried.daily_ceiling' => 'nullable|numeric|min:0',
            ]);

            // Préparer les données de configuration
            $settingsData = [
                'volunteer' => [
                    'active' => $validated['volunteer']['active'] ?? false,
                    'annual_ceiling' => $validated['volunteer']['annual_ceiling'] ?? 3900,
                    'daily_ceiling' => $validated['volunteer']['daily_ceiling'] ?? 42.31,
                    'mileage_allowance' => $validated['volunteer']['mileage_allowance'] ?? 0.4,
                    'max_annual_mileage' => $validated['volunteer']['max_annual_mileage'] ?? 2000,
                ],
                'student' => [
                    'active' => $validated['student']['active'] ?? false,
                    'annual_ceiling' => $validated['student']['annual_ceiling'] ?? 0,
                    'daily_ceiling' => $validated['student']['daily_ceiling'] ?? 0,
                ],
                'article17' => [
                    'active' => $validated['article17']['active'] ?? false,
                    'annual_ceiling' => $validated['article17']['annual_ceiling'] ?? 0,
                    'daily_ceiling' => $validated['article17']['daily_ceiling'] ?? 0,
                ],
                'freelance' => [
                    'active' => $validated['freelance']['active'] ?? false,
                    'annual_ceiling' => $validated['freelance']['annual_ceiling'] ?? 0,
                    'daily_ceiling' => $validated['freelance']['daily_ceiling'] ?? 0,
                ],
                'salaried' => [
                    'active' => $validated['salaried']['active'] ?? false,
                    'annual_ceiling' => $validated['salaried']['annual_ceiling'] ?? 0,
                    'daily_ceiling' => $validated['salaried']['daily_ceiling'] ?? 0,
                ],
            ];

            // Sauvegarder dans la base de données
            App\Models\AppSetting::updateOrCreate(
                ['key' => 'contract_parameters'],
                [
                    'value' => json_encode($settingsData), 
                    'type' => 'json',
                    'group' => 'contracts'
                ]
            );

            return response()->json([
                'success' => true, 
                'message' => 'Types de contrats mis à jour avec succès.',
                'data' => $settingsData
            ]);
    });

    // Exceedance Thresholds management
    Route::get('/settings/exceedance-thresholds', function (Illuminate\Http\Request $request) {
            // Le middleware 'admin' s'occupe de l'authentification et de la vérification du rôle
            $settings = App\Models\AppSetting::where('key', 'exceedance_thresholds')->first();
            
            if (!$settings) {
                // Retourner les valeurs par défaut si aucun paramètre n'est sauvegardé
                $defaultSettings = [
                    'orange' => 80,
                    'red' => 95
                ];
                return response()->json(['success' => true, 'data' => $defaultSettings]);
            }
            
            return response()->json(['success' => true, 'data' => json_decode($settings->value, true)]);
        });

    Route::put('/settings/exceedance-thresholds', function (Illuminate\Http\Request $request) {
            // Le middleware 'admin' s'occupe de l'authentification et de la vérification du rôle
            
            // Validation des seuils
            $validated = $request->validate([
                'orange' => 'required|integer|min:0|max:100',
                'red' => 'required|integer|min:0|max:100',
            ]);

            // Vérifier que le seuil rouge est supérieur au seuil orange
            if ($validated['red'] <= $validated['orange']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le seuil rouge doit être supérieur au seuil orange.'
                ], 422);
            }

            // Préparer les données de configuration
            $settingsData = [
                'orange' => $validated['orange'],
                'red' => $validated['red']
            ];

            // Sauvegarder dans la base de données
            App\Models\AppSetting::updateOrCreate(
                ['key' => 'exceedance_thresholds'],
                [
                    'value' => json_encode($settingsData), 
                    'type' => 'json',
                    'group' => 'exceedance'
                ]
            );

            return response()->json([
                'success' => true, 
                'message' => 'Paramètres de dépassement mis à jour avec succès.',
                'data' => $settingsData
            ]);
    });

    // Generic settings routes
        Route::get('/settings/{type}', [AdminController::class, 'getSettings']);
        Route::put('/settings/{type}', [AdminController::class, 'updateSettings']);
        Route::post('/upload-logo', [AdminController::class, 'uploadLogo']);

        // Clubs management
        Route::get('/clubs', [AdminController::class, 'getClubs']);
        Route::post('/clubs', [AdminController::class, 'createClub']);
        Route::get('/clubs/{id}', [AdminController::class, 'getClub']);
        Route::put('/clubs/{id}', [AdminController::class, 'updateClub']);
        Route::delete('/clubs/{id}', [AdminController::class, 'deleteClub']);
        Route::post('/clubs/{id}/toggle-status', [AdminController::class, 'toggleClubStatus']);

        // System management
        Route::get('/system/status', [AdminController::class, 'getSystemStatus']);
        Route::post('/system/clear-cache', [AdminController::class, 'clearCache']);
});

// Routes d'analyse Neo4j
Route::prefix('neo4j')->middleware('admin')->group(function () {
    Route::get('/metrics', [App\Http\Controllers\Api\Neo4jAnalysisController::class, 'getGlobalMetrics']);
    Route::get('/sync-stats', [App\Http\Controllers\Api\Neo4jAnalysisController::class, 'getSyncStats']);
    Route::get('/user-club-relations', [App\Http\Controllers\Api\Neo4jAnalysisController::class, 'analyzeUserClubRelations']);
    Route::get('/teachers-by-specialty', [App\Http\Controllers\Api\Neo4jAnalysisController::class, 'analyzeTeachersBySpecialty']);
    Route::get('/contracts', [App\Http\Controllers\Api\Neo4jAnalysisController::class, 'analyzeContracts']);
    Route::get('/most-connected-teachers', [App\Http\Controllers\Api\Neo4jAnalysisController::class, 'findMostConnectedTeachers']);
    Route::get('/geographic-distribution', [App\Http\Controllers\Api\Neo4jAnalysisController::class, 'analyzeGeographicDistribution']);
    Route::get('/teacher-club-relations', [App\Http\Controllers\Api\Neo4jAnalysisController::class, 'analyzeTeacherClubRelations']);
    Route::get('/contract-trends', [App\Http\Controllers\Api\Neo4jAnalysisController::class, 'analyzeContractTrends']);
    Route::get('/most-demanded-specialties', [App\Http\Controllers\Api\Neo4jAnalysisController::class, 'analyzeMostDemandedSpecialties']);
    Route::get('/club-performance', [App\Http\Controllers\Api\Neo4jAnalysisController::class, 'analyzeClubPerformance']);
    Route::post('/recommend-teachers', [App\Http\Controllers\Api\Neo4jAnalysisController::class, 'recommendTeachersForClub']);
    Route::post('/custom-query', [App\Http\Controllers\Api\Neo4jAnalysisController::class, 'executeCustomQuery']);
});
