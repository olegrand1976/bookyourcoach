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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('specialties')->nullable(); // ['dressage', 'obstacle', 'cross', 'western']
            $table->integer('experience_years')->default(0);
            $table->json('certifications')->nullable();
            $table->decimal('hourly_rate', 8, 2)->default(0);
            $table->text('bio')->nullable();
            $table->boolean('is_available')->default(true);
            $table->integer('max_travel_distance')->default(50); // in km
            $table->json('preferred_locations')->nullable();
            $table->string('stripe_account_id')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_lessons')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id']);
            $table->index(['is_available']);
            $table->index(['rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
