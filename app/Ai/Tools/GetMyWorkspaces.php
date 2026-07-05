<?php

namespace App\Ai\Tools;

use App\Enums\WorkspaceRole;
use App\Models\WorkspaceMembership;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * The clubs the current user is an approved member of, with their roles.
 */
class GetMyWorkspaces extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List the clubs the current user is a member of, along with their role in each. '
            .'Use this for "which clubs am I in?".';
    }

    public function handle(Request $request): Stringable|string
    {
        $workspaces = $this->user->workspaceMemberships()
            ->where('status', 'approved')
            ->with(['workspace:id,name', 'roles'])
            ->get()
            ->filter(fn (WorkspaceMembership $membership) => $membership->workspace !== null)
            ->map(fn (WorkspaceMembership $membership): array => [
                'workspace' => $membership->workspace->name,
                'workspaceId' => $membership->workspace_id,
                'roles' => $membership->workspaceRoles()->map(fn (WorkspaceRole $role): string => $role->value)->all(),
                'joinedAt' => $membership->joined_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return $this->json(['workspaces' => $workspaces]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
