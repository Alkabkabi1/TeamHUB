<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\DashboardData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectsOverviewController extends Controller
{
    public function __construct(private DashboardData $hub) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $workspaceId = $request->integer('workspace') ?: null;
        $search = trim((string) $request->string('q'));

        $query = $this->hub->projectsQuery($user, $workspaceId);

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $projects = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($project) => $this->hub->presentProject($project));

        return Inertia::render('team-hub/Projects', [
            'projects' => $projects,
            'search' => $search,
            'workspaceId' => $workspaceId,
            'creatableWorkspaces' => $this->hub->creatableWorkspaces($user),
        ]);
    }
}
