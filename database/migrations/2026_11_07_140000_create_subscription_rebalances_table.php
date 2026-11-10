<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remises en cohérence des abonnements d'un élève (ou d'une famille) : état avant / après,
 * pour pouvoir annuler une correction élève par élève.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_rebalances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->json('student_ids');
            $table->json('instance_ids');
            $table->json('before');
            $table->json('after');
            $table->json('summary');
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reverted_at')->nullable();
            $table->foreignId('reverted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['club_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_rebalances');
    }
};
