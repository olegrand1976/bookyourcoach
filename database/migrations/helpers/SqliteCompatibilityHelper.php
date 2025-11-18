<?php

namespace Database\Migrations\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class SqliteCompatibilityHelper
{
    /**
     * Drop multiple columns in a way compatible with SQLite
     * SQLite doesn't support multiple dropColumn calls in a single modification
     */
    public static function dropColumns(string $table, array $columns): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // Pour SQLite, séparer chaque dropColumn
            foreach (array_reverse($columns) as $column) {
                if (Schema::hasColumn($table, $column)) {
                    Schema::table($table, function (Blueprint $table) use ($column) {
                        $table->dropColumn($column);
                    });
                }
            }
        } else {
            // Pour MySQL/PostgreSQL, on peut tout faire en une fois
            Schema::table($table, function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
}

