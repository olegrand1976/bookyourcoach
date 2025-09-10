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
        Schema::create('course_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->unsignedBigInteger('facility_id');
            $table->unsignedBigInteger('course_type_id');
            $table->string('name'); // Ex: "Cours dressage matin", "Cours obstacle soir"
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->date('start_date'); // Date de début de la plage
            $table->date('end_date')->nullable(); // Date de fin (null = récurrent)
            $table->integer('max_students')->default(8);
            $table->decimal('price', 8, 2);
            $table->boolean('is_recurring')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('club_facilities')->onDelete('cascade');
            $table->foreign('course_type_id')->references('id')->on('course_types')->onDelete('cascade');
            
            $table->index(['club_id', 'day_of_week', 'start_time']);
            $table->index(['facility_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_slots');
    }
};
