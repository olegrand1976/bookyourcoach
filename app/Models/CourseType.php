<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseType extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',  // Ajouté pour permettre les CourseTypes spécifiques aux clubs
        'discipline_id',
        'name',
        'description',
        'duration_minutes',
        'price',
        'is_individual',
        'max_participants',
        'is_active',
    ];

    protected $casts = [
        'is_individual' => 'boolean',
        'is_active' => 'boolean',
        'duration_minutes' => 'integer',
        'max_participants' => 'integer',
        'price' => 'decimal:2',
    ];

    /**
     * Get the discipline that owns this course type
     */
    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    /**
     * Get the student preferences for this course type
     */
    public function studentPreferences(): HasMany
    {
        return $this->hasMany(StudentPreference::class);
    }

    /**
     * Get the lessons for this course type
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * Get the club open slots that can use this course type
     */
    public function clubOpenSlots()
    {
        return $this->belongsToMany(ClubOpenSlot::class, 'club_open_slot_course_types');
    }

    /**
     * Scope: Récupérer les types de cours accessibles pour un club
     * Inclut les types spécifiques au club + les types génériques matchant les disciplines du club
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $clubId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForClub($query, $clubId)
    {
        $club = \App\Models\Club::find($clubId);
        
        if (!$club) {
            return $query->whereRaw('1 = 0'); // Retourner une requête vide
        }

        // Récupérer les disciplines configurées dans le club (peut être des noms ou des IDs)
        $clubDisciplines = $club->disciplines;
        
        if (empty($clubDisciplines)) {
            // Si aucune discipline n'est configurée, retourner uniquement les types génériques sans discipline
            return $query->where(function($q) {
                $q->whereNull('club_id')
                  ->whereNull('discipline_id');
            });
        }

        // Si c'est un array de noms, convertir en IDs
        $disciplineIds = [];
        if (is_array($clubDisciplines)) {
            foreach ($clubDisciplines as $disc) {
                if (is_numeric($disc)) {
                    $disciplineIds[] = (int)$disc;
                } else {
                    // C'est un nom, chercher l'ID
                    $discipline = \App\Models\Discipline::where('name', 'LIKE', '%' . $disc . '%')->first();
                    if ($discipline) {
                        $disciplineIds[] = $discipline->id;
                    }
                }
            }
        }

        return $query->where('is_active', true)
            ->where(function($q) use ($clubId, $disciplineIds) {
                // Types spécifiques au club
                $q->where('club_id', $clubId);
                
                // OU types génériques (club_id = NULL) avec discipline matchant
                if (!empty($disciplineIds)) {
                    $q->orWhere(function($subQ) use ($disciplineIds) {
                        $subQ->whereNull('club_id')
                             ->whereIn('discipline_id', $disciplineIds);
                    });
                }
                
                // OU types vraiment génériques (sans club_id ni discipline_id)
                $q->orWhere(function($subQ) {
                    $subQ->whereNull('club_id')
                         ->whereNull('discipline_id');
                });
            });
    }

    /**
     * Scope: Récupérer uniquement les types génériques (sans club_id)
     */
    public function scopeGeneric($query)
    {
        return $query->whereNull('club_id');
    }

    /**
     * Scope: Récupérer uniquement les types spécifiques à un club
     */
    public function scopeClubSpecific($query, $clubId)
    {
        return $query->where('club_id', $clubId);
    }
}