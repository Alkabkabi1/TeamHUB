<?php

namespace App\Ai\Tools;

use App\Models\ClubJoinApplication;
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
        return 'List the current user\'s club join applications with their status (pending, approved, or '
            .'rejected). Use this for "was my join request accepted?" or "which clubs did I apply to?".';
    }

    public function handle(Request $request): Stringable|string
    {
        $applications = $this->user->joinApplications()
            ->with('club:id,name')
            ->latest()
            ->get()
            ->map(fn (ClubJoinApplication $application): array => [
                'club' => $application->club?->name,
                'status' => $application->status,
                'major' => $application->major,
                'level' => $application->level,
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
