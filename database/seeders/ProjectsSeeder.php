<?php

namespace Database\Seeders;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Models\Workspace;
use Database\Factories\ProjectUpdateFactory;
use Illuminate\Database\Seeder;

class ProjectsSeeder extends Seeder
{
    /**
     * @var list<array{name: string, description: string, lead?: string, member?: string}>
     */
    private array $projects = [
        [
            'name' => 'المبادرة العلمية',
            'description' => 'مشروع المحتوى العلمي والتقني للفريق.',
            'lead' => 'project-lead@teamhub.test',
            'member' => 'student@teamhub.test',
        ],
        [
            'name' => 'الإعلام والتواصل',
            'description' => 'مشروع الهوية البصرية والمحتوى الرقمي.',
            'lead' => 'workspace-lead@teamhub.test',
            'member' => 'member@teamhub.test',
        ],
        [
            'name' => 'إدارة المشاريع',
            'description' => 'مشروع تنسيق المهام والتسليمات بين الفرق.',
            'lead' => 'project-lead@teamhub.test',
            'member' => 'staff@teamhub.test',
        ],
    ];

    public function run(): void
    {
        $workspace = Workspace::query()->where('name', 'مساحة الحاسبات')->first();

        if ($workspace === null) {
            return;
        }

        foreach ($this->projects as $data) {
            $project = Project::query()->updateOrCreate(
                ['workspace_id' => $workspace->id, 'name' => $data['name']],
                ['description' => $data['description'], 'status' => 'active'],
            );

            $lead = isset($data['lead'])
                ? User::query()->where('email', $data['lead'])->first()
                : null;
            if ($lead !== null) {
                $this->seedMembership($lead, $project, [ProjectRole::ProjectLead]);
            }

            $member = isset($data['member'])
                ? User::query()->where('email', $data['member'])->first()
                : null;
            if ($member !== null) {
                $this->seedMembership($member, $project, [ProjectRole::Member]);
            }

            $this->seedContent($project, $workspace->id);
        }
    }

    /**
     * @param  array<int, ProjectRole>  $roles
     */
    private function seedMembership(User $user, Project $project, array $roles): void
    {
        $membership = ProjectMembership::query()->firstOrCreate(
            ['user_id' => $user->id, 'project_id' => $project->id],
            [
                'status' => 'approved',
                'requested_at' => now()->subMonths(6),
                'reviewed_by' => $user->id,
                'reviewed_at' => now()->subMonths(6),
                'joined_at' => now()->subMonths(6),
            ],
        );

        $membership->syncProjectRoles(array_merge([ProjectRole::Member], $roles));
    }

    private function seedContent(Project $project, int $workspaceId): void
    {
        if ($project->updates()->exists()) {
            return;
        }

        ProjectUpdateFactory::new()->count(2)->create([
            'workspace_id' => $workspaceId,
            'project_id' => $project->id,
            'published_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
