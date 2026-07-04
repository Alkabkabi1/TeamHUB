<?php

namespace App\Http\Controllers;

use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\CommitteeMembership;
use App\Models\Post;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isStudent()) {
            abort(403);
        }

        $workspaceMemberships = $user->clubMemberships()
            ->where('status', 'approved')
            ->with('club:id,name')
            ->orderBy('joined_at')
            ->get();

        $projectMemberships = $user->committeeMemberships()
            ->where('status', 'approved')
            ->with('committee.club:id,name')
            ->orderBy('joined_at')
            ->get();

        $projectCountsByWorkspace = $projectMemberships
            ->filter(fn (CommitteeMembership $membership) => $membership->committee !== null)
            ->groupBy(fn (CommitteeMembership $membership) => $membership->committee?->club_id)
            ->map->count();

        $workspaces = $workspaceMemberships
            ->filter(fn (ClubMembership $membership) => $membership->club !== null)
            ->map(fn (ClubMembership $membership) => [
                'id' => $membership->club_id,
                'name' => $membership->club->name,
                'memberSince' => $membership->joined_at?->format('Y') ?? '',
                'projectCount' => (int) ($projectCountsByWorkspace[$membership->club_id] ?? 0),
            ])
            ->values();

        $projects = $projectMemberships
            ->filter(fn (CommitteeMembership $membership) => $membership->committee !== null && $membership->committee->club !== null)
            ->map(fn (CommitteeMembership $membership) => [
                'id' => $membership->committee_id,
                'name' => $membership->committee?->name ?? '',
                'clubId' => $membership->committee?->club_id,
                'clubName' => $membership->committee?->club?->name ?? '',
                'joinedAt' => $membership->joined_at?->toIso8601String(),
            ])
            ->values();

        $assignedTaskBaseQuery = Task::query()
            ->assignedTo($user)
            ->incomplete()
            ->with(['committee:id,club_id,name', 'committee.club:id,name'])
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

        $latestApplication = $user->joinApplications()
            ->where('status', 'approved')
            ->latest('reviewed_at')
            ->first();

        $committeeIds = $projectMemberships->pluck('committee_id')->filter()->unique()->values();

        $recentUpdates = $committeeIds->isEmpty()
            ? collect()
            : Post::query()
                ->whereIn('committee_id', $committeeIds)
                ->with(['committee:id,name', 'club:id,name'])
                ->latest('published_at')
                ->limit(6)
                ->get()
                ->map(fn (Post $post) => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'committeeName' => $post->committee?->name ?? '',
                    'clubName' => $post->club?->name ?? '',
                    'publishedAt' => $post->published_at?->toIso8601String(),
                    'url' => route('committees.updates.index', [$post->club_id, $post->committee_id], absolute: false),
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
            'clubs' => $workspaces,
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

    private function profileSubtitle(?ClubJoinApplication $application): string
    {
        if ($application === null) {
            return '';
        }

        $parts = array_filter([$application->major, $application->level]);

        return implode(' - ', $parts);
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
            'clubId' => $task->committee?->club_id,
            'clubName' => $task->committee?->club?->name ?? '',
            'committeeId' => $task->committee_id,
            'committeeName' => $task->committee?->name ?? '',
            'detailUrl' => route('committees.tasks.show', [$task->committee?->club_id, $task->committee_id, $task], absolute: false),
        ];
    }
}
