<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\LessonReplacement;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Créer une notification pour une nouvelle demande de remplacement
     */
    public function notifyReplacementRequest(LessonReplacement $replacement): void
    {
        try {
            $replacementTeacher = $replacement->replacementTeacher;
            $originalTeacher = $replacement->originalTeacher;
            $lesson = $replacement->lesson;

            if (!$replacementTeacher || !$replacementTeacher->user) {
                Log::warning('❌ Impossible de notifier: enseignant remplaçant introuvable', [
                    'replacement_id' => $replacement->id
                ]);
                return;
            }

            $studentName = $lesson->student->user->name ?? 'Élève non assigné';
            $lessonDate = $lesson->start_time->format('d/m/Y à H:i');

            Notification::create([
                'user_id' => $replacementTeacher->user->id,
                'type' => 'replacement_request',
                'title' => '🔔 Nouvelle demande de remplacement',
                'message' => "{$originalTeacher->user->name} vous demande de le/la remplacer pour un cours avec {$studentName} le {$lessonDate}",
                'data' => [
                    'replacement_id' => $replacement->id,
                    'lesson_id' => $lesson->id,
                    'original_teacher_id' => $originalTeacher->id,
                    'lesson_date' => $lesson->start_time->toISOString(),
                ]
            ]);

            Log::info('✅ Notification demande de remplacement créée', [
                'replacement_id' => $replacement->id,
                'to_user' => $replacementTeacher->user->email
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur création notification demande: ' . $e->getMessage());
        }
    }

    /**
     * Créer une notification quand un remplacement est accepté
     */
    public function notifyReplacementAccepted(LessonReplacement $replacement): void
    {
        try {
            $replacementTeacher = $replacement->replacementTeacher;
            $originalTeacher = $replacement->originalTeacher;
            $lesson = $replacement->lesson;

            // 1. Notifier l'enseignant demandeur
            if ($originalTeacher && $originalTeacher->user) {
                $lessonDate = $lesson->start_time->format('d/m/Y à H:i');
                $studentName = $lesson->student->user->name ?? 'Élève non assigné';

                Notification::create([
                    'user_id' => $originalTeacher->user->id,
                    'type' => 'replacement_accepted',
                    'title' => '✅ Remplacement accepté',
                    'message' => "{$replacementTeacher->user->name} a accepté de vous remplacer pour le cours avec {$studentName} le {$lessonDate}",
                    'data' => [
                        'replacement_id' => $replacement->id,
                        'lesson_id' => $lesson->id,
                        'replacement_teacher_id' => $replacementTeacher->id,
                        'lesson_date' => $lesson->start_time->toISOString(),
                    ]
                ]);

                Log::info('✅ Notification acceptation créée pour demandeur', [
                    'replacement_id' => $replacement->id,
                    'to_user' => $originalTeacher->user->email
                ]);
            }

            // 2. Notifier le club (administrateurs du club)
            $club = $lesson->club;
            if ($club) {
                $lessonDate = $lesson->start_time->format('d/m/Y à H:i');
                
                // Récupérer les administrateurs du club
                $clubAdmins = $club->users()->wherePivot('is_admin', true)->get();
                
                foreach ($clubAdmins as $admin) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'type' => 'club_replacement_accepted',
                        'title' => 'ℹ️ Changement d\'enseignant',
                        'message' => "{$replacementTeacher->user->name} remplacera {$originalTeacher->user->name} le {$lessonDate}",
                        'data' => [
                            'replacement_id' => $replacement->id,
                            'lesson_id' => $lesson->id,
                            'original_teacher_id' => $originalTeacher->id,
                            'replacement_teacher_id' => $replacementTeacher->id,
                            'lesson_date' => $lesson->start_time->toISOString(),
                        ]
                    ]);

                    Log::info('✅ Notification acceptation créée pour club admin', [
                        'replacement_id' => $replacement->id,
                        'club' => $club->name,
                        'admin' => $admin->email
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('❌ Erreur création notification acceptation: ' . $e->getMessage());
        }
    }

    /**
     * Créer une notification quand un remplacement est refusé
     */
    public function notifyReplacementRejected(LessonReplacement $replacement): void
    {
        try {
            $replacementTeacher = $replacement->replacementTeacher;
            $originalTeacher = $replacement->originalTeacher;
            $lesson = $replacement->lesson;

            if (!$originalTeacher || !$originalTeacher->user) {
                Log::warning('❌ Impossible de notifier: enseignant demandeur introuvable', [
                    'replacement_id' => $replacement->id
                ]);
                return;
            }

            $lessonDate = $lesson->start_time->format('d/m/Y à H:i');
            $studentName = $lesson->student->user->name ?? 'Élève non assigné';

            Notification::create([
                'user_id' => $originalTeacher->user->id,
                'type' => 'replacement_rejected',
                'title' => '❌ Remplacement refusé',
                'message' => "{$replacementTeacher->user->name} a refusé votre demande de remplacement pour le cours avec {$studentName} le {$lessonDate}",
                'data' => [
                    'replacement_id' => $replacement->id,
                    'lesson_id' => $lesson->id,
                    'replacement_teacher_id' => $replacementTeacher->id,
                    'lesson_date' => $lesson->start_time->toISOString(),
                ]
            ]);

            Log::info('✅ Notification refus créée', [
                'replacement_id' => $replacement->id,
                'to_user' => $originalTeacher->user->email
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur création notification refus: ' . $e->getMessage());
        }
    }

    /**
     * Récupérer les notifications non lues pour un utilisateur
     */
    public function getUnreadCount(User $user): int
    {
        try {
            // Vérifier si la table existe et si la colonne read existe
            if (!\Illuminate\Support\Facades\Schema::hasTable('notifications')) {
                Log::warning('Table notifications does not exist');
                return 0;
            }
            
            // Utiliser whereRaw pour gérer les cas où 'read' pourrait être une colonne réservée
            return Notification::where('user_id', $user->id)
                ->where('read', false)
                ->count();
        } catch (\Exception $e) {
            Log::error('❌ Erreur dans getUnreadCount: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            // Retourner 0 en cas d'erreur pour ne pas bloquer l'interface
            return 0;
        }
    }

    /**
     * Récupérer toutes les notifications d'un utilisateur
     */
    public function getUserNotifications(User $user, int $limit = 50)
    {
        return Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(User $user): void
    {
        Notification::where('user_id', $user->id)
            ->where('read', false)
            ->update([
                'read' => true,
                'read_at' => now()
            ]);
    }
}

