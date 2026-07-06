<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceMembershipRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $statusInput = $request->filled('status') ? trim((string) $request->string('status')) : null;

        $hasSearch = $search !== '';
        $hasExplicitStatus = $statusInput !== null && $statusInput !== '';

        $widenCaps = $hasSearch || $hasExplicitStatus;
        $workspaceLimit = $widenCaps ? 50 : 8;

        $workspacesQuery = Workspace::query()
            ->with('media')
            ->withCount('memberships as members_count')
            ->when($hasSearch, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($hasExplicitStatus, fn ($query) => $query->where('status', $statusInput))
            ->when(! $hasExplicitStatus, fn ($query) => $query->where('status', 'active'));

        $workspaces = $workspacesQuery
            ->orderByDesc('members_count')
            ->orderBy('name')
            ->limit($workspaceLimit)
            ->get(['id', 'name', 'theme', 'status']);

        $userId = $request->user()?->id;
        $workspaceIds = $workspaces->pluck('id');

        $memberWorkspaceIds = $userId
            ? WorkspaceMembership::query()
                ->where('user_id', $userId)
                ->whereIn('workspace_id', $workspaceIds)
                ->where('status', 'approved')
                ->pluck('workspace_id')
                ->merge(
                    WorkspaceMembershipRequest::query()
                        ->where('user_id', $userId)
                        ->whereIn('workspace_id', $workspaceIds)
                        ->where('status', 'pending')
                        ->pluck('workspace_id')
                )
                ->unique()
            : collect();

        $workspaces->each(function (Workspace $workspace) use ($memberWorkspaceIds): void {
            $workspace->is_member = $memberWorkspaceIds->contains($workspace->id);
        });

        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'workspaces' => $workspaces,
            'filters' => [
                'search' => $search,
                'status' => $statusInput ?? '',
            ],
            'filterOptions' => [
                'statuses' => [
                    ['value' => 'active', 'label' => 'نشط'],
                    ['value' => 'inactive', 'label' => 'غير نشط'],
                    ['value' => 'founding', 'label' => 'تحت التأسيس'],
                ],
            ],
        ]);
    }
}
