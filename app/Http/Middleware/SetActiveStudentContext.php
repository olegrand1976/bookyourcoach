<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveStudentContext
{
    /**
     * Handle an incoming request.
     * Définit le contexte de l'étudiant actif pour les utilisateurs avec le rôle 'student'.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if ($user && $user->role === 'student' && $user->student) {
            // Aligné sur DashboardController::getActiveStudent : priorité au paramètre requête (query / body), puis session.
            $param = $request->query('active_student_id') ?? $request->input('active_student_id');
            if ($param !== null && $param !== '' && $param !== 'all') {
                $activeStudentId = (int) $param;
            } else {
                $activeStudentId = (int) session('active_student_id', $user->student->id);
            }

            // Vérifier que l'étudiant est bien lié au compte ou est le compte principal
            $linkedStudents = $user->getLinkedStudents();
            $linkedIds = $linkedStudents->pluck('id')->map(fn ($id) => (int) $id);
            $isLinked = $linkedIds->contains((int) $activeStudentId)
                     || (int) $user->student->id === (int) $activeStudentId;

            if ($isLinked) {
                session(['active_student_id' => $activeStudentId]);
                $request->merge(['active_student_id' => $activeStudentId]);
            } else {
                // Réinitialiser au compte principal si le compte actif n'est plus valide
                session(['active_student_id' => $user->student->id]);
                $request->merge(['active_student_id' => $user->student->id]);
            }
        }
        
        return $next($request);
    }
}
