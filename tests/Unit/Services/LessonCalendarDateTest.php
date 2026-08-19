<?php

namespace Tests\Unit\Services;

use App\Services\LessonCalendarDate;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LessonCalendarDateTest extends TestCase
{
    #[Test]
    public function to_ymd_uses_configured_calendar_timezone(): void
    {
        config([
            'bookyourcoach.lesson_calendar.timezone' => 'Europe/Paris',
            'bookyourcoach.lesson_calendar.db_storage_timezone' => 'UTC',
        ]);

        $utcLate = Carbon::parse('2026-07-10 22:30:00', 'UTC');

        $this->assertSame('2026-07-11', LessonCalendarDate::toYmd($utcLate));
    }

    #[Test]
    public function sql_date_expression_uses_date_on_sqlite(): void
    {
        $this->assertSame(
            'DATE(lessons.start_time)',
            LessonCalendarDate::sqlDateExpression('lessons.start_time', \DB::connection())
        );
    }

    #[Test]
    public function db_storage_timezone_defaults_to_calendar_timezone_when_unset(): void
    {
        config([
            'bookyourcoach.lesson_calendar.timezone' => 'Europe/Paris',
            'bookyourcoach.lesson_calendar.db_storage_timezone' => 'Europe/Paris',
        ]);

        $this->assertSame('Europe/Paris', LessonCalendarDate::timezone());
        $this->assertSame('Europe/Paris', LessonCalendarDate::dbStorageTimezone());
        $this->assertSame(
            'DATE(lessons.start_time)',
            LessonCalendarDate::sqlDateExpression('lessons.start_time', \DB::connection())
        );
    }

    #[Test]
    public function where_on_calendar_date_matches_lesson_on_calendar_day_with_timezone_offset(): void
    {
        config([
            'bookyourcoach.lesson_calendar.timezone' => 'Europe/Paris',
            'bookyourcoach.lesson_calendar.db_storage_timezone' => 'UTC',
        ]);

        $club = \App\Models\Club::factory()->create();
        $lesson = \App\Models\Lesson::factory()->forClub($club)->create([
            'start_time' => '2026-07-10 22:30:00',
            'end_time' => '2026-07-10 23:30:00',
            'status' => 'confirmed',
        ]);

        $matched = \App\Models\Lesson::query()
            ->where('id', $lesson->id);
        LessonCalendarDate::whereOnCalendarDate($matched, 'start_time', '2026-07-11');
        $this->assertTrue($matched->exists());

        $notMatched = \App\Models\Lesson::query()
            ->where('id', $lesson->id);
        LessonCalendarDate::whereOnCalendarDate($notMatched, 'start_time', '2026-07-10');
        $this->assertFalse($notMatched->exists());
    }
}
