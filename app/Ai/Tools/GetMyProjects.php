<?php

namespace App\Ai\Tools;

use App\Enums\ProjectRole;
use App\Models\ProjectMembership;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * The committees the current user is an approved member of, with their roles.
 */
class GetMyProjects extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List the committees (sub-groups within clubs) the current user belongs to, with their role in each.';
    }

    public function handle(Request $request): Stringable|string
    {
        $projects = $this->user->projectMemberships()
            ->where('status', 'approved')
            ->with(['project.workspace:id,name', 'roles'])
            ->get()
            ->filter(fn (ProjectMembership $membership) => $membership->project !== null)
            ->map(fn (ProjectMembership $membership): array => [
                'project' => $membership->project->name,
                'projectId' => $membership->project_id,
                'workspace' => $membership->project->workspace?->name,
                'roles' => $membership->projectRoles()->map(fn (ProjectRole $role): string => $role->value)->all(),
                'joinedAt' => $membership->joined_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return $this->json(['projects' => $projects]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
