<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('status');
            $table->foreignId('cancelled_by_user_id')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->string('cancelled_by_role', 32)->nullable()->after('cancelled_by_user_id');
            $table->json('cancelled_subscription_instance_ids')->nullable()->after('cancelled_by_role');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by_user_id']);
            $table->dropColumn([
                'cancelled_at',
                'cancelled_by_user_id',
                'cancelled_by_role',
                'cancelled_subscription_instance_ids',
            ]);
        });
    }
};
