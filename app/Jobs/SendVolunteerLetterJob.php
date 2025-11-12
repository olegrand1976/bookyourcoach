<?php

namespace App\Jobs;

use App\Models\Club;
use App\Models\Teacher;
use App\Models\VolunteerLetterSend;
use App\Mail\VolunteerLetterMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class SendVolunteerLetterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected $clubId;
    protected $teacherId;
    protected $sentByUserId;

    /**
     * Create a new job instance.
     */
    public function __construct($clubId, $teacherId, $sentByUserId = null)
    {
        $this->clubId = $clubId;
        $this->teacherId = $teacherId;
        $this->sentByUserId = $sentByUserId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Charger le club et l'enseignant
            $club = Club::find($this->clubId);
            $teacher = Teacher::with('user')->find($this->teacherId);

            if (!$club || !$teacher || !$teacher->user || !$teacher->user->email) {
                Log::error('SendVolunteerLetterJob - Données manquantes', [
                    'club_id' => $this->clubId,
                    'teacher_id' => $this->teacherId
                ]);
                return;
            }

            // Créer l'enregistrement d'envoi
            $letterSend = VolunteerLetterSend::create([
                'club_id' => $club->id,
                'teacher_id' => $teacher->id,
                'sent_by_user_id' => $this->sentByUserId,
                'recipient_email' => $teacher->user->email,
                'status' => 'pending',
            ]);

            // Générer le PDF
            $pdfPath = $this->generatePDF($club, $teacher);

            // Envoyer l'email
            Mail::to($teacher->user->email)->send(
                new VolunteerLetterMail($club, $teacher, $pdfPath)
            );

            // Marquer comme envoyé
            $letterSend->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            // Supprimer le fichier temporaire
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            Log::info('SendVolunteerLetterJob - Lettre envoyée avec succès', [
                'teacher' => $teacher->user->name,
                'email' => $teacher->user->email
            ]);

        } catch (\Exception $e) {
            // Marquer comme échoué
            if (isset($letterSend)) {
                $letterSend->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            // Supprimer le fichier temporaire si erreur
            if (isset($pdfPath) && file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            Log::error('SendVolunteerLetterJob - Erreur', [
                'teacher_id' => $this->teacherId,
                'error' => $e->getMessage()
            ]);

            throw $e; // Relancer pour permettre les retry
        }
    }

    /**
     * Générer le PDF de la lettre
     */
    private function generatePDF(Club $club, Teacher $teacher): string
    {
        // Générer la vue HTML
        $html = view('pdf.volunteer-letter', [
            'club' => $club,
            'teacher' => $teacher
        ])->render();

        // Générer le PDF
        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);

        // Sauvegarder temporairement
        $fileName = 'volunteer_letter_' . $club->id . '_' . $teacher->id . '_' . time() . '.pdf';
        $pdfPath = storage_path('app/temp/' . $fileName);

        // Créer le dossier temp s'il n'existe pas
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $pdf->save($pdfPath);

        return $pdfPath;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendVolunteerLetterJob - Échec définitif après tous les essais', [
            'club_id' => $this->clubId,
            'teacher_id' => $this->teacherId,
            'error' => $exception->getMessage()
        ]);

        // Marquer comme échoué dans la base de données
        $letterSend = VolunteerLetterSend::where('club_id', $this->clubId)
            ->where('teacher_id', $this->teacherId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($letterSend) {
            $letterSend->update([
                'status' => 'failed',
                'error_message' => 'Échec après ' . $this->tries . ' tentatives: ' . $exception->getMessage(),
            ]);
        }
    }
}
