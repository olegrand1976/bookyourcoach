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
        if (!Schema::hasTable('teacher_contracts')) {
            Schema::create('teacher_contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
                $table->foreignId('club_id')->constrained('clubs')->onDelete('cascade');
                $table->enum('contract_type', ['permanent', 'temporary', 'freelance', 'seasonal'])->default('permanent');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->integer('max_hours_per_week')->nullable();
                $table->integer('min_hours_per_week')->nullable();
                $table->decimal('hourly_rate', 8, 2);
                $table->json('allowed_disciplines')->nullable();
                $table->json('restricted_disciplines')->nullable();
                $table->json('preferred_facilities')->nullable();
                $table->json('unavailable_days')->nullable();
                $table->time('earliest_start_time')->nullable();
                $table->time('latest_end_time')->nullable();
                $table->boolean('can_teach_weekends')->default(false);
                $table->boolean('can_teach_holidays')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['teacher_id', 'is_active']);
                $table->index(['club_id', 'is_active']);
                $table->index(['start_date', 'end_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_contracts');
    }
};

