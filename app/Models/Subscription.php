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
     * Vérifier si la colonne club_id existe dans la table
     */
    public static function hasClubIdColumn(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasColumn((new static)->getTable(), 'club_id');
    }

    /**
     * Créer un abonnement en gérant automatiquement club_id
     */
    public static function createSafe(array $attributes = [])
    {
        // Retirer club_id si la colonne n'existe pas
        if (!static::hasClubIdColumn() && isset($attributes['club_id'])) {
            unset($attributes['club_id']);
        }
        
        return static::create($attributes);
    }

    /**
     * Boot method pour générer le numéro d'abonnement
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscription) {
            // Vérifier si la colonne club_id existe
            $hasClubIdColumn = \Illuminate\Support\Facades\Schema::hasColumn((new static)->getTable(), 'club_id');
            
            // Si club_id est défini mais que la colonne n'existe pas, le retirer des attributs
            if (!$hasClubIdColumn && isset($subscription->attributes['club_id'])) {
                unset($subscription->attributes['club_id']);
            }
            
            // Générer le numéro AAMM-incrément si non fourni
            if (!$subscription->subscription_number) {
                $clubId = $hasClubIdColumn ? ($subscription->club_id ?? null) : null;
                $subscription->subscription_number = static::generateSubscriptionNumber($clubId);
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
        
        // Vérifier si la colonne club_id existe dans la table
        $hasClubIdColumn = \Illuminate\Support\Facades\Schema::hasColumn((new static)->getTable(), 'club_id');
        
        // Trouver le dernier numéro pour ce mois (et ce club si la colonne existe)
        $query = static::where('subscription_number', 'like', $yearMonth . '-%');
        
        if ($hasClubIdColumn && $clubId) {
            $query->where('club_id', $clubId);
        }
        
        $lastSubscription = $query->orderBy('subscription_number', 'desc')->first();
        
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
     * Utilise club_id directement, ou passe par template si club_id n'existe pas
     */
    public function club()
    {
        // Si club_id existe dans la table, utiliser la relation directe
        if (static::hasClubIdColumn()) {
            return $this->belongsTo(Club::class);
        }
        
        // Sinon, passer par la relation template -> club
        return $this->hasOneThrough(
            Club::class,
            SubscriptionTemplate::class,
            'id', // Foreign key on subscription_templates table
            'id', // Foreign key on clubs table
            'subscription_template_id', // Local key on subscriptions table
            'club_id' // Local key on subscription_templates table
        );
    }

    /**
     * Scope pour filtrer les abonnements par club
     * Gère automatiquement le cas où club_id n'existe pas (passe par template)
     */
    public function scopeForClub($query, $clubId)
    {
        if (static::hasClubIdColumn()) {
            return $query->where('club_id', $clubId);
        }
        
        // Si club_id n'existe pas, filtrer via template
        return $query->whereHas('template', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        });
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
