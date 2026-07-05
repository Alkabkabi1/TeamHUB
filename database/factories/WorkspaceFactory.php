<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'مساحة عمل '.fake()->unique()->words(2, true),
            'theme' => null,
            'status' => 'active',
        ];
    }

    public function withTheme(string $theme = '#c8924a'): static
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

    public function withLogo(): static
    {
        return $this->afterCreating(function (Workspace $workspace): void {
            $workspace->addMediaFromString('fake-logo-content')
                ->usingFileName('logo.png')
                ->toMediaCollection(Workspace::LOGO_COLLECTION);
        });
    }
}
