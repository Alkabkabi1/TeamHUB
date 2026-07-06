<?php

namespace App\Ai\Tools;

use App\Enums\WorkspaceCapability;
use App\Models\WorkspaceMembershipRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * The pending join applications awaiting review for a club the user manages.
 * Restricted to managers with the manage-members capability.
 */
class GetWorkspacePendingApplications extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List the pending join applications awaiting review for a workspace you manage (applicant name, '
            .'email, major, submission date). Only available to workspace leads with the manage-members capability.';
    }

    public function handle(Request $request): Stringable|string
    {
        $workspace = $this->resolveWorkspace($request['workspace'] ?? $request['workspace'] ?? null);

        if ($workspace === null) {
            return $this->json(['error' => 'No workspace matched that name.']);
        }

        if (! $this->user->can(WorkspaceCapability::ManageMembers->value, $workspace)) {
            return $this->json(['error' => 'You are not permitted to review this workspace\'s applications.']);
        }

        $applications = WorkspaceMembershipRequest::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', 'pending')
            ->with('user:id,name,email')
            ->orderBy('created_at')
            ->get()
            ->map(fn (WorkspaceMembershipRequest $application): array => [
                'id' => $application->id,
                'applicant' => $application->user?->name ?? $application->full_name,
                'email' => $application->user?->email ?? $application->university_email,
                'major' => $application->major,
                'level' => $application->level,
                'submittedAt' => $application->created_at?->toIso8601String(),
            ])
            ->all();

        return $this->json([
            'workspace' => $workspace->name,
            'pendingCount' => count($applications),
            'applications' => $applications,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'workspace' => $schema->string()
                ->description('The workspace name (or numeric id) whose pending applications to list.')
                ->required(),
        ];
    }
}
