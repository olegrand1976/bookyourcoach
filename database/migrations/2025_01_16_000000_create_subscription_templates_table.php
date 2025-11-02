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
        // Vérifier que la table clubs existe avant de créer les clés étrangères
        if (!Schema::hasTable('clubs')) {
            throw new \Exception('La table clubs doit exister avant de créer subscription_templates. Veuillez exécuter toutes les migrations dans l\'ordre.');
        }

        // Créer la table des modèles d'abonnements
        Schema::create('subscription_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->string('model_number'); // Numéro du modèle (ex: MOD001, MOD002)
            $table->integer('total_lessons'); // Nombre de cours
            $table->integer('free_lessons')->default(0); // Nombre de cours gratuits
            $table->decimal('price', 10, 2); // Prix de l'abonnement
            $table->integer('validity_months')->default(12); // Durée de validité en mois
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['club_id', 'model_number']);
            $table->index(['club_id', 'is_active']);
            
            // Ajouter la clé étrangère avec un nom court
            $table->foreign('club_id', 'sub_template_club_fk')
                  ->references('id')
                  ->on('clubs')
                  ->onDelete('cascade');
        });

        // Table de liaison entre modèles et types de cours
        Schema::create('subscription_template_course_types', function (Blueprint $table) {
            $table->id();
            // Nom d'index court pour les clés étrangères (MySQL limite à 64 caractères)
            $table->unsignedBigInteger('subscription_template_id');
            $table->unsignedBigInteger('course_type_id');
            $table->timestamps();
            
            $table->unique(['subscription_template_id', 'course_type_id'], 'sub_template_course_unique');
            
            // Ajouter les clés étrangères avec des noms courts
            $table->foreign('subscription_template_id', 'sub_template_id_fk')
                  ->references('id')
                  ->on('subscription_templates')
                  ->onDelete('cascade');
            $table->foreign('course_type_id', 'sub_template_course_type_id_fk')
                  ->references('id')
                  ->on('course_types')
                  ->onDelete('cascade');
        });

        // Modifier la table subscriptions pour utiliser subscription_template_id
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                // Ajouter subscription_template_id et subscription_number
                if (!Schema::hasColumn('subscriptions', 'subscription_template_id')) {
                    $table->unsignedBigInteger('subscription_template_id')->nullable()->after('club_id');
                    $table->foreign('subscription_template_id', 'sub_template_fk')
                          ->references('id')
                          ->on('subscription_templates')
                          ->onDelete('cascade');
                }
                if (!Schema::hasColumn('subscriptions', 'subscription_number')) {
                    $table->string('subscription_number')->unique()->nullable()->after('subscription_template_id');
                }
                // Supprimer les colonnes name, description, total_lessons, free_lessons, price, validity_months
                // On garde ces colonnes pour la migration progressive, on les rend nullable d'abord
                $table->string('name')->nullable()->change();
                $table->text('description')->nullable()->change();
                $table->integer('total_lessons')->nullable()->change();
                $table->integer('free_lessons')->nullable()->change();
                $table->decimal('price', 10, 2)->nullable()->change();
                $table->integer('validity_months')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (Schema::hasColumn('subscriptions', 'subscription_template_id')) {
                    $table->dropForeign('sub_template_fk');
                    $table->dropColumn('subscription_template_id');
                }
                if (Schema::hasColumn('subscriptions', 'subscription_number')) {
                    $table->dropUnique(['subscription_number']);
                    $table->dropColumn('subscription_number');
                }
            });
        }
        
        Schema::dropIfExists('subscription_template_course_types');
        Schema::dropIfExists('subscription_templates');
    }
};

