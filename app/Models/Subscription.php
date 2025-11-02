<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'subscription_template_id',
        'subscription_number',
        // Colonnes legacy pour compatibilité
        'name',
        'total_lessons',
        'free_lessons',
        'price',
        'validity_months',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_lessons' => 'integer',
        'free_lessons' => 'integer',
        'price' => 'decimal:2',
        'validity_months' => 'integer',
    ];

    /**
     * Boot method pour générer le numéro d'abonnement
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscription) {
            // Générer le numéro AAMM-incrément si non fourni
            if (!$subscription->subscription_number) {
                $subscription->subscription_number = static::generateSubscriptionNumber($subscription->club_id);
            }
        });
    }

    /**
     * Générer un numéro d'abonnement au format AAMM-incrément
     * Exemple : 2501-001 (année 2025, mois 01, incrément 001)
     */
    public static function generateSubscriptionNumber($clubId): string
    {
        $now = Carbon::now();
        $yearMonth = $now->format('ym'); // Format AAMM (ex: 2501)
        
        // Trouver le dernier numéro pour ce mois et ce club
        $lastSubscription = static::where('club_id', $clubId)
            ->where('subscription_number', 'like', $yearMonth . '-%')
            ->orderBy('subscription_number', 'desc')
            ->first();
        
        if ($lastSubscription && $lastSubscription->subscription_number) {
            // Extraire l'incrément et l'incrémenter
            $parts = explode('-', $lastSubscription->subscription_number);
            if (count($parts) === 2) {
                $increment = (int) $parts[1];
                $increment++;
            } else {
                $increment = 1;
            }
        } else {
            // Premier abonnement du mois
            $increment = 1;
        }
        
        return sprintf('%s-%03d', $yearMonth, $increment);
    }

    /**
     * Le club qui possède cet abonnement
     */
    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Le modèle d'abonnement utilisé
     */
    public function template()
    {
        return $this->belongsTo(SubscriptionTemplate::class, 'subscription_template_id');
    }

    /**
     * Les types de cours inclus dans cet abonnement (via le template)
     */
    public function courseTypes()
    {
        if ($this->subscription_template_id && $this->template) {
            return $this->template->courseTypes();
        }
        
        // Fallback pour compatibilité avec l'ancienne structure
        return $this->belongsToMany(CourseType::class, 'subscription_course_types', 'subscription_id', 'course_type_id')
            ->withTimestamps();
    }

    /**
     * Les instances d'abonnements (abonnements utilisés par les élèves)
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
     * Nombre total de cours (via le template ou legacy)
     */
    public function getTotalAvailableLessonsAttribute()
    {
        if ($this->template) {
            return $this->template->total_available_lessons;
        }
        return ($this->total_lessons ?? 0) + ($this->free_lessons ?? 0);
    }

    /**
     * Prix (via le template ou legacy)
     */
    public function getPriceAttribute($value)
    {
        if ($this->template) {
            return $this->template->price;
        }
        return $value;
    }

    /**
     * Nombre de cours total (via le template ou legacy)
     */
    public function getTotalLessonsAttribute($value)
    {
        if ($this->template) {
            return $this->template->total_lessons;
        }
        return $value;
    }

    /**
     * Nombre de cours gratuits (via le template ou legacy)
     */
    public function getFreeLessonsAttribute($value)
    {
        if ($this->template) {
            return $this->template->free_lessons;
        }
        return $value ?? 0;
    }

    /**
     * Durée de validité (via le template ou legacy)
     */
    public function getValidityMonthsAttribute($value)
    {
        if ($this->template) {
            return $this->template->validity_months;
        }
        return $value ?? 12;
    }
}
