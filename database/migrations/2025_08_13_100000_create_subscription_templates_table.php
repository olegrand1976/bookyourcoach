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

        // Créer la table des modèles d'abonnements seulement si elle n'existe pas déjà
        if (!Schema::hasTable('subscription_templates')) {
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
        }

        // Table de liaison entre modèles et types de cours (seulement si elle n'existe pas déjà)
        if (!Schema::hasTable('subscription_template_course_types')) {
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
        }

        // Modifier la table subscriptions pour utiliser subscription_template_id
        if (Schema::hasTable('subscriptions')) {
            // Vérifier les colonnes existantes avant d'entrer dans la closure
            $hasClubId = Schema::hasColumn('subscriptions', 'club_id');
            $hasSubscriptionTemplateId = Schema::hasColumn('subscriptions', 'subscription_template_id');
            
            $subscriptionNumberAddedAfter = null; // Variable pour suivre après quelle colonne ajouter subscription_number
            
            Schema::table('subscriptions', function (Blueprint $table) use ($hasClubId, $hasSubscriptionTemplateId, &$subscriptionNumberAddedAfter) {
                // Ajouter subscription_template_id et subscription_number
                if (!$hasSubscriptionTemplateId) {
                    // Ajouter après club_id si elle existe, sinon sans position spécifique
                    if ($hasClubId) {
                        $table->unsignedBigInteger('subscription_template_id')->nullable()->after('club_id');
                    } else {
                        $table->unsignedBigInteger('subscription_template_id')->nullable();
                    }
                    $table->foreign('subscription_template_id', 'sub_template_fk')
                          ->references('id')
                          ->on('subscription_templates')
                          ->onDelete('cascade');
                    
                    // Marquer que subscription_template_id existe maintenant pour subscription_number
                    $subscriptionNumberAddedAfter = 'subscription_template_id';
                } elseif ($hasSubscriptionTemplateId) {
                    // Si subscription_template_id existe déjà, on peut l'utiliser pour subscription_number
                    $subscriptionNumberAddedAfter = 'subscription_template_id';
                }
                
                // Ajouter subscription_number après subscription_template_id si disponible
                if (!Schema::hasColumn('subscriptions', 'subscription_number')) {
                    if ($subscriptionNumberAddedAfter) {
                        $table->string('subscription_number')->unique()->nullable()->after($subscriptionNumberAddedAfter);
                    } else {
                        $table->string('subscription_number')->unique()->nullable();
                    }
                }
                
                // Supprimer les colonnes name, description, total_lessons, free_lessons, price, validity_months
                // On garde ces colonnes pour la migration progressive, on les rend nullable d'abord
                // Vérifier que chaque colonne existe avant de la modifier
                if (Schema::hasColumn('subscriptions', 'name')) {
                    $table->string('name')->nullable()->change();
                }
                if (Schema::hasColumn('subscriptions', 'description')) {
                    $table->text('description')->nullable()->change();
                }
                if (Schema::hasColumn('subscriptions', 'total_lessons')) {
                    $table->integer('total_lessons')->nullable()->change();
                }
                if (Schema::hasColumn('subscriptions', 'free_lessons')) {
                    $table->integer('free_lessons')->nullable()->change();
                }
                if (Schema::hasColumn('subscriptions', 'price')) {
                    $table->decimal('price', 10, 2)->nullable()->change();
                }
                if (Schema::hasColumn('subscriptions', 'validity_months')) {
                    $table->integer('validity_months')->nullable()->change();
                }
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

