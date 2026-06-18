<?php

namespace App\Ai\Tools;

use App\Enums\CommitteeCapability;
use App\Services\CommitteeReportService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Read-only reporting for a committee: high-level stats, member roster,
 * volunteer hours, or attendance. Restricted to managers with the
 * view-committee-reports capability.
 */
class GetCommitteeReport extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Get a report for a committee you manage: "stats", "members", "volunteer-hours", or '
            .'"attendance". Only available to committee managers with the view-committee-reports capability.';
    }

    public function handle(Request $request): Stringable|string
    {
        $club = $this->resolveClub($request['club'] ?? null);
        $committee = $this->resolveCommittee($request['committee'] ?? null, $club);

        if ($committee === null) {
            return $this->json(['error' => 'No committee matched that name.']);
        }

        if (! $this->user->can(CommitteeCapability::ViewReports->value, $committee)) {
            return $this->json(['error' => 'You are not permitted to view reports for this committee.']);
        }

        $type = (string) ($request['type'] ?? 'stats');
        $locale = app()->getLocale();
        $service = app(CommitteeReportService::class);

        $report = match ($type) {
            'members' => $service->membersReport($committee, $locale, $this->user->name),
            'volunteer-hours' => $service->volunteerHoursReport($committee, $locale, $this->user->name),
            'attendance' => $service->attendanceReport($committee, $locale, $this->user->name),
            default => $service->committeeStats(
                $committee,
                $service->committeeMembersForManagement($committee, $locale)->count(),
            ),
        };

        return $this->json(['committee' => $committee->name, 'type' => $type, 'report' => $report]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'committee' => $schema->string()
                ->description('The committee name (or numeric id) to report on.')
                ->required(),
            'club' => $schema->string()
                ->description('Optional parent club name to disambiguate the committee.'),
            'type' => $schema->string()
                ->enum(['stats', 'members', 'volunteer-hours', 'attendance'])
                ->description('Which report to return (default "stats").'),
        ];
    }
}
