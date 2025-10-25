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
        Schema::create('teacher_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('certification_id')->constrained()->onDelete('cascade');
            $table->date('obtained_date'); // Date d'obtention
            $table->date('expiry_date')->nullable(); // Date d'expiration (null = permanente)
            $table->string('certificate_number')->nullable(); // Numéro de certificat
            $table->string('issuing_authority')->nullable(); // Autorité émettrice (peut différer de celle de la certification)
            $table->string('certificate_file')->nullable(); // Fichier du certificat
            $table->text('notes')->nullable(); // Notes personnelles
            $table->boolean('is_valid')->default(true); // Certificat valide
            $table->boolean('renewal_required')->default(false); // Renouvellement requis
            $table->date('renewal_reminder_date')->nullable(); // Date de rappel pour renouvellement
            $table->boolean('is_verified')->default(false); // Certificat vérifié
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null'); // Qui a vérifié
            $table->date('verified_at')->nullable(); // Date de vérification
            $table->timestamps();

            $table->unique(['teacher_id', 'certification_id']);
            $table->index(['teacher_id', 'is_valid']);
            $table->index(['certification_id', 'is_valid']);
            $table->index(['expiry_date', 'renewal_required']);
            $table->index(['is_verified', 'is_valid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_certifications');
    }
};