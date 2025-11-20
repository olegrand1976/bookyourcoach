<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    // ⚠️ IMPORTANT : Définir explicitement les colonnes fillable
    // Ne jamais inclure: name, total_lessons, free_lessons, price (ces colonnes n'existent plus)
    protected $fillable = [
        'club_id',
        'subscription_template_id',
        'subscription_number',
        'validity_months', // Existe encore dans la table pour compatibilité
    ];
    
    /**
     * Override save() pour s'assurer que 'name' n'est jamais inséré
     */
    public function save(array $options = [])
    {
        // Retirer 'name' et autres colonnes legacy avant toute sauvegarde
        $legacyColumns = ['name', 'total_lessons', 'free_lessons', 'price', 'description', 'is_active'];
        foreach ($legacyColumns as $col) {
            if (isset($this->attributes[$col])) {
                unset($this->attributes[$col]);
            }
        }
        
        return parent::save($options);
    }

    // Ne pas définir de casts statiques pour les colonnes qui pourraient ne pas exister
    // Les casts seront gérés dans les accesseurs si nécessaire
    // Ne pas surcharger getCasts() car cela peut causer des problèmes lors de la sérialisation
    protected $casts = [];

    /**
     * Vérifier si la colonne club_id existe dans la table
     */
    public static function hasClubIdColumn(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasColumn((new static)->getTable(), 'club_id');
    }

    /**
     * Vérifier si une colonne existe dans la table
     */
    public static function hasColumn(string $columnName): bool
    {
        return \Illuminate\Support\Facades\Schema::hasColumn((new static)->getTable(), $columnName);
    }

    /**
     * Créer un abonnement en gérant automatiquement club_id et les champs depuis le template
     */
    public static function createSafe(array $attributes = [])
    {
        // Obtenir la liste des colonnes qui existent réellement dans la table
        $tableName = (new static)->getTable();
        $existingColumns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
        
        // Retirer club_id si la colonne n'existe pas
        if (!in_array('club_id', $existingColumns) && isset($attributes['club_id'])) {
            unset($attributes['club_id']);
        }
        
        // ⚠️ IMPORTANT : Ne JAMAIS remplir les colonnes legacy (name, total_lessons, free_lessons, price, validity_months)
        // Ces colonnes n'existent plus dans la nouvelle structure de la table subscriptions
        // Toutes ces informations sont maintenant dans subscription_templates
        
        // ⚠️ IMPORTANT : Retirer explicitement 'name' si présent (cette colonne n'existe pas)
        if (isset($attributes['name'])) {
            unset($attributes['name']);
        }
        
        // Filtrer les attributs pour ne garder que ceux dont les colonnes existent
        $filteredAttributes = [];
        foreach ($attributes as $key => $value) {
            // Ignorer 'name' explicitement
            if ($key === 'name') {
                continue;
            }
            if (in_array($key, $existingColumns)) {
                $filteredAttributes[$key] = $value;
            }
        }
        
        \Log::info("🔍 [createSafe] Colonnes existantes: " . implode(', ', $existingColumns), [
            'attributes_originaux' => array_keys($attributes),
            'attributes_filtres' => array_keys($filteredAttributes),
            'colonnes_retirees' => array_diff(array_keys($attributes), array_keys($filteredAttributes))
        ]);
        
        // Créer l'instance avec seulement les attributs valides
        // Utiliser une approche directe avec DB pour éviter les problèmes avec $fillable
        $instance = new static();
        
        // S'assurer que seules les colonnes existantes sont définies
        // ET exclure explicitement 'name' qui n'existe pas
        $finalAttributes = [];
        foreach ($filteredAttributes as $key => $value) {
            // Ignorer 'name' explicitement
            if ($key === 'name') {
                continue;
            }
            if (in_array($key, $existingColumns)) {
                $finalAttributes[$key] = $value;
            }
        }
        
        // Générer le numéro d'abonnement si nécessaire et si la colonne existe
        if (in_array('subscription_number', $existingColumns) && !isset($finalAttributes['subscription_number'])) {
            $hasClubIdColumn = in_array('club_id', $existingColumns);
            $clubId = $hasClubIdColumn ? ($finalAttributes['club_id'] ?? null) : null;
            $finalAttributes['subscription_number'] = static::generateSubscriptionNumber($clubId);
        }
        
        // Utiliser DB::table() directement pour l'insertion afin d'éviter complètement $fillable
        try {
            $now = \Carbon\Carbon::now();
            if (in_array('created_at', $existingColumns)) {
                $finalAttributes['created_at'] = $now;
            }
            if (in_array('updated_at', $existingColumns)) {
                $finalAttributes['updated_at'] = $now;
            }
            
            // Insérer directement dans la table
            $id = DB::table($tableName)->insertGetId($finalAttributes);
            
            // Charger l'instance créée
            $instance = static::find($id);
            
            \Log::info("✅ [createSafe] Abonnement créé avec succès", [
                'id' => $id,
                'attributes_insertes' => array_keys($finalAttributes),
                'subscription_number' => $instance->subscription_number ?? null
            ]);
        } catch (\Exception $e) {
            \Log::error("❌ [createSafe] Erreur lors de l'insertion directe", [
                'error' => $e->getMessage(),
                'attributes' => $finalAttributes,
                'existing_columns' => $existingColumns,
                'sql' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        
        return $instance;
    }

    /**
     * Boot method pour générer le numéro d'abonnement et nettoyer les colonnes inexistantes
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscription) {
            // Obtenir la liste des colonnes qui existent réellement dans la table
            $tableName = (new static)->getTable();
            $existingColumns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
            
            // ⚠️ CRITIQUE : Retirer explicitement 'name' en PREMIER car cette colonne n'existe pas
            // Faire cela AVANT toute autre opération
            if (isset($subscription->attributes['name'])) {
                unset($subscription->attributes['name']);
                \Log::info("🧹 [boot] Colonne 'name' retirée explicitement avant insertion");
            }
            
            // Nettoyer tous les attributs qui n'existent pas dans la table
            // Cela évite les erreurs SQL lors de l'insertion
            $attributesToRemove = [];
            foreach ($subscription->attributes as $key => $value) {
                // Ignorer 'name' explicitement
                if ($key === 'name') {
                    $attributesToRemove[] = $key;
                    continue;
                }
                
                // Garder les colonnes système (id, timestamps, etc.)
                if (in_array($key, ['id', 'created_at', 'updated_at'])) {
                    continue;
                }
                
                // Retirer si la colonne n'existe pas dans la table
                if (!in_array($key, $existingColumns)) {
                    $attributesToRemove[] = $key;
                }
            }
            
            // Retirer les attributs invalides
            foreach ($attributesToRemove as $key) {
                unset($subscription->attributes[$key]);
            }
            
            // Vérification finale : s'assurer que 'name' n'est vraiment pas là
            if (isset($subscription->attributes['name'])) {
                unset($subscription->attributes['name']);
                \Log::warning("⚠️ [boot] Colonne 'name' encore présente après nettoyage, retirée en urgence");
            }
            
            if (!empty($attributesToRemove)) {
                \Log::info("🧹 Colonnes retirées avant insertion (n'existent pas dans la table): " . implode(', ', $attributesToRemove), [
                    'subscription_id' => $subscription->id ?? null,
                    'colonnes_existantes' => $existingColumns,
                    'attributes_apres_nettoyage' => array_keys($subscription->getAttributes())
                ]);
            }
            
            // Générer le numéro AAMM-incrément si non fourni et si la colonne existe
            if (in_array('subscription_number', $existingColumns) && !$subscription->subscription_number) {
                $hasClubIdColumn = in_array('club_id', $existingColumns);
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
        // Toujours utiliser le template si disponible
        if ($this->subscription_template_id) {
            // Charger le template si nécessaire
            if (!$this->relationLoaded('template')) {
                $this->load('template');
            }
            
            if ($this->template) {
                return $this->template->courseTypes();
            }
        }
        
        // Fallback pour compatibilité avec l'ancienne structure (utilise discipline_id)
        // Note: subscription_course_types utilise discipline_id, pas course_type_id
        return $this->belongsToMany(CourseType::class, 'subscription_course_types', 'subscription_id', 'discipline_id', 'id', 'discipline_id')
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
        // Si les colonnes legacy n'existent pas, retourner 0
        $totalLessons = static::hasColumn('total_lessons') ? ($this->attributes['total_lessons'] ?? 0) : 0;
        $freeLessons = static::hasColumn('free_lessons') ? ($this->attributes['free_lessons'] ?? 0) : 0;
        return $totalLessons + $freeLessons;
    }

    /**
     * Prix (via le template ou legacy)
     */
    public function getPriceAttribute($value)
    {
        try {
            if ($this->template) {
                return $this->template->price;
            }
            // Si la colonne n'existe pas, retourner null
            if (!static::hasColumn('price')) {
                return null;
            }
            return $value;
        } catch (\Exception $e) {
            \Log::warning('Erreur dans getPriceAttribute: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Nombre de cours total (via le template ou legacy)
     */
    public function getTotalLessonsAttribute($value)
    {
        try {
            if ($this->template) {
                return $this->template->total_lessons;
            }
            // Si la colonne n'existe pas, retourner null
            if (!static::hasColumn('total_lessons')) {
                return null;
            }
            return $value;
        } catch (\Exception $e) {
            \Log::warning('Erreur dans getTotalLessonsAttribute: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Nombre de cours gratuits (via le template ou legacy)
     */
    public function getFreeLessonsAttribute($value)
    {
        try {
            if ($this->template) {
                return $this->template->free_lessons;
            }
            // Si la colonne n'existe pas, retourner 0
            if (!static::hasColumn('free_lessons')) {
                return 0;
            }
            return $value ?? 0;
        } catch (\Exception $e) {
            \Log::warning('Erreur dans getFreeLessonsAttribute: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Durée de validité (via le template ou legacy)
     */
    public function getValidityMonthsAttribute($value)
    {
        try {
            if ($this->template) {
                return $this->template->validity_months;
            }
            // Si la colonne n'existe pas, retourner 12 par défaut
            if (!static::hasColumn('validity_months')) {
                return 12;
            }
            return $value ?? 12;
        } catch (\Exception $e) {
            \Log::warning('Erreur dans getValidityMonthsAttribute: ' . $e->getMessage());
            return 12;
        }
    }
}
