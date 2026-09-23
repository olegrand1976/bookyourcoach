<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_closure_days', function (Blueprint $table) {
            // Qui a fermé la journée : la table n'en gardait aucune trace, si bien
            // qu'il a fallu les journaux applicatifs pour reconstituer l'incident.
            $table->foreignId('closed_by_user_id')->nullable()->after('closed_on')
                ->constrained('users')->nullOnDelete();

            // Couples cours/carnet détachés par la fermeture, pour que la réouverture
            // puisse les restaurer. Sans ce relevé, l'information n'existe nulle part.
            $table->json('detached_links')->nullable()->after('closed_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('club_closure_days', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_by_user_id');
            $table->dropColumn('detached_links');
        });
    }
};
