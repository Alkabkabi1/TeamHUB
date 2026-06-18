<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventAttendance>
 */
class EventAttendanceFactory extends Factory
{
    protected $model = EventAttendance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_id' => Event::factory(),
            'status' => 'pending',
            'checked_in_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'checked_in_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'checked_in_at' => null,
        ]);
    }

    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);
    }

    public function forPastEvent(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_id' => Event::factory()->past(),
        ]);
    }
}
