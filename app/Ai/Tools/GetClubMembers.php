<?php

namespace App\Ai\Tools;

use App\Enums\ClubCapability;
use App\Services\ClubSupervisorReportService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Roster of a club's approved members, with roles, join date, and volunteer
 * hours. Restricted to users who can manage that club's members.
 */
class GetClubMembers extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List the approved members of a club you manage (name, roles, join date, volunteer hours). '
            .'Only available to club managers with the manage-members capability.';
    }

    public function handle(Request $request): Stringable|string
    {
        $club = $this->resolveClub($request['club'] ?? null);

        if ($club === null) {
            return $this->json(['error' => 'No club matched that name.']);
        }

        if (! $this->user->can(ClubCapability::ManageMembers->value, $club)) {
            return $this->json(['error' => 'You are not permitted to view the members of this club.']);
        }

        $members = app(ClubSupervisorReportService::class)->clubMembersForManagement($club);

        return $this->json([
            'club' => $club->name,
            'membersCount' => $members->count(),
            'members' => $members->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'club' => $schema->string()
                ->description('The club name (or numeric id) whose members to list.')
                ->required(),
        ];
    }
}
