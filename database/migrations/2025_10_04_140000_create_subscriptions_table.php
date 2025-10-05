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
        // Table principale des abonnements (modèles d'abonnements définis par le club)
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Nom de l'abonnement (ex: "Formule 10 cours")
            $table->integer('total_lessons'); // Nombre total de cours inclus
            $table->integer('free_lessons')->default(0); // Nombre de cours gratuits offerts
            $table->decimal('price', 10, 2); // Prix de l'abonnement
            $table->text('description')->nullable(); // Description de l'abonnement
            $table->boolean('is_active')->default(true); // L'abonnement est-il proposé actuellement
            $table->timestamps();
        });

        // Table des types de cours associés à un abonnement
        Schema::create('subscription_course_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('discipline_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Un abonnement ne peut avoir qu'une seule fois le même type de cours
            $table->unique(['subscription_id', 'discipline_id']);
        });

        // Table des instances d'abonnements (abonnements achetés, partagés ou individuels)
        Schema::create('subscription_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->integer('lessons_used')->default(0); // Nombre de cours déjà utilisés
            $table->date('started_at'); // Date de début de l'abonnement
            $table->date('expires_at')->nullable(); // Date d'expiration (optionnel)
            $table->enum('status', ['active', 'completed', 'expired', 'cancelled'])->default('active');
            $table->timestamps();
            
            // Index pour les recherches fréquentes
            $table->index(['subscription_id', 'status']);
        });

        // Table de liaison many-to-many entre instances et élèves (pour abonnements familiaux)
        Schema::create('subscription_instance_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_instance_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Un élève ne peut être lié qu'une seule fois à une instance donnée
            $table->unique(['subscription_instance_id', 'student_id'], 'sub_instance_student_unique');
            
            // Index pour les recherches
            $table->index('student_id');
            $table->index('subscription_instance_id');
        });

        // Table de liaison entre instances d'abonnements et cours consommés
        Schema::create('subscription_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_instance_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Un cours ne peut être associé qu'à un seul abonnement
            $table->unique('lesson_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_lessons');
        Schema::dropIfExists('subscription_instance_students');
        Schema::dropIfExists('subscription_instances');
        Schema::dropIfExists('subscription_course_types');
        Schema::dropIfExists('subscriptions');
    }
};

