<?php

namespace App\Ai\Tools;

use App\Enums\ProjectCapability;
use App\Services\ProjectMemberReportService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetProjectReport extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Get a report for a committee you manage: "stats" or "members". '
            .'Only available to committee managers with the view-committee-reports capability.';
    }

    public function handle(Request $request): Stringable|string
    {
        $workspace = $this->resolveWorkspace($request['workspace'] ?? null);
        $project = $this->resolveProject($request['project'] ?? null, $workspace);

        if ($project === null) {
            return $this->json(['error' => 'No committee matched that name.']);
        }

        if (! $this->user->can(ProjectCapability::ViewReports->value, $project)) {
            return $this->json(['error' => 'You are not permitted to view reports for this committee.']);
        }

        $type = (string) ($request['type'] ?? 'stats');
        $locale = app()->getLocale();
        $service = app(ProjectMemberReportService::class);

        $report = match ($type) {
            'members' => $service->membersReport($project, $locale, $this->user->name),
            default => $service->committeeStats(
                $project,
                $service->committeeMembersForManagement($project, $locale)->count(),
            ),
        };

        return $this->json(['project' => $project->name, 'type' => $type, 'report' => $report]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()
                ->description('The project name (or numeric id) to report on.')
                ->required(),
            'workspace' => $schema->string()
                ->description('Optional parent club name to disambiguate the committee.'),
            'type' => $schema->string()
                ->enum(['stats', 'members'])
                ->description('Which report to return (default "stats").'),
        ];
    }
}
