<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\AssignProjectLeaderAction;
use App\Actions\Dashboard\AssignWorkspaceLeaderAction;
use App\Actions\Dashboard\CreateProjectAction;
use App\Actions\Dashboard\MessageProjectLeaderAction;
use App\Actions\Dashboard\MessageWorkspaceLeaderAction;
use App\Enums\ProjectCapability;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Support\DemoWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardActionController extends Controller
{
    public function storeProject(Request $request, CreateProjectAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'workspace_id' => ['nullable', 'integer', 'exists:workspaces,id'],
            'leader_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $workspace = ! empty($validated['workspace_id'])
            ? Workspace::query()->findOrFail($validated['workspace_id'])
            : DemoWorkspace::defaultWorkspace();

        $draft = new Project(['workspace_id' => $workspace->id]);
        $draft->setRelation('workspace', $workspace);

        abort_unless(
            $user->isAdmin() || $user->can('create', [Project::class, $draft]),
            403,
        );

        $action->execute($user, $workspace, $validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('dashboard.admin.project_created'),
        ]);

        return redirect()->route('dashboard');
    }

    public function assignLeader(Request $request, AssignProjectLeaderAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'leader_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $project = Project::query()->with('workspace')->findOrFail($validated['project_id']);
        $leader = User::query()->findOrFail($validated['leader_id']);

        abort_unless(
            $user->isAdmin()
                || $user->hasProjectCapability(ProjectCapability::ManageMembers, $project)
                || ($project->workspace !== null && $user->canManageWorkspace($project->workspace)),
            403,
        );

        $action->execute($user, $project, $leader);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('dashboard.admin.leader_assigned'),
        ]);

        return redirect()->route('dashboard');
    }

    public function messageLeader(Request $request, MessageProjectLeaderAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->isAdmin(), 403);

        $validated = $request->validate([
            'leader_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $leader = User::query()->findOrFail($validated['leader_id']);
        $action->execute($user, $leader, $validated['message']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('dashboard.admin.message_sent'),
        ]);

        return redirect()->route('dashboard');
    }

    public function assignWorkspaceLeader(Request $request, AssignWorkspaceLeaderAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->isAdmin(), 403);

        $validated = $request->validate([
            'workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
            'leader_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $workspace = Workspace::query()->findOrFail($validated['workspace_id']);
        $leader = User::query()->findOrFail($validated['leader_id']);

        $action->execute($user, $workspace, $leader);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('dashboard.admin.workspace_leader_assigned'),
        ]);

        return redirect()->route('dashboard');
    }

    public function messageWorkspaceLeader(Request $request, MessageWorkspaceLeaderAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->isAdmin(), 403);

        $validated = $request->validate([
            'leader_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $leader = User::query()->findOrFail($validated['leader_id']);
        $action->execute($user, $leader, $validated['message']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('dashboard.admin.workspace_message_sent'),
        ]);

        return redirect()->route('dashboard');
    }
}
