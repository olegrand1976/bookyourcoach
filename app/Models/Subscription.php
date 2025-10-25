<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'name',
        'total_lessons',
        'free_lessons',
        'price',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_lessons' => 'integer',
        'free_lessons' => 'integer',
        'price' => 'decimal:2',
    ];

    /**
     * Le club qui propose cet abonnement
     */
    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Les types de cours (disciplines) inclus dans cet abonnement
     */
    public function courseTypes()
    {
        return $this->belongsToMany(Discipline::class, 'subscription_course_types', 'subscription_id', 'discipline_id')
            ->withTimestamps();
    }

    /**
     * Les instances d'abonnements (abonnements achetés)
     */
    public function instances()
    {
        return $this->hasMany(SubscriptionInstance::class);
    }

    /**
     * Alias pour compatibilité
     */
    public function subscriptionStudents()
    {
        return $this->instances();
    }

    /**
     * Nombre total de cours (payés + gratuits)
     */
    public function getTotalAvailableLessonsAttribute()
    {
        return $this->total_lessons + $this->free_lessons;
    }
}
