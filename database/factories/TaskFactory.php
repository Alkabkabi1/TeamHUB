<?php

namespace Database\Factories;

use App\Models\Committee;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'committee_id' => Committee::factory(),
            'created_by' => User::factory(),
            'assigned_to' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => 'todo',
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'due_at' => fake()->optional()->dateTimeBetween('now', '+3 weeks'),
            'deliverable_url' => null,
            'deliverable_notes' => null,
            'submitted_for_review_at' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'completed_at' => null,
            'review_notes' => null,
        ];
    }
}
