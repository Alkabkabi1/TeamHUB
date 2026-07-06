<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceCapability;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembershipRequest;
use App\Services\WorkspaceMemberReportService;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceManagementController extends Controller
{
    public function __construct(
        private readonly WorkspaceMemberReportService $reports,
    ) {}

    public function index(Workspace $workspace): Response
    {
        /** @var User $user */
        $user = auth()->user();

        abort_unless($user->canManageWorkspace($workspace), 403);

        return Inertia::render('workspaces/Manage', $this->managementPayload($workspace, $user));
    }

    public function members(Workspace $workspace): Response
    {
        /** @var User $user */
        $user = auth()->user();

        abort_unless($user->canManageWorkspace($workspace), 403);

        $payload = $this->managementPayload($workspace, $user);

        return Inertia::render('workspaces/Members', [
            'theme' => $payload['theme'],
            'workspace' => $payload['workspace'],
            'capabilities' => $payload['capabilities'],
            'canManageRoles' => $payload['canManageRoles'],
            'roleOptions' => $payload['roleOptions'],
            'members' => $payload['members'],
            'pendingApplications' => $payload['pendingApplications'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function managementPayload(Workspace $workspace, User $user): array
    {
        $capabilities = $user->isAdmin()
            ? WorkspaceCapability::values()
            : $user->workspaceCapabilitiesFor($workspace)->map(fn (WorkspaceCapability $capability): string => $capability->value)->values()->all();

        $members = $this->reports->workspaceMembersForManagement($workspace);
        $projectIds = $workspace->projects()->pluck('id');

        $workspaceTaskQuery = Task::query()->whereIn('project_id', $projectIds);
        $workspaceProjects = $workspace->projects()
            ->withCount([
                'memberships as members_count',
                'tasks as tasks_count',
                'tasks as overdue_tasks_count' => fn ($query) => $query
                    ->whereNotNull('due_at')
                    ->where('due_at', '<', now())
                    ->whereNotIn('status', ['done']),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status->value,
                'logo_url' => $project->logo_url,
                'members_count' => $project->members_count,
                'tasks_count' => $project->tasks_count,
                'overdue_tasks_count' => $project->overdue_tasks_count,
            ])
            ->values();

        $recentActivity = collect([
            ...Task::query()
                ->whereIn('project_id', $projectIds)
                ->with('project:id,name,workspace_id')
                ->latest('updated_at')
                ->limit(6)
                ->get()
                ->map(fn (Task $task): array => [
                    'id' => "task-{$task->id}",
                    'type' => 'task',
                    'title' => $task->title,
                    'context' => $task->project?->name ?? '',
                    'time' => $task->updated_at?->diffForHumans(),
                    'sort_at' => $task->updated_at?->timestamp ?? 0,
                    'url' => route('projects.tasks.show', [$workspace, $task->project_id, $task]),
                ]),
            ...ProjectUpdate::query()
                ->whereIn('project_id', $projectIds)
                ->with('project:id,name,workspace_id')
                ->latest('published_at')
                ->limit(4)
                ->get()
                ->map(fn (ProjectUpdate $post): array => [
                    'id' => "post-{$post->id}",
                    'type' => 'update',
                    'title' => $post->title,
                    'context' => $post->project?->name ?? $workspace->name,
                    'time' => $post->published_at?->diffForHumans(),
                    'sort_at' => $post->published_at?->timestamp ?? 0,
                    'url' => $post->project_id
                        ? route('projects.updates.index', [$workspace, $post->project_id])
                        : route('workspaces.manage', $workspace),
                ]),
        ])->sortByDesc('sort_at')->take(8)->map(function (array $item): array {
            unset($item['sort_at']);

            return $item;
        })->values();

        return [
            'theme' => ['brand' => $workspace->theme ?: config('theme.brand')],
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'theme' => $workspace->theme,
                'logo_url' => $workspace->logo_url,
            ],
            'capabilities' => $capabilities,
            'canManageRoles' => $user->can(WorkspaceCapability::ManageWorkspace->value, $workspace),
            'roleOptions' => collect(WorkspaceRole::cases())
                ->map(fn (WorkspaceRole $role): array => [
                    'value' => $role->value,
                    'label' => __($role->label()),
                    'isManager' => $role->isManager(),
                ])
                ->values(),
            'stats' => [
                'membersCount' => $members->count(),
                'pendingApplicationsCount' => WorkspaceMembershipRequest::query()
                    ->where('workspace_id', $workspace->id)
                    ->where('status', 'pending')
                    ->count(),
                'projectsCount' => $workspaceProjects->count(),
                'openTasksCount' => (clone $workspaceTaskQuery)->whereNotIn('status', ['done'])->count(),
            ],
            'workspaceStats' => [
                'projects_count' => $workspaceProjects->count(),
                'tasks_count' => (clone $workspaceTaskQuery)->count(),
                'overdue_tasks_count' => (clone $workspaceTaskQuery)
                    ->whereNotNull('due_at')
                    ->where('due_at', '<', now())
                    ->whereNotIn('status', ['done'])
                    ->count(),
            ],
            'workspaceProjects' => $workspaceProjects,
            'recentActivity' => $recentActivity,
            'members' => $members,
            'pendingApplications' => WorkspaceMembershipRequest::query()
                ->where('workspace_id', $workspace->id)
                ->where('status', 'pending')
                ->latest()
                ->get()
                ->map(fn (WorkspaceMembershipRequest $application) => [
                    'id' => $application->id,
                    'name' => $application->full_name,
                    'details' => $application->skills ?? '',
                    'time' => $application->created_at?->diffForHumans(),
                ])
                ->values(),
        ];
    }
}
