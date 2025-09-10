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
        Schema::create('teacher_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('club_id');
            $table->enum('contract_type', ['permanent', 'temporary', 'freelance', 'seasonal']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('max_hours_per_week')->nullable();
            $table->integer('min_hours_per_week')->nullable();
            $table->decimal('hourly_rate', 8, 2);
            $table->json('allowed_disciplines')->nullable();
            $table->json('restricted_disciplines')->nullable();
            $table->json('preferred_facilities')->nullable();
            $table->json('unavailable_days')->nullable(); // Jours de la semaine indisponibles
            $table->time('earliest_start_time')->nullable();
            $table->time('latest_end_time')->nullable();
            $table->boolean('can_teach_weekends')->default(false);
            $table->boolean('can_teach_holidays')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            
            $table->index(['teacher_id', 'club_id', 'is_active']);
            $table->index(['contract_type', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_contracts');
    }
};
