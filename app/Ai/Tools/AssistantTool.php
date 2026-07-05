<?php

namespace App\Ai\Tools;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembershipRequest;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Ai\Contracts\Tool;

/**
 * Base class for every assistant tool. Each tool is constructed with the
 * authenticated user and runs queries scoped to — and authorized against —
 * that user, so the assistant can never surface data the user could not see
 * themselves.
 */
abstract class AssistantTool implements Tool
{
    public function __construct(protected ?User $user = null) {}

    /**
     * @param  array<string, mixed>  $data
     */
    protected function json(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    protected function resolveWorkspace(int|string|null $identifier, bool $activeOnly = false): ?Workspace
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        $query = Workspace::query()->when($activeOnly, fn ($q) => $q->where('status', 'active'));

        if (ctype_digit($identifier)) {
            return $query->whereKey((int) $identifier)->first()
                ?? $query->where('name', 'like', "%{$identifier}%")->orderBy('name')->first();
        }

        return $query->where('name', 'like', "%{$identifier}%")->orderBy('name')->first();
    }

    protected function resolveUser(int|string|null $identifier, ?Workspace $workspace = null): ?User
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        $query = User::query()
            ->when(
                $workspace !== null,
                fn ($q) => $q->whereIn(
                    'id',
                    $workspace->memberships()->where('status', 'approved')->select('user_id'),
                ),
            );

        if (ctype_digit($identifier)) {
            return $query->clone()->whereKey((int) $identifier)->first()
                ?? $query->where('name', 'like', "%{$identifier}%")->orderBy('name')->first();
        }

