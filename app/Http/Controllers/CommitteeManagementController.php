<?php

namespace App\Http\Controllers;

use App\Enums\CommitteeCapability;
use App\Enums\CommitteeRole;
use App\Models\Club;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Event;
use App\Models\Post;
use App\Models\User;
use App\Services\CommitteeReportService;
use Inertia\Inertia;
use Inertia\Response;

class CommitteeManagementController extends Controller
{
    public function __construct(
        private readonly CommitteeReportService $reports,
    ) {}

    /**
     * Show the committee management dashboard (Figma 505-1402). Mirrors the club
     * dashboard: any holder of a committee capability (and staff / parent-club
     * leads, who inherit them all) may open it; sections render per capability.
     */
    public function index(Club $club, Committee $committee): Response
    {
        /** @var User $user */
        $user = auth()->user();

        abort_unless($user->canManageCommittee($committee), 403);

        $committee->loadMissing('club:id,name');

        $capabilities = $user->committeeCapabilitiesFor($committee)
            ->map(fn (CommitteeCapability $capability): string => $capability->value)
            ->values()
            ->all();

        $pastEvents = $this->reports->pastEventsForCommittee($committee);
        $eligibleAttendees = $this->reports->eligibleAttendeesForCommittee($committee, $pastEvents);
        $members = $this->reports->committeeMembersForManagement($committee);

        return Inertia::render('committees/Manage', [
            'theme' => ['brand' => $committee->theme ?: ($club->theme ?: config('theme.brand'))],
            'club' => $club->only(['id', 'name', 'theme', 'logo_url']),
            'committee' => [
                ...$committee->only(['id', 'name', 'theme', 'status']),
                'logo_url' => $committee->logo_url,
            ],
            'capabilities' => $capabilities,
            'canManageRoles' => $user->can(CommitteeCapability::ManageCommittee->value, $committee),
            'roleOptions' => collect(CommitteeRole::cases())
                ->map(fn (CommitteeRole $role): array => [
                    'value' => $role->value,
                    'label' => __($role->label()),
                    'isManager' => $role->isManager(),
                ])
                ->values(),
            'pastEvents' => $pastEvents,
            'eligibleAttendees' => $eligibleAttendees,
            'stats' => $this->reports->committeeStats($committee, $members->count()),
            'members' => $members,
            'pendingApplications' => CommitteeMembership::query()
                ->where('committee_id', $committee->id)
                ->where('status', 'pending')
                ->with('user:id,name,email')
                ->latest('requested_at')
                ->get()
                ->map(fn (CommitteeMembership $membership) => [
                    'id' => $membership->id,
                    'name' => $membership->user?->name ?? '',
                    'details' => $membership->user?->email ?? '',
                    'time' => $membership->requested_at?->diffForHumans(),
                ])
                ->values(),
            'managedEvents' => Event::query()
                ->where('committee_id', $committee->id)
                ->withCount('attendances')
                ->orderByDesc('starts_at')
                ->get()
                ->map(fn (Event $event) => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'starts_at' => $event->starts_at?->toIso8601String(),
                    'ends_at' => $event->ends_at?->toIso8601String(),
                    'location' => $event->location,
                    'capacity' => $event->capacity,
                    'status' => $event->status->value,
                    'attendances_count' => $event->attendances_count,
                ])
                ->values(),
            'posts' => Post::query()
                ->where('committee_id', $committee->id)
                ->orderByDesc('published_at')
                ->limit(10)
                ->get()
                ->map(fn (Post $post) => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'published_at' => $post->published_at?->toIso8601String(),
                ])
                ->values(),
        ]);
    }
}
