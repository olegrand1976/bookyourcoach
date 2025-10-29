<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use App\Models\Teacher;
use App\Notifications\TeacherWelcomeNotification;

class ClubController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_teachers' => 0,
                    'total_students' => 0,
                    'total_lessons' => 0,
                    'completed_lessons' => 0,
                    'total_revenue' => 0,
                    'monthly_revenue' => 0,
                ],
                'recentTeachers' => [],
                'recentStudents' => [],
                'recentLessons' => [],
            ],
            'message' => 'Données du dashboard récupérées avec succès'
        ]);
    }

    public function getProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            // Log pour debugging
            \Log::info('ClubController::getProfile - User:', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);
            
            // Récupérer le club associé à cet utilisateur
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->first();
            
            if (!$clubUser) {
                \Log::warning('ClubController::getProfile - Aucun club_manager trouvé', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                
                // Si l'utilisateur a le rôle 'club' mais n'est pas dans club_managers,
                // retourner un profil par défaut plutôt qu'une erreur 404
                if ($user->role === 'club') {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'id' => null,
                            'name' => $user->name ?? 'Mon Club',
                            'email' => $user->email,
                            'phone' => null,
                            'website' => null,
                            'description' => null,
                            'address' => null,
                            'city' => null,
                            'postal_code' => null,
                            'country' => null,
                            'is_active' => true,
                            'activity_types' => [],
                            'disciplines' => [],
                            'discipline_settings' => [],
                            'needs_setup' => true // Indicateur pour le frontend
                        ]
                    ]);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 404);
            }
            
            $club = DB::table('clubs')
                ->where('id', $clubUser->club_id)
                ->first();
            
            if (!$club) {
                \Log::error('ClubController::getProfile - Club non trouvé', [
                    'club_id' => $clubUser->club_id,
                    'user_id' => $user->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }
            
            // Convertir l'objet en array pour manipulation
            $clubData = (array) $club;
            
            // S'assurer que tous les champs nécessaires existent (compatibilité avec bases de données sans migrations récentes)
            $requiredFields = [
                'company_number' => null,
                'legal_representative_name' => null,
                'legal_representative_role' => null,
                'insurance_rc_company' => null,
                'insurance_rc_policy_number' => null,
                'insurance_additional_company' => null,
                'insurance_additional_policy_number' => null,
                'insurance_additional_details' => null,
                'expense_reimbursement_type' => null,
                'expense_reimbursement_details' => null
            ];
            
            foreach ($requiredFields as $field => $defaultValue) {
                if (!isset($clubData[$field])) {
                    $clubData[$field] = $defaultValue;
                }
            }
            
            \Log::info('ClubController::getProfile - Club trouvé', [
                'club_id' => $clubData['id'],
                'club_name' => $clubData['name'],
                'has_company_number' => isset($clubData['company_number']) && !empty($clubData['company_number']),
                'has_legal_rep' => isset($clubData['legal_representative_name']) && !empty($clubData['legal_representative_name'])
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $clubData
            ]);
            
        } catch (\Exception $e) {
            \Log::error('ClubController::getProfile - Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du profil'
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            \Log::info('ClubController::updateProfile - Début', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'request_data' => $request->all()
            ]);
            
            // Récupérer le club associé à cet utilisateur
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->first();
            
            if (!$clubUser) {
                \Log::warning('ClubController::updateProfile - Aucun club_manager trouvé', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                
                // Si l'utilisateur a le rôle 'club' mais n'est pas dans club_user,
                // créer un nouveau club et l'association
                if ($user->role === 'club') {
                    // Obtenir les colonnes existantes de la table clubs
                    $existingColumns = $this->getTableColumns('clubs');
                    
                    // Préparer les données du club
                    $allData = $request->only([
                        'name', 'company_number', 'description', 'email', 'phone', 'address',
                        'city', 'postal_code', 'country', 'website', 'is_active',
                        'legal_representative_name', 'legal_representative_role',
                        'insurance_rc_company', 'insurance_rc_policy_number',
                        'insurance_additional_company', 'insurance_additional_policy_number', 'insurance_additional_details',
                        'expense_reimbursement_type', 'expense_reimbursement_details',
                        'activity_types', 'disciplines', 'discipline_settings', 'schedule_config'
                    ]);
                    
                    // Ne garder que les colonnes qui existent dans la table
                    $updateData = array_filter($allData, function($key) use ($existingColumns) {
                        return in_array($key, $existingColumns);
                    }, ARRAY_FILTER_USE_KEY);
                    
                    // Encoder les arrays en JSON si nécessaire
                    if (isset($updateData['activity_types']) && is_array($updateData['activity_types'])) {
                        $updateData['activity_types'] = json_encode($updateData['activity_types']);
                    }
                    if (isset($updateData['disciplines']) && is_array($updateData['disciplines'])) {
                        $updateData['disciplines'] = json_encode($updateData['disciplines']);
                    }
                    if (isset($updateData['discipline_settings']) && is_array($updateData['discipline_settings'])) {
                        $updateData['discipline_settings'] = json_encode($updateData['discipline_settings']);
                    }
                    if (isset($updateData['schedule_config']) && is_array($updateData['schedule_config'])) {
                        $updateData['schedule_config'] = json_encode($updateData['schedule_config']);
                    }
                    
                    // Convertir les chaînes vides en NULL pour certains champs
                    foreach (['company_number', 'description', 'website', 'legal_representative_name', 'legal_representative_role',
                              'insurance_rc_company', 'insurance_rc_policy_number', 'insurance_additional_company', 
                              'insurance_additional_policy_number', 'insurance_additional_details', 'expense_reimbursement_details'] as $field) {
                        if (isset($updateData[$field]) && $updateData[$field] === '') {
                            $updateData[$field] = null;
                        }
                    }
                    
                    // Valeurs par défaut
                    $updateData['created_at'] = now();
                    $updateData['updated_at'] = now();
                    
                    \Log::info('ClubController::updateProfile - Données à insérer', [
                        'data' => $updateData
                    ]);
                    
                    // Créer le nouveau club
                    $clubId = DB::table('clubs')->insertGetId($updateData);
                    
                    // Créer l'association club_user
                    DB::table('club_user')->insert([
                        'club_id' => $clubId,
                        'user_id' => $user->id,
                        'role' => 'owner',
                        'is_admin' => true,
                        'joined_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    \Log::info('ClubController::updateProfile - Nouveau club créé', [
                        'club_id' => $clubId,
                        'user_id' => $user->id
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Profil créé avec succès'
                    ]);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 404);
            }
            
            // Obtenir les colonnes existantes de la table clubs
            $existingColumns = $this->getTableColumns('clubs');
            
            // Mettre à jour le club existant
            $allData = $request->only([
                'name', 'company_number', 'description', 'email', 'phone', 'address',
                'city', 'postal_code', 'country', 'website', 'is_active',
                'legal_representative_name', 'legal_representative_role',
                'insurance_rc_company', 'insurance_rc_policy_number',
                'insurance_additional_company', 'insurance_additional_policy_number', 'insurance_additional_details',
                'expense_reimbursement_type', 'expense_reimbursement_details',
                'activity_types', 'disciplines', 'discipline_settings', 'schedule_config'
            ]);
            
            // Ne garder que les colonnes qui existent dans la table
            $updateData = array_filter($allData, function($key) use ($existingColumns) {
                return in_array($key, $existingColumns);
            }, ARRAY_FILTER_USE_KEY);
            
            // Encoder les arrays en JSON si nécessaire
            if (isset($updateData['activity_types']) && is_array($updateData['activity_types'])) {
                $updateData['activity_types'] = json_encode($updateData['activity_types']);
            }
            if (isset($updateData['disciplines']) && is_array($updateData['disciplines'])) {
                $updateData['disciplines'] = json_encode($updateData['disciplines']);
            }
            if (isset($updateData['discipline_settings']) && is_array($updateData['discipline_settings'])) {
                $updateData['discipline_settings'] = json_encode($updateData['discipline_settings']);
            }
            if (isset($updateData['schedule_config']) && is_array($updateData['schedule_config'])) {
                $updateData['schedule_config'] = json_encode($updateData['schedule_config']);
            }
            
            // Convertir les chaînes vides en NULL pour certains champs
            foreach (['company_number', 'description', 'website', 'legal_representative_name', 'legal_representative_role',
                      'insurance_rc_company', 'insurance_rc_policy_number', 'insurance_additional_company', 
                      'insurance_additional_policy_number', 'insurance_additional_details', 'expense_reimbursement_details'] as $field) {
                if (isset($updateData[$field]) && $updateData[$field] === '') {
                    $updateData[$field] = null;
                }
            }
            
            $updateData['updated_at'] = now();
            
            \Log::info('ClubController::updateProfile - Données à mettre à jour', [
                'club_id' => $clubUser->club_id,
                'data' => $updateData,
                'existing_columns' => $existingColumns
            ]);
            
            DB::table('clubs')
                ->where('id', $clubUser->club_id)
                ->update($updateData);
            
            \Log::info('ClubController::updateProfile - Club mis à jour avec succès', [
                'club_id' => $clubUser->club_id,
                'user_id' => $user->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('ClubController::updateProfile - Exception', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer la liste des colonnes existantes d'une table
     */
    private function getTableColumns($tableName)
    {
        try {
            $columns = DB::getSchemaBuilder()->getColumnListing($tableName);
            return $columns;
        } catch (\Exception $e) {
            \Log::warning('getTableColumns - Impossible de récupérer les colonnes', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);
            // Retourner les colonnes de base si la requête échoue
            return ['name', 'description', 'email', 'phone', 'address', 'city', 'postal_code', 'country', 'website', 'is_active',
                    'activity_types', 'disciplines', 'discipline_settings', 'schedule_config'];
        }
    }

    public function getCustomSpecialties(Request $request)
    {
        try {
            $user = $request->user();
            
            // Récupérer le club associé à cet utilisateur
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->first();
            
            if (!$clubUser) {
                return response()->json([
                    'success' => false,
                    'data' => []
                ]);
            }
            
            $specialties = DB::table('club_custom_specialties')
                ->where('club_id', $clubUser->club_id)
                ->where('is_active', true)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $specialties
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => []
            ]);
        }
    }

    public function getTeachers(Request $request)
    {
        try {
            $user = $request->user();
            
            \Log::info('ClubController::getTeachers - Début', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            // Récupérer le club associé à cet utilisateur
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->first();
            
            if (!$clubUser) {
                \Log::warning('ClubController::getTeachers - Aucun club trouvé', [
                    'user_id' => $user->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 404);
            }
            
            \Log::info('ClubController::getTeachers - Club trouvé', [
                'club_id' => $clubUser->club_id
            ]);
            
            // Utiliser Eloquent pour obtenir les relations
            $club = \App\Models\Club::find($clubUser->club_id);
            
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }
            
            // Récupérer les enseignants avec leurs utilisateurs et les données du pivot
            $allTeachers = $club->teachers()
                ->with('user')
                ->withPivot('contract_type', 'hourly_rate', 'is_active')
                ->wherePivot('is_active', true)
                ->get();
            
            // Mapper les données du pivot
            $allTeachers = $allTeachers->map(function ($teacher) {
                $teacher->contract_type = $teacher->pivot->contract_type ?? 'volunteer'; // Par défaut: volunteer
                $teacher->pivot_hourly_rate = $teacher->pivot->hourly_rate ?? null;
                return $teacher;
            });
            
            // Filtrer par type de contrat si spécifié
            if ($request->has('contract_type')) {
                $contractType = $request->input('contract_type');
                
                if ($contractType === 'volunteer') {
                    // Pour 'volunteer', inclure les enseignants avec contract_type = 'volunteer' OU NULL
                    $teachers = $allTeachers->filter(function ($teacher) {
                        return $teacher->contract_type === 'volunteer' || 
                               $teacher->pivot->contract_type === null ||
                               $teacher->pivot->contract_type === 'volunteer';
                    })->values();
                } else {
                    // Pour les autres types, filtrer exactement
                    $teachers = $allTeachers->filter(function ($teacher) use ($contractType) {
                        return $teacher->contract_type === $contractType;
                    })->values();
                }
            } else {
                $teachers = $allTeachers;
            }
            
            \Log::info('ClubController::getTeachers - Enseignants trouvés', [
                'club_id' => $clubUser->club_id,
                'teachers_count' => $teachers->count(),
                'contract_type_filter' => $request->input('contract_type', 'all')
            ]);
            
            return response()->json([
                'success' => true,
                'teachers' => $teachers
            ]);
            
        } catch (\Exception $e) {
            \Log::error('ClubController::getTeachers - Exception', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des enseignants: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStudents(Request $request)
    {
        try {
            $user = $request->user();
            
            // Récupérer le club associé à cet utilisateur
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->first();
            
            if (!$clubUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 404);
            }
            
            $students = DB::table('club_students')
                ->join('students', 'club_students.student_id', '=', 'students.id')
                ->join('users', 'students.user_id', '=', 'users.id')
                ->where('club_students.club_id', $clubUser->club_id)
                ->where('club_students.is_active', true)
                ->select(
                    'students.id',  // Corrigé : retourner students.id au lieu de users.id
                    'users.name',
                    'users.email',
                    'users.phone',
                    'students.date_of_birth',
                    'students.level',
                    'students.total_lessons',
                    'students.total_spent',
                    'club_students.joined_at'
                )
                ->get()
                ->map(function($student) {
                    // Calculer l'âge si date_of_birth existe
                    if ($student->date_of_birth) {
                        $student->age = \Carbon\Carbon::parse($student->date_of_birth)->age;
                    } else {
                        $student->age = null;
                    }
                    return $student;
                });
            
            return response()->json([
                'success' => true,
                'data' => $students
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des étudiants'
            ], 500);
        }
    }

    public function createTeacher(Request $request)
    {
        try {
            // Validation des données
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'nullable|string|max:20',
                'street' => 'nullable|string|max:255',
                'street_number' => 'nullable|string|max:20',
                'postal_code' => 'nullable|string|max:10',
                'city' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'contract_type' => 'nullable|in:volunteer,student,employee,freelance,intern,article17',
                'hourly_rate' => 'nullable|numeric|min:0',
                'bio' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            
            // Récupérer le club associé à cet utilisateur
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->first();
            
            if (!$clubUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 404);
            }

            // Commencer une transaction
            DB::beginTransaction();

            // Créer l'utilisateur
            $fullName = trim($request->first_name . ' ' . $request->last_name);
            $newUser = User::create([
                'name' => $fullName,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make('ActiviBe2024!'), // Mot de passe par défaut
                'role' => 'teacher',
                'phone' => $request->phone,
                'street' => $request->street,
                'street_number' => $request->street_number,
                'postal_code' => $request->postal_code,
                'city' => $request->city,
                'country' => $request->country ?? 'Belgium',
                'is_active' => true,
                'status' => 'active',
            ]);

            // Créer le profil enseignant
            $teacher = Teacher::create([
                'user_id' => $newUser->id,
                'hourly_rate' => $request->hourly_rate ?? 0,
                'experience_years' => 0,
                'bio' => $request->bio,
                'is_available' => true,
                'specialties' => json_encode([]),
            ]);

            // Lier l'enseignant au club
            DB::table('club_teachers')->insert([
                'club_id' => $clubUser->club_id,
                'teacher_id' => $teacher->id,
                'contract_type' => $request->contract_type ?? 'employee',
                'hourly_rate' => $request->hourly_rate ?? 0,
                'is_active' => true,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Générer un token de réinitialisation de mot de passe
            $resetToken = Password::broker()->createToken($newUser);
            
            // Récupérer le nom du club
            $club = DB::table('clubs')->where('id', $clubUser->club_id)->first();
            $clubName = $club ? $club->name : 'votre club';
            
            // Envoyer la notification de bienvenue avec le lien de réinitialisation
            $newUser->notify(new TeacherWelcomeNotification($clubName, $resetToken));

            DB::commit();

            \Log::info('Enseignant créé avec succès', [
                'user_id' => $newUser->id,
                'teacher_id' => $teacher->id,
                'club_id' => $clubUser->club_id,
                'email' => $newUser->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Enseignant créé avec succès. Un email de bienvenue a été envoyé.',
                'data' => [
                    'id' => $teacher->id,
                    'name' => $newUser->name,
                    'email' => $newUser->email,
                    'phone' => $newUser->phone,
                    'hourly_rate' => $teacher->hourly_rate,
                    'contract_type' => $request->contract_type ?? 'employee',
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Erreur lors de la création de l\'enseignant', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'enseignant: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resendTeacherInvitation(Request $request, $teacherId)
    {
        try {
            $user = $request->user();
            
            // Récupérer le club associé à cet utilisateur
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->first();
            
            if (!$clubUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 404);
            }

            // Vérifier que l'enseignant appartient bien au club
            $teacher = Teacher::find($teacherId);
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enseignant non trouvé'
                ], 404);
            }

            $clubTeacher = DB::table('club_teachers')
                ->where('club_id', $clubUser->club_id)
                ->where('teacher_id', $teacherId)
                ->first();

            if (!$clubTeacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet enseignant n\'appartient pas à votre club'
                ], 403);
            }

            // Récupérer l'utilisateur de l'enseignant
            $teacherUser = User::find($teacher->user_id);
            if (!$teacherUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur enseignant non trouvé'
                ], 404);
            }

            // Vérifier que l'email est valide
            if (!$teacherUser->email || !filter_var($teacherUser->email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'adresse email de cet enseignant est invalide (' . $teacherUser->email . '). Veuillez la corriger avant d\'envoyer l\'invitation.'
                ], 400);
            }

            // Générer un nouveau token de réinitialisation de mot de passe
            $resetToken = Password::broker()->createToken($teacherUser);
            
            // Récupérer le nom du club
            $club = DB::table('clubs')->where('id', $clubUser->club_id)->first();
            $clubName = $club ? $club->name : 'votre club';
            
            // Envoyer la notification de bienvenue avec le lien de réinitialisation
            $teacherUser->notify(new TeacherWelcomeNotification($clubName, $resetToken));

            \Log::info('Email d\'invitation renvoyé à l\'enseignant', [
                'teacher_id' => $teacherId,
                'user_id' => $teacherUser->id,
                'club_id' => $clubUser->club_id,
                'email' => $teacherUser->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email d\'invitation renvoyé avec succès à ' . $teacherUser->email
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors du renvoi de l\'invitation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du renvoi de l\'invitation: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateTeacher(Request $request, $teacherId)
    {
        try {
            $user = $request->user();
            
            // Récupérer le club associé à cet utilisateur
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->first();
            
            if (!$clubUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 404);
            }

            // Vérifier que l'enseignant appartient bien au club
            $teacher = Teacher::find($teacherId);
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enseignant non trouvé'
                ], 404);
            }

            $clubTeacher = DB::table('club_teachers')
                ->where('club_id', $clubUser->club_id)
                ->where('teacher_id', $teacherId)
                ->first();

            if (!$clubTeacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet enseignant n\'appartient pas à votre club'
                ], 403);
            }

            // Validation des données
            $validator = Validator::make($request->all(), [
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $teacher->user_id,
                'phone' => 'nullable|string|max:20',
                'hourly_rate' => 'nullable|numeric|min:0',
                'experience_years' => 'nullable|integer|min:0',
                'bio' => 'nullable|string',
                'contract_type' => 'nullable|in:volunteer,student,employee,freelance,intern,article17',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Mettre à jour l'utilisateur
            $teacherUser = User::find($teacher->user_id);
            if ($request->has('first_name') || $request->has('last_name')) {
                $firstName = $request->input('first_name', $teacherUser->first_name);
                $lastName = $request->input('last_name', $teacherUser->last_name);
                $teacherUser->name = trim($firstName . ' ' . $lastName);
                $teacherUser->first_name = $firstName;
                $teacherUser->last_name = $lastName;
            }
            
            if ($request->has('email')) {
                $teacherUser->email = $request->email;
            }
            
            if ($request->has('phone')) {
                $teacherUser->phone = $request->phone;
            }
            
            $teacherUser->save();

            // Mettre à jour le profil enseignant
            if ($request->has('hourly_rate')) {
                $teacher->hourly_rate = $request->hourly_rate;
            }
            
            if ($request->has('experience_years')) {
                $teacher->experience_years = $request->experience_years;
            }
            
            if ($request->has('bio')) {
                $teacher->bio = $request->bio;
            }
            
            $teacher->save();

            // Mettre à jour les informations du club_teachers
            if ($request->has('contract_type') || $request->has('hourly_rate')) {
                $updateData = ['updated_at' => now()];
                
                if ($request->has('contract_type')) {
                    $updateData['contract_type'] = $request->contract_type;
                }
                
                if ($request->has('hourly_rate')) {
                    $updateData['hourly_rate'] = $request->hourly_rate;
                }
                
                DB::table('club_teachers')
                    ->where('club_id', $clubUser->club_id)
                    ->where('teacher_id', $teacherId)
                    ->update($updateData);
            }

            DB::commit();

            \Log::info('Enseignant mis à jour avec succès', [
                'teacher_id' => $teacherId,
                'club_id' => $clubUser->club_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Enseignant mis à jour avec succès',
                'data' => [
                    'id' => $teacher->id,
                    'name' => $teacherUser->name,
                    'email' => $teacherUser->email,
                    'phone' => $teacherUser->phone,
                    'hourly_rate' => $teacher->hourly_rate,
                    'experience_years' => $teacher->experience_years,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Erreur lors de la mise à jour de l\'enseignant', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'enseignant: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteTeacher(Request $request, $teacherId)
    {
        try {
            $user = $request->user();
            
            // Récupérer le club associé à cet utilisateur
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->first();
            
            if (!$clubUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 404);
            }

            // Vérifier que l'enseignant appartient bien au club
            $teacher = Teacher::find($teacherId);
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enseignant non trouvé'
                ], 404);
            }

            $clubTeacher = DB::table('club_teachers')
                ->where('club_id', $clubUser->club_id)
                ->where('teacher_id', $teacherId)
                ->first();

            if (!$clubTeacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet enseignant n\'appartient pas à votre club'
                ], 403);
            }

            DB::beginTransaction();

            // Désactiver la relation club-enseignant au lieu de supprimer
            DB::table('club_teachers')
                ->where('club_id', $clubUser->club_id)
                ->where('teacher_id', $teacherId)
                ->update([
                    'is_active' => false,
                    'updated_at' => now()
                ]);

            DB::commit();

            \Log::info('Enseignant désactivé du club', [
                'teacher_id' => $teacherId,
                'club_id' => $clubUser->club_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Enseignant retiré du club avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Erreur lors de la suppression de l\'enseignant', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'enseignant: ' . $e->getMessage()
            ], 500);
        }
    }
}