<?php

namespace App\Ai\Tools;

use App\Models\WorkspaceMembershipRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * The current user's club join applications and their review status
 * (pending / approved / rejected).
 */
class GetMyApplications extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List the current user\'s workspace membership applications with their status (pending, approved, or '
            .'rejected). Use this for "was my join request accepted?" or "which workspaces did I apply to?".';
    }

    public function handle(Request $request): Stringable|string
    {
        $applications = $this->user->membershipRequests()
            ->with('workspace:id,name')
            ->latest()
            ->get()
            ->map(fn (WorkspaceMembershipRequest $application): array => [
                'workspace' => $application->workspace?->name,
                'status' => $application->status,
                'submittedAt' => $application->created_at?->toIso8601String(),
                'reviewedAt' => $application->reviewed_at?->toIso8601String(),
            ])
            ->all();

        return $this->json(['applications' => $applications]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
