<?php

namespace Database\Factories;

use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Club>
 */
class ClubFactory extends Factory
{
    protected $model = Club::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'نادي '.fake()->unique()->words(2, true),
            'theme' => null,
            'category' => fake()->randomElement(['ثقافي', 'رياضي', 'أكاديمي', 'تقني']),
            'college' => fake()->randomElement([
                'كلية الحاسبات والمعلومات',
                'كلية الطب',
                'كلية الهندسة',
            ]),
            'status' => 'active',
        ];
    }

    public function withTheme(string $theme = '#006471'): static
    {
        return $this->state(fn (array $attributes) => [
            'theme' => $theme,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function founding(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'founding',
        ]);
    }

    /**
     * Attach a logo to the created club's media collection.
     */
    public function withLogo(): static
    {
        return $this->afterCreating(function (Club $club): void {
            $club->addMediaFromString('fake-logo-content')
                ->usingFileName('logo.png')
                ->toMediaCollection(Club::LOGO_COLLECTION);
        });
    }
}
