<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\AssignProjectLeaderAction;
use App\Actions\Dashboard\CreateProjectAction;
use App\Actions\Dashboard\CreateProjectTaskAction;
use App\Actions\Dashboard\MessageProjectLeaderAction;
use App\Actions\Dashboard\ReviewTaskDeliverableAction;
use App\Actions\Dashboard\SubmitTaskDeliverableAction;
use App\Enums\ProjectCapability;
use App\Http\Requests\StoreDashboardTaskRequest;
use App\Models\Project;
use App\Models\Task;
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

    public function storeTask(StoreDashboardTaskRequest $request, CreateProjectTaskAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $project = $request->project();
        abort_unless($project instanceof Project, 422);

        $action->execute($user, $project, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('dashboard.leader.task_created'),
        ]);

        return redirect()->route('dashboard');
    }

    public function submitDeliverable(
        Request $request,
        Task $task,
        SubmitTaskDeliverableAction $action,
    ): RedirectResponse {
        $this->authorize('submitDeliverable', $task);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'deliverable_url' => ['nullable', 'url', 'max:2048'],
            'deliverable_notes' => ['nullable', 'string', 'max:2000'],
            'deliverable_file' => ['nullable', 'file', 'max:10240'],
        ]);

        $action->execute(
            $user,
            $task,
            $validated,
            $request->file('deliverable_file'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('tasks.deliverable_submitted'),
        ]);

        return redirect()->route('dashboard');
    }

    public function approveDeliverable(
        Request $request,
        Task $task,
        ReviewTaskDeliverableAction $action,
    ): RedirectResponse {
        $this->authorize('approveDeliverable', $task);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $action->approve($user, $task, $validated['review_notes'] ?? null);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('tasks.review_approved'),
        ]);

        return redirect()->route('dashboard');
    }

    public function requestChanges(
        Request $request,
        Task $task,
        ReviewTaskDeliverableAction $action,
    ): RedirectResponse {
        $this->authorize('requestChanges', $task);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $action->requestChanges($user, $task, $validated['review_notes'] ?? null);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('tasks.review_changes_requested'),
        ]);

        return redirect()->route('dashboard');
    }
}
