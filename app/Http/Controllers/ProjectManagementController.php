<?php

namespace App\Http\Controllers;

use App\Enums\ProjectCapability;
use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectMembership;
use App\Models\ProjectUpdate;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ProjectMemberReportService;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProjectManagementController extends Controller
{
    public function __construct(
        private readonly ProjectMemberReportService $reports,
    ) {}

    public function index(Workspace $workspace, Project $project): Response
    {
        /** @var User $user */
        $user = auth()->user();

        abort_unless($user->canManageProject($project), 403);

        return Inertia::render('committees/Manage', $this->managementPayload($workspace, $project, $user));
    }

    public function files(Workspace $workspace, Project $project): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $this->authorizeProjectView($user, $project);

        return Inertia::render('committees/Files', [
            'theme' => ['brand' => $project->theme ?: ($workspace->theme ?: config('theme.brand'))],
            'club' => $workspace->only(['id', 'name', 'theme', 'logo_url']),
            'committee' => [
                ...$project->only(['id', 'name', 'theme', 'status']),
                'logo_url' => $project->logo_url,
            ],
            'canManageFiles' => $user->can(ProjectCapability::ManageProject->value, $project),
            'files' => ProjectFile::query()
                ->forProject($project)
                ->latest('published_at')
                ->get()
                ->map(fn (ProjectFile $resource): array => [
                    'id' => $resource->id,
                    'title' => $resource->title,
                    'description' => $resource->description,
                    'type' => $resource->type,
                    'format' => $resource->format,
                    'access' => $resource->access,
                    'published_at' => $resource->published_at?->toIso8601String(),
                    'download_url' => $resource->file_path ? Storage::disk('public')->url($resource->file_path) : null,
                ])
                ->values(),
        ]);
    }

    public function updates(Workspace $workspace, Project $project): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $this->authorizeProjectView($user, $project);

        return Inertia::render('committees/Updates', [
            'theme' => ['brand' => $project->theme ?: ($workspace->theme ?: config('theme.brand'))],
            'club' => $workspace->only(['id', 'name', 'theme', 'logo_url']),
            'committee' => [
                ...$project->only(['id', 'name', 'theme', 'status']),
                'logo_url' => $project->logo_url,
            ],
            'canManageUpdates' => $user->can(ProjectCapability::ManageUpdates->value, $project),
            'updates' => ProjectUpdate::query()
                ->where('project_id', $project->id)
                ->latest('published_at')
                ->get()
                ->map(fn (ProjectUpdate $post): array => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'excerpt' => mb_substr(strip_tags((string) $post->body), 0, 180),
                    'published_at' => $post->published_at?->toIso8601String(),
                ])
                ->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function managementPayload(Workspace $workspace, Project $project, User $user): array
    {
        $project->loadMissing('workspace:id,name');

        $capabilities = $user->projectCapabilitiesFor($project)
            ->map(fn (ProjectCapability $capability): string => $capability->value)
            ->values()
            ->all();

        $members = $this->reports->committeeMembersForManagement($project);

        $taskStats = [
            'todo' => Task::query()->where('project_id', $project->id)->where('status', 'todo')->count(),
            'in_progress' => Task::query()->where('project_id', $project->id)->where('status', 'in_progress')->count(),
            'review' => Task::query()->where('project_id', $project->id)->where('status', 'review')->count(),
            'done' => Task::query()->where('project_id', $project->id)->where('status', 'done')->count(),
            'overdue' => Task::query()
                ->where('project_id', $project->id)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->whereNotIn('status', ['done'])
                ->count(),
        ];

        return [
            'theme' => ['brand' => $project->theme ?: ($workspace->theme ?: config('theme.brand'))],
            'club' => $workspace->only(['id', 'name', 'theme', 'logo_url']),
            'committee' => [
                ...$project->only(['id', 'name', 'theme', 'status']),
                'logo_url' => $project->logo_url,
            ],
            'capabilities' => $capabilities,
            'canManageRoles' => $user->can(ProjectCapability::ManageProject->value, $project),
            'roleOptions' => collect(ProjectRole::cases())
                ->map(fn (ProjectRole $role): array => [
                    'value' => $role->value,
                    'label' => __($role->label()),
                    'isManager' => $role->isManager(),
                ])
                ->values(),
            'stats' => $this->reports->committeeStats($project, $members->count()),
            'taskStats' => $taskStats,
            'overviewMembers' => $members->take(8)->values(),
            'recentUpdates' => ProjectUpdate::query()
                ->where('project_id', $project->id)
                ->orderByDesc('published_at')
                ->limit(5)
                ->get()
                ->map(fn (ProjectUpdate $post): array => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'published_at' => $post->published_at?->toIso8601String(),
                ])
                ->values(),
            'recentActivities' => TaskActivity::query()
                ->whereHas('task', fn ($query) => $query->where('project_id', $project->id))
                ->with(['task:id,title,project_id', 'user:id,name'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (TaskActivity $activity): array => [
                    'id' => $activity->id,
                    'message' => $activity->message(),
                    'created_at' => $activity->created_at?->toIso8601String(),
                    'task_title' => $activity->task?->title ?? '',
                    'task_url' => route('projects.tasks.show', [$workspace, $project, $activity->task_id], absolute: false),
                ])
                ->values(),
            'members' => $members,
            'pendingApplications' => ProjectMembership::query()
                ->where('project_id', $project->id)
                ->where('status', 'pending')
                ->with('user:id,name,email')
                ->latest('requested_at')
                ->get()
                ->map(fn (ProjectMembership $membership) => [
                    'id' => $membership->id,
                    'name' => $membership->user?->name ?? '',
                    'details' => $membership->user?->email ?? '',
                    'time' => $membership->requested_at?->diffForHumans(),
                ])
                ->values(),
            'posts' => ProjectUpdate::query()
                ->where('project_id', $project->id)
                ->orderByDesc('published_at')
                ->limit(10)
                ->get()
                ->map(fn (ProjectUpdate $post) => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'published_at' => $post->published_at?->toIso8601String(),
                ])
                ->values(),
        ];
    }

    private function authorizeProjectView(User $user, Project $project): void
    {
        abort_unless(
            $user->canManageProject($project) || $user->projectMembershipFor($project) !== null,
            403,
        );
    }
}
