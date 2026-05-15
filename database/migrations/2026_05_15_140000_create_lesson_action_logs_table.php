<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_instance_id')->nullable()->constrained('subscription_instances')->nullOnDelete();
            $table->foreignId('performed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('performed_by_role', 32)->nullable();
            $table->string('action', 64);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['club_id', 'created_at']);
            $table->index(['club_id', 'student_id']);
            $table->index(['club_id', 'action']);
            $table->index(['club_id', 'subscription_instance_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_action_logs');
    }
};
