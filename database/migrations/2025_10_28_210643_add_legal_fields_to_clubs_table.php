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
        Schema::table('clubs', function (Blueprint $table) {
            // Représentant légal
            $table->string('legal_representative_name')->nullable()->after('company_number');
            $table->string('legal_representative_role')->nullable()->after('legal_representative_name');
            
            // Assurance RC (obligatoire)
            $table->string('insurance_rc_company')->nullable()->after('legal_representative_role');
            $table->string('insurance_rc_policy_number')->nullable()->after('insurance_rc_company');
            
            // Assurance complémentaire (optionnelle)
            $table->string('insurance_additional_company')->nullable()->after('insurance_rc_policy_number');
            $table->string('insurance_additional_policy_number')->nullable()->after('insurance_additional_company');
            $table->text('insurance_additional_details')->nullable()->after('insurance_additional_policy_number');
            
            // Régime de défraiement
            $table->enum('expense_reimbursement_type', ['forfait', 'reel', 'aucun'])->default('aucun')->after('insurance_additional_details');
            $table->text('expense_reimbursement_details')->nullable()->after('expense_reimbursement_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropColumn([
                'legal_representative_name',
                'legal_representative_role',
                'insurance_rc_company',
                'insurance_rc_policy_number',
                'insurance_additional_company',
                'insurance_additional_policy_number',
                'insurance_additional_details',
                'expense_reimbursement_type',
                'expense_reimbursement_details'
            ]);
        });
    }
};
