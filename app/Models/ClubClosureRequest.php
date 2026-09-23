<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubClosureRequest extends Model
{
    public const UPDATED_AT = null;

    public const ACTION_CLOSE = 'close';

    public const ACTION_OPEN = 'open';

    public const METHOD_PASSWORD = 'password';

    public const METHOD_TOTP = 'totp';

    public const OUTCOME_SUCCESS = 'success';

    public const OUTCOME_INVALID_CREDENTIAL = 'invalid_credential';

    public const OUTCOME_IMPACT_MISMATCH = 'impact_mismatch';

    // Confirmation acceptée, mais l'action a levé une exception.
    public const OUTCOME_ERROR = 'error';

    protected $fillable = [
        'club_id',
        'user_id',
        'closed_on',
        'action',
        'confirmation_method',
        'outcome',
        'impacted_lessons',
        'ip_address',
        'forwarded_for',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'country_code',
        'country',
        'region',
        'city',
        'time_zone',
        'organisation',
        'accuracy_radius_km',
    ];

    protected $casts = [
        'club_id' => 'integer',
        'user_id' => 'integer',
        'closed_on' => 'date',
        'impacted_lessons' => 'integer',
        'accuracy_radius_km' => 'integer',
        'created_at' => 'datetime',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** « Bruxelles, Belgique » — ou null si la base GeoLite2 n'était pas disponible. */
    public function getLocationLabelAttribute(): ?string
    {
        $parts = array_values(array_filter([$this->city, $this->region, $this->country]));

        return $parts === [] ? null : implode(', ', array_unique($parts));
    }
}
