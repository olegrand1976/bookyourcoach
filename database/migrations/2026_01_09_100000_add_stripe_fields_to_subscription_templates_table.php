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
        Schema::table('subscription_templates', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false)->after('model_number'); // true = subscription, false = pack
            $table->string('stripe_product_id')->nullable()->after('price');
            $table->string('stripe_price_id')->nullable()->after('stripe_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_templates', function (Blueprint $table) {
            $table->dropColumn(['is_recurring', 'stripe_product_id', 'stripe_price_id']);
        });
    }
};
