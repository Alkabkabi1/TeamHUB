<?php

namespace Database\Factories;

use App\Enums\CommitteeRole;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommitteeMembership>
 */
class CommitteeMembershipFactory extends Factory
{
    protected $model = CommitteeMembership::class;

    /**
     * Every approved membership holds the baseline Member role; manager states
     * layer additional named roles on top via their own afterCreating hooks.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (CommitteeMembership $membership): void {
            if ($membership->status === 'approved') {
                $membership->assignCommitteeRole(CommitteeRole::Member);
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
            'committee_id' => Committee::factory(),
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
            fn (CommitteeMembership $membership) => $membership->assignCommitteeRole(CommitteeRole::CommitteeLead),
        );
    }

    public function eventsManager(): static
    {
        return $this->afterCreating(
            fn (CommitteeMembership $membership) => $membership->assignCommitteeRole(CommitteeRole::ContentManager),
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
