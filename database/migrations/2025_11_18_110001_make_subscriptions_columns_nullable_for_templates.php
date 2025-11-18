<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Rendre les colonnes name, total_lessons, price nullable dans subscriptions
     * car ces valeurs peuvent venir du template au lieu d'être définies directement
     */
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            return;
        }

        $driver = DB::getDriverName();
        
        // SQLite ne supporte pas ->change(), donc on doit recréer la table
        if ($driver === 'sqlite') {
            // Pour SQLite, on ne peut pas modifier les colonnes facilement
            // On accepte que les colonnes soient NOT NULL dans les tests SQLite
            // Le contrôleur doit gérer les valeurs par défaut
            return;
        }
        
        // Pour MySQL/PostgreSQL
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'name')) {
                $table->string('name')->nullable()->change();
            }
            if (Schema::hasColumn('subscriptions', 'total_lessons')) {
                $table->integer('total_lessons')->nullable()->change();
            }
            if (Schema::hasColumn('subscriptions', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // On ne peut pas rollback facilement car on ne sait pas quelles étaient les valeurs NULL
        // Les valeurs NULL seraient perdues lors du rollback
    }
};

