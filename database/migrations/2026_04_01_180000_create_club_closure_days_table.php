<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_closure_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->date('closed_on');
            $table->timestamps();

            $table->unique(['club_id', 'closed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_closure_days');
    }
};
