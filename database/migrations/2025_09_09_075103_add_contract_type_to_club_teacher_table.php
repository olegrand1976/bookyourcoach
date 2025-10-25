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
        Schema::table('club_teachers', function (Blueprint $table) {
            $table->string('contract_type')->default('freelance')->after('hourly_rate')->comment('Type of contract: freelance, salaried, volunteer, student, article_17');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_teachers', function (Blueprint $table) {
            $table->dropColumn('contract_type');
        });
    }
};
