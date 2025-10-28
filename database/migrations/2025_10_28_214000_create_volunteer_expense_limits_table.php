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
        Schema::create('volunteer_expense_limits', function (Blueprint $table) {
            $table->id();
            $table->year('year')->unique(); // Année civile
            $table->decimal('daily_amount', 8, 2); // Montant par jour
            $table->decimal('yearly_amount', 8, 2); // Montant par an
            $table->decimal('yearly_special_categories', 8, 2)->nullable(); // Montant pour catégories spéciales
            $table->decimal('yearly_health_sector', 8, 2)->nullable(); // Montant secteur santé
            $table->string('source_url')->nullable(); // URL source
            $table->timestamp('fetched_at')->nullable(); // Date de récupération
            $table->timestamps();
            
            $table->index('year');
        });
        
        // Insérer les valeurs de 2025 (données actuelles)
        DB::table('volunteer_expense_limits')->insert([
            'year' => 2025,
            'daily_amount' => 42.31,
            'yearly_amount' => 1692.51,
            'yearly_special_categories' => 3108.44,
            'yearly_health_sector' => null,
            'source_url' => 'https://conseilsuperieurvolontaires.belgium.be/fr/defraiements/plafonds-limites-indexes.htm',
            'fetched_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteer_expense_limits');
    }
};

