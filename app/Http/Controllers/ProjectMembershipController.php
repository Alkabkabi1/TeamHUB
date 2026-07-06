<?php

namespace App\Http\Controllers;

use App\Enums\ProjectCapability;
use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectMembershipController extends Controller
{
    public function store(Request $request, Workspace $workspace, Project $project): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->isMember(), 403);
        abort_unless($user->workspaceMembershipFor($workspace) !== null, 403, __('project.members.must_be_workspace_member'));

        $existing = ProjectMembership::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($existing) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('project.members.already_requested')]);

            return back();
        }

        ProjectMembership::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('project.members.request_sent')]);

        return back();
    }

    public function approve(Request $request, Workspace $workspace, Project $project, ProjectMembership $membership): RedirectResponse
    {
        $this->authorize(ProjectCapability::ManageMembers->value, $project);

        abort_unless($membership->project_id === $project->id, 404);

        $membership->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'joined_at' => now(),
            'rejection_reason' => null,
        ]);

        $membership->assignProjectRole(ProjectRole::Member);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('project.members.request_approved')]);

        return back();
    }

    public function reject(Request $request, Workspace $workspace, Project $project, ProjectMembership $membership): RedirectResponse
    {
        $this->authorize(ProjectCapability::ManageMembers->value, $project);

        abort_unless($membership->project_id === $project->id, 404);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $membership->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['reason'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('project.members.request_rejected')]);

        return back();
    }
}
