<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Ajoute les champs nécessaires pour le calcul des commissions sur les cours individuels :
     * - est_legacy : Flag pour distinguer DCL (false) et NDCL (true)
     * - date_paiement : Date de paiement du cours (pour déterminer le mois de commission)
     * - montant : Montant payé pour ce cours
     */
    public function up(): void
    {
        // Vérifier que la table lessons existe avant d'essayer de la modifier
        if (!Schema::hasTable('lessons')) {
            \Log::warning('La table lessons n\'existe pas. La migration sera ignorée.');
            return;
        }

        Schema::table('lessons', function (Blueprint $table) {
            // Ajouter les champs pour les commissions si ils n'existent pas déjà
            if (!Schema::hasColumn('lessons', 'est_legacy')) {
                $table->boolean('est_legacy')->default(false)->after('status')
                    ->comment('false = DCL (Déclaré), true = NDCL (Non Déclaré)');
            }
            
            if (!Schema::hasColumn('lessons', 'date_paiement')) {
                $table->date('date_paiement')->nullable()->after('est_legacy')
                    ->comment('Date de paiement du cours (détermine le mois de commission)');
            }
            
            if (!Schema::hasColumn('lessons', 'montant')) {
                // Le champ price existe déjà, mais montant peut être différent (montant réellement payé)
                // Si price existe, on peut utiliser price comme montant par défaut
                $table->decimal('montant', 10, 2)->nullable()->after('date_paiement')
                    ->comment('Montant réellement payé pour ce cours (peut différer de price)');
            }
        });

        // Ajouter les index seulement si la table existe et si les colonnes existent
        if (Schema::hasTable('lessons')) {
            try {
                Schema::table('lessons', function (Blueprint $table) {
                    // Vérifier si l'index n'existe pas déjà avant de le créer
                    try {
                        $table->index(['teacher_id', 'date_paiement', 'est_legacy'], 'lessons_commission_index');
                    } catch (\Exception $e) {
                        // Index existe déjà, ignorer
                        if (strpos($e->getMessage(), 'Duplicate key') === false) {
                            throw $e;
                        }
                    }
                    
                    try {
                        $table->index(['date_paiement', 'status'], 'lessons_payment_date_index');
                    } catch (\Exception $e) {
                        // Index existe déjà, ignorer
                        if (strpos($e->getMessage(), 'Duplicate key') === false) {
                            throw $e;
                        }
                    }
                });
            } catch (\Exception $e) {
                // Ignorer les erreurs d'index si elles existent déjà
                \Log::warning('Erreur lors de la création des index pour lessons: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite ne supporte pas plusieurs dropColumn dans une seule modification
        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // Pour SQLite, séparer chaque dropColumn
            if (Schema::hasColumn('lessons', 'montant')) {
                Schema::table('lessons', function (Blueprint $table) {
                    $table->dropColumn('montant');
                });
            }
            if (Schema::hasColumn('lessons', 'date_paiement')) {
                Schema::table('lessons', function (Blueprint $table) {
                    $table->dropColumn('date_paiement');
                });
            }
            if (Schema::hasColumn('lessons', 'est_legacy')) {
                Schema::table('lessons', function (Blueprint $table) {
                    $table->dropColumn('est_legacy');
                });
            }
            
            // Supprimer les index séparément
            try {
                Schema::table('lessons', function (Blueprint $table) {
                    $table->dropIndex('lessons_commission_index');
                });
            } catch (\Exception $e) {
                // Index n'existe pas, ignorer
            }
            try {
                Schema::table('lessons', function (Blueprint $table) {
                    $table->dropIndex('lessons_payment_date_index');
                });
            } catch (\Exception $e) {
                // Index n'existe pas, ignorer
            }
        } else {
            Schema::table('lessons', function (Blueprint $table) {
                // Supprimer les index
                try {
                    $table->dropIndex('lessons_commission_index');
                } catch (\Exception $e) {
                    // Index n'existe pas, ignorer
                }
                try {
                    $table->dropIndex('lessons_payment_date_index');
                } catch (\Exception $e) {
                    // Index n'existe pas, ignorer
                }
                
                // Supprimer les colonnes
                if (Schema::hasColumn('lessons', 'montant')) {
                    $table->dropColumn('montant');
                }
                if (Schema::hasColumn('lessons', 'date_paiement')) {
                    $table->dropColumn('date_paiement');
                }
                if (Schema::hasColumn('lessons', 'est_legacy')) {
                    $table->dropColumn('est_legacy');
                }
            });
        }
    }
};

