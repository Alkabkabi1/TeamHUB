<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\Committee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Committee>
 */
class CommitteeFactory extends Factory
{
    protected $model = Committee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'name' => 'اللجنة '.fake()->unique()->randomElement(['العلمية', 'الطبية', 'الثقافية', 'الإعلامية', 'الاجتماعية', 'التقنية', 'التنظيمية']).' '.fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->sentence(12),
            'theme' => null,
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function withTheme(string $theme = '#006471'): static
    {
        return $this->state(fn (array $attributes) => [
            'theme' => $theme,
        ]);
    }

    /**
     * Attach a logo to the created committee's media collection.
     */
    public function withLogo(): static
    {
        return $this->afterCreating(function (Committee $committee): void {
            $committee->addMediaFromString('fake-logo-content')
                ->usingFileName('logo.png')
                ->toMediaCollection(Committee::LOGO_COLLECTION);
        });
    }
}
