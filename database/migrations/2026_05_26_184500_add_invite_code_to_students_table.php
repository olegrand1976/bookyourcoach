<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('invite_code', 12)->nullable()->unique()->after('user_id');
            $table->timestamp('invite_code_expires_at')->nullable()->after('invite_code');
            $table->unsignedBigInteger('linked_by_user_id')->nullable()->after('invite_code_expires_at');
            $table->timestamp('linked_at')->nullable()->after('linked_by_user_id');

            $table->foreign('linked_by_user_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['linked_by_user_id']);
            $table->dropUnique(['invite_code']);
            $table->dropColumn([
                'invite_code',
                'invite_code_expires_at',
                'linked_by_user_id',
                'linked_at',
            ]);
        });
    }
};
