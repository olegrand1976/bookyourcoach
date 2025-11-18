<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migration corrective pour s'assurer que subscription_template_id et subscription_number
     * existent bien dans la table subscriptions, notamment pour SQLite en environnement de test.
     */
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            return; // Si la table n'existe pas, rien à faire
        }

        // Vérifier si subscription_template_id existe
        if (!Schema::hasColumn('subscriptions', 'subscription_template_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $hasClubId = Schema::hasColumn('subscriptions', 'club_id');
                
                if ($hasClubId) {
                    $table->unsignedBigInteger('subscription_template_id')->nullable()->after('club_id');
                } else {
                    $table->unsignedBigInteger('subscription_template_id')->nullable();
                }
            });
            
            // Ajouter la clé étrangère si subscription_templates existe
            if (Schema::hasTable('subscription_templates')) {
                try {
                    Schema::table('subscriptions', function (Blueprint $table) {
                        $table->foreign('subscription_template_id', 'sub_template_fk_v2')
                              ->references('id')
                              ->on('subscription_templates')
                              ->onDelete('cascade');
                    });
                } catch (\Exception $e) {
                    // SQLite peut échouer sur les clés étrangères, on continue quand même
                    \Log::warning('Échec de l\'ajout de la clé étrangère subscription_template_id: ' . $e->getMessage());
                }
            }
        }

        // Vérifier si subscription_number existe
        if (!Schema::hasColumn('subscriptions', 'subscription_number')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $hasSubscriptionTemplateId = Schema::hasColumn('subscriptions', 'subscription_template_id');
                
                if ($hasSubscriptionTemplateId) {
                    $table->string('subscription_number')->unique()->nullable()->after('subscription_template_id');
                } else {
                    $table->string('subscription_number')->unique()->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            return;
        }

        $driver = DB::getDriverName();
        
        // Supprimer la clé étrangère si elle existe
        if (Schema::hasColumn('subscriptions', 'subscription_template_id')) {
            try {
                Schema::table('subscriptions', function (Blueprint $table) {
                    $table->dropForeign('sub_template_fk_v2');
                });
            } catch (\Exception $e) {
                // Ignorer si la contrainte n'existe pas
            }
        }
        
        // Supprimer les colonnes
        if ($driver === 'sqlite') {
            // SQLite ne supporte pas multiple dropColumn
            if (Schema::hasColumn('subscriptions', 'subscription_number')) {
                Schema::table('subscriptions', function (Blueprint $table) {
                    $table->dropColumn('subscription_number');
                });
            }
            if (Schema::hasColumn('subscriptions', 'subscription_template_id')) {
                Schema::table('subscriptions', function (Blueprint $table) {
                    $table->dropColumn('subscription_template_id');
                });
            }
        } else {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (Schema::hasColumn('subscriptions', 'subscription_number')) {
                    $table->dropColumn('subscription_number');
                }
                if (Schema::hasColumn('subscriptions', 'subscription_template_id')) {
                    $table->dropColumn('subscription_template_id');
                }
            });
        }
    }
};

