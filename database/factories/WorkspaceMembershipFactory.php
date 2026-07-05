<?php

namespace Database\Factories;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkspaceMembership>
 */
class WorkspaceMembershipFactory extends Factory
{
    protected $model = WorkspaceMembership::class;

    public function configure(): static
    {
        return $this->afterCreating(function (WorkspaceMembership $membership): void {
            $membership->assignWorkspaceRole(WorkspaceRole::Member);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $joinedAt = fake()->dateTimeBetween('-2 years', '-1 month');

        return [
            'user_id' => User::factory(),
            'workspace_id' => Workspace::factory(),
            'status' => 'approved',
            'requested_at' => $joinedAt,
            'reviewed_by' => null,
            'reviewed_at' => $joinedAt,
            'rejection_reason' => null,
            'joined_at' => $joinedAt,
        ];
    }

    public function member(): static
    {
        return $this;
    }

    public function organizer(): static
    {
        return $this->afterCreating(
            fn (WorkspaceMembership $membership) => $membership->assignWorkspaceRole(WorkspaceRole::MembershipManager),
        );
    }

    public function supervisor(): static
    {
        return $this->afterCreating(
            fn (WorkspaceMembership $membership) => $membership->assignWorkspaceRole(WorkspaceRole::WorkspaceLead),
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

    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            $joinedAt = $attributes['joined_at'] ?? fake()->dateTimeBetween('-2 years', '-1 month');

            return [
                'status' => 'approved',
                'requested_at' => $attributes['requested_at'] ?? $joinedAt,
                'reviewed_at' => $attributes['reviewed_at'] ?? $joinedAt,
                'rejection_reason' => null,
                'joined_at' => $joinedAt,
            ];
        });
    }

    public function rejected(string $reason = 'لم تستوفِ متطلبات العضوية'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'requested_at' => now()->subDays(3),
            'reviewed_at' => now()->subDay(),
            'rejection_reason' => $reason,
            'joined_at' => null,
        ]);
    }
}
