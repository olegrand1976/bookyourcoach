<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MaterializeRecurringSlotLessonRequest;
use App\Models\Lesson;
use App\Models\SubscriptionRecurringSlot;
use App\Services\LegacyRecurringSlotService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecurringSlotController extends Controller
{
    /**
     * Liste des créneaux récurrents pour un club
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'club') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé'
                ], 404);
            }

            $validated = $request->validate([
                'status' => 'nullable|string|in:active,cancelled,expired,paused',
                'teacher_id' => 'nullable|integer|exists:teachers,id',
                'student_id' => 'nullable|integer|exists:students,id',
                'search' => 'nullable|string|max:200',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
            ]);

            // Récupérer les créneaux récurrents via les subscription_instances du club
            $query = SubscriptionRecurringSlot::whereHas('subscriptionInstance', function ($q) use ($club) {
                $q->whereHas('subscription', function ($sub) use ($club) {
                    $sub->where('club_id', $club->id);
                });
            })
                ->with([
                    'subscriptionInstance.subscription.template',
                    'subscriptionInstance.students.user',
                    'teacher.user',
                    'student.user',
                    'openSlot',
                ])
                ->orderBy('day_of_week')
                ->orderBy('start_time');

            if (! empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }
            if (! empty($validated['teacher_id'])) {
                $query->where('teacher_id', (int) $validated['teacher_id']);
            }
            if (! empty($validated['student_id'])) {
                $query->where('student_id', (int) $validated['student_id']);
            }

            // Jour : 0 = dimanche — ne pas utiliser filled() (empty(0) === true en PHP)
            if ($request->query->has('day_of_week') && $request->query('day_of_week') !== '' && $request->query('day_of_week') !== null) {
                $dow = (int) $request->query('day_of_week');
                if ($dow >= 0 && $dow <= 6) {
                    $query->where('day_of_week', $dow);
                }
            }

            if (! empty($validated['date_from'])) {
                $query->whereDate('end_date', '>=', $validated['date_from']);
            }
            if (! empty($validated['date_to'])) {
                $query->whereDate('start_date', '<=', $validated['date_to']);
            }

            if (! empty($validated['search'])) {
                $term = '%'.addcslashes($validated['search'], '%_\\').'%';
                $query->where(function ($q) use ($term) {
                    $q->whereHas('student.user', fn ($u) => $u->where('name', 'like', $term))
                        ->orWhereHas('teacher.user', fn ($u) => $u->where('name', 'like', $term))
                        ->orWhereHas('subscriptionInstance.students.user', fn ($u) => $u->where('name', 'like', $term))
                        ->orWhereHas('subscriptionInstance.subscription', fn ($s) => $s->where('subscription_number', 'like', $term))
                        ->orWhereHas('subscriptionInstance.subscription.template', function ($t) use ($term) {
                            $t->where('name', 'like', $term)
                                ->orWhere('model_number', 'like', $term);
                        });
                });
            }

            $recurringSlots = $query->get();

            return response()->json([
                'success' => true,
                'data' => $recurringSlots
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des créneaux récurrents: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des créneaux récurrents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Libérer manuellement un créneau récurrent
     * Utilisé quand on sait que l'abonnement va se terminer ou qu'on veut libérer le créneau
     */
    public function release(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'club') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé'
                ], 404);
            }

            $validated = $request->validate([
                'reason' => 'nullable|string|max:500'
            ]);

            $recurringSlot = SubscriptionRecurringSlot::whereHas('subscriptionInstance', function ($query) use ($club) {
                    $query->whereHas('subscription', function ($q) use ($club) {
                        $q->where('club_id', $club->id);
                    });
                })
                ->findOrFail($id);

            $recurringSlot->release($validated['reason'] ?? null);

            Log::info("🔓 Créneau récurrent libéré manuellement", [
                'recurring_slot_id' => $id,
                'subscription_instance_id' => $recurringSlot->subscription_instance_id,
                'club_id' => $club->id,
                'user_id' => $user->id,
                'reason' => $validated['reason'] ?? 'Non spécifiée'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Créneau libéré avec succès',
                'data' => $recurringSlot->fresh()
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Créneau récurrent non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la libération du créneau récurrent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la libération du créneau',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réactiver un créneau récurrent annulé
     * Utilisé pour rétablir une réservation qui avait été libérée
     */
    public function reactivate(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'club') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé'
                ], 404);
            }

            $validated = $request->validate([
                'reason' => 'nullable|string|max:500'
            ]);

            $recurringSlot = SubscriptionRecurringSlot::whereHas('subscriptionInstance', function ($query) use ($club) {
                    $query->whereHas('subscription', function ($q) use ($club) {
                        $q->where('club_id', $club->id);
                    });
                })
                ->findOrFail($id);

            if ($recurringSlot->status !== 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les créneaux annulés peuvent être réactivés'
                ], 422);
            }

            $recurringSlot->reactivate($validated['reason'] ?? null);

            Log::info("🔄 Créneau récurrent réactivé manuellement", [
                'recurring_slot_id' => $id,
                'subscription_instance_id' => $recurringSlot->subscription_instance_id,
                'club_id' => $club->id,
                'user_id' => $user->id,
                'reason' => $validated['reason'] ?? 'Non spécifiée'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Créneau réactivé avec succès',
                'data' => $recurringSlot->fresh()
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Créneau récurrent non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la réactivation du créneau récurrent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réactivation du créneau',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Génère (ou renvoie) le cours confirmé pour une date d’occurrence de la série (planning club).
     */
    public function materializeLesson(MaterializeRecurringSlotLessonRequest $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $club = $user->getFirstClub();
            if (! $club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé',
                ], 404);
            }

            $validated = $request->validated();
            $occurrence = Carbon::createFromFormat('Y-m-d', $validated['date'], config('app.timezone'))->startOfDay();

            $recurringSlot = SubscriptionRecurringSlot::whereHas('subscriptionInstance', function ($query) use ($club) {
                $query->whereHas('subscription', function ($q) use ($club) {
                    $q->where('club_id', $club->id);
                });
            })->findOrFail($id);

            $service = new LegacyRecurringSlotService;
            $result = $service->materializeLessonForSingleDate($recurringSlot, $occurrence);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Impossible de générer le cours.',
                ], 422);
            }

            $lesson = Lesson::with([
                'teacher.user',
                'student.user',
                'courseType',
                'location',
                'subscriptionInstances.subscription.template',
            ])->findOrFail($result['lesson']->id);

            return response()->json([
                'success' => true,
                'message' => ($result['already_existed'] ?? false)
                    ? 'Cours déjà présent à cette date.'
                    : 'Cours généré pour cette séance.',
                'data' => [
                    'lesson' => $lesson,
                    'already_existed' => (bool) ($result['already_existed'] ?? false),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Créneau récurrent non trouvé',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur materializeLesson recurring slot: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du cours',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher les détails d'un créneau récurrent
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'club') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé'
                ], 404);
            }

            $recurringSlot = SubscriptionRecurringSlot::whereHas('subscriptionInstance', function ($query) use ($club) {
                    $query->whereHas('subscription', function ($q) use ($club) {
                        $q->where('club_id', $club->id);
                    });
                })
                ->with([
                    'subscriptionInstance.subscription.template',
                    'subscriptionInstance.students.user',
                    'teacher.user',
                    'student.user',
                    'openSlot'
                ])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $recurringSlot
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Créneau récurrent non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du créneau récurrent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du créneau',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

