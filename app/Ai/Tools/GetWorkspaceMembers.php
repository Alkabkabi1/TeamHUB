<?php

namespace App\Ai\Tools;

use App\Enums\WorkspaceCapability;
use App\Services\WorkspaceMemberReportService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Roster of a club's approved members, with roles, join date, and volunteer
 * hours. Restricted to users who can manage that club's members.
 */
class GetWorkspaceMembers extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List the approved members of a club you manage (name, roles, join date, volunteer hours). '
            .'Only available to club managers with the manage-members capability.';
    }

    public function handle(Request $request): Stringable|string
    {
        $workspace = $this->resolveWorkspace($request['workspace'] ?? $request['workspace'] ?? null);

        if ($workspace === null) {
            return $this->json(['error' => 'No workspace matched that name.']);
        }

        if (! $this->user->can(WorkspaceCapability::ManageMembers->value, $workspace)) {
            return $this->json(['error' => 'You are not permitted to view the members of this workspace.']);
        }

        $members = app(WorkspaceMemberReportService::class)->clubMembersForManagement($workspace);

        return $this->json([
            'workspace' => $workspace->name,
            'membersCount' => $members->count(),
            'members' => $members->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'workspace' => $schema->string()
                ->description('The workspace name (or numeric id) whose members to list.')
                ->required(),
        ];
    }
}
