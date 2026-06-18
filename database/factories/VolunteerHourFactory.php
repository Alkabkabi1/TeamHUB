<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use App\Models\VolunteerHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VolunteerHour>
 */
class VolunteerHourFactory extends Factory
{
    protected $model = VolunteerHour::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_id' => Event::factory()->past(),
            'club_id' => fn (array $attributes) => Event::find($attributes['event_id'])?->club_id,
            'approved_by' => null,
            'hours' => fake()->randomFloat(2, 2, 8),
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_by' => User::factory()->clubSupervisor(),
            'approved_at' => now(),
        ]);
    }

    public function forPastEvent(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_id' => Event::factory()->past(),
        ]);
    }
}
