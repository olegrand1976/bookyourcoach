<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LessonReplacement;
use App\Models\Notification;
use App\Services\NotificationService;

class TestNotifications extends Command
{
    protected $signature = 'test:notifications {replacement_id}';
    protected $description = 'Tester le système de notifications en simulant l\'acceptation d\'un remplacement';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $replacementId = $this->argument('replacement_id');
        
        $this->info("🧪 Test du système de notifications");
        $this->info("====================================\n");

        try {
            // Récupérer le remplacement
            $replacement = LessonReplacement::with([
                'lesson.student.user',
                'lesson.courseType',
                'lesson.club',
                'originalTeacher.user',
                'replacementTeacher.user'
            ])->findOrFail($replacementId);

            $this->line("📋 Remplacement #{$replacementId}");
            $this->line("  Demandeur: {$replacement->originalTeacher->user->name}");
            $this->line("  Remplaçant: {$replacement->replacementTeacher->user->name}");
            $this->line("  Statut: {$replacement->status}");
            $this->line("  Cours: " . $replacement->lesson->start_time->format('d/m/Y à H:i'));
            $this->newLine();

            if ($replacement->status !== 'pending') {
                $this->error("❌ Ce remplacement n'est pas en attente (statut: {$replacement->status})");
                return 1;
            }

            // Créer les notifications
            $this->info("📬 Création des notifications...\n");

            // 1. Notification au demandeur
            $this->line("1️⃣ Notification à l'enseignant demandeur:");
            $this->notificationService->notifyReplacementAccepted($replacement);
            $this->line("   ✅ Envoyée à: {$replacement->originalTeacher->user->email}");

            // Afficher les notifications créées
            $this->newLine();
            $this->info("📊 Notifications créées:");
            $this->newLine();

            $teacherNotif = Notification::where('user_id', $replacement->originalTeacher->user->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($teacherNotif) {
                $this->line("📧 Pour {$replacement->originalTeacher->user->name}:");
                $this->line("   Titre: {$teacherNotif->title}");
                $this->line("   Message: {$teacherNotif->message}");
                $this->line("   Type: {$teacherNotif->type}");
                $this->line("   Lu: " . ($teacherNotif->read ? 'Oui' : 'Non'));
                $this->newLine();
            }

            $clubAdmins = $replacement->lesson->club->users()->wherePivot('is_admin', true)->get();
            
            if ($clubAdmins->count() > 0) {
                foreach ($clubAdmins as $admin) {
                    $clubNotif = Notification::where('user_id', $admin->id)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($clubNotif) {
                        $this->line("📧 Pour le club admin ({$admin->name}):");
                        $this->line("   Titre: {$clubNotif->title}");
                        $this->line("   Message: {$clubNotif->message}");
                        $this->line("   Type: {$clubNotif->type}");
                        $this->line("   Lu: " . ($clubNotif->read ? 'Oui' : 'Non'));
                        $this->newLine();
                    }
                }
            }

            // Statistiques globales
            $this->info("📈 Statistiques totales:");
            $teacherTotal = Notification::where('user_id', $replacement->originalTeacher->user->id)->count();
            $replacementTotal = Notification::where('user_id', $replacement->replacementTeacher->user->id)->count();

            $this->line("   {$replacement->originalTeacher->user->name}: {$teacherTotal} notification(s)");
            $this->line("   {$replacement->replacementTeacher->user->name}: {$replacementTotal} notification(s)");
            
            foreach ($clubAdmins as $admin) {
                $adminTotal = Notification::where('user_id', $admin->id)->count();
                $this->line("   {$admin->name} (admin club): {$adminTotal} notification(s)");
            }

            $this->newLine();
            $this->info("✅ Test terminé avec succès !");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ ERREUR: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}

