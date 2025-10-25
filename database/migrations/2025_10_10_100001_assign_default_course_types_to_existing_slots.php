<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pour chaque créneau existant, assigner tous les types de cours de sa discipline
        $slots = DB::table('club_open_slots')->whereNotNull('discipline_id')->get();
        
        foreach ($slots as $slot) {
            // Récupérer tous les types de cours pour cette discipline (y compris les génériques)
            $courseTypes = DB::table('course_types')
                ->where(function($query) use ($slot) {
                    $query->where('discipline_id', $slot->discipline_id)
                          ->orWhereNull('discipline_id');
                })
                ->where('is_active', true)
                ->pluck('id');
            
            // Assigner ces types de cours au créneau
            foreach ($courseTypes as $courseTypeId) {
                DB::table('club_open_slot_course_types')->insert([
                    'club_open_slot_id' => $slot->id,
                    'course_type_id' => $courseTypeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Pour les créneaux sans discipline, assigner tous les types de cours génériques
        $slotsWithoutDiscipline = DB::table('club_open_slots')->whereNull('discipline_id')->get();
        
        foreach ($slotsWithoutDiscipline as $slot) {
            $genericCourseTypes = DB::table('course_types')
                ->whereNull('discipline_id')
                ->where('is_active', true)
                ->pluck('id');
            
            foreach ($genericCourseTypes as $courseTypeId) {
                DB::table('club_open_slot_course_types')->insert([
                    'club_open_slot_id' => $slot->id,
                    'course_type_id' => $courseTypeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer toutes les associations
        DB::table('club_open_slot_course_types')->truncate();
    }
};

