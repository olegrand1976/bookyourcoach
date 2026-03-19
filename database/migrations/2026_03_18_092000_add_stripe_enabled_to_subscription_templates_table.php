<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_templates', 'stripe_enabled')) {
                $table->boolean('stripe_enabled')->default(false)->after('model_number');
            }
        });

        DB::table('subscription_templates')
            ->whereNotNull('stripe_price_id')
            ->update(['stripe_enabled' => true]);
    }

    public function down(): void
    {
        Schema::table('subscription_templates', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_templates', 'stripe_enabled')) {
                $table->dropColumn('stripe_enabled');
            }
        });
    }
};
