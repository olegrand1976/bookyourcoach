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
            $table->string('street')->nullable()->after('phone');
            $table->string('street_number')->nullable()->after('street');
            $table->string('street_box')->nullable()->after('street_number');
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
            if (Schema::hasColumn('clubs', 'street_box')) {
                Schema::table('clubs', function (Blueprint $table) {
                    $table->dropColumn('street_box');
                });
            }
            if (Schema::hasColumn('clubs', 'street_number')) {
                Schema::table('clubs', function (Blueprint $table) {
                    $table->dropColumn('street_number');
                });
            }
            if (Schema::hasColumn('clubs', 'street')) {
                Schema::table('clubs', function (Blueprint $table) {
                    $table->dropColumn('street');
                });
            }
        } else {
            Schema::table('clubs', function (Blueprint $table) {
                $table->dropColumn(['street', 'street_number', 'street_box']);
            });
        }
    }
};
