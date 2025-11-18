<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Ajoute les champs nécessaires pour le calcul des commissions enseignants :
     * - est_legacy : Flag pour distinguer Type 1 (false) et Type 2 (true)
     * - date_paiement : Date de paiement/renouvellement de l'abonnement
     * - montant : Montant payé pour cet abonnement (base de calcul de commission)
     * - teacher_id : Enseignant qui doit recevoir la commission
     */
    public function up(): void
    {
        Schema::table('subscription_instances', function (Blueprint $table) {
            // Flag pour distinguer Type 1 (standard) et Type 2 (legacy)
            $table->boolean('est_legacy')->default(false)->after('status');
            
            // Date de paiement/renouvellement (détermine à quel mois appartient le paiement)
            $table->date('date_paiement')->nullable()->after('expires_at');
            
            // Montant payé pour cet abonnement (base de calcul de commission)
            $table->decimal('montant', 10, 2)->nullable()->after('date_paiement');
            
            // Enseignant qui doit recevoir la commission
            // Note: Si null, la commission sera calculée à partir des cours liés
            $table->foreignId('teacher_id')->nullable()->after('montant')
                ->constrained('teachers')->onDelete('set null');
            
            // Index pour les recherches par période et enseignant
            $table->index(['date_paiement', 'est_legacy']);
            $table->index('teacher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite ne supporte pas plusieurs dropColumn dans une seule modification
        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        
        Schema::table('subscription_instances', function (Blueprint $table) {
            try {
                $table->dropIndex(['date_paiement', 'est_legacy']);
            } catch (\Exception $e) {
                // Index n'existe pas, ignorer
            }
            try {
                $table->dropIndex(['teacher_id']);
            } catch (\Exception $e) {
                // Index n'existe pas, ignorer
            }
            try {
                $table->dropForeign(['teacher_id']);
            } catch (\Exception $e) {
                // Foreign key n'existe pas, ignorer
            }
        });
        
        if ($driver === 'sqlite') {
            // Pour SQLite, séparer chaque dropColumn
            if (Schema::hasColumn('subscription_instances', 'teacher_id')) {
                Schema::table('subscription_instances', function (Blueprint $table) {
                    $table->dropColumn('teacher_id');
                });
            }
            if (Schema::hasColumn('subscription_instances', 'montant')) {
                Schema::table('subscription_instances', function (Blueprint $table) {
                    $table->dropColumn('montant');
                });
            }
            if (Schema::hasColumn('subscription_instances', 'date_paiement')) {
                Schema::table('subscription_instances', function (Blueprint $table) {
                    $table->dropColumn('date_paiement');
                });
            }
            if (Schema::hasColumn('subscription_instances', 'est_legacy')) {
                Schema::table('subscription_instances', function (Blueprint $table) {
                    $table->dropColumn('est_legacy');
                });
            }
        } else {
            Schema::table('subscription_instances', function (Blueprint $table) {
                $table->dropColumn(['est_legacy', 'date_paiement', 'montant', 'teacher_id']);
            });
        }
    }
};
