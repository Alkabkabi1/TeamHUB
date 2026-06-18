<?php

namespace Database\Factories;

use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClubMembership>
 */
class ClubMembershipFactory extends Factory
{
    protected $model = ClubMembership::class;

    /**
     * Every membership holds the baseline Member role; manager states layer
     * additional named roles on top via their own afterCreating hooks.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (ClubMembership $membership): void {
            $membership->assignClubRole(ClubRole::Member);
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
            'club_id' => Club::factory(),
            'status' => 'approved',
            'requested_at' => $joinedAt,
            'reviewed_by' => null,
            'reviewed_at' => $joinedAt,
            'rejection_reason' => null,
            'joined_at' => $joinedAt,
        ];
    }

    /**
     * A plain member (baseline Member role only).
     */
    public function member(): static
    {
        return $this;
    }

    /**
     * An events manager (organizer).
     */
    public function organizer(): static
    {
        return $this->afterCreating(
            fn (ClubMembership $membership) => $membership->assignClubRole(ClubRole::EventsManager),
        );
    }

    /**
     * A club lead (full management capabilities).
     */
    public function supervisor(): static
    {
        return $this->afterCreating(
            fn (ClubMembership $membership) => $membership->assignClubRole(ClubRole::ClubLead),
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

    public function rejected(string $reason = 'لم يستوفِ متطلبات العضوية'): static
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
