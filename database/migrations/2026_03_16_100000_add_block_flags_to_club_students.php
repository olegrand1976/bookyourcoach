<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_students', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(true)->after('is_active');
            $table->boolean('subscription_creation_blocked')->default(true)->after('is_blocked');
        });
    }

    public function down(): void
    {
        Schema::table('club_students', function (Blueprint $table) {
            $table->dropColumn(['is_blocked', 'subscription_creation_blocked']);
        });
    }
};
