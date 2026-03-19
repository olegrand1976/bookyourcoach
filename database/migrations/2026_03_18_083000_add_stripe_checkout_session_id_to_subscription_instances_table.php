<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_instances', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_instances', 'stripe_checkout_session_id')) {
                $table->string('stripe_checkout_session_id')->nullable()->after('status');
                $table->unique('stripe_checkout_session_id', 'sub_instances_stripe_checkout_session_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_instances', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_instances', 'stripe_checkout_session_id')) {
                $table->dropUnique('sub_instances_stripe_checkout_session_unique');
                $table->dropColumn('stripe_checkout_session_id');
            }
        });
    }
};
