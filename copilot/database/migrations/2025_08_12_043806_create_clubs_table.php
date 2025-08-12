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
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('France');
            $table->string('website')->nullable();
            $table->json('facilities')->nullable(); // Manège, carrière, obstacles, etc.
            $table->json('disciplines')->nullable(); // Dressage, CSO, CCE, etc.
            $table->integer('max_students')->nullable();
            $table->decimal('subscription_price', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('terms_and_conditions')->nullable();
            $table->timestamps();
            
            $table->index(['city', 'is_active']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
