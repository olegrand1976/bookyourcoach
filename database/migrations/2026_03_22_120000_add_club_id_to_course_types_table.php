<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Types de cours par club (scopeForClub) : colonne référencée par le modèle mais absente des migrations historiques.
     */
    public function up(): void
    {
        if (! Schema::hasTable('course_types')) {
            return;
        }

        if (Schema::hasColumn('course_types', 'club_id')) {
            return;
        }

        Schema::table('course_types', function (Blueprint $table) {
            $table->foreignId('club_id')
                ->nullable()
                ->after('id')
                ->constrained('clubs')
                ->nullOnDelete();
            $table->index('club_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('course_types') || ! Schema::hasColumn('course_types', 'club_id')) {
            return;
        }

        Schema::table('course_types', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
            $table->dropColumn('club_id');
        });
    }
};
