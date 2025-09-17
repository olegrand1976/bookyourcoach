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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // general, payment, email, etc.
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('data_type')->default('string'); // string, boolean, integer, json
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // Visible par les utilisateurs
            $table->timestamps();
            
            $table->unique(['type', 'key']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
