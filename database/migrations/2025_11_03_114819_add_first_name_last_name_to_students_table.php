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
        Schema::table('students', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('user_id');
            $table->string('last_name')->nullable()->after('first_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite ne supporte pas plusieurs dropColumn dans une seule modification
        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        
        if ($driver === 'sqlite') {
            if (Schema::hasColumn('students', 'last_name')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->dropColumn('last_name');
                });
            }
            if (Schema::hasColumn('students', 'first_name')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->dropColumn('first_name');
                });
            }
        } else {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn(['first_name', 'last_name']);
            });
        }
    }
};
