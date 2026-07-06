<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Workspace $workspace, Project $project): Response
    {
        $this->authorize('viewAny', [Task::class, $project]);

        $tasks = Task::query()
            ->forProject($project)
            ->with(['assignee:id,name', 'creator:id,name'])
            ->orderByRaw("case status when 'review' then 0 when 'in_progress' then 1 when 'todo' then 2 else 3 end")
            ->orderBy('due_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Task $task): array => $this->taskSummary($task))
            ->values();

        return Inertia::render('projects/tasks/Index', [
            'theme' => ['brand' => $project->theme ?: ($workspace->theme ?: config('theme.brand'))],
            'workspace' => $workspace->only(['id', 'name', 'theme', 'logo_url']),
            'project' => [
                ...$project->only(['id', 'name', 'theme', 'status']),
                'logo_url' => $project->logo_url,
            ],
            'tasks' => $tasks,
            'members' => $this->memberOptions($project),
            'statusOptions' => $this->statusOptions(),
            'priorityOptions' => $this->priorityOptions(),
            'canManageTasks' => auth()->user()?->canManageProject($project) ?? false,
            'manageUrl' => route('projects.manage', [$workspace, $project], absolute: false),
        ]);
    }

    public function show(Workspace $workspace, Project $project, Task $task): Response
    {
        $this->authorize('view', $task);

        $task->loadMissing([
            'project.workspace:id,name',
            'assignee:id,name',
            'creator:id,name',
            'reviewer:id,name',
            'media',
        ]);

        $comments = $task->comments()
            ->with('user:id,name')
            ->latest()
            ->get();

        $activities = $task->activities()
            ->with('user:id,name')
            ->latest()
            ->get();

        /** @var User $user */
        $user = auth()->user();

        return Inertia::render('projects/tasks/Show', [
            'theme' => ['brand' => $project->theme ?: ($workspace->theme ?: config('theme.brand'))],
            'workspace' => $workspace->only(['id', 'name', 'theme', 'logo_url']),
            'project' => [
                ...$project->only(['id', 'name', 'theme', 'status']),
                'logo_url' => $project->logo_url,
            ],
            'task' => $this->taskDetail($task),
            'comments' => $comments->map(fn (TaskComment $comment): array => $this->commentSummary($comment, $workspace, $project, $task, $user))->values(),
            'activities' => $activities->map(fn (TaskActivity $activity): array => $this->activitySummary($activity, $workspace, $project))->values(),
            'members' => $this->memberOptions($project),
            'statusOptions' => $this->statusOptions(),
            'priorityOptions' => $this->priorityOptions(),
            'canManageTasks' => $user->canManageProject($project),
            'canSubmitDeliverable' => $user->can('submitDeliverable', $task),
            'canApproveDeliverable' => $user->can('approveDeliverable', $task),
            'canUpdateProgress' => $user->can('update', $task),
            'canComment' => $user->can('view', $task),
            'indexUrl' => route('projects.tasks.index', [$workspace, $project], absolute: false),
            'manageUrl' => route('projects.manage', [$workspace, $project], absolute: false),
        ]);
    }

    public function store(StoreTaskRequest $request, Workspace $workspace, Project $project): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $task = Task::create([
            ...$request->safe()->except(['assigned_to']),
            'project_id' => $project->id,
            'created_by' => $user->id,
            'assigned_to' => $request->validated('assigned_to'),
            'status' => $request->validated('status') ?? TaskStatus::Todo->value,
            'priority' => $request->validated('priority') ?? TaskPriority::Medium->value,
        ]);

        $task->recordCreated($user);

        if ($task->assigned_to !== null) {
            $task->recordAssignment($user, null, $task->assignee);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('tasks.created'),
        ]);

        return redirect()->route('projects.tasks.show', [$workspace, $project, $task]);
    }

    public function update(UpdateTaskRequest $request, Workspace $workspace, Project $project, Task $task): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $canManage = $user->canManageProject($project);
        $task->loadMissing('assignee:id,name,email,locale');
        $originalStatus = $task->status;
        $originalAssignee = $task->assignee;

        if ($canManage) {
            $task->fill([
                'title' => $request->validated('title', $task->title),
                'description' => $request->validated('description'),
                'assigned_to' => $request->validated('assigned_to'),
                'priority' => $request->validated('priority', $task->priority->value),
                'due_at' => $request->validated('due_at'),
            ]);

            if ($request->filled('status')) {
                $task->status = TaskStatus::from($request->validated('status'));
            }

            $task->save();
            $task->load('assignee:id,name,email,locale');
        } else {
            abort_unless($task->isAssignedTo($user), 403);
            abort_if(in_array($task->status, [TaskStatus::Review, TaskStatus::Done], true), 403);

            if ($request->filled('status')) {
                $task->update([
                    'status' => TaskStatus::from($request->validated('status')),
                ]);
            }
        }

        $task->refresh();
        $task->loadMissing('assignee:id,name,email,locale');

        if ($originalStatus !== $task->status) {
            $task->recordStatusChange($user, $originalStatus, $task->status);
        }

        if (($originalAssignee?->id) !== $task->assigned_to) {
            $task->recordAssignment($user, $originalAssignee, $task->assignee);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('tasks.updated'),
        ]);

        if ($request->filled('return_to')) {
            return redirect($request->validated('return_to'));
        }

        return redirect()->route('projects.tasks.show', [$workspace, $project, $task]);
    }

    public function destroy(Workspace $workspace, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('tasks.deleted'),
        ]);

        return redirect()->route('projects.tasks.index', [$workspace, $project]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (TaskStatus $status): array => ['value' => $status->value, 'label' => __($status->label())],
            TaskStatus::cases(),
        );
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function priorityOptions(): array
    {
        return array_map(
            fn (TaskPriority $priority): array => ['value' => $priority->value, 'label' => __($priority->label())],
            TaskPriority::cases(),
        );
    }

    /**
     * @return array<int, array{value: number, label: string}>
     */
    private function memberOptions(Project $project): array
    {
        return ProjectMembership::query()
            ->where('project_id', $project->id)
            ->where('status', 'approved')
            ->with('user:id,name')
            ->get()
            ->filter(fn (ProjectMembership $membership): bool => $membership->user !== null)
            ->map(fn (ProjectMembership $membership): array => [
                'value' => $membership->user_id,
                'label' => $membership->user?->name ?? '',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function taskSummary(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'status' => $task->status->value,
            'status_label' => __($task->status->label()),
            'priority' => $task->priority->value,
            'priority_label' => __($task->priority->label()),
            'due_at' => $task->due_at?->toIso8601String(),
            'assignee_name' => $task->assignee?->name,
            'creator_name' => $task->creator?->name,
            'has_deliverable' => $task->getFirstMedia(Task::DELIVERABLE_COLLECTION) !== null
                || filled($task->deliverable_url)
                || filled($task->deliverable_notes),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function taskDetail(Task $task): array
    {
        $deliverableFile = $task->getFirstMedia(Task::DELIVERABLE_COLLECTION);

        return [
            ...$this->taskSummary($task),
            'description' => $task->description,
            'assignee_id' => $task->assigned_to,
            'deliverable_url' => $task->deliverable_url,
            'deliverable_notes' => $task->deliverable_notes,
            'submitted_for_review_at' => $task->submitted_for_review_at?->toIso8601String(),
            'reviewed_at' => $task->reviewed_at?->toIso8601String(),
            'completed_at' => $task->completed_at?->toIso8601String(),
            'review_notes' => $task->review_notes,
            'creator' => $task->creator ? ['id' => $task->creator->id, 'name' => $task->creator->name] : null,
            'assignee' => $task->assignee ? ['id' => $task->assignee->id, 'name' => $task->assignee->name] : null,
            'reviewer' => $task->reviewer ? ['id' => $task->reviewer->id, 'name' => $task->reviewer->name] : null,
            'deliverable_file' => $deliverableFile ? [
                'name' => $deliverableFile->file_name,
                'url' => $deliverableFile->getUrl(),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function commentSummary(TaskComment $comment, Workspace $workspace, Project $project, Task $task, User $user): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'author_name' => $comment->user?->name ?? __('tasks.activity.system'),
            'created_at' => $comment->created_at?->toIso8601String(),
            'can_delete' => $comment->user_id === $user->id || $user->canManageProject($project),
            'delete_url' => route('projects.tasks.comments.destroy', [$workspace, $project, $task, $comment], absolute: false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function activitySummary(TaskActivity $activity, Workspace $workspace, Project $project): array
    {
        return [
            'id' => $activity->id,
            'type' => $activity->type->value,
            'message' => $activity->message(),
            'created_at' => $activity->created_at?->toIso8601String(),
            'task' => [
                'id' => $activity->task_id,
                'title' => $activity->task?->title ?? '',
                'url' => route('projects.tasks.show', [$workspace, $project, $activity->task_id], absolute: false),
            ],
        ];
    }
}
