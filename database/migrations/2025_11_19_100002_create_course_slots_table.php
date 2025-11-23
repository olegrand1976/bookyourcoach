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
        if (!Schema::hasTable('course_slots')) {
            Schema::create('course_slots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('club_id')->constrained('clubs')->onDelete('cascade');
                $table->foreignId('facility_id')->constrained('club_facilities')->onDelete('cascade');
                $table->foreignId('course_type_id')->constrained('course_types')->onDelete('cascade');
                $table->string('name'); // "Cours dressage matin", "Cours CSO après-midi", etc.
                $table->time('start_time');
                $table->time('end_time');
                $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->integer('max_students')->default(8);
                $table->decimal('price', 8, 2);
                $table->boolean('is_recurring')->default(true);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['club_id', 'is_active']);
                $table->index(['facility_id', 'day_of_week']);
                $table->index(['course_type_id', 'is_active']);
                $table->index(['day_of_week', 'start_time', 'is_active']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_slots');
    }
};

