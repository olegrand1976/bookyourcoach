<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_templates', 'stripe_product_id')) {
                $table->string('stripe_product_id')->nullable()->after('model_number');
            }
            if (!Schema::hasColumn('subscription_templates', 'stripe_price_id')) {
                $table->string('stripe_price_id')->nullable()->after('stripe_product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_templates', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_templates', 'stripe_price_id')) {
                $table->dropColumn('stripe_price_id');
            }
            if (Schema::hasColumn('subscription_templates', 'stripe_product_id')) {
                $table->dropColumn('stripe_product_id');
            }
        });
    }
};
