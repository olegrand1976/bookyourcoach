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
        // Ajouter club_id à la table subscriptions si elle n'existe pas
        if (Schema::hasTable('subscriptions') && !Schema::hasColumn('subscriptions', 'club_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                // Ajouter club_id après l'id
                $table->foreignId('club_id')->nullable()->after('id')->constrained('clubs')->onDelete('cascade');
            });
            
            // Mettre à jour les subscriptions existantes si nécessaire
            // (on les laisse nullable pour l'instant pour éviter de perdre des données)
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'club_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropForeign(['club_id']);
                $table->dropColumn('club_id');
            });
        }
    }
};
