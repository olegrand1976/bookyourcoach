<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="GoogleCalendarToken",
 *     type="object",
 *     title="Google Calendar Token",
 *     description="Token d'accès Google Calendar pour un utilisateur",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="access_token", type="string", example="..."),
 *     @OA\Property(property="user_info", type="object"),
 *     @OA\Property(property="calendars", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class GoogleCalendarToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'access_token',
        'user_info',
        'calendars',
        'expires_at'
    ];

    protected $casts = [
        'user_info' => 'array',
        'calendars' => 'array',
        'expires_at' => 'datetime'
    ];

    /**
     * Get the user that owns this token
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the token is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Get the access token as an array
     */
    public function getAccessTokenArray(): array
    {
        if (is_string($this->access_token)) {
            return json_decode($this->access_token, true) ?? [];
        }

        return $this->access_token ?? [];
    }

    /**
     * Set the access token from an array
     */
    public function setAccessTokenArray(array $token): void
    {
        $this->access_token = json_encode($token);
    }
}

