<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\VolunteerLetterSend;
use App\Mail\VolunteerLetterMail;
use App\Jobs\SendVolunteerLetterJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class VolunteerLetterController extends Controller
{
    /**
     * Envoyer la lettre à un enseignant spécifique
     */
    public function sendToTeacher(Request $request, $teacherId)
    {
        try {
            $user = $request->user();
            
            // Récupérer le club de l'utilisateur
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->first();
            
            if (!$clubUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez être administrateur d\'un club'
                ], 403);
            }
            
            $club = Club::with(['teachers'])->find($clubUser->club_id);
            
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club introuvable'
                ], 404);
            }
            
            // Vérifier que l'enseignant appartient au club
            $teacher = $club->teachers()->with('user')->find($teacherId);
            
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enseignant introuvable ou non affilié à votre club'
                ], 404);
            }
            
            if (!$teacher->user || !$teacher->user->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'enseignant n\'a pas d\'adresse email'
                ], 400);
            }
            
            // Vérifier les informations légales du club
            if (!$this->checkClubLegalInfo($club)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les informations légales du club sont incomplètes'
                ], 400);
            }
            
            // Dispatcher le job pour l'envoi asynchrone
            SendVolunteerLetterJob::dispatch($club->id, $teacher->id, $user->id);
            
            Log::info('Job d\'envoi de lettre ajouté à la queue', [
                'club_id' => $club->id,
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->user->name,
                'email' => $teacher->user->email
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'La lettre à ' . $teacher->user->name . ' sera envoyée sous peu.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur envoi lettre individuelle: ' . $e->getMessage(), [
                'teacher_id' => $teacherId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la lettre',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Envoyer les lettres à tous les enseignants du club
     */
    public function sendToAll(Request $request)
    {
        try {
            $user = $request->user();
            
            // Récupérer le club de l'utilisateur
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->first();
            
            if (!$clubUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez être administrateur d\'un club'
                ], 403);
            }
            
            $club = Club::with(['teachers.user'])->find($clubUser->club_id);
            
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club introuvable'
                ], 404);
            }
            
            // Vérifier les informations légales du club
            if (!$this->checkClubLegalInfo($club)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les informations légales du club sont incomplètes'
                ], 400);
            }
            
            $teachers = $club->teachers()->with('user')->get();
            
            if ($teachers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun enseignant affilié à votre club'
                ], 404);
            }
            
            $queued = 0;
            $skipped = 0;
            $details = [];
            
            // Dispatcher un job pour chaque enseignant
            foreach ($teachers as $teacher) {
                // Vérifier que l'enseignant a un email
                if (!$teacher->user || !$teacher->user->email) {
                    $skipped++;
                    $details[] = [
                        'teacher' => $teacher->user->name ?? 'Inconnu',
                        'status' => 'skipped',
                        'message' => 'Pas d\'adresse email'
                    ];
                    continue;
                }
                
                // Dispatcher le job
                SendVolunteerLetterJob::dispatch($club->id, $teacher->id, $user->id);
                $queued++;
                
                $details[] = [
                    'teacher' => $teacher->user->name,
                    'email' => $teacher->user->email,
                    'status' => 'queued',
                    'message' => 'Ajouté à la file d\'attente'
                ];
            }
            
            Log::info('Jobs d\'envoi de lettres ajoutés à la queue', [
                'club_id' => $club->id,
                'queued' => $queued,
                'skipped' => $skipped
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "{$queued} lettre(s) en cours d'envoi. Vous serez notifié une fois l'envoi terminé.",
                'results' => [
                    'total' => $teachers->count(),
                    'queued' => $queued,
                    'skipped' => $skipped,
                    'details' => $details
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur envoi lettres en masse: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi des lettres',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtenir l'historique des envois
     */
    public function history(Request $request)
    {
        try {
            $user = $request->user();
            
            // Récupérer le club de l'utilisateur
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->first();
            
            if (!$clubUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez être administrateur d\'un club'
                ], 403);
            }
            
            $sends = VolunteerLetterSend::where('club_id', $clubUser->club_id)
                ->with(['teacher.user', 'sentBy'])
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();
            
            return response()->json([
                'success' => true,
                'sends' => $sends
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur récupération historique: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Vérifier que les informations légales sont complètes
     */
    private function checkClubLegalInfo(Club $club): bool
    {
        $required = [
            'name',
            'company_number',
            'legal_representative_name',
            'legal_representative_role',
            'insurance_rc_company',
            'insurance_rc_policy_number',
            'expense_reimbursement_type'
        ];
        
        foreach ($required as $field) {
            if (empty($club->$field)) {
                return false;
            }
        }
        
        return true;
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
}
