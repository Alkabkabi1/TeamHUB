<?php

namespace App\Ai\Tools;

use App\Enums\CommitteeRole;
use App\Models\CommitteeMembership;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * The committees the current user is an approved member of, with their roles.
 */
class GetMyCommittees extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List the committees (sub-groups within clubs) the current user belongs to, with their role in each.';
    }

    public function handle(Request $request): Stringable|string
    {
        $committees = $this->user->committeeMemberships()
            ->where('status', 'approved')
            ->with(['committee.club:id,name', 'roles'])
            ->get()
            ->filter(fn (CommitteeMembership $membership) => $membership->committee !== null)
            ->map(fn (CommitteeMembership $membership): array => [
                'committee' => $membership->committee->name,
                'committeeId' => $membership->committee_id,
                'club' => $membership->committee->club?->name,
                'roles' => $membership->committeeRoles()->map(fn (CommitteeRole $role): string => $role->value)->all(),
                'joinedAt' => $membership->joined_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return $this->json(['committees' => $committees]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
