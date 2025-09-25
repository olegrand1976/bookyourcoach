<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Club;
use App\Models\Lesson;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Le middleware 'admin' est déjà appliqué dans les routes
        // Pas besoin de vérification manuelle ici
    }

    /**
     * Dashboard admin avec statistiques
     */
    public function dashboard()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_users' => User::count(),
                    'total_teachers' => User::where('role', 'teacher')->count(),
                    'total_students' => User::where('role', 'student')->count(),
                    'total_clubs' => Club::count(),
                    'total_lessons' => Lesson::count(),
                    'total_payments' => Payment::count(),
                    'revenue_this_month' => Payment::whereMonth('created_at', now()->month)->sum('amount'),
                ],
                'recent_users' => User::latest()->take(5)->get(),
                'recent_lessons' => Lesson::latest()->take(5)->get(),
            ]
        ]);
    }

    /**
     * Liste des utilisateurs avec filtres
     */
    public function users(Request $request)
    {
        $query = User::query();

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('postal_code')) {
            $query->where('postal_code', $request->postal_code);
        }

        $users = $query->orderBy('created_at', 'desc')
                      ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Créer un utilisateur
     */
    public function storeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,teacher,student',
            'phone' => 'nullable|string',
            'street' => 'nullable|string',
            'street_number' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'birth_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            ...$validator->validated(),
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Utilisateur créé avec succès'
        ], 201);
    }

    /**
     * Modifier un utilisateur
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'role' => 'sometimes|required|in:admin,teacher,student',
            'phone' => 'nullable|string',
            'street' => 'nullable|string',
            'street_number' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'birth_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Utilisateur modifié avec succès'
        ]);
    }

    /**
     * Réinitialiser le mot de passe d'un utilisateur
     */
    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);
    }

    /**
     * Modifier le rôle d'un utilisateur
     */
    public function updateRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:admin,teacher,student',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update(['role' => $request->role]);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Rôle modifié avec succès'
        ]);
    }

    /**
     * Modifier le statut d'un utilisateur
     */
    public function updateStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Empêcher la désactivation du dernier admin
        if ($user->role === 'admin' && $user->is_active && !$request->is_active) {
            $activeAdmins = User::where('role', 'admin')->where('is_active', true)->count();
            if ($activeAdmins <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de désactiver le dernier administrateur'
                ], 422);
            }
        }

        $user->update(['is_active' => $request->is_active]);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Statut modifié avec succès'
        ]);
    }

    /**
     * Basculer le statut d'un utilisateur
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        // Empêcher la désactivation du dernier admin
        if ($user->role === 'admin' && $user->is_active) {
            $activeAdmins = User::where('role', 'admin')->where('is_active', true)->count();
            if ($activeAdmins <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de désactiver le dernier administrateur'
                ], 422);
            }
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Statut basculé avec succès'
        ]);
    }

    /**
     * Statistiques générales
     */
    public function stats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'users' => [
                    'total' => User::count(),
                    'active' => User::where('is_active', true)->count(),
                    'inactive' => User::where('is_active', false)->count(),
                    'by_role' => User::selectRaw('role, COUNT(*) as count')->groupBy('role')->get(),
                ],
                'lessons' => [
                    'total' => Lesson::count(),
                    'this_month' => Lesson::whereMonth('created_at', now()->month)->count(),
                    'this_year' => Lesson::whereYear('created_at', now()->year)->count(),
                ],
                'payments' => [
                    'total_amount' => Payment::sum('amount'),
                    'this_month' => Payment::whereMonth('created_at', now()->month)->sum('amount'),
                    'this_year' => Payment::whereYear('created_at', now()->year)->sum('amount'),
                ],
                'clubs' => [
                    'total' => Club::count(),
                    'active' => Club::where('is_active', true)->count(),
                ],
            ]
        ]);
    }

    /**
     * Paramètres de l'application
     */
    public function settings()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'app_name' => config('app.name'),
                'app_env' => config('app.env'),
                'app_debug' => config('app.debug'),
                'database' => config('database.default'),
                'cache' => config('cache.default'),
                'queue' => config('queue.default'),
            ]
        ]);
    }

    /**
     * Modifier les paramètres
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_name' => 'sometimes|string|max:255',
            'app_debug' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Ici vous pouvez ajouter la logique pour sauvegarder les paramètres
        // Par exemple dans une table settings ou un fichier de configuration

        return response()->json([
            'success' => true,
            'message' => 'Paramètres modifiés avec succès'
        ]);
    }

    /**
     * Paramètres par type
     */
    public function getSettingsByType($type)
    {
        $allowedTypes = ['general', 'email', 'payment', 'notification'];

        if (!in_array($type, $allowedTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Type de paramètre invalide'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'settings' => [] // À implémenter selon vos besoins
            ]
        ]);
    }

    /**
     * Modifier les paramètres par type
     */
    public function updateSettingsByType(Request $request, $type)
    {
        $allowedTypes = ['general', 'email', 'payment', 'notification'];

        if (!in_array($type, $allowedTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Type de paramètre invalide'
            ], 400);
        }

        // Ici vous pouvez ajouter la logique pour sauvegarder les paramètres par type

        return response()->json([
            'success' => true,
            'message' => "Paramètres {$type} modifiés avec succès"
        ]);
    }

    /**
     * Créer un club
     */
    public function storeClub(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'website' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $club = Club::create([
            ...$validator->validated(),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $club,
            'message' => 'Club créé avec succès'
        ], 201);
    }

    /**
     * Maintenance
     */
    public function maintenance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:cache_clear,optimize,config_clear,route_clear,view_clear',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            switch ($request->action) {
                case 'cache_clear':
                    \Artisan::call('cache:clear');
                    break;
                case 'optimize':
                    \Artisan::call('optimize');
                    break;
                case 'config_clear':
                    \Artisan::call('config:clear');
                    break;
                case 'route_clear':
                    \Artisan::call('route:clear');
                    break;
                case 'view_clear':
                    \Artisan::call('view:clear');
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Action de maintenance exécutée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'exécution de la maintenance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vider le cache
     */
    public function clearCache()
    {
        try {
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache vidé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du vidage du cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logs d'audit
     */
    public function auditLogs(Request $request)
    {
        $page = $request->get('page', 1);
        $per_page = $request->get('per_page', 20);

        // Ici vous pouvez implémenter la récupération des logs d'audit
        // Par exemple depuis une table audit_logs ou des fichiers de log

        return response()->json([
            'success' => true,
            'data' => [
                'logs' => [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total' => 0
                ]
            ]
        ]);
    }
}
