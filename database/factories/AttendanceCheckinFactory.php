<?php

namespace Database\Factories;

use App\Models\AttendanceCheckin;
use App\Models\EventAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceCheckin>
 */
class AttendanceCheckinFactory extends Factory
{
    protected $model = AttendanceCheckin::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_attendance_id' => EventAttendance::factory(),
            'attended_on' => now()->toDateString(),
            'checked_in_at' => now(),
            'recorded_by' => null,
        ];
    }

    /**
     * A check-in logged on a specific calendar day.
     */
    public function on(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'attended_on' => $date,
            'checked_in_at' => $date.' 09:00:00',
        ]);
    }
}
