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
        if (Schema::hasTable('subscription_templates')) {
            Schema::table('subscription_templates', function (Blueprint $table) {
                if (!Schema::hasColumn('subscription_templates', 'validity_value')) {
                    $table->integer('validity_value')->nullable()->after('validity_months');
                }
                if (!Schema::hasColumn('subscription_templates', 'validity_unit')) {
                    $table->enum('validity_unit', ['weeks', 'months'])->nullable()->default('months')->after('validity_value');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('subscription_templates')) {
            Schema::table('subscription_templates', function (Blueprint $table) {
                if (Schema::hasColumn('subscription_templates', 'validity_unit')) {
                    $table->dropColumn('validity_unit');
                }
                if (Schema::hasColumn('subscription_templates', 'validity_value')) {
                    $table->dropColumn('validity_value');
                }
            });
        }
    }
};
