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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('level', ['debutant', 'intermediaire', 'avance', 'expert'])->default('debutant');
            $table->text('goals')->nullable();
            $table->text('medical_info')->nullable();
            $table->json('preferences')->nullable();
            $table->integer('total_lessons')->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->json('emergency_contacts')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
