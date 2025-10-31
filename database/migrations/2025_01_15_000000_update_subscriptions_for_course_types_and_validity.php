<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajouter validity_months aux subscriptions (par défaut 12 mois = 1 an)
        if (Schema::hasTable('subscriptions') && !Schema::hasColumn('subscriptions', 'validity_months')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->integer('validity_months')->default(12)->after('price');
            });
        }

        // Si subscription_course_types existe avec discipline_id, on le remplace par course_type_id
        if (Schema::hasTable('subscription_course_types')) {
            // Vérifier si la colonne discipline_id existe
            if (Schema::hasColumn('subscription_course_types', 'discipline_id')) {
                // Créer une nouvelle table temporaire avec course_type_id
                Schema::create('subscription_course_types_new', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
                    $table->foreignId('course_type_id')->constrained('course_types')->onDelete('cascade');
                    $table->timestamps();
                    
                    $table->unique(['subscription_id', 'course_type_id']);
                });

                // Copier les données si possible (nécessiterait une correspondance discipline -> course_type)
                // Pour l'instant, on vide la table et on laisse le club reconfigurer
                
                // Supprimer l'ancienne table
                Schema::dropIfExists('subscription_course_types');
                
                // Renommer la nouvelle table
                Schema::rename('subscription_course_types_new', 'subscription_course_types');
            } elseif (!Schema::hasColumn('subscription_course_types', 'course_type_id')) {
                // Si la table existe mais n'a pas course_type_id, l'ajouter
                Schema::table('subscription_course_types', function (Blueprint $table) {
                    $table->foreignId('course_type_id')->nullable()->after('subscription_id')->constrained('course_types')->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'validity_months')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('validity_months');
            });
        }

        // Note: On ne fait pas de rollback complet pour subscription_course_types
        // car cela pourrait casser les données existantes
    }
};

