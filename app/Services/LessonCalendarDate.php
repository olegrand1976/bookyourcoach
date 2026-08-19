<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;

/**
 * Date calendaire d'un cours (jour local club) — source unique pour fermetures, filtres API et récurrence.
 */
class LessonCalendarDate
{
    public static function timezone(): string
    {
        return (string) config(
            'bookyourcoach.lesson_calendar.timezone',
            config('bookyourcoach.club_daily_planning_insight.timezone', config('app.timezone'))
        );
    }

    public static function dbStorageTimezone(): string
    {
        return (string) config(
            'bookyourcoach.lesson_calendar.db_storage_timezone',
            config('app.timezone')
        );
    }

    public static function toYmd(Carbon|string|null $startTime): ?string
    {
        if ($startTime === null || $startTime === '') {
            return null;
        }

        return Carbon::parse($startTime)
            ->timezone(self::timezone())
            ->toDateString();
    }

    /**
     * Expression SQL DATE(...) alignée sur {@see toYmd()} pour les requêtes (exclude / include closure).
     */
    public static function sqlDateExpression(string $qualifiedColumn, ?Connection $connection = null): string
    {
        $driver = $connection?->getDriverName() ?? 'mysql';

        if ($driver === 'sqlite') {
            return "DATE({$qualifiedColumn})";
        }

        $storageTz = self::dbStorageTimezone();
        $calendarTz = self::timezone();

        if ($storageTz === $calendarTz) {
            return "DATE({$qualifiedColumn})";
        }

        return "DATE(CONVERT_TZ({$qualifiedColumn}, '{$storageTz}', '{$calendarTz}'))";
    }

    /**
     * Filtre les lignes dont la date calendaire du cours correspond à $dateYmd (même règle que {@see toYmd()}).
     */
    public static function whereOnCalendarDate(Builder $query, string $qualifiedColumn, string $dateYmd): void
    {
        $connection = $query->getConnection();
        $storageTz = self::dbStorageTimezone();
        $calendarTz = self::timezone();

        if ($storageTz !== $calendarTz && $connection->getDriverName() === 'sqlite') {
            $start = Carbon::parse($dateYmd, $calendarTz)->startOfDay()->timezone($storageTz);
            $end = Carbon::parse($dateYmd, $calendarTz)->endOfDay()->timezone($storageTz);
            $query->whereBetween($qualifiedColumn, [
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s'),
            ]);

            return;
        }

        $expr = self::sqlDateExpression($qualifiedColumn, $connection);
        $query->whereRaw("{$expr} = ?", [$dateYmd]);
    }
}
