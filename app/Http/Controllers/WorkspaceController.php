<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\Task;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceMembershipRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function show(Request $request, Workspace $workspace): Response
    {
        $projectIds = $workspace->projects()->pluck('id');

        $workspace->loadCount([
            'memberships as members_count',
            'projects as projects_count',
        ]);

        $openTasksCount = Task::query()
            ->whereIn('project_id', $projectIds)
            ->whereNotIn('status', ['done'])
            ->count();

        $recentUpdates = ProjectUpdate::query()
            ->whereIn('project_id', $projectIds)
            ->with('project:id,name,workspace_id')
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get(['id', 'title', 'body', 'published_at', 'project_id', 'workspace_id'])
            ->map(fn (ProjectUpdate $update) => [
                'id' => $update->id,
                'title' => $update->title,
                'excerpt' => mb_substr(strip_tags((string) $update->body), 0, 160),
                'published_at' => $update->published_at?->locale(app()->getLocale())->diffForHumans(),
                'project_name' => $update->project?->name,
                'url' => $update->project_id
                    ? route('projects.updates.index', [$workspace, $update->project_id], absolute: false)
                    : null,
            ])
            ->values();

        $projects = $workspace->projects()
            ->withCount([
                'memberships as members_count',
                'tasks as tasks_count',
            ])
            ->with('media')
            ->where('status', 'active')
            ->orderByDesc('members_count')
            ->limit(4)
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => mb_substr(strip_tags((string) $project->description), 0, 160),
                'image_url' => $project->logo_url ?: $project->coverImageUrl(),
                'members_count' => $project->members_count,
                'tasks_count' => $project->tasks_count,
            ])
            ->values();

        $userId = $request->user()?->id;
        $isMember = $userId && (
            WorkspaceMembership::query()
                ->where('user_id', $userId)
                ->where('workspace_id', $workspace->id)
                ->where('status', 'approved')
                ->exists()
            || WorkspaceMembershipRequest::query()
                ->where('user_id', $userId)
                ->where('workspace_id', $workspace->id)
                ->where('status', 'pending')
                ->exists()
        );

        return Inertia::render('ClubPage', [
            'theme' => ['brand' => $workspace->theme ?: config('theme.brand')],
            'club' => $workspace->only(['id', 'name', 'theme', 'logo_url', 'status']),
            'canManage' => $request->user()?->canManageWorkspace($workspace) ?? false,
            'isMember' => $isMember,
            'stats' => [
                'members_count' => $workspace->members_count,
                'projects_count' => $workspace->projects_count,
                'open_tasks_count' => $openTasksCount,
            ],
            'recentUpdates' => $recentUpdates,
            'committees' => $projects,
        ]);
    }
}
