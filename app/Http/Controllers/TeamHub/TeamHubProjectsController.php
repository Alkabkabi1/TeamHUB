<?php

namespace App\Http\Controllers\TeamHub;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TeamHub\TeamHubData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamHubProjectsController extends Controller
{
    public function __construct(private TeamHubData $hub) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $workspaceId = $request->integer('workspace') ?: null;
        $search = trim((string) $request->string('q'));

        $query = $this->hub->committeesQuery($user, $workspaceId);

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $projects = $query->get()->map(fn ($committee) => $this->hub->presentProject($committee));

        return Inertia::render('team-hub/Projects', [
            'projects' => $projects,
            'search' => $search,
            'workspaceId' => $workspaceId,
            'creatableWorkspaces' => $this->hub->creatableWorkspaces($user),
        ]);
    }
}
