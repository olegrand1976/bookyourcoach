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
        if (!Schema::hasTable('course_types')) {
            Schema::create('course_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discipline_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->integer('duration_minutes')->default(60);
                $table->boolean('is_individual')->default(true);
                $table->integer('max_participants')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index(['discipline_id', 'is_active']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_types');
    }
};
