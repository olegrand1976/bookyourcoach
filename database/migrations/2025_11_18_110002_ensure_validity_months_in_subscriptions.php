<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migration corrective pour s'assurer que validity_months existe dans subscriptions
     */
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            return;
        }

        if (!Schema::hasColumn('subscriptions', 'validity_months')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $hasPrice = Schema::hasColumn('subscriptions', 'price');
                $hasIsActive = Schema::hasColumn('subscriptions', 'is_active');
                
                if ($hasPrice) {
                    $table->integer('validity_months')->default(12)->after('price');
                } elseif ($hasIsActive) {
                    $table->integer('validity_months')->default(12)->after('is_active');
                } else {
                    $table->integer('validity_months')->default(12);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'validity_months')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('validity_months');
            });
        }
    }
};

