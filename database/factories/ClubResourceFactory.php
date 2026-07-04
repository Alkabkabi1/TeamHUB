<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\ClubResource;
use App\Models\Committee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClubResource>
 */
class ClubResourceFactory extends Factory
{
    protected $model = ClubResource::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'committee_id' => null,
            'type' => ClubResource::TYPE_DOWNLOAD,
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'format' => fake()->randomElement(['PDF', 'PPTX', 'PNG', 'MP4']),
            'access' => fake()->randomElement(['عام', 'خاص']),
            'file_path' => 'resources/'.fake()->uuid().'.pdf',
            'published_at' => now(),
        ];
    }

    public function download(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ClubResource::TYPE_DOWNLOAD,
            'file_path' => 'resources/'.fake()->uuid().'.pdf',
        ]);
    }

    public function media(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ClubResource::TYPE_MEDIA,
            'format' => fake()->randomElement(['PNG', 'MP4']),
            'file_path' => null,
            'published_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function forCommittee(Committee $committee): static
    {
        return $this->state(fn (array $attributes) => [
            'club_id' => $committee->club_id,
            'committee_id' => $committee->id,
        ]);
    }
}
