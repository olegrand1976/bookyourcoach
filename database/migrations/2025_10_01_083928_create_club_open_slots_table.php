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
        Schema::create('club_open_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->unsignedTinyInteger('day_of_week'); // 0-6 (dimanche=0)
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedBigInteger('discipline_id')->nullable();
            $table->unsignedSmallInteger('max_capacity')->default(1);
            $table->unsignedSmallInteger('duration')->default(60); // minutes
            $table->decimal('price', 8, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->index(['club_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_open_slots');
    }
};
