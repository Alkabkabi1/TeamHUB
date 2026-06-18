<?php

namespace App\Ai\Tools;

use App\Enums\ClubCapability;
use App\Models\ClubJoinApplication;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * The pending join applications awaiting review for a club the user manages.
 * Restricted to managers with the manage-members capability.
 */
class GetClubPendingApplications extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List the pending join applications awaiting review for a club you manage (applicant name, '
            .'email, major, submission date). Only available to club managers with the manage-members capability.';
    }

    public function handle(Request $request): Stringable|string
    {
        $club = $this->resolveClub($request['club'] ?? null);

        if ($club === null) {
            return $this->json(['error' => 'No club matched that name.']);
        }

        if (! $this->user->can(ClubCapability::ManageMembers->value, $club)) {
            return $this->json(['error' => 'You are not permitted to review this club\'s applications.']);
        }

        $applications = ClubJoinApplication::query()
            ->where('club_id', $club->id)
            ->where('status', 'pending')
            ->with('user:id,name,email')
            ->orderBy('created_at')
            ->get()
            ->map(fn (ClubJoinApplication $application): array => [
                'id' => $application->id,
                'applicant' => $application->user?->name ?? $application->full_name,
                'email' => $application->user?->email ?? $application->university_email,
                'major' => $application->major,
                'level' => $application->level,
                'submittedAt' => $application->created_at?->toIso8601String(),
            ])
            ->all();

        return $this->json([
            'club' => $club->name,
            'pendingCount' => count($applications),
            'applications' => $applications,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'club' => $schema->string()
                ->description('The club name (or numeric id) whose pending applications to list.')
                ->required(),
        ];
    }
}
