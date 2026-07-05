<?php

namespace Database\Factories;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectMembership>
 */
class ProjectMembershipFactory extends Factory
{
    protected $model = ProjectMembership::class;

    public function configure(): static
    {
        return $this->afterCreating(function (ProjectMembership $membership): void {
            if ($membership->status === 'approved') {
                $membership->assignProjectRole(ProjectRole::Member);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $joinedAt = fake()->dateTimeBetween('-1 year', '-1 month');

        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'status' => 'approved',
            'requested_at' => $joinedAt,
            'reviewed_by' => null,
            'reviewed_at' => $joinedAt,
            'rejection_reason' => null,
            'joined_at' => $joinedAt,
        ];
    }

    public function lead(): static
    {
        return $this->afterCreating(
            fn (ProjectMembership $membership) => $membership->assignProjectRole(ProjectRole::ProjectLead),
        );
    }

    public function contentManager(): static
    {
        return $this->afterCreating(
            fn (ProjectMembership $membership) => $membership->assignProjectRole(ProjectRole::ContentManager),
        );
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'requested_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
            'joined_at' => null,
        ]);
    }
}
