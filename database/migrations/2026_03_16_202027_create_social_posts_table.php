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
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->nullable()->constrained('clubs')->nullOnDelete();
            $table->date('scheduled_at');
            $table->string('type', 32)->default('Conseil'); // Conseil, Promo, Fun Fact
            $table->text('text');
            $table->text('image_prompt');
            $table->string('image_path', 512)->nullable();
            $table->string('status', 32)->default('draft'); // draft, validated
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
