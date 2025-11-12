<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'model_number',
        'total_lessons',
        'free_lessons',
        'price',
        'validity_months',
        'validity_value',
        'validity_unit',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_lessons' => 'integer',
        'free_lessons' => 'integer',
        'price' => 'decimal:2',
        'validity_months' => 'integer',
        'validity_value' => 'integer',
    ];

    protected $attributes = [
        'free_lessons' => 0,
        'validity_months' => 12,
        'is_active' => true,
    ];

    /**
     * Accesseurs à ajouter automatiquement à la sérialisation JSON
     */
    protected $appends = [
        'total_available_lessons',
    ];

    /**
     * Le club qui possède ce modèle
     */
    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Les types de cours associés à ce modèle
     */
    public function courseTypes()
    {
        return $this->belongsToMany(CourseType::class, 'subscription_template_course_types', 'subscription_template_id', 'course_type_id')
            ->withTimestamps();
    }

    /**
     * Les abonnements créés à partir de ce modèle
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Nombre total de cours disponibles (payés + gratuits)
     */
    public function getTotalAvailableLessonsAttribute()
    {
        return $this->total_lessons + $this->free_lessons;
    }
}

