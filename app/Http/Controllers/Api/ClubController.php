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

    /**
     * Diagnostic: Vérifier les colonnes de la table clubs
     */
    public function diagnoseColumns(Request $request)
    {
        try {
            $columns = DB::getSchemaBuilder()->getColumnListing('clubs');
            
            $legalFields = [
                'company_number',
                'legal_representative_name',
                'legal_representative_role',
                'insurance_rc_company',
                'insurance_rc_policy_number',
                'insurance_additional_company',
                'insurance_additional_policy_number',
                'insurance_additional_details',
                'expense_reimbursement_type',
                'expense_reimbursement_details'
            ];
            
            $status = [];
            foreach ($legalFields as $field) {
                $status[$field] = in_array($field, $columns) ? 'EXISTS' : 'MISSING';
            }
            
            // Récupérer le club de l'utilisateur si disponible
            $user = $request->user();
            $clubData = null;
            if ($user) {
                $clubUser = DB::table('club_user')
                    ->where('user_id', $user->id)
                    ->where('is_admin', true)
                    ->first();
                
                if ($clubUser) {
                    $club = DB::table('clubs')->where('id', $clubUser->club_id)->first();
                    if ($club) {
                        $clubData = [];
                        foreach ($legalFields as $field) {
                            if (property_exists($club, $field)) {
                                $value = $club->$field;
                                $clubData[$field] = [
                                    'value' => $value,
                                    'is_empty' => empty($value),
                                    'type' => gettype($value)
                                ];
                            } else {
                                $clubData[$field] = 'COLUMN_NOT_EXISTS';
                            }
                        }
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'all_columns' => $columns,
                'legal_fields_status' => $status,
                'current_club_data' => $clubData,
                'total_columns' => count($columns),
                'legal_fields_existing' => count(array_filter($status, fn($s) => $s === 'EXISTS'))
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
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
            
            $requestData = $request->all();
            \Log::info('ClubController::updateProfile - Début', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'request_data' => $requestData,
                'legal_fields_received' => [
                    'company_number' => $requestData['company_number'] ?? 'NOT_SENT',
                    'legal_representative_name' => $requestData['legal_representative_name'] ?? 'NOT_SENT',
                    'legal_representative_role' => $requestData['legal_representative_role'] ?? 'NOT_SENT',
                    'insurance_rc_company' => $requestData['insurance_rc_company'] ?? 'NOT_SENT',
                    'insurance_rc_policy_number' => $requestData['insurance_rc_policy_number'] ?? 'NOT_SENT',
                    'insurance_additional_company' => $requestData['insurance_additional_company'] ?? 'NOT_SENT',
                    'insurance_additional_policy_number' => $requestData['insurance_additional_policy_number'] ?? 'NOT_SENT',
                    'insurance_additional_details' => $requestData['insurance_additional_details'] ?? 'NOT_SENT',
                    'expense_reimbursement_type' => $requestData['expense_reimbursement_type'] ?? 'NOT_SENT',
                    'expense_reimbursement_details' => $requestData['expense_reimbursement_details'] ?? 'NOT_SENT',
                ]
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
                        'activity_types', 'disciplines', 'discipline_settings', 'schedule_config',
                        'default_subscription_total_lessons', 'default_subscription_free_lessons',
                        'default_subscription_price', 'default_subscription_validity_value',
                        'default_subscription_validity_unit'
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
            
            // Validation des champs obligatoires
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'company_number' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'legal_representative_name' => 'required|string|max:255',
                'legal_representative_role' => 'required|string|max:255',
                'insurance_rc_company' => 'required|string|max:255',
                'insurance_rc_policy_number' => 'required|string|max:255',
                'insurance_additional_details' => 'required|string|max:1000',
                'expense_reimbursement_type' => 'required|in:forfait,reel,aucun',
                'expense_reimbursement_details' => 'required_if:expense_reimbursement_type,forfait,reel|nullable|string',
                'phone' => 'nullable|string|max:20',
                'description' => 'nullable|string',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:10',
                'country' => 'nullable|string|max:255',
                'website' => 'nullable|url',
                'is_active' => 'nullable|boolean',
                'insurance_additional_company' => 'nullable|string|max:255',
                'insurance_additional_policy_number' => 'nullable|string|max:255',
                'activity_types' => 'nullable|array',
                'disciplines' => 'nullable|array',
                'discipline_settings' => 'nullable|array',
                'schedule_config' => 'nullable|array',
                'default_subscription_total_lessons' => 'nullable|integer|min:1',
                'default_subscription_free_lessons' => 'nullable|integer|min:0',
                'default_subscription_price' => 'nullable|numeric|min:0',
                'default_subscription_validity_value' => 'nullable|integer|min:1',
                'default_subscription_validity_unit' => 'nullable|in:weeks,months'
            ]);
            
            // Mettre à jour le club existant avec les données validées
            $allData = $validated;
            
            // S'assurer que tous les champs importants sont bien présents avant le filtre
            \Log::info('ClubController::updateProfile - Données validées', [
                'validated_data' => $allData,
                'company_number' => $allData['company_number'] ?? 'MISSING',
                'legal_representative_name' => $allData['legal_representative_name'] ?? 'MISSING',
                'legal_representative_role' => $allData['legal_representative_role'] ?? 'MISSING',
                'expense_reimbursement_type' => $allData['expense_reimbursement_type'] ?? 'MISSING',
                'expense_reimbursement_details' => $allData['expense_reimbursement_details'] ?? 'MISSING',
            ]);
            
            // Ne garder que les colonnes qui existent dans la table
            $updateData = array_filter($allData, function($key) use ($existingColumns) {
                return in_array($key, $existingColumns);
            }, ARRAY_FILTER_USE_KEY);
            
            // Log pour vérifier que les champs importants sont bien dans updateData
            \Log::info('ClubController::updateProfile - Données après filtre colonnes', [
                'updateData_keys' => array_keys($updateData),
                'company_number_in_update' => isset($updateData['company_number']),
                'legal_representative_name_in_update' => isset($updateData['legal_representative_name']),
                'legal_representative_role_in_update' => isset($updateData['legal_representative_role']),
                'expense_reimbursement_type_in_update' => isset($updateData['expense_reimbursement_type']),
                'expense_reimbursement_details_in_update' => isset($updateData['expense_reimbursement_details']),
            ]);
            
            // Encoder les arrays en JSON si nécessaire
            if (isset($updateData['activity_types']) && is_array($updateData['activity_types'])) {
                $updateData['activity_types'] = json_encode($updateData['activity_types']);
            }
            if (isset($updateData['disciplines'])) {
                // S'assurer que c'est un tableau
                if (is_array($updateData['disciplines'])) {
                    // Filtrer pour ne garder que les IDs numériques valides
                    $updateData['disciplines'] = array_values(array_filter($updateData['disciplines'], function($id) {
                        return is_numeric($id) && $id > 0;
                    }));
                    $updateData['disciplines'] = json_encode($updateData['disciplines']);
                } elseif (is_string($updateData['disciplines'])) {
                    // Si c'est déjà une chaîne JSON, vérifier qu'elle est valide
                    $decoded = json_decode($updateData['disciplines'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $decoded = array_values(array_filter($decoded, function($id) {
                            return is_numeric($id) && $id > 0;
                        }));
                        $updateData['disciplines'] = json_encode($decoded);
                    }
                }
                
                \Log::info('ClubController::updateProfile - Disciplines traitées', [
                    'raw_input' => $requestData['disciplines'] ?? null,
                    'after_filtering' => $updateData['disciplines'],
                    'club_id' => $clubUser->club_id
                ]);
            }
            if (isset($updateData['discipline_settings']) && is_array($updateData['discipline_settings'])) {
                $updateData['discipline_settings'] = json_encode($updateData['discipline_settings']);
            }
            if (isset($updateData['schedule_config']) && is_array($updateData['schedule_config'])) {
                $updateData['schedule_config'] = json_encode($updateData['schedule_config']);
            }
            
            // Ne PAS convertir les champs obligatoires en NULL même s'ils sont vides (la validation les empêchera)
            // Convertir les chaînes vides en NULL pour les champs optionnels uniquement
            foreach (['description', 'website', 'insurance_additional_company', 
                      'insurance_additional_policy_number', 'expense_reimbursement_details'] as $field) {
                if (isset($updateData[$field]) && $updateData[$field] === '') {
                    $updateData[$field] = null;
                }
            }
            
            // S'assurer que expense_reimbursement_type est bien présent (ne pas le convertir en NULL si vide)
            // Si non fourni ou vide, garder la valeur existante ou 'aucun' par défaut
            if (!isset($updateData['expense_reimbursement_type']) || $updateData['expense_reimbursement_type'] === '') {
                // Récupérer la valeur existante si le champ n'est pas dans la requête
                if (!isset($allData['expense_reimbursement_type'])) {
                    $existingClub = DB::table('clubs')->where('id', $clubUser->club_id)->first();
                    $updateData['expense_reimbursement_type'] = $existingClub->expense_reimbursement_type ?? 'aucun';
                } else {
                    $updateData['expense_reimbursement_type'] = 'aucun';
                }
            }
            
            // S'assurer que expense_reimbursement_details est NULL si expense_reimbursement_type est 'aucun'
            if (isset($updateData['expense_reimbursement_type']) && $updateData['expense_reimbursement_type'] === 'aucun') {
                $updateData['expense_reimbursement_details'] = null;
            }
            
            $updateData['updated_at'] = now();
            
            \Log::info('ClubController::updateProfile - Données à mettre à jour', [
                'club_id' => $clubUser->club_id,
                'data' => $updateData,
                'existing_columns' => $existingColumns,
                'all_data_received' => $allData,
                'filtered_out_fields' => array_diff(array_keys($allData), array_keys($updateData))
            ]);
            
            DB::table('clubs')
                ->where('id', $clubUser->club_id)
                ->update($updateData);
            
            // Vérifier les données après update
            $updatedClub = DB::table('clubs')->where('id', $clubUser->club_id)->first();
            
            \Log::info('ClubController::updateProfile - Club mis à jour avec succès', [
                'club_id' => $clubUser->club_id,
                'user_id' => $user->id,
                'legal_fields_after_update' => [
                    'company_number' => $updatedClub->company_number ?? 'NULL',
                    'legal_representative_name' => $updatedClub->legal_representative_name ?? 'NULL',
                    'legal_representative_role' => $updatedClub->legal_representative_role ?? 'NULL',
                    'insurance_rc_company' => $updatedClub->insurance_rc_company ?? 'NULL',
                    'insurance_rc_policy_number' => $updatedClub->insurance_rc_policy_number ?? 'NULL',
                    'expense_reimbursement_type' => $updatedClub->expense_reimbursement_type ?? 'NULL',
                ]
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
            
            // Vérifier si les tables nécessaires existent
            if (!\Illuminate\Support\Facades\Schema::hasTable('club_students') || 
                !\Illuminate\Support\Facades\Schema::hasTable('students') || 
                !\Illuminate\Support\Facades\Schema::hasTable('users')) {
                \Log::warning('Tables manquantes pour getStudents. Migrations non exécutées.');
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Aucun élève disponible. Les migrations doivent être exécutées.'
                ]);
            }
            
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
            
            // Utiliser leftJoin pour gérer les étudiants sans user_id
            $students = DB::table('club_students')
                ->join('students', 'club_students.student_id', '=', 'students.id')
                ->leftJoin('users', 'students.user_id', '=', 'users.id')
                ->where('club_students.club_id', $clubUser->club_id)
                ->where('club_students.is_active', true)
                ->select(
                    'students.id',
                    'students.user_id',
                    'users.name',
                    'users.email',
                    'users.phone',
                    'users.first_name',
                    'users.last_name',
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
            \Log::error('Erreur lors de la récupération des étudiants: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
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
                'niss' => 'nullable|string|max:15',
                'street' => 'nullable|string|max:255',
                'street_number' => 'nullable|string|max:20',
                'postal_code' => 'nullable|string|max:10',
                'city' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'bank_account_number' => 'nullable|string|max:50',
                'experience_start_date' => 'nullable|date',
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

            // Calculer les années d'expérience si experience_start_date est fourni
            // Sinon, la date de création du profil sera utilisée (via l'accessor du modèle)
            $experienceYears = 0;
            if ($request->experience_start_date) {
                $startDate = \Carbon\Carbon::parse($request->experience_start_date);
                $experienceYears = max(0, $startDate->diffInYears(now()));
            }
            // Si pas de date fournie, experience_years sera calculé dynamiquement par l'accessor

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
                'niss' => $request->niss,
                'street' => $request->street,
                'street_number' => $request->street_number,
                'postal_code' => $request->postal_code,
                'city' => $request->city,
                'country' => $request->country ?? 'Belgium',
                'bank_account_number' => $request->bank_account_number,
                'experience_start_date' => $request->experience_start_date,
                'is_active' => true,
                'status' => 'active',
            ]);

            // Créer le profil enseignant
            // Note: experience_years sera calculé automatiquement par l'accessor du modèle
            // si experience_start_date est défini, sinon il utilisera created_at
            $teacher = Teacher::create([
                'user_id' => $newUser->id,
                'hourly_rate' => $request->hourly_rate ?? 0,
                'experience_years' => $experienceYears, // Valeur initiale, sera recalculée par l'accessor
                'bio' => $request->bio,
                'is_available' => true,
                'specialties' => json_encode([]),
            ]);
            
            // Recharger la relation user pour que l'accessor puisse accéder à experience_start_date
            $teacher->load('user');

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