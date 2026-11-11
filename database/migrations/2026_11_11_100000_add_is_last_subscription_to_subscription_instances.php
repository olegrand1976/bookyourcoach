<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * « Dernier abonnement » : la récurrence de l'élève s'arrête à la fin de cet abonnement.
 * Sans cette mention (défaut, y compris pour l'existant), la récurrence est ouverte sans fin
 * (horizon glissant, prévisualisation au-delà des abonnements).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_instances', function (Blueprint $table) {
            $table->boolean('is_last_subscription')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_instances', function (Blueprint $table) {
            $table->dropColumn('is_last_subscription');
        });
    }
};
