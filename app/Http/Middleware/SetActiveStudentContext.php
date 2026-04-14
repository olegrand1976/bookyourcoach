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
        
        if ($user && $user->role === 'student') {
            $householdIds = $user->getHouseholdStudentIds();
            if ($householdIds === []) {
                return $next($request);
            }

            $defaultStudentId = $user->student?->id ?? $householdIds[0];

            // Aligné sur DashboardController::getActiveStudent : priorité au paramètre requête (query / body), puis session.
            $param = $request->query('active_student_id') ?? $request->input('active_student_id');
            $isGlobalScope = $param === 'all';
            if ($param !== null && $param !== '' && ! $isGlobalScope) {
                $activeStudentId = (int) $param;
            } else {
                $activeStudentId = (int) session('active_student_id', $defaultStudentId);
            }

            // Vérifier que l'étudiant appartient au foyer (même user_id + liens famille)
            $isLinked = in_array((int) $activeStudentId, $householdIds, true);

            if ($isLinked) {
                session(['active_student_id' => $activeStudentId]);
                // Ne jamais écraser active_student_id=all : sinon la vue globale est perdue.
                if (! $isGlobalScope) {
                    $request->merge(['active_student_id' => $activeStudentId]);
                }
            } else {
                // Réinitialiser au compte principal si le compte actif n'est plus valide
                session(['active_student_id' => $defaultStudentId]);
                if (! $isGlobalScope) {
                    $request->merge(['active_student_id' => $defaultStudentId]);
                }
            }
        }
        
        return $next($request);
    }
}
