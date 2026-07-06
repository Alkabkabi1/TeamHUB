<?php

namespace App\Services;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use Illuminate\Support\Collection;

/**
 * Project-scoped reporting for member management dashboards and PDF exports.
 */
class ProjectMemberReportService
{
    /**
     * @return array{projectName: string, workspaceName: string, generatedAt: string, locale: string, supervisorName: string|null}
     */
    public function reportMeta(Project $project, string $locale, ?string $supervisorName = null): array
    {
        $workspaceName = $project->workspace?->name;

        return [
            'projectName' => $project->name,
            'workspaceName' => $workspaceName !== null ? "{$workspaceName} – {$project->name}" : $project->name,
            'generatedAt' => now()->locale($locale)->translatedFormat('d F Y H:i'),
            'locale' => $locale,
            'supervisorName' => $supervisorName,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function projectMembersForManagement(Project $project, ?string $locale = null): Collection
    {
        $locale = $locale ?? app()->getLocale();

        $memberships = ProjectMembership::query()
            ->where('project_id', $project->id)
            ->where('status', 'approved')
            ->with(['user:id,name,email', 'roles'])
            ->orderBy('joined_at')
            ->get();

        return $memberships
            ->filter(fn (ProjectMembership $membership) => $membership->user !== null)
            ->map(function (ProjectMembership $membership) use ($locale) {
                $roles = $membership->projectRoles();

                return [
                    'membershipId' => $membership->id,
                    'userId' => $membership->user_id,
                    'name' => $membership->user->name,
                    'email' => $membership->user->email,
                    'major' => '',
                    'joinDate' => $membership->joined_at?->locale($locale)->translatedFormat('F Y') ?? '',
                    'volunteerHours' => 0.0,
                    'roles' => $roles->map(fn (ProjectRole $role): string => $role->value)->values()->all(),
                    'isManager' => $roles->contains(fn (ProjectRole $role): bool => $role->isManager()),
                    'status' => __('dashboard.status_active'),
                ];
            })
            ->values();
    }

    /**
     * @return array{totalHours: float, pendingApplicationsCount: int, upcomingEventsCount: int, membersCount: int}
     */
    public function projectStats(Project $project, int $membersCount): array
    {
        return [
            'totalHours' => 0.0,
            'pendingApplicationsCount' => ProjectMembership::query()
                ->where('project_id', $project->id)
                ->where('status', 'pending')
                ->count(),
            'upcomingEventsCount' => 0,
            'membersCount' => $membersCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function membersReport(Project $project, string $locale, ?string $supervisorName = null): array
    {
        $members = $this->projectMembersForManagement($project, $locale);

        return array_merge($this->reportMeta($project, $locale, $supervisorName), [
            'members' => $members,
            'totalHours' => $members->sum('volunteerHours'),
        ]);
    }
}
