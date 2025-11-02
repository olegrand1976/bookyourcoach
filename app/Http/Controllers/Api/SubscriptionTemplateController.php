<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SubscriptionTemplateController extends Controller
{
    /**
     * Liste tous les modèles d'abonnement du club
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'club') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux clubs'
                ], 403);
            }

            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            // Vérifier si la table existe
            if (!Schema::hasTable('subscription_templates')) {
                return response()->json([
                    'success' => false,
                    'message' => 'La table subscription_templates n\'existe pas. Veuillez exécuter les migrations avec: php artisan migrate'
                ], 500);
            }

            $templates = SubscriptionTemplate::where('club_id', $club->id)
                ->with(['courseTypes:id,name,description'])
                ->orderBy('is_active', 'desc')
                ->orderBy('model_number', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $templates
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des modèles d\'abonnement: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des modèles',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Créer un nouveau modèle d'abonnement
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'club') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux clubs'
                ], 403);
            }

            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            $validated = $request->validate([
                'model_number' => 'nullable|string|max:50', // Rendre optionnel, sera généré automatiquement
                'total_lessons' => 'required|integer|min:1',
                'free_lessons' => 'nullable|integer|min:0',
                'price' => 'required|numeric|min:0',
                'validity_value' => 'required|integer|min:1',
                'validity_unit' => 'required|in:weeks,months',
                'validity_months' => 'nullable|integer|min:1|max:60', // Calculé automatiquement si non fourni
                'is_active' => 'boolean',
                'course_type_ids' => 'required|array|min:1',
                'course_type_ids.*' => 'exists:course_types,id'
            ]);
            
            // Calculer validity_months à partir de validity_value et validity_unit si non fourni
            if (!isset($validated['validity_months']) || $validated['validity_months'] === null) {
                if ($validated['validity_unit'] === 'weeks') {
                    // Conversion: 1 mois = 4.33 semaines (approximation)
                    $validated['validity_months'] = (int) ceil($validated['validity_value'] / 4.33);
                } else {
                    $validated['validity_months'] = $validated['validity_value'];
                }
            } else {
                // S'assurer que validity_months est un entier
                $validated['validity_months'] = (int) ceil((float) $validated['validity_months']);
            }

            DB::beginTransaction();

            // Générer automatiquement le numéro de modèle si non fourni
            $modelNumber = $validated['model_number'] ?? null;
            
            if (!$modelNumber) {
                // Récupérer les noms des types de cours
                $courseTypes = \App\Models\CourseType::whereIn('id', $validated['course_type_ids'])
                    ->orderBy('name')
                    ->pluck('name')
                    ->toArray();
                
                // Trouver le prochain numéro incrémental pour ce club
                // Récupérer tous les modèles avec le format MOD-XX- et extraire le plus grand numéro
                $templates = SubscriptionTemplate::where('club_id', $club->id)
                    ->where('model_number', 'like', 'MOD-%')
                    ->pluck('model_number')
                    ->toArray();
                
                $maxNumber = 0;
                foreach ($templates as $templateNumber) {
                    if (preg_match('/^MOD-(\d+)-/', $templateNumber, $matches)) {
                        $currentNumber = (int) $matches[1];
                        if ($currentNumber > $maxNumber) {
                            $maxNumber = $currentNumber;
                        }
                    }
                }
                
                $nextNumber = $maxNumber + 1;
                
                // Construire le numéro : MOD-{numéro à 2 chiffres}-{noms des cours}
                $courseTypesString = implode(', ', $courseTypes);
                // Limiter la longueur pour éviter les problèmes de base de données (max 50 caractères)
                $maxCourseTypesLength = 50 - strlen(sprintf('MOD-%02d-', $nextNumber));
                if (strlen($courseTypesString) > $maxCourseTypesLength) {
                    $courseTypesString = substr($courseTypesString, 0, $maxCourseTypesLength - 3) . '...';
                }
                $modelNumber = sprintf('MOD-%02d-%s', $nextNumber, $courseTypesString);
            }

            // Vérifier que le numéro de modèle est unique pour ce club
            $exists = SubscriptionTemplate::where('club_id', $club->id)
                ->where('model_number', $modelNumber)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce numéro de modèle existe déjà pour votre club'
                ], 422);
            }

            $template = SubscriptionTemplate::create([
                'club_id' => $club->id,
                'model_number' => $modelNumber,
                'total_lessons' => $validated['total_lessons'],
                'free_lessons' => $validated['free_lessons'] ?? 0,
                'price' => $validated['price'],
                'validity_months' => $validated['validity_months'],
                'validity_value' => $validated['validity_value'],
                'validity_unit' => $validated['validity_unit'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Attacher les types de cours
            $template->courseTypes()->sync($validated['course_type_ids']);

            DB::commit();

            $template->load('courseTypes');

            return response()->json([
                'success' => true,
                'message' => 'Modèle d\'abonnement créé avec succès',
                'data' => $template
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création du modèle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du modèle'
            ], 500);
        }
    }

    /**
     * Mettre à jour un modèle d'abonnement
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'club') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux clubs'
                ], 403);
            }

            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            $template = SubscriptionTemplate::where('club_id', $club->id)->findOrFail($id);

            $validated = $request->validate([
                'model_number' => 'sometimes|string|max:50',
                'total_lessons' => 'sometimes|integer|min:1',
                'free_lessons' => 'sometimes|integer|min:0',
                'price' => 'sometimes|numeric|min:0',
                'validity_value' => 'sometimes|integer|min:1',
                'validity_unit' => 'sometimes|in:weeks,months',
                'validity_months' => 'sometimes|integer|min:1|max:60',
                'is_active' => 'boolean',
                'course_type_ids' => 'sometimes|array|min:1',
                'course_type_ids.*' => 'exists:course_types,id'
            ]);
            
            // Si validity_value et validity_unit sont fournis, calculer validity_months
            if (isset($validated['validity_value']) && isset($validated['validity_unit'])) {
                if ($validated['validity_unit'] === 'weeks') {
                    $validated['validity_months'] = (int) ceil($validated['validity_value'] / 4.33);
                } else {
                    $validated['validity_months'] = $validated['validity_value'];
                }
            } elseif (isset($validated['validity_months'])) {
                // Si seulement validity_months est fourni (backward compatibility), s'assurer que c'est un entier
                $validated['validity_months'] = (int) ceil((float) $validated['validity_months']);
            }

            // Vérifier l'unicité du numéro de modèle si modifié
            if (isset($validated['model_number']) && $validated['model_number'] !== $template->model_number) {
                $exists = SubscriptionTemplate::where('club_id', $club->id)
                    ->where('model_number', $validated['model_number'])
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce numéro de modèle existe déjà pour votre club'
                    ], 422);
                }
            }

            DB::beginTransaction();

            $template->update($validated);

            // Mettre à jour les types de cours si fournis
            if (isset($validated['course_type_ids'])) {
                $template->courseTypes()->sync($validated['course_type_ids']);
            }

            DB::commit();

            $template->load('courseTypes');

            return response()->json([
                'success' => true,
                'message' => 'Modèle d\'abonnement mis à jour avec succès',
                'data' => $template
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour du modèle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du modèle'
            ], 500);
        }
    }

    /**
     * Supprimer un modèle d'abonnement
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'club') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux clubs'
                ], 403);
            }

            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            $template = SubscriptionTemplate::where('club_id', $club->id)->findOrFail($id);

            // Vérifier s'il y a des abonnements actifs utilisant ce modèle
            $activeSubscriptions = $template->subscriptions()
                ->whereHas('instances', function ($query) {
                    $query->where('status', 'active');
                })
                ->count();

            if ($activeSubscriptions > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce modèle car des abonnements l\'utilisent actuellement'
                ], 422);
            }

            $template->delete();

            return response()->json([
                'success' => true,
                'message' => 'Modèle d\'abonnement supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du modèle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du modèle'
            ], 500);
        }
    }
}

