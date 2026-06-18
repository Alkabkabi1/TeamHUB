<?php

namespace App\Http\Controllers;

use App\Enums\CommitteeCapability;
use App\Enums\CommitteeRole;
use App\Models\Club;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommitteeMembershipController extends Controller
{
    /**
     * A student requests to join a committee. Only approved members of the
     * parent club may request, and only once.
     */
    public function store(Request $request, Club $club, Committee $committee): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->isStudent(), 403);
        abort_unless($user->clubMembershipFor($club) !== null, 403, __('committee_members.must_be_club_member'));

        $existing = CommitteeMembership::query()
            ->where('committee_id', $committee->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($existing) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('committee_members.already_requested')]);

            return back();
        }

        CommitteeMembership::create([
            'committee_id' => $committee->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('committee_members.request_sent')]);

        return back();
    }

    /**
     * Approve a pending join request.
     */
    public function approve(Request $request, Club $club, Committee $committee, CommitteeMembership $membership): RedirectResponse
    {
        $this->authorize(CommitteeCapability::ManageMembers->value, $committee);

        abort_unless($membership->committee_id === $committee->id, 404);

        $membership->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'joined_at' => now(),
            'rejection_reason' => null,
        ]);

        $membership->assignCommitteeRole(CommitteeRole::Member);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('committee_members.request_approved')]);

        return back();
    }

    /**
     * Reject a pending join request.
     */
    public function reject(Request $request, Club $club, Committee $committee, CommitteeMembership $membership): RedirectResponse
    {
        $this->authorize(CommitteeCapability::ManageMembers->value, $committee);

        abort_unless($membership->committee_id === $committee->id, 404);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $membership->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['reason'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('committee_members.request_rejected')]);

        return back();
    }
}
