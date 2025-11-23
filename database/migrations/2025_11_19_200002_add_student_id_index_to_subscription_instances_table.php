<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        
        // La relation student est via la table pivot subscription_instance_students
        // Vérifier si l'index existe sur cette table
        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Vérifier si l'index existe déjà sur subscription_instance_students
            $indexes = DB::select("SHOW INDEXES FROM subscription_instance_students WHERE Column_name = 'student_id'");
            
            if (empty($indexes)) {
                Schema::table('subscription_instance_students', function (Blueprint $table) {
                    $table->index('student_id');
                });
            }
            
            // Ajouter aussi un index sur subscription_instance_id si nécessaire
            $indexes = DB::select("SHOW INDEXES FROM subscription_instance_students WHERE Column_name = 'subscription_instance_id'");
            if (empty($indexes)) {
                Schema::table('subscription_instance_students', function (Blueprint $table) {
                    $table->index('subscription_instance_id');
                });
            }
        } elseif ($driver === 'sqlite') {
            // Pour SQLite, vérifier si l'index existe
            $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='subscription_instance_students' AND sql LIKE '%student_id%'");
            
            if (empty($indexes)) {
                Schema::table('subscription_instance_students', function (Blueprint $table) {
                    $table->index('student_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            $indexes = DB::select("SHOW INDEXES FROM subscription_instance_students WHERE Column_name = 'subscription_instance_id'");
            if (!empty($indexes)) {
                Schema::table('subscription_instance_students', function (Blueprint $table) {
                    $table->dropIndex(['subscription_instance_id']);
                });
            }
            
            $indexes = DB::select("SHOW INDEXES FROM subscription_instance_students WHERE Column_name = 'student_id'");
            if (!empty($indexes)) {
                Schema::table('subscription_instance_students', function (Blueprint $table) {
                    $table->dropIndex(['student_id']);
                });
            }
        } elseif ($driver === 'sqlite') {
            // Pour SQLite, on ne peut pas facilement supprimer un index
            // Laisser tel quel car SQLite gère les index différemment
        }
    }
};

