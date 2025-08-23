<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="AuditLog",
 *     type="object",
 *     title="AuditLog",
 *     description="Journal d'audit des actions"
 * )
 */
class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'data',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'data' => 'array',
        'user_id' => 'integer',
        'model_id' => 'integer'
    ];

    /**
     * Relation avec l'utilisateur qui a effectué l'action
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation polymorphe avec le modèle concerné
     */
    public function model()
    {
        return $this->morphTo();
    }

    /**
     * Scope pour filtrer par utilisateur
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour filtrer par type de modèle
     */
    public function scopeForModel($query, $modelType, $modelId = null)
    {
        $query = $query->where('model_type', $modelType);

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        return $query;
    }

    /**
     * Scope pour filtrer par action
     */
    public function scopeForAction($query, $action)
    {
        return $query->where('action', $action);
    }
}
