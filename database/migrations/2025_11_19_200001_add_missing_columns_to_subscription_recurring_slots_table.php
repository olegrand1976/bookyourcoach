<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscription_recurring_slots', function (Blueprint $table) {
            // Ajouter open_slot_id si elle n'existe pas
            if (!Schema::hasColumn('subscription_recurring_slots', 'open_slot_id')) {
                $table->foreignId('open_slot_id')->nullable()->after('subscription_instance_id')
                    ->constrained('club_open_slots')->onDelete('set null');
            }
            
            // Ajouter status si elle n'existe pas
            if (!Schema::hasColumn('subscription_recurring_slots', 'status')) {
                $table->enum('status', ['active', 'cancelled', 'completed', 'expired'])
                    ->default('active')->after('end_date');
            }
            
            // Ajouter notes si elle n'existe pas
            if (!Schema::hasColumn('subscription_recurring_slots', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
        });
        
        // Ajouter les index après avoir vérifié qu'ils n'existent pas
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Vérifier si l'index sur open_slot_id existe
            $indexes = DB::select("SHOW INDEXES FROM subscription_recurring_slots WHERE Column_name = 'open_slot_id'");
            if (empty($indexes)) {
                Schema::table('subscription_recurring_slots', function (Blueprint $table) {
                    $table->index('open_slot_id');
                });
            }
            
            // Vérifier si l'index sur status existe
            $indexes = DB::select("SHOW INDEXES FROM subscription_recurring_slots WHERE Column_name = 'status'");
            if (empty($indexes)) {
                Schema::table('subscription_recurring_slots', function (Blueprint $table) {
                    $table->index('status');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_recurring_slots', function (Blueprint $table) {
            // Supprimer les index d'abord
            $indexes = DB::select("SHOW INDEXES FROM subscription_recurring_slots WHERE Column_name = 'status'");
            if (!empty($indexes)) {
                $table->dropIndex(['status']);
            }
            
            $indexes = DB::select("SHOW INDEXES FROM subscription_recurring_slots WHERE Column_name = 'open_slot_id'");
            if (!empty($indexes)) {
                $table->dropIndex(['open_slot_id']);
            }
            
            // Supprimer les colonnes
            if (Schema::hasColumn('subscription_recurring_slots', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('subscription_recurring_slots', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('subscription_recurring_slots', 'open_slot_id')) {
                $table->dropForeign(['open_slot_id']);
                $table->dropColumn('open_slot_id');
            }
        });
    }
};

