<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Support\TaskPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskDetailController extends Controller
{
    public function __construct(private TaskPresenter $taskPresenter) {}

    public function show(Request $request, Task $task): Response
    {
        $this->authorize('view', $task);

        $task->loadMissing([
            'project.workspace:id,name,theme,logo_url',
            'assignee:id,name',
            'creator:id,name',
            'reviewer:id,name',
            'media',
        ]);

        /** @var User $user */
        $user = $request->user();

        /** @var Project $project */
        $project = $task->project;
        /** @var Workspace $workspace */
        $workspace = $project->workspace;

        return Inertia::render('app/TaskShow', [
            'theme' => ['brand' => $project->theme ?: ($workspace->theme ?: config('theme.brand'))],
            'workspace' => $workspace->only(['id', 'name', 'theme', 'logo_url']),
            'project' => [
                ...$project->only(['id', 'name', 'theme', 'status']),
                'logo_url' => $project->logo_url,
            ],
            'task' => $this->taskPresenter->detail($task),
            'canSubmitDeliverable' => $user->can('submitDeliverable', $task),
            'canApproveDeliverable' => $user->can('approveDeliverable', $task),
            'canUpdateProgress' => $user->can('update', $task),
            'statusOptions' => $this->statusOptions(),
            'priorityOptions' => $this->priorityOptions(),
            'approveUrl' => route('tasks.approve', $task, absolute: false),
            'requestChangesUrl' => route('tasks.request-changes', $task, absolute: false),
            'submitDeliverableUrl' => route('tasks.deliverable', $task, absolute: false),
            'tasksIndexUrl' => route('tasks', absolute: false),
        ]);
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
}
