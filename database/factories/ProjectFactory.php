<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => 'مشروع '.fake()->unique()->randomElement(['تقني', 'إعلامي', 'تطوعي', 'ثقافي', 'رياضي', 'بحثي']).' '.fake()->unique()->numberBetween(1, 9999),
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

    public function withTheme(string $theme = '#c8924a'): static
    {
        return $this->state(fn (array $attributes) => [
            'theme' => $theme,
        ]);
    }

    public function withLogo(): static
    {
        return $this->afterCreating(function (Project $project): void {
            $project->addMediaFromString('fake-logo-content')
                ->usingFileName('logo.png')
                ->toMediaCollection(Project::LOGO_COLLECTION);
        });
    }
}
