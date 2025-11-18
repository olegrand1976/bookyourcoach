<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modifier la colonne role pour inclure 'club'
        // Note: SQLite ne supporte pas nativement les ENUMs ni les ->change()
        // Pour SQLite, on accepte simplement toute valeur varchar
        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        
        if ($driver !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'teacher', 'student', 'club'])->change();
            });
        }
        // Pour SQLite, pas de modification nécessaire car ENUM est déjà un VARCHAR

        // Ajouter club_id aux enseignants
        Schema::table('teachers', function (Blueprint $table) {
            $table->unsignedBigInteger('club_id')->nullable()->after('user_id');
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('set null');
            $table->index('club_id');
        });

        // Ajouter club_id aux élèves (pour l'affiliation)
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('club_id')->nullable()->after('user_id');
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('set null');
            $table->index('club_id');
        });

        // Créer la table de liaison club-user pour les gestionnaires de club
        Schema::create('club_managers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['owner', 'manager', 'admin'])->default('manager');
            $table->timestamps();

            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['club_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_managers');

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
            $table->dropColumn('club_id');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
            $table->dropColumn('club_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'teacher', 'student'])->change();
        });
    }
};
