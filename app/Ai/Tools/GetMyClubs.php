<?php

namespace App\Ai\Tools;

use App\Enums\ClubRole;
use App\Models\ClubMembership;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * The clubs the current user is an approved member of, with their roles.
 */
class GetMyClubs extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List the clubs the current user is a member of, along with their role in each. '
            .'Use this for "which clubs am I in?".';
    }

    public function handle(Request $request): Stringable|string
    {
        $clubs = $this->user->clubMemberships()
            ->where('status', 'approved')
            ->with(['club:id,name', 'roles'])
            ->get()
            ->filter(fn (ClubMembership $membership) => $membership->club !== null)
            ->map(fn (ClubMembership $membership): array => [
                'club' => $membership->club->name,
                'clubId' => $membership->club_id,
                'roles' => $membership->clubRoles()->map(fn (ClubRole $role): string => $role->value)->all(),
                'joinedAt' => $membership->joined_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return $this->json(['clubs' => $clubs]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
