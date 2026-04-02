<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('audience', 20);
            $table->string('subject', 255);
            $table->text('body');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamps();

            $table->index(['club_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_communication_logs');
    }
};
