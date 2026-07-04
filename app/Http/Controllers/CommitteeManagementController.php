<?php

namespace App\Http\Controllers;

use App\Enums\CommitteeCapability;
use App\Enums\CommitteeRole;
use App\Models\Club;
use App\Models\ClubResource;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Event;
use App\Models\Post;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use App\Services\CommitteeReportService;
use Illuminate\Support\Facades\Storage;
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

        return Inertia::render('committees/Manage', $this->managementPayload($club, $committee, $user));
    }

    public function files(Club $club, Committee $committee): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $this->authorizeProjectView($user, $committee);

        return Inertia::render('committees/Files', [
            'theme' => ['brand' => $committee->theme ?: ($club->theme ?: config('theme.brand'))],
            'club' => $club->only(['id', 'name', 'theme', 'logo_url']),
            'committee' => [
                ...$committee->only(['id', 'name', 'theme', 'status']),
                'logo_url' => $committee->logo_url,
            ],
            'canManageFiles' => $user->can(CommitteeCapability::ManageCommittee->value, $committee),
            'files' => ClubResource::query()
                ->forCommittee($committee)
                ->latest('published_at')
                ->get()
                ->map(fn (ClubResource $resource): array => [
                    'id' => $resource->id,
                    'title' => $resource->title,
                    'description' => $resource->description,
                    'type' => $resource->type,
                    'format' => $resource->format,
                    'access' => $resource->access,
                    'published_at' => $resource->published_at?->toIso8601String(),
                    'download_url' => $resource->file_path ? Storage::disk('public')->url($resource->file_path) : null,
                ])
                ->values(),
        ]);
    }

    public function updates(Club $club, Committee $committee): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $this->authorizeProjectView($user, $committee);

        return Inertia::render('committees/Updates', [
            'theme' => ['brand' => $committee->theme ?: ($club->theme ?: config('theme.brand'))],
            'club' => $club->only(['id', 'name', 'theme', 'logo_url']),
            'committee' => [
                ...$committee->only(['id', 'name', 'theme', 'status']),
                'logo_url' => $committee->logo_url,
            ],
            'canManageUpdates' => $user->can(CommitteeCapability::ManageNews->value, $committee),
            'updates' => Post::query()
                ->where('committee_id', $committee->id)
                ->latest('published_at')
                ->get()
                ->map(fn (Post $post): array => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'excerpt' => mb_substr(strip_tags((string) $post->body), 0, 180),
                    'published_at' => $post->published_at?->toIso8601String(),
                ])
                ->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function managementPayload(Club $club, Committee $committee, User $user): array
    {
        $committee->loadMissing('club:id,name');

        $capabilities = $user->committeeCapabilitiesFor($committee)
            ->map(fn (CommitteeCapability $capability): string => $capability->value)
            ->values()
            ->all();

        $pastEvents = $this->reports->pastEventsForCommittee($committee);
        $eligibleAttendees = $this->reports->eligibleAttendeesForCommittee($committee, $pastEvents);
        $members = $this->reports->committeeMembersForManagement($committee);

        $taskStats = [
            'todo' => Task::query()->where('committee_id', $committee->id)->where('status', 'todo')->count(),
            'in_progress' => Task::query()->where('committee_id', $committee->id)->where('status', 'in_progress')->count(),
            'review' => Task::query()->where('committee_id', $committee->id)->where('status', 'review')->count(),
            'done' => Task::query()->where('committee_id', $committee->id)->where('status', 'done')->count(),
            'overdue' => Task::query()
                ->where('committee_id', $committee->id)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->whereNotIn('status', ['done'])
                ->count(),
        ];

        return [
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
            'taskStats' => $taskStats,
            'overviewMembers' => $members->take(8)->values(),
            'recentUpdates' => Post::query()
                ->where('committee_id', $committee->id)
                ->orderByDesc('published_at')
                ->limit(5)
                ->get()
                ->map(fn (Post $post): array => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'published_at' => $post->published_at?->toIso8601String(),
                ])
                ->values(),
            'recentActivities' => TaskActivity::query()
                ->whereHas('task', fn ($query) => $query->where('committee_id', $committee->id))
                ->with(['task:id,title,committee_id', 'user:id,name'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (TaskActivity $activity): array => [
                    'id' => $activity->id,
                    'message' => $activity->message(),
                    'created_at' => $activity->created_at?->toIso8601String(),
                    'task_title' => $activity->task?->title ?? '',
                    'task_url' => route('committees.tasks.show', [$club, $committee, $activity->task_id], absolute: false),
                ])
                ->values(),
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
        ];
    }

    private function authorizeProjectView(User $user, Committee $committee): void
    {
        abort_unless(
            $user->canManageCommittee($committee) || $user->committeeMembershipFor($committee) !== null,
            403,
        );
    }
}
