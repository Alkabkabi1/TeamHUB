<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\User;
use App\Support\DashboardData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TasksOverviewController extends Controller
{
    public function __construct(private DashboardData $hub) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $workspaceId = $request->integer('workspace') ?: null;
        $search = trim((string) $request->string('q'));
        $status = $request->string('status')->toString();
        $statusFilter = $status !== '' && $status !== 'all' ? $status : null;

        $query = $this->hub->tasksQuery($user, $workspaceId)
            ->orderByRaw("case status when 'review' then 0 when 'in_progress' then 1 when 'todo' then 2 else 3 end")
            ->orderBy('due_at');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhereHas('project', fn ($project) => $project->where('name', 'like', "%{$search}%"));
            });
        }

        if ($statusFilter !== null && TaskStatus::tryFrom($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        $tasks = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($task) => $this->hub->presentTask($task));

        return Inertia::render('app/Tasks', [
            'tasks' => $tasks,
            'search' => $search,
            'status' => $statusFilter ?? 'all',
            'workspaceId' => $workspaceId,
        ]);
    }
}
