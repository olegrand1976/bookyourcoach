<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Seuil d'alerte "fin de parcours" (ex. 8 = alerte à la 8ème séance). Si null, le backend utilise 8.
     */
    public function up(): void
    {
        Schema::table('subscription_templates', function (Blueprint $table) {
            $table->unsignedTinyInteger('warning_at_session')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_templates', function (Blueprint $table) {
            $table->dropColumn('warning_at_session');
        });
    }
};
