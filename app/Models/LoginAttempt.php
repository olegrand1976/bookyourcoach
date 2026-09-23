<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginAttempt extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const REASON_INVALID_CREDENTIALS = 'invalid_credentials';

    // Mot de passe correct mais code 2FA faux : le mot de passe est probablement
    // connu d'un tiers, c'est le signal le plus fort de cet historique.
    public const REASON_INVALID_TWO_FACTOR = 'invalid_two_factor';

    protected $fillable = [
        'user_id',
        'email',
        'successful',
        'failure_reason',
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
        'successful' => 'boolean',
        'accuracy_radius_km' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('successful', true);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('successful', false);
    }

    /** « Bruxelles, Belgique » — ou null si la base GeoLite2 n'était pas disponible. */
    public function getLocationLabelAttribute(): ?string
    {
        $parts = array_values(array_filter([$this->city, $this->region, $this->country]));

        return $parts === [] ? null : implode(', ', array_unique($parts));
    }
}
