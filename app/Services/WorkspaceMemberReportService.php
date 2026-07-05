<?php

namespace App\Services;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceMembershipRequest;
use Illuminate\Support\Collection;

class WorkspaceMemberReportService
{
    /**
     * @return array{
     *     workspaceName: string,
     *     generatedAt: string,
     *     locale: string,
     *     supervisorName: string|null
     * }
     */
    public function reportMeta(Workspace $workspace, string $locale, ?User $supervisor = null): array
    {
        return [
            'workspaceName' => $workspace->name,
            'generatedAt' => now()->locale($locale)->translatedFormat('d F Y H:i'),
            'locale' => $locale,
            'supervisorName' => $supervisor?->name,
        ];
    }

    public function supervisedWorkspace(User $user): ?Workspace
    {
        return $user->managedWorkspace();
    }

    /**
     * @return Collection<int, array{name: string, email: string, major: string, joinDate: string, volunteerHours: float, status: string}>
     */
    public function membersForWorkspace(Workspace $workspace, ?string $locale = null): Collection
    {
        $locale = $locale ?? app()->getLocale();

        $memberships = WorkspaceMembership::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', 'approved')
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('role', WorkspaceRole::managerRoleValues()))
            ->with('user:id,name,email')
            ->orderBy('joined_at')
            ->get();

        $memberUserIds = $memberships->pluck('user_id')->filter()->all();
        $applicationsByUserId = $this->latestApprovedApplicationsByUserId($workspace->id, $memberUserIds);

        return $memberships
            ->filter(fn (WorkspaceMembership $membership) => $membership->user !== null)
            ->map(function (WorkspaceMembership $membership) use ($applicationsByUserId, $locale) {
                $application = $applicationsByUserId->get($membership->user_id);

                return [
                    'name' => $membership->user->name,
                    'email' => $membership->user->email,
                    'major' => $this->applicationSkillsLabel($application),
                    'joinDate' => $membership->joined_at?->locale($locale)->translatedFormat('F Y') ?? '',
                    'volunteerHours' => 0.0,
                    'status' => __('dashboard.status_active'),
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array{
     *     membershipId: int,
     *     userId: int,
     *     name: string,
     *     email: string,
     *     major: string,
     *     joinDate: string,
     *     volunteerHours: float,
     *     roles: array<int, string>,
     *     isManager: bool,
     *     status: string
     * }>
     */
    public function clubMembersForManagement(Workspace $workspace, ?string $locale = null): Collection
    {
        $locale = $locale ?? app()->getLocale();

        $memberships = WorkspaceMembership::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', 'approved')
            ->with(['user:id,name,email', 'roles'])
            ->orderBy('joined_at')
            ->get();

        $memberUserIds = $memberships->pluck('user_id')->filter()->all();
        $applicationsByUserId = $this->latestApprovedApplicationsByUserId($workspace->id, $memberUserIds);

        return $memberships
            ->filter(fn (WorkspaceMembership $membership) => $membership->user !== null)
            ->map(function (WorkspaceMembership $membership) use ($applicationsByUserId, $locale) {
                $roles = $membership->workspaceRoles();

                return [
                    'membershipId' => $membership->id,
                    'userId' => $membership->user_id,
                    'name' => $membership->user->name,
                    'email' => $membership->user->email,
                    'major' => $this->applicationSkillsLabel($applicationsByUserId->get($membership->user_id)),
                    'joinDate' => $membership->joined_at?->locale($locale)->translatedFormat('F Y') ?? '',
                    'volunteerHours' => 0.0,
                    'roles' => $roles->map(fn (WorkspaceRole $role): string => $role->value)->values()->all(),
                    'isManager' => $roles->contains(fn (WorkspaceRole $role): bool => $role->isManager()),
                    'status' => __('dashboard.status_active'),
                ];
            })
            ->values();
    }

    /**
     * @return array{
     *     totalHours: float,
     *     pendingApplicationsCount: int,
     *     upcomingEventsCount: int,
     *     membersCount: int
     * }
     */
    public function clubStats(Workspace $workspace, int $membersCount): array
    {
        return [
            'totalHours' => 0.0,
            'pendingApplicationsCount' => WorkspaceMembershipRequest::query()
                ->where('workspace_id', $workspace->id)
                ->where('status', 'pending')
                ->count(),
            'upcomingEventsCount' => 0,
            'membersCount' => $membersCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function membersReport(Workspace $workspace, string $locale, ?User $supervisor = null): array
    {
        $members = $this->membersForWorkspace($workspace, $locale);

        return array_merge($this->reportMeta($workspace, $locale, $supervisor), [
            'members' => $members,
            'totalHours' => $members->sum('volunteerHours'),
        ]);
    }

    /**
     * @param  array<int, int>  $userIds
     * @return Collection<int, WorkspaceMembershipRequest>
     */
    private function latestApprovedApplicationsByUserId(int $workspaceId, array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        return WorkspaceMembershipRequest::query()
            ->where('workspace_id', $workspaceId)
            ->whereIn('user_id', $userIds)
            ->where('status', 'approved')
            ->orderByDesc('reviewed_at')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');
    }

    private function applicationSkillsLabel(?WorkspaceMembershipRequest $application): string
    {
        if ($application === null) {
            return '';
        }

        return (string) ($application->skills ?? '');
    }
}
