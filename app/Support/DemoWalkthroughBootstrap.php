<?php

namespace App\Support;

use App\Enums\CommitteeRole;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Task;
use App\Models\User;

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
            'project_leader' => self::ensureProjectLeader($user),
            'staff' => self::ensureStaff($user),
            default => null,
        };
    }

    private static function ensureAdmin(User $user): void
    {
        if ($user->role !== UserRole::UniversityStaff) {
            $user->forceFill(['role' => UserRole::UniversityStaff])->save();
        }

        DemoWorkspace::defaultClub();
    }

    private static function ensureProjectLeader(User $user): void
    {
        $committee = self::demoCommittee();

        self::ensureClubMember($user, $committee->club_id);
        self::ensureCommitteeRole($user, $committee, [CommitteeRole::CommitteeLead]);
        self::ensureSampleTasks($committee, $user);
    }

    private static function ensureStaff(User $user): void
    {
        $committee = self::demoCommittee();

        self::ensureClubMember($user, $committee->club_id);
        self::ensureCommitteeRole($user, $committee, [CommitteeRole::Member]);
        self::ensureSampleTasks($committee, User::query()->where('email', 'project-leader@teamhub.test')->first() ?? $user);
    }

    private static function demoCommittee(): Committee
    {
        $club = Club::query()->where('name', self::DEMO_CLUB)->first()
            ?? DemoWorkspace::defaultClub();

        return Committee::query()->firstOrCreate(
            ['club_id' => $club->id, 'name' => self::DEMO_COMMITTEE],
            [
                'description' => 'مشروع تجريبي لعرض سير العمل في TeamHUB.',
                'status' => 'active',
            ],
        );
    }

    private static function ensureClubMember(User $user, int $clubId): void
    {
        $membership = ClubMembership::query()->firstOrCreate(
            ['user_id' => $user->id, 'club_id' => $clubId],
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
     * @param  array<int, CommitteeRole>  $roles
     */
    private static function ensureCommitteeRole(User $user, Committee $committee, array $roles): void
    {
        $membership = CommitteeMembership::query()->firstOrCreate(
            ['user_id' => $user->id, 'committee_id' => $committee->id],
            [
                'status' => 'approved',
                'requested_at' => now()->subMonths(3),
                'reviewed_by' => $user->id,
                'reviewed_at' => now()->subMonths(3),
                'joined_at' => now()->subMonths(3),
            ],
        );

        $membership->assignCommitteeRole(CommitteeRole::Member);

        foreach ($roles as $role) {
            $membership->assignCommitteeRole($role);
        }
    }

    private static function ensureSampleTasks(Committee $committee, User $creator): void
    {
        $staff = User::query()->where('email', 'staff@teamhub.test')->first();

        if ($staff === null) {
            return;
        }

        if ($committee->tasks()->exists()) {
            return;
        }

        Task::query()->create([
            'committee_id' => $committee->id,
            'created_by' => $creator->id,
            'assigned_to' => $staff->id,
            'title' => 'إعداد خطة المشروع الأولى',
            'description' => 'صياغة الأهداف والجدول الزمني للمرحلة الأولى.',
            'status' => TaskStatus::InProgress,
            'priority' => 'high',
            'due_at' => now()->addDays(3),
        ]);

        Task::query()->create([
            'committee_id' => $committee->id,
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
            'committee_id' => $committee->id,
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
