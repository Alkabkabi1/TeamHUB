<?php

namespace App\Support;

use App\Enums\ProjectRole;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

class DemoWalkthroughBootstrap
{
    private const DEMO_CLUB = 'نادي الحاسبات';

    private const DEMO_COMMITTEE = 'لجنة إدارة المشاريع';

    public static function ensure(User $user): void
    {
        $account = DemoRoles::find($user->email);

        if ($account === null) {
            return;
        }

        match ($account['role']) {
            'admin' => self::ensureAdmin($user),
            'workspace_lead' => self::ensureWorkspaceLead($user),
            'project_leader' => self::ensureProjectLeader($user),
            'staff' => self::ensureStaff($user),
            default => null,
        };
    }

    private static function ensureAdmin(User $user): void
    {
        if ($user->role !== UserRole::Admin) {
            $user->forceFill(['role' => UserRole::Admin])->save();
        }

        DemoWorkspace::defaultWorkspace();
    }

    private static function ensureWorkspaceLead(User $user): void
    {
        $workspace = Workspace::query()->where('name', 'مساحة الحاسبات')->first()
            ?? self::demoProject()->workspace;

        $membership = WorkspaceMembership::query()->firstOrCreate(
            ['user_id' => $user->id, 'workspace_id' => $workspace->id],
            [
                'status' => 'approved',
                'requested_at' => now()->subMonths(6),
                'reviewed_at' => now()->subMonths(6),
                'joined_at' => now()->subMonths(6),
            ],
        );

        if ($membership->status !== 'approved') {
            $membership->forceFill([
                'status' => 'approved',
                'joined_at' => now()->subMonths(6),
            ])->save();
        }

        $membership->syncWorkspaceRoles([WorkspaceRole::WorkspaceLead, WorkspaceRole::Member]);
    }

    private static function ensureProjectLeader(User $user): void
    {
        $project = self::demoProject();

        self::ensureWorkspaceMember($user, $project->workspace_id);
        self::ensureProjectRole($user, $project, [ProjectRole::ProjectLead]);
        self::ensureSampleTasks($project, $user);
    }

    private static function ensureStaff(User $user): void
    {
        $project = self::demoProject();

        self::ensureWorkspaceMember($user, $project->workspace_id);
        self::ensureProjectRole($user, $project, [ProjectRole::Member]);
        self::ensureSampleTasks($project, User::query()->where('email', 'project-lead@teamhub.test')->first() ?? $user);
    }

    private static function demoProject(): Project
    {
        $workspace = Workspace::query()->where('name', self::DEMO_CLUB)->first()
            ?? DemoWorkspace::defaultWorkspace();

        return Project::query()->firstOrCreate(
            ['workspace_id' => $workspace->id, 'name' => self::DEMO_COMMITTEE],
            [
                'description' => 'مشروع تجريبي لعرض سير العمل في TeamHUB.',
                'status' => 'active',
            ],
        );
    }

    private static function ensureWorkspaceMember(User $user, int $workspaceId): void
    {
        $membership = WorkspaceMembership::query()->firstOrCreate(
            ['user_id' => $user->id, 'workspace_id' => $workspaceId],
            [
                'status' => 'approved',
                'requested_at' => now()->subMonths(3),
                'reviewed_at' => now()->subMonths(3),
                'joined_at' => now()->subMonths(3),
            ],
        );

        if ($membership->status !== 'approved') {
            $membership->forceFill([
                'status' => 'approved',
                'joined_at' => now()->subMonths(3),
            ])->save();
        }
    }

    /**
     * @param  array<int, ProjectRole>  $roles
     */
    private static function ensureProjectRole(User $user, Project $project, array $roles): void
    {
        $membership = ProjectMembership::query()->firstOrCreate(
            ['user_id' => $user->id, 'project_id' => $project->id],
            [
                'status' => 'approved',
                'requested_at' => now()->subMonths(3),
                'reviewed_by' => $user->id,
                'reviewed_at' => now()->subMonths(3),
                'joined_at' => now()->subMonths(3),
            ],
        );

        $membership->assignProjectRole(ProjectRole::Member);

        foreach ($roles as $role) {
            $membership->assignProjectRole($role);
        }
    }

    public static function ensureDemoStaffOnProject(Project $project, User $reviewedBy): void
    {
        if (! config('demo.quick_login')) {
            return;
        }

        $staff = User::query()->where('email', 'staff@teamhub.test')->first();

        if ($staff === null) {
            return;
        }

        self::ensureWorkspaceMember($staff, $project->workspace_id);
        self::ensureProjectRole($staff, $project, [ProjectRole::Member]);
    }

    private static function ensureSampleTasks(Project $project, User $creator): void
    {
        $staff = User::query()->where('email', 'staff@teamhub.test')->first();

        if ($staff === null) {
            return;
        }

        if ($project->tasks()->exists()) {
            return;
        }

        Task::query()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'assigned_to' => $staff->id,
            'title' => 'إعداد خطة المشروع الأولى',
            'description' => 'صياغة الأهداف والجدول الزمني للمرحلة الأولى.',
            'status' => TaskStatus::InProgress,
            'priority' => 'high',
            'due_at' => now()->addDays(3),
        ]);

        Task::query()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'assigned_to' => $staff->id,
            'title' => 'تصميم واجهة العرض',
            'description' => 'إعداد نموذج أولي للواجهة الرئيسية.',
            'status' => TaskStatus::Review,
            'priority' => 'medium',
            'due_at' => now()->addDay(),
            'deliverable_url' => 'https://figma.com/demo-wireframe',
            'deliverable_notes' => 'النسخة الأولى جاهزة للمراجعة.',
            'submitted_for_review_at' => now()->subHours(4),
        ]);

        Task::query()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'assigned_to' => null,
            'title' => 'توثيق متطلبات المشروع',
            'description' => 'مهمة جديدة بانتظار التخصيص.',
            'status' => TaskStatus::Todo,
            'priority' => 'low',
            'due_at' => now()->addWeek(),
        ]);
    }
}