        return $query->where('name', 'like', "%{$identifier}%")->orderBy('name')->first();
    }

    protected function resolvePendingWorkspaceMembershipRequest(
        int|string|null $applicationId,
        int|string|null $applicant = null,
        int|string|null $workspace = null,
    ): ?WorkspaceMembershipRequest {
        $applicationId = trim((string) $applicationId);

        if ($applicationId !== '' && ctype_digit($applicationId)) {
            return WorkspaceMembershipRequest::query()
                ->with('workspace', 'user')
                ->whereKey((int) $applicationId)
                ->first();
        }

        $workspaceModel = $this->resolveWorkspace($workspace);
        $applicant = trim((string) $applicant);

        if ($workspaceModel === null || $applicant === '') {
            return null;
        }

        return WorkspaceMembershipRequest::query()
            ->with('workspace', 'user')
            ->where('workspace_id', $workspaceModel->id)
            ->where('status', 'pending')
            ->where(function ($query) use ($applicant): void {
                $query->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$applicant}%"))
                    ->orWhere('full_name', 'like', "%{$applicant}%");
            })
            ->orderBy('created_at')
            ->first();
    }

    protected function resolveProject(int|string|null $identifier, ?Workspace $workspace = null): ?Project
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        $query = Project::query()
            ->when($workspace !== null, fn ($q) => $q->where('workspace_id', $workspace->id))
            ->with('workspace');

        if (ctype_digit($identifier)) {
            return $query->whereKey((int) $identifier)->first()
                ?? $query->where('name', 'like', "%{$identifier}%")->orderBy('name')->first();
        }

        return $query->where('name', 'like', "%{$identifier}%")->orderBy('name')->first();
    }

    protected function resolveTask(int|string|null $identifier, ?Project $project = null): ?Task
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '' || $this->user === null) {
            return null;
        }

        $query = $this->visibleTaskQuery()
            ->when($project !== null, fn (Builder $q) => $q->where('project_id', $project->id));

        if (ctype_digit($identifier)) {
            return $query->clone()->whereKey((int) $identifier)->first()
                ?? $query->where('title', 'like', "%{$identifier}%")->orderByDesc('updated_at')->first();
        }

        return $query->where('title', 'like', "%{$identifier}%")->orderByDesc('updated_at')->first();
    }

    protected function resolveProjectMember(int|string|null $identifier, Project $project): ?User
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        $query = User::query()->whereIn(
            'id',
            $project->memberships()->where('status', 'approved')->select('user_id'),
        );

        if (ctype_digit($identifier)) {
            return $query->clone()->whereKey((int) $identifier)->first()
                ?? $query->where('name', 'like', "%{$identifier}%")->orderBy('name')->first();
        }

        return $query->where('name', 'like', "%{$identifier}%")->orderBy('name')->first();
    }

    protected function canAccessProject(Project $project): bool
    {
        if ($this->user === null) {
            return false;
        }

        if ($this->user->isAdmin() || $this->user->canManageProject($project)) {
            return true;
        }

        return $this->user->projectMemberships()
            ->where('project_id', $project->id)
            ->where('status', 'approved')
            ->exists();
    }

    protected function resolveAccessibleProject(
        int|string|null $identifier,
        ?Workspace $workspace = null,
    ): ?Project {
        $project = $this->resolveProject($identifier, $workspace);

        if ($project === null || ! $this->canAccessProject($project)) {
            return null;
        }

        return $project;
    }

    /**
     * @return Builder<Task>
     */
    protected function visibleTaskQuery(): Builder
    {
        /** @var User $user */
        $user = $this->user;

        $query = Task::query()
            ->with([
                'project:id,workspace_id,name',
                'project.workspace:id,name',
                'assignee:id,name',
                'creator:id,name',
            ]);

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereIn('project_id', $this->accessibleProjectIds());
    }

    /**
     * @return array<int, int>
     */
    protected function accessibleProjectIds(): array
    {
        if ($this->user === null) {
            return [];
        }

        if ($this->user->isAdmin()) {
            return Project::query()->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
        }

        $membershipIds = $this->user->projectMemberships()
            ->where('status', 'approved')
            ->pluck('project_id');

        $managedProjectIds = $this->user->managedProjects()->pluck('id');
        $managedWorkspaceIds = $this->user->managedWorkspaces()->pluck('id');

        $managedWorkspaceProjectIds = $managedWorkspaceIds->isEmpty()
            ? collect()
            : Project::query()
                ->whereIn('workspace_id', $managedWorkspaceIds)
                ->pluck('id');

        return $membershipIds
            ->merge($managedProjectIds)
            ->merge($managedWorkspaceProjectIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status->value,
            'status_label' => __($task->status->label()),
            'priority' => $task->priority->value,
            'priority_label' => __($task->priority->label()),
            'due_at' => $task->due_at?->toIso8601String(),
            'assignee_name' => $task->assignee?->name,
            'creator_name' => $task->creator?->name,
            'workspace' => [
                'id' => $task->project?->workspace_id,
                'name' => $task->project?->workspace?->name ?? '',
                'manage_url' => $task->project?->workspace_id
                    ? route('workspaces.manage', [$task->project->workspace_id], absolute: false)
                    : null,
            ],
            'project' => [
                'id' => $task->project_id,
                'name' => $task->project?->name ?? '',
                'tasks_url' => $task->project?->workspace_id
                    ? route('projects.tasks.index', [$task->project->workspace_id, $task->project_id], absolute: false)
                    : null,
                'manage_url' => $task->project?->workspace_id
                    ? route('projects.manage', [$task->project->workspace_id, $task->project_id], absolute: false)
                    : null,
            ],
            'detail_url' => $task->project?->workspace_id
                ? route('projects.tasks.show', [$task->project->workspace_id, $task->project_id, $task], absolute: false)
                : null,
            'has_deliverable' => $task->getFirstMedia(Task::DELIVERABLE_COLLECTION) !== null
                || filled($task->deliverable_url)
                || filled($task->deliverable_notes),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentActivity(TaskActivity $activity): array
    {
        return [
            'id' => $activity->id,
            'type' => $activity->type->value,
            'message' => $activity->message(),
            'created_at' => $activity->created_at?->toIso8601String(),
            'actor_name' => $activity->user?->name ?? __('tasks.activity.system'),
            'task_title' => $activity->task?->title ?? '',
        ];
    }
}
