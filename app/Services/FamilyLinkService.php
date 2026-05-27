<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Gestion des liens "compte famille" : génération de codes d'invitation
 * pour les élèves créés sans email, rattachement à un compte parent,
 * dissociation.
 */
class FamilyLinkService
{
    /**
     * Durée de validité par défaut d'un code d'invitation.
     */
    public const DEFAULT_TTL_DAYS = 30;

    /**
     * Longueur du code généré.
     */
    public const CODE_LENGTH = 10;

    /**
     * Caractères autorisés : Base32 sans 0/O/I/1 (lisibilité humaine).
     */
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Nombre maximum de tentatives pour générer un code unique.
     */
    private const MAX_GENERATION_ATTEMPTS = 10;

    /**
     * Génère un code d'invitation pour un élève qui n'a pas encore de compte.
     * Ne fait rien (renvoie le code existant s'il est encore valide).
     *
     * @throws RuntimeException si l'élève a déjà un user_id (déjà rattaché).
     */
    public function generateInviteCode(Student $student): string
    {
        if ($student->user_id !== null) {
            throw new RuntimeException('Impossible de générer un code : cet élève est déjà rattaché à un compte.');
        }

        if ($this->isExistingCodeStillValid($student)) {
            return (string) $student->invite_code;
        }

        return $this->assignFreshCode($student);
    }

    /**
     * Régénère un code (invalide l'ancien). Réservé aux clubs/admins.
     *
     * @throws RuntimeException si l'élève est déjà rattaché.
     */
    public function regenerateInviteCode(Student $student, User $by): string
    {
        if ($student->user_id !== null) {
            throw new RuntimeException('Impossible de régénérer un code : cet élève est déjà rattaché.');
        }

        $code = $this->assignFreshCode($student);

        Log::info('Code d\'invitation famille régénéré', [
            'student_id' => $student->id,
            'by_user_id' => $by->id,
        ]);

        return $code;
    }

    /**
     * Rattache un enfant (Student sans user_id) à un compte parent via le code.
     *
     * @throws RuntimeException avec message court (clé d'erreur) pour mapping HTTP.
     */
    public function linkChildToParent(string $code, User $parent): Student
    {
        $normalized = $this->normalizeCode($code);

        if ($parent->role !== User::ROLE_STUDENT) {
            throw new RuntimeException('parent_role_invalid');
        }

        return DB::transaction(function () use ($normalized, $parent) {
            $student = Student::where('invite_code', $normalized)->lockForUpdate()->first();

            if (! $student) {
                throw new RuntimeException('code_not_found');
            }

            if ($student->user_id !== null) {
                throw new RuntimeException('already_linked');
            }

            if ($student->invite_code_expires_at && $student->invite_code_expires_at->isPast()) {
                throw new RuntimeException('code_expired');
            }

            $student->update([
                'user_id' => $parent->id,
                'invite_code' => null,
                'invite_code_expires_at' => null,
                'linked_by_user_id' => $parent->id,
                'linked_at' => now(),
            ]);

            $this->maybeAttachFamilyLink($student, $parent);

            Log::info('Enfant rattaché au compte parent', [
                'student_id' => $student->id,
                'parent_user_id' => $parent->id,
            ]);

            return $student->fresh();
        });
    }

    /**
     * Dissocie un enfant du compte parent et regénère un code.
     */
    public function unlinkChildFromParent(Student $child, User $by): Student
    {
        if ($child->user_id === null) {
            throw new RuntimeException('not_linked');
        }

        return DB::transaction(function () use ($child, $by) {
            $previousParentId = $child->user_id;

            $child->update([
                'user_id' => null,
                'linked_at' => null,
                'linked_by_user_id' => null,
            ]);

            $newCode = $this->assignFreshCode($child);

            $this->detachFamilyLinks($child->id, $previousParentId);

            Log::info('Enfant dissocié du compte parent', [
                'student_id' => $child->id,
                'previous_parent_user_id' => $previousParentId,
                'by_user_id' => $by->id,
                'new_code_generated' => true,
            ]);

            $child->setAttribute('invite_code', $newCode);

            return $child;
        });
    }

    /**
     * Normalise un code saisi (uppercase, suppression espaces/tirets).
     */
    public function normalizeCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/i', '', $code));
    }

    private function isExistingCodeStillValid(Student $student): bool
    {
        if (empty($student->invite_code)) {
            return false;
        }

        if (! $student->invite_code_expires_at) {
            return true;
        }

        return $student->invite_code_expires_at->isFuture();
    }

    private function assignFreshCode(Student $student): string
    {
        $code = $this->generateUniqueCode();

        $student->update([
            'invite_code' => $code,
            'invite_code_expires_at' => now()->addDays(self::DEFAULT_TTL_DAYS),
        ]);

        return $code;
    }

    private function generateUniqueCode(): string
    {
        for ($i = 0; $i < self::MAX_GENERATION_ATTEMPTS; $i++) {
            $candidate = $this->randomCode();

            if (! Student::where('invite_code', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new RuntimeException('Impossible de générer un code d\'invitation unique après plusieurs tentatives.');
    }

    private function randomCode(): string
    {
        $alphabet = self::CODE_ALPHABET;
        $max = strlen($alphabet) - 1;
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
    }

    /**
     * Si le parent possède son propre profil Student, on matérialise le lien
     * dans student_family_links (sémantique "parent").
     */
    private function maybeAttachFamilyLink(Student $child, User $parent): void
    {
        $parentStudent = Student::where('user_id', $parent->id)
            ->where('id', '!=', $child->id)
            ->orderBy('id')
            ->first();

        if (! $parentStudent) {
            return;
        }

        $alreadyLinked = DB::table('student_family_links')
            ->where(function ($q) use ($parentStudent, $child) {
                $q->where('primary_student_id', $parentStudent->id)
                    ->where('linked_student_id', $child->id);
            })
            ->orWhere(function ($q) use ($parentStudent, $child) {
                $q->where('primary_student_id', $child->id)
                    ->where('linked_student_id', $parentStudent->id);
            })
            ->exists();

        if ($alreadyLinked) {
            return;
        }

        DB::table('student_family_links')->insert([
            'primary_student_id' => $parentStudent->id,
            'linked_student_id' => $child->id,
            'relationship_type' => 'parent',
            'created_by' => $parent->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Supprime les liens family entre l'enfant dissocié et l'ancien parent.
     */
    private function detachFamilyLinks(int $childStudentId, int $previousParentUserId): void
    {
        $parentStudentIds = Student::where('user_id', $previousParentUserId)
            ->pluck('id')
            ->all();

        if ($parentStudentIds === []) {
            return;
        }

        DB::table('student_family_links')
            ->where(function ($q) use ($childStudentId, $parentStudentIds) {
                $q->whereIn('primary_student_id', $parentStudentIds)
                    ->where('linked_student_id', $childStudentId);
            })
            ->orWhere(function ($q) use ($childStudentId, $parentStudentIds) {
                $q->where('primary_student_id', $childStudentId)
                    ->whereIn('linked_student_id', $parentStudentIds);
            })
            ->delete();
    }
}
