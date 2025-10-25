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
        Schema::table('club_open_slots', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_slots')->default(1)->after('max_capacity')
                ->comment('Nombre de créneaux parallèles possibles (ex: 5 couloirs = 5 cours simultanés)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_open_slots', function (Blueprint $table) {
            $table->dropColumn('max_slots');
        });
    }
};
