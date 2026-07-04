<?php

namespace App\Http\Controllers\TeamHub;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TeamHub\TeamHubData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamHubTasksController extends Controller
{
    public function __construct(private TeamHubData $hub) {}

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
                    ->orWhereHas('committee', fn ($committee) => $committee->where('name', 'like', "%{$search}%"));
            });
        }

        if ($statusFilter !== null && TaskStatus::tryFrom($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        $tasks = $query->get()->map(fn ($task) => $this->hub->presentTask($task));

        return Inertia::render('team-hub/Tasks', [
            'tasks' => $tasks,
            'search' => $search,
            'status' => $statusFilter ?? 'all',
            'workspaceId' => $workspaceId,
        ]);
    }
}
