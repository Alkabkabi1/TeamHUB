<?php

namespace App\Ai\Tools;

use App\Models\Post;
use App\Models\Task;
use App\Models\TaskActivity;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetProjectSummary extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Summarize a visible project with task counts, review/overdue blockers, recent activity, and '
            .'direct TeamHUB links. Use this when the user asks for a project summary or asks what is blocked.';
    }

    public function handle(Request $request): Stringable|string
    {
        if ($this->user === null) {
            return $this->json(['error' => 'Please sign in to view project summaries.']);
        }

        $workspace = null;

        if (! empty($request['workspace'])) {
            $workspace = $this->resolveClub($request['workspace']);

            if ($workspace === null) {
                return $this->json(['error' => 'No workspace matched that name.']);
            }
        }

        $project = $this->resolveAccessibleCommittee($request['project'] ?? null, $workspace);

        if ($project === null) {
            return $this->json(['error' => 'No visible project matched that name.']);
        }

        $project->loadMissing('club:id,name');

        $tasks = Task::query()
            ->where('committee_id', $project->id)
            ->with(['assignee:id,name', 'creator:id,name', 'committee:id,club_id,name', 'committee.club:id,name'])
            ->orderBy('due_at')
            ->orderByDesc('updated_at');

        $stats = [
            'totalCount' => (clone $tasks)->count(),
            'todoCount' => (clone $tasks)->where('status', 'todo')->count(),
            'inProgressCount' => (clone $tasks)->where('status', 'in_progress')->count(),
            'reviewCount' => (clone $tasks)->where('status', 'review')->count(),
            'doneCount' => (clone $tasks)->where('status', 'done')->count(),
            'overdueCount' => (clone $tasks)->overdue()->count(),
            'unassignedCount' => (clone $tasks)->whereNull('assigned_to')->count(),
        ];

        $blockers = collect()
            ->merge(
                (clone $tasks)
                    ->overdue()
                    ->limit(5)
                    ->get()
                    ->map(fn (Task $task) => [
                        ...$this->presentTask($task),
                        'reason' => 'overdue',
                    ]),
            )
            ->merge(
                (clone $tasks)
                    ->where('status', 'review')
                    ->limit(5)
                    ->get()
                    ->map(fn (Task $task) => [
                        ...$this->presentTask($task),
                        'reason' => 'review',
                    ]),
            )
            ->unique('id')
            ->values()
            ->all();

        $recentActivity = TaskActivity::query()
            ->whereHas('task', fn ($query) => $query->where('committee_id', $project->id))
            ->with(['task:id,title', 'user:id,name'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (TaskActivity $activity) => $this->presentActivity($activity))
            ->values()
            ->all();

        $recentUpdates = Post::query()
            ->where('committee_id', $project->id)
            ->latest('published_at')
            ->limit(3)
            ->get()
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'published_at' => $post->published_at?->toIso8601String(),
                'url' => route('committees.updates.index', [$project->club_id, $project->id], absolute: false),
            ])
            ->values()
            ->all();

        return $this->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'workspace' => $project->club?->name ?? '',
                'tasksUrl' => route('committees.tasks.index', [$project->club_id, $project->id], absolute: false),
                'manageUrl' => route('committees.manage', [$project->club_id, $project->id], absolute: false),
                'filesUrl' => route('committees.files.index', [$project->club_id, $project->id], absolute: false),
                'updatesUrl' => route('committees.updates.index', [$project->club_id, $project->id], absolute: false),
            ],
            'stats' => $stats,
            'blockers' => $blockers,
            'recentActivity' => $recentActivity,
            'recentUpdates' => $recentUpdates,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()
                ->description('Project name or numeric id.')
                ->required(),
            'workspace' => $schema->string()
                ->description('Optional workspace name to disambiguate the project.'),
        ];
    }
}
