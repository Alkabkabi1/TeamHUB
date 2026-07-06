<?php

namespace App\Http\Controllers;

use App\Models\ProjectMembership;
use App\Models\ProjectUpdate;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceMembershipRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isMember()) {
            abort(403);
        }

        $workspaceMemberships = $user->workspaceMemberships()
            ->where('status', 'approved')
            ->with('workspace:id,name')
            ->orderBy('joined_at')
            ->get();

        $projectMemberships = $user->projectMemberships()
            ->where('status', 'approved')
            ->with('project.workspace:id,name')
            ->orderBy('joined_at')
            ->get();

        $projectCountsByWorkspace = $projectMemberships
            ->filter(fn (ProjectMembership $membership) => $membership->project !== null)
            ->groupBy(fn (ProjectMembership $membership) => $membership->project?->workspace_id)
            ->map->count();

        $workspaces = $workspaceMemberships
            ->filter(fn (WorkspaceMembership $membership) => $membership->workspace !== null)
            ->map(fn (WorkspaceMembership $membership) => [
                'id' => $membership->workspace_id,
                'name' => $membership->workspace->name,
                'memberSince' => $membership->joined_at?->format('Y') ?? '',
                'projectCount' => (int) ($projectCountsByWorkspace[$membership->workspace_id] ?? 0),
            ])
            ->values();

        $projects = $projectMemberships
            ->filter(fn (ProjectMembership $membership) => $membership->project !== null && $membership->project->workspace !== null)
            ->map(fn (ProjectMembership $membership) => [
                'id' => $membership->project_id,
                'name' => $membership->project?->name ?? '',
                'workspaceId' => $membership->project?->workspace_id,
                'workspaceName' => $membership->project?->workspace?->name ?? '',
                'joinedAt' => $membership->joined_at?->toIso8601String(),
            ])
            ->values();

        $assignedTaskBaseQuery = Task::query()
            ->assignedTo($user)
            ->incomplete()
            ->with(['project:id,workspace_id,name', 'project.workspace:id,name'])
            ->orderBy('due_at')
            ->orderByDesc('updated_at');

        $overdueTasks = (clone $assignedTaskBaseQuery)
            ->overdue()
            ->get();

        $dueTodayTasks = (clone $assignedTaskBaseQuery)
            ->dueToday()
            ->get();

        $upcomingTasks = (clone $assignedTaskBaseQuery)
            ->upcoming()
            ->limit(6)
            ->get();

        $attentionTasks = $overdueTasks
            ->concat($dueTodayTasks)
            ->sortBy(fn (Task $task) => $task->due_at?->timestamp ?? PHP_INT_MAX)
            ->take(6)
            ->values();

        $latestApplication = $user->membershipRequests()
            ->where('status', 'approved')
            ->latest('reviewed_at')
            ->first();

        $projectIds = $projectMemberships->pluck('project_id')->filter()->unique()->values();

        $recentUpdates = $projectIds->isEmpty()
            ? collect()
            : ProjectUpdate::query()
                ->whereIn('project_id', $projectIds)
                ->with(['project:id,name', 'workspace:id,name'])
                ->latest('published_at')
                ->limit(6)
                ->get()
                ->map(fn (ProjectUpdate $post) => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'projectName' => $post->project?->name ?? '',
                    'workspaceName' => $post->workspace?->name ?? '',
                    'publishedAt' => $post->published_at?->toIso8601String(),
                    'url' => route('projects.updates.index', [$post->workspace_id, $post->project_id], absolute: false),
                ])
                ->values();

        return Inertia::render('StudentDashboard', [
            'stats' => [
                'workspacesCount' => $workspaceMemberships->count(),
                'projectsCount' => $projects->count(),
                'openTasksCount' => (clone $assignedTaskBaseQuery)->count(),
                'dueTodayCount' => $dueTodayTasks->count(),
                'overdueCount' => $overdueTasks->count(),
            ],
            'workspaces' => $workspaces,
            'workspaces' => $workspaces,
            'projects' => $projects,
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'subtitle' => $this->profileSubtitle($latestApplication),
                'joinedAt' => $user->created_at?->toIso8601String(),
            ],
            'attentionTasks' => $attentionTasks->map(fn (Task $task): array => $this->presentTask($task))->values(),
            'upcomingTasks' => $upcomingTasks->map(fn (Task $task): array => $this->presentTask($task))->values(),
            'recentUpdates' => $recentUpdates,
            'myTasksUrl' => route('my-tasks', absolute: false),
        ]);
    }

    private function profileSubtitle(?WorkspaceMembershipRequest $application): string
    {
        if ($application === null) {
            return '';
        }

        return (string) ($application->skills ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function presentTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'status' => $task->status->value,
            'statusLabel' => __($task->status->label()),
            'priority' => $task->priority->value,
            'priorityLabel' => __($task->priority->label()),
            'dueAt' => $task->due_at?->toIso8601String(),
            'workspaceId' => $task->project?->workspace_id,
            'workspaceName' => $task->project?->workspace?->name ?? '',
            'projectId' => $task->project_id,
            'projectName' => $task->project?->name ?? '',
            'detailUrl' => route('projects.tasks.show', [$task->project?->workspace_id, $task->project_id, $task], absolute: false),
        ];
    }
}
