<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\CourseType;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition()
    {
        $startDate = $this->faker->dateTimeBetween('now', '+30 days');
        $startTime = Carbon::parse($startDate);
        
        // Durée par défaut de 60 minutes si pas de CourseType
        $duration = 60;
        
        return [
            'student_id' => Student::factory(),
            'teacher_id' => Teacher::factory(),
            'course_type_id' => CourseType::factory(),
            'location_id' => Location::factory(),
            'start_time' => $startTime,
            'end_time' => $startTime->copy()->addMinutes($duration),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'completed', 'cancelled']),
            'price' => $this->faker->randomFloat(2, 30, 80),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'refunded']),
        ];
    }

    /**
     * Lesson for today
     */
    public function today()
    {
        return $this->state(function (array $attributes) {
            $startTime = Carbon::today()->addHours($this->faker->numberBetween(8, 18));
            return [
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addHour(),
            ];
        });
    }

    /**
     * Lesson for tomorrow
     */
    public function tomorrow()
    {
        return $this->state(function (array $attributes) {
            $startTime = Carbon::tomorrow()->addHours($this->faker->numberBetween(8, 18));
            return [
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addHour(),
            ];
        });
    }

    /**
     * Confirmed lesson
     */
    public function confirmed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'confirmed',
                'payment_status' => 'paid',
            ];
        });
    }

    /**
     * Pending lesson
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'payment_status' => 'pending',
            ];
        });
    }

    /**
     * Completed lesson
     */
    public function completed()
    {
        return $this->state(function (array $attributes) {
            $startTime = $this->faker->dateTimeBetween('-30 days', '-1 day');
            return [
                'start_time' => $startTime,
                'end_time' => Carbon::parse($startTime)->addHour(),
                'status' => 'completed',
                'payment_status' => 'paid',
            ];
        });
    }

    /**
     * Cancelled lesson
     */
    public function cancelled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
                'payment_status' => $this->faker->randomElement(['pending', 'refunded']),
            ];
        });
    }

    /**
     * Lesson with specific duration
     */
    public function withDuration($minutes)
    {
        return $this->state(function (array $attributes) use ($minutes) {
            $startTime = Carbon::parse($attributes['start_time'] ?? $this->faker->dateTimeBetween('now', '+30 days'));
            return [
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addMinutes($minutes),
            ];
        });
    }

    /**
     * Lesson with specific price
     */
    public function withPrice($price)
    {
        return $this->state(function (array $attributes) use ($price) {
            return [
                'price' => $price,
            ];
        });
    }

    /**
     * Lesson with notes
     */
    public function withNotes($notes = null)
    {
        return $this->state(function (array $attributes) use ($notes) {
            return [
                'notes' => $notes ?? $this->faker->paragraph(),
            ];
        });
    }

    /**
     * Lesson for specific student
     */
    public function forStudent($student)
    {
        return $this->state(function (array $attributes) use ($student) {
            return [
                'student_id' => $student->id ?? $student,
            ];
        });
    }

    /**
     * Lesson for specific teacher
     */
    public function forTeacher($teacher)
    {
        return $this->state(function (array $attributes) use ($teacher) {
            return [
                'teacher_id' => $teacher->id ?? $teacher,
            ];
        });
    }

    /**
     * Lesson at specific location
     */
    public function atLocation($location)
    {
        return $this->state(function (array $attributes) use ($location) {
            return [
                'location_id' => $location->id ?? $location,
            ];
        });
    }

    /**
     * Morning lesson
     */
    public function morning()
    {
        return $this->state(function (array $attributes) {
            $date = Carbon::parse($attributes['start_time'] ?? $this->faker->dateTimeBetween('now', '+30 days'))->startOfDay();
            $startTime = $date->addHours($this->faker->numberBetween(8, 11));
            return [
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addHour(),
            ];
        });
    }

    /**
     * Afternoon lesson
     */
    public function afternoon()
    {
        return $this->state(function (array $attributes) {
            $date = Carbon::parse($attributes['start_time'] ?? $this->faker->dateTimeBetween('now', '+30 days'))->startOfDay();
            $startTime = $date->addHours($this->faker->numberBetween(14, 17));
            return [
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addHour(),
            ];
        });
    }
}
