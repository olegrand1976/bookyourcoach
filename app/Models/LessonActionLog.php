<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonActionLog extends Model
{
    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_CANCELLED = 'cancelled';

    public const ACTION_CANCELLED_CASCADE = 'cancelled_cascade';

    public const ACTION_DELETED = 'deleted';

    public const ACTION_DELETED_CASCADE = 'deleted_cascade';

    public const ACTION_REACTIVATED = 'reactivated';

    public const ACTION_SUBSCRIPTION_LINKED = 'subscription_linked';

    public const ACTION_SUBSCRIPTION_UNLINKED = 'subscription_unlinked';

    public const ACTION_CERTIFICATE_ACCEPTED = 'certificate_accepted';

    public const ACTION_CERTIFICATE_REJECTED = 'certificate_rejected';

    public const ACTION_CERTIFICATE_CLOSED = 'certificate_closed';

    public const ACTION_STUDENT_CANCELLED = 'student_cancelled';

    /** Filtre API : toutes les variantes d'annulation. */
    public const ACTION_FILTER_ALL_CANCELLATIONS = 'all_cancellations';

    /** @var list<string> */
    public const CANCELLATION_ACTIONS = [
        self::ACTION_CANCELLED,
        self::ACTION_CANCELLED_CASCADE,
        self::ACTION_STUDENT_CANCELLED,
    ];

    /** @var array<string, string> */
    public const ACTION_LABELS = [
        self::ACTION_CREATED => 'Création',
        self::ACTION_UPDATED => 'Modification',
        self::ACTION_CANCELLED => 'Annulation',
        self::ACTION_CANCELLED_CASCADE => 'Annulation (série)',
        self::ACTION_DELETED => 'Suppression',
        self::ACTION_DELETED_CASCADE => 'Suppression (série)',
        self::ACTION_REACTIVATED => 'Réactivation',
        self::ACTION_SUBSCRIPTION_LINKED => 'Lien abonnement',
        self::ACTION_SUBSCRIPTION_UNLINKED => 'Délien abonnement',
        self::ACTION_CERTIFICATE_ACCEPTED => 'Certificat accepté',
        self::ACTION_CERTIFICATE_REJECTED => 'Certificat refusé',
        self::ACTION_CERTIFICATE_CLOSED => 'Certificat clôturé',
        self::ACTION_STUDENT_CANCELLED => 'Annulation par l\'élève',
    ];

    protected $fillable = [
        'club_id',
        'lesson_id',
        'student_id',
        'subscription_instance_id',
        'performed_by_user_id',
        'performed_by_role',
        'action',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'club_id' => 'integer',
        'lesson_id' => 'integer',
        'student_id' => 'integer',
        'subscription_instance_id' => 'integer',
        'performed_by_user_id' => 'integer',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subscriptionInstance(): BelongsTo
    {
        return $this->belongsTo(SubscriptionInstance::class);
    }

    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    public function actionLabel(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }

    /**
     * Résout le filtre action de l'API en liste d'actions SQL.
     * « Annulation » (cancelled) inclut aussi les annulations élève et série.
     *
     * @return list<string>|null null = pas de filtre action
     */
    public static function resolveActionFilters(?string $action): ?array
    {
        if ($action === null || $action === '') {
            return null;
        }

        if ($action === self::ACTION_FILTER_ALL_CANCELLATIONS || $action === self::ACTION_CANCELLED) {
            return self::CANCELLATION_ACTIONS;
        }

        if (in_array($action, self::CANCELLATION_ACTIONS, true)) {
            return [$action];
        }

        return [$action];
    }
}
