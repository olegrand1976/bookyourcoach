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
        Schema::table('clubs', function (Blueprint $table) {
            $table->integer('default_subscription_total_lessons')->nullable()->default(10)->after('subscription_price');
            $table->integer('default_subscription_free_lessons')->nullable()->default(1)->after('default_subscription_total_lessons');
            $table->decimal('default_subscription_price', 8, 2)->nullable()->default(180.00)->after('default_subscription_free_lessons');
            $table->integer('default_subscription_validity_value')->nullable()->default(12)->after('default_subscription_price');
            $table->enum('default_subscription_validity_unit', ['weeks', 'months'])->nullable()->default('weeks')->after('default_subscription_validity_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropColumn([
                'default_subscription_total_lessons',
                'default_subscription_free_lessons',
                'default_subscription_price',
                'default_subscription_validity_value',
                'default_subscription_validity_unit'
            ]);
        });
    }
};
