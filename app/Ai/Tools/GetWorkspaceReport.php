<?php

namespace App\Ai\Tools;

use App\Enums\WorkspaceCapability;
use App\Services\WorkspaceMemberReportService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetWorkspaceReport extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Get a report for a club you manage: "stats" (totals) or "members". '
            .'Only available to club managers with the view-reports capability.';
    }

    public function handle(Request $request): Stringable|string
    {
        $workspace = $this->resolveWorkspace($request['workspace'] ?? $request['workspace'] ?? null);

        if ($workspace === null) {
            return $this->json(['error' => 'No workspace matched that name.']);
        }

        if (! $this->user->can(WorkspaceCapability::ViewReports->value, $workspace)) {
            return $this->json(['error' => 'You are not permitted to view reports for this workspace.']);
        }

        $type = (string) ($request['type'] ?? 'stats');
        $locale = app()->getLocale();
        $service = app(WorkspaceMemberReportService::class);

        $report = match ($type) {
            'members' => $service->membersReport($workspace, $locale, $this->user),
            default => $service->clubStats(
                $workspace,
                $service->clubMembersForManagement($workspace, $locale)->count(),
            ),
        };

        return $this->json(['workspace' => $workspace->name, 'type' => $type, 'report' => $report]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'workspace' => $schema->string()
                ->description('The workspace name (or numeric id) to report on.')
                ->required(),
            'type' => $schema->string()
                ->enum(['stats', 'members'])
                ->description('Which report to return (default "stats").'),
        ];
    }
}
