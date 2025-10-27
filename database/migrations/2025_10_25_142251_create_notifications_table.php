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
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->enum('type', [
                    'replacement_request',      // Demande de remplacement reçue
                    'replacement_accepted',     // Remplacement accepté
                    'replacement_rejected',     // Remplacement refusé
                    'replacement_cancelled',    // Remplacement annulé
                    'club_replacement_accepted' // Notification pour le club
                ]);
                $table->string('title');
                $table->text('message');
                $table->json('data')->nullable(); // Données supplémentaires (IDs, etc.)
                $table->boolean('read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'read']);
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
