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
            // Récupérer l'ID de l'étudiant actif depuis la session
            $activeStudentId = session('active_student_id', $user->student->id);
            
            // Vérifier que l'étudiant est bien lié au compte ou est le compte principal
            $linkedStudents = $user->getLinkedStudents();
            $isLinked = $linkedStudents->contains('id', $activeStudentId) 
                     || $user->student->id === $activeStudentId;
            
            if ($isLinked) {
                // Injecter dans la requête pour utilisation dans les contrôleurs
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
