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
            $table->string('qr_code')->unique()->nullable()->after('phone');
            $table->timestamp('qr_code_generated_at')->nullable()->after('qr_code');
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
            if (Schema::hasColumn('clubs', 'qr_code_generated_at')) {
                Schema::table('clubs', function (Blueprint $table) {
                    $table->dropColumn('qr_code_generated_at');
                });
            }
            if (Schema::hasColumn('clubs', 'qr_code')) {
                Schema::table('clubs', function (Blueprint $table) {
                    $table->dropColumn('qr_code');
                });
            }
        } else {
            Schema::table('clubs', function (Blueprint $table) {
                $table->dropColumn(['qr_code', 'qr_code_generated_at']);
            });
        }
    }
};
