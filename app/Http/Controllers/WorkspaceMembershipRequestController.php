<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceCapability;
use App\Enums\WorkspaceRole;
use App\Enums\WorkspaceStatus;
use App\Http\Requests\StoreWorkspaceMembershipRequest;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceMembershipRequest;
use App\Notifications\JoinApplicationReceivedNotification;
use App\Notifications\MembershipApprovedNotification;
use App\Notifications\MembershipRejectedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceMembershipRequestController extends Controller
{
    public function create(Workspace $workspace): Response
    {
        if ($workspace->status !== WorkspaceStatus::Active) {
            abort(404);
        }

        $user = auth()->user();

        abort_unless($user->isMember(), 403);

        return Inertia::render('ClubJoinForm', [
            'club' => $workspace->only(['id', 'name']),
            'defaults' => [
                'full_name' => $user?->name ?? '',
            ],
        ]);
    }

    public function store(StoreWorkspaceMembershipRequest $request, Workspace $workspace): RedirectResponse
    {
        $application = WorkspaceMembershipRequest::create([
            ...$request->validated(),
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        $this->notifyReviewers($application);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('join.submitted'),
        ]);

        return redirect()->route('workspaces.show', $workspace);
    }

    public function approve(WorkspaceMembershipRequest $application): RedirectResponse
    {
        $user = auth()->user();

        $this->authorize(WorkspaceCapability::ManageMembers->value, $application->workspace);

        $application->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $user->id,
        ]);

        $membership = WorkspaceMembership::updateOrCreate(
            [
                'user_id' => $application->user_id,
                'workspace_id' => $application->workspace_id,
            ],
            [
                'status' => 'approved',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'joined_at' => now(),
            ]
        );

        $membership->assignWorkspaceRole(WorkspaceRole::Member);

        $application->user->notify(new MembershipApprovedNotification($application->workspace));

        return back();
    }

    public function reject(WorkspaceMembershipRequest $application): RedirectResponse
    {
        $user = auth()->user();

        $this->authorize(WorkspaceCapability::ManageMembers->value, $application->workspace);

        $application->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $user->id,
        ]);

        WorkspaceMembership::where('user_id', $application->user_id)
            ->where('workspace_id', $application->workspace_id)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

        $application->user->notify(new MembershipRejectedNotification($application->workspace));

        return back();
    }

    private function notifyReviewers(WorkspaceMembershipRequest $application): void
    {
        $reviewerIds = WorkspaceMembership::query()
            ->where('workspace_id', $application->workspace_id)
            ->where('status', 'approved')
            ->whereHas('roles', fn ($query) => $query->whereIn(
                'role',
                WorkspaceRole::valuesWithCapability(WorkspaceCapability::ManageMembers),
            ))
            ->pluck('user_id')
            ->unique();

        $reviewers = User::whereIn('id', $reviewerIds)->get();

        if ($reviewers->isNotEmpty()) {
            Notification::send($reviewers, new JoinApplicationReceivedNotification($application));
        }
    }
}
