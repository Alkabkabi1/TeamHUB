<?php

namespace App\Ai\Tools;

use App\Enums\ClubCapability;
use App\Services\ClubSupervisorReportService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Read-only reporting for a club: high-level stats, member roster, volunteer
 * hours, or attendance. Restricted to managers with the view-reports capability.
 */
class GetClubReport extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Get a report for a club you manage: "stats" (totals), "members", "volunteer-hours", or '
            .'"attendance". Only available to club managers with the view-reports capability.';
    }

    public function handle(Request $request): Stringable|string
    {
        $club = $this->resolveClub($request['club'] ?? null);

        if ($club === null) {
            return $this->json(['error' => 'No club matched that name.']);
        }

        if (! $this->user->can(ClubCapability::ViewReports->value, $club)) {
            return $this->json(['error' => 'You are not permitted to view reports for this club.']);
        }

        $type = (string) ($request['type'] ?? 'stats');
        $locale = app()->getLocale();
        $service = app(ClubSupervisorReportService::class);

        $report = match ($type) {
            'members' => $service->membersReport($club, $locale, $this->user),
            'volunteer-hours' => $service->volunteerHoursReport($club, $locale, $this->user),
            'attendance' => $service->attendanceReport($club, $locale, $this->user),
            default => $service->clubStats(
                $club,
                $service->clubMembersForManagement($club, $locale)->count(),
            ),
        };

        return $this->json(['club' => $club->name, 'type' => $type, 'report' => $report]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'club' => $schema->string()
                ->description('The club name (or numeric id) to report on.')
                ->required(),
            'type' => $schema->string()
                ->enum(['stats', 'members', 'volunteer-hours', 'attendance'])
                ->description('Which report to return (default "stats").'),
        ];
    }
}
