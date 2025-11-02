<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LessonReplacement;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LessonReplacementController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Liste des demandes de remplacement pour l'enseignant connecté
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable'
                ], 404);
            }

            // Récupérer les demandes où on est le remplaçant potentiel
            // ou où on est le professeur d'origine
            $replacements = LessonReplacement::with([
                'lesson.student.user',
                'lesson.courseType',  // ✅ Correction: courseType au lieu de course_type
                'lesson.club',
                'originalTeacher.user',
                'replacementTeacher.user'
            ])
            ->where(function($query) use ($teacher) {
                $query->where('original_teacher_id', $teacher->id)
                      ->orWhere('replacement_teacher_id', $teacher->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $replacements
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [LessonReplacement] Erreur lors de la récupération des remplacements: ' . $e->getMessage());
            Log::error('❌ [LessonReplacement] Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des remplacements',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Créer une demande de remplacement
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'lesson_id' => 'required|exists:lessons,id',
                'replacement_teacher_id' => 'required|exists:teachers,id',
                'reason' => 'required|string|max:500',
                'notes' => 'nullable|string|max:1000'
            ]);

            $user = $request->user();
            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable'
                ], 404);
            }

            // Vérifier qu'on ne se sélectionne pas soi-même comme remplaçant
            if ($validated['replacement_teacher_id'] == $teacher->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas vous sélectionner comme remplaçant'
                ], 400);
            }

            // Vérifier que le cours appartient bien à cet enseignant
            $lesson = Lesson::findOrFail($validated['lesson_id']);
            
            if ($lesson->teacher_id !== $teacher->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce cours ne vous appartient pas'
                ], 403);
            }

            // Vérifier que le cours n'est pas dans le passé
            if (Carbon::parse($lesson->start_time)->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de demander un remplacement pour un cours passé'
                ], 400);
            }

            // Vérifier qu'il n'existe pas déjà une demande en attente pour ce cours
            $existingReplacement = LessonReplacement::where('lesson_id', $validated['lesson_id'])
                ->where('status', 'pending')
                ->first();

            if ($existingReplacement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une demande de remplacement est déjà en attente pour ce cours'
                ], 400);
            }

            // Vérifier la disponibilité du prof de remplacement
            $replacementTeacher = Teacher::findOrFail($validated['replacement_teacher_id']);
            
            $hasConflict = Lesson::where('teacher_id', $replacementTeacher->id)
                ->where('status', '!=', 'cancelled')
                ->where(function($query) use ($lesson) {
                    $query->whereBetween('start_time', [$lesson->start_time, $lesson->end_time])
                          ->orWhereBetween('end_time', [$lesson->start_time, $lesson->end_time])
                          ->orWhere(function($q) use ($lesson) {
                              $q->where('start_time', '<=', $lesson->start_time)
                                ->where('end_time', '>=', $lesson->end_time);
                          });
                })
                ->exists();

            if ($hasConflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le professeur de remplacement n\'est pas disponible à cet horaire'
                ], 400);
            }

            // Créer la demande
            $replacement = LessonReplacement::create([
                'lesson_id' => $validated['lesson_id'],
                'original_teacher_id' => $teacher->id,
                'replacement_teacher_id' => $validated['replacement_teacher_id'],
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending'
            ]);

            // Charger les relations
            $replacement->load([
                'lesson.student.user',
                'lesson.courseType',
                'lesson.club',
                'originalTeacher.user',
                'replacementTeacher.user'
            ]);

            // Envoyer une notification au professeur de remplacement
            $this->notificationService->notifyReplacementRequest($replacement);

            return response()->json([
                'success' => true,
                'message' => 'Demande de remplacement créée avec succès',
                'data' => $replacement
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la demande de remplacement: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la demande'
            ], 500);
        }
    }

    /**
     * Répondre à une demande de remplacement (accepter/refuser)
     */
    public function respond(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'action' => 'required|in:accept,reject'
            ]);

            $user = $request->user();
            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable'
                ], 404);
            }

            $replacement = LessonReplacement::with([
                'lesson',
                'originalTeacher.user',
                'replacementTeacher.user'
            ])->findOrFail($id);

            // Vérifier que c'est bien le prof de remplacement qui répond
            if ($replacement->replacement_teacher_id !== $teacher->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à répondre à cette demande'
                ], 403);
            }

            // Vérifier que la demande est en attente
            if ($replacement->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette demande a déjà été traitée'
                ], 400);
            }

            DB::beginTransaction();

            try {
                if ($validated['action'] === 'accept') {
                    // Accepter le remplacement
                    $replacement->status = 'accepted';
                    $replacement->responded_at = now();
                    $replacement->save();

                    // Transférer automatiquement le cours vers le remplaçant
                    $lesson = $replacement->lesson;
                    $originalTeacherId = $lesson->teacher_id;
                    $lesson->teacher_id = $replacement->replacement_teacher_id;
                    $lesson->save();

                    // Vérifier que le transfert a bien été effectué
                    $lesson->refresh();
                    if ($lesson->teacher_id !== $replacement->replacement_teacher_id) {
                        Log::error('❌ [LessonReplacement] Échec du transfert du cours', [
                            'lesson_id' => $lesson->id,
                            'expected_teacher_id' => $replacement->replacement_teacher_id,
                            'actual_teacher_id' => $lesson->teacher_id
                        ]);
                        throw new \Exception('Échec du transfert du cours vers le remplaçant');
                    }

                    Log::info('✅ [LessonReplacement] Cours transféré automatiquement', [
                        'lesson_id' => $lesson->id,
                        'original_teacher_id' => $originalTeacherId,
                        'new_teacher_id' => $replacement->replacement_teacher_id,
                        'replacement_id' => $replacement->id
                    ]);

                    DB::commit();

                    // Notifier le professeur d'origine et le club
                    $replacement->load(['lesson.club', 'lesson.teacher.user', 'originalTeacher.user', 'replacementTeacher.user']);
                    $this->notificationService->notifyReplacementAccepted($replacement);

                    return response()->json([
                        'success' => true,
                        'message' => 'Remplacement accepté avec succès. Le cours a été transféré automatiquement.',
                        'data' => $replacement
                    ]);

                } else {
                    // Refuser le remplacement
                    $replacement->status = 'rejected';
                    $replacement->responded_at = now();
                    $replacement->save();

                    DB::commit();

                    // Notifier le professeur d'origine
                    $replacement->load(['originalTeacher.user', 'replacementTeacher.user']);
                    $this->notificationService->notifyReplacementRejected($replacement);

                    return response()->json([
                        'success' => true,
                        'message' => 'Remplacement refusé',
                        'data' => $replacement
                    ]);
                }

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la réponse à la demande de remplacement: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement de la réponse'
            ], 500);
        }
    }

    /**
     * Annuler une demande de remplacement (par le professeur d'origine)
     */
    public function cancel(Request $request, $id)
    {
        try {
            $user = $request->user();
            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable'
                ], 404);
            }

            $replacement = LessonReplacement::findOrFail($id);

            // Vérifier que c'est bien le prof d'origine qui annule
            if ($replacement->original_teacher_id !== $teacher->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à annuler cette demande'
                ], 403);
            }

            // On ne peut annuler qu'une demande en attente
            if ($replacement->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d\'annuler une demande déjà traitée'
                ], 400);
            }

            $replacement->status = 'cancelled';
            $replacement->save();

            return response()->json([
                'success' => true,
                'message' => 'Demande de remplacement annulée'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'annulation de la demande: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation'
            ], 500);
        }
    }
}
