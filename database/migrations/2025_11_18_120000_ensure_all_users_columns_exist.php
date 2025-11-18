<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migration corrective finale pour garantir que toutes les colonnes de users existent
     * Ceci consolide toutes les colonnes ajoutées par différentes migrations
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            // Colonnes from base users table
            // (id, name, email, password, remember_token, created_at, updated_at already exist)
            
            // From 2025_08_10_201834_add_role_and_fields_to_users_table
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('student')->after('password');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('role');
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active')->after('phone');
            }
            
            // From 2025_08_11_142438_add_is_active_to_users_table
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
            
            // From 2025_09_08_150215_add_qr_code_to_users_table
            if (!Schema::hasColumn('users', 'qr_code')) {
                $table->string('qr_code')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('users', 'qr_code_generated_at')) {
                $table->timestamp('qr_code_generated_at')->nullable()->after('qr_code');
            }
            
            // From 2025_09_09_142031_update_users_table_add_detailed_fields
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users', 'street')) {
                $table->string('street')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'street_number')) {
                $table->string('street_number')->nullable()->after('street');
            }
            if (!Schema::hasColumn('users', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('street_number');
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('postal_code');
            }
            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country')->default('Belgium')->after('city');
            }
            if (!Schema::hasColumn('users', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('country');
            }
            
            // From 2025_09_17_190334_add_street_box_to_users_table
            if (!Schema::hasColumn('users', 'street_box')) {
                $table->string('street_box')->nullable()->after('street_number');
            }
            
            // From 2025_10_28_210644_add_address_fields_to_users_table
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('email');
            }
            
            // From 2025_11_02_111356_add_niss_bank_account_and_experience_start_to_users_table
            if (!Schema::hasColumn('users', 'niss')) {
                $table->string('niss')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'bank_account')) {
                $table->string('bank_account')->nullable()->after('niss');
            }
            if (!Schema::hasColumn('users', 'experience_start_date')) {
                $table->date('experience_start_date')->nullable()->after('bank_account');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // On ne supprime pas les colonnes dans le down car elles peuvent être utilisées ailleurs
    }
};

