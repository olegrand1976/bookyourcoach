<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_recurring_slots')) {
            return;
        }

        Schema::table('subscription_recurring_slots', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_recurring_slots', 'last_generated_at')) {
                if (Schema::hasColumn('subscription_recurring_slots', 'notes')) {
                    $table->timestamp('last_generated_at')
                        ->nullable()
                        ->after('notes')
                        ->comment('Dernière exécution de la génération automatique de cours');
                } else {
                    $table->timestamp('last_generated_at')
                        ->nullable()
                        ->comment('Dernière exécution de la génération automatique de cours');
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('subscription_recurring_slots')) {
            return;
        }

        Schema::table('subscription_recurring_slots', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_recurring_slots', 'last_generated_at')) {
                $table->dropColumn('last_generated_at');
            }
        });
    }
};
