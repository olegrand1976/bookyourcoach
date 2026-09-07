<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_movement_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 64);
            $table->string('update_scope', 32)->nullable();
            $table->foreignId('cascade_from_lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->unsignedBigInteger('old_teacher_id')->nullable();
            $table->unsignedBigInteger('new_teacher_id')->nullable();
            $table->dateTime('old_start_time')->nullable();
            $table->dateTime('new_start_time')->nullable();
            $table->dateTime('old_end_time')->nullable();
            $table->dateTime('new_end_time')->nullable();
            $table->string('old_status', 32)->nullable();
            $table->string('new_status', 32)->nullable();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('performed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('performed_by_role', 32)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['club_id', 'created_at']);
            $table->index(['lesson_id', 'created_at']);
            $table->index(['old_teacher_id', 'created_at']);
            $table->index(['new_teacher_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_movement_histories');
    }
};
