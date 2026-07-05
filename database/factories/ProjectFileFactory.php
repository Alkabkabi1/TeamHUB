<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectFile>
 */
class ProjectFileFactory extends Factory
{
    protected $model = ProjectFile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'project_id' => null,
            'type' => ProjectFile::TYPE_DOWNLOAD,
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
            'type' => ProjectFile::TYPE_DOWNLOAD,
            'file_path' => 'resources/'.fake()->uuid().'.pdf',
        ]);
    }

    public function media(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProjectFile::TYPE_MEDIA,
            'format' => fake()->randomElement(['PNG', 'MP4']),
            'file_path' => null,
            'published_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $project->workspace_id,
            'project_id' => $project->id,
        ]);
    }
}
