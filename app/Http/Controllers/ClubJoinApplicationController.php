<?php

namespace App\Http\Controllers;

use App\Enums\ClubCapability;
use App\Enums\ClubRole;
use App\Enums\ClubStatus;
use App\Http\Requests\StoreClubJoinApplicationRequest;
use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\User;
use App\Notifications\JoinApplicationReceivedNotification;
use App\Notifications\MembershipApprovedNotification;
use App\Notifications\MembershipRejectedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class ClubJoinApplicationController extends Controller
{
    /**
     * Show the join application form for a club.
     */
    public function create(Club $club): Response
    {
        if ($club->status !== ClubStatus::Active) {
            abort(404);
        }

        $user = auth()->user();

        abort_unless($user->isStudent(), 403);

        return Inertia::render('ClubJoinForm', [
            'club' => $club->only(['id', 'name']),
            'defaults' => [
                'full_name' => $user?->name ?? '',
                'university_email' => $user?->email ?? '',
            ],
        ]);
    }

    /**
     * Store a new join application.
     */
    public function store(StoreClubJoinApplicationRequest $request, Club $club): RedirectResponse
    {
        $application = ClubJoinApplication::create([
            ...$request->validated(),
            'club_id' => $club->id,
            'user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        $this->notifyReviewers($application);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('join.submitted'),
        ]);

        return redirect()->route('clubs.show', $club);
    }

    public function approve(ClubJoinApplication $application): RedirectResponse
    {
        $user = auth()->user();

        $this->authorize(ClubCapability::ManageMembers->value, $application->club);

        $application->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $user->id,
        ]);

        $membership = ClubMembership::updateOrCreate(
            [
                'user_id' => $application->user_id,
                'club_id' => $application->club_id,
            ],
            [
                'status' => 'approved',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'joined_at' => now(),
            ]
        );

        $membership->assignClubRole(ClubRole::Member);

        $application->user->notify(new MembershipApprovedNotification($application->club));

        return back();
    }

    /**
     * Reject a pending join application.
     */
    public function reject(ClubJoinApplication $application): RedirectResponse
    {
        $user = auth()->user();

        $this->authorize(ClubCapability::ManageMembers->value, $application->club);

        $application->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $user->id,
        ]);

        ClubMembership::where('user_id', $application->user_id)
            ->where('club_id', $application->club_id)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

        $application->user->notify(new MembershipRejectedNotification($application->club));

        return back();
    }

    /**
     * Notify everyone who can review members for the club that a new
     * application is awaiting their decision (per the membership BPMN).
     */
    private function notifyReviewers(ClubJoinApplication $application): void
    {
        $reviewerIds = ClubMembership::query()
            ->where('club_id', $application->club_id)
            ->where('status', 'approved')
            ->whereHas('roles', fn ($query) => $query->whereIn(
                'role',
                ClubRole::valuesWithCapability(ClubCapability::ManageMembers),
            ))
            ->pluck('user_id')
            ->unique();

        $reviewers = User::whereIn('id', $reviewerIds)->get();

        if ($reviewers->isNotEmpty()) {
            Notification::send($reviewers, new JoinApplicationReceivedNotification($application));
        }
    }
}
