<?php

namespace App\Http\Controllers;

use App\Concerns\SyncsImageUploads;
use App\Enums\ClubCapability;
use App\Enums\CommitteeCapability;
use App\Enums\EventAttendanceStatus;
use App\Enums\EventStatus;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Club;
use App\Models\Committee;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use App\Notifications\EventCancelledNotification;
use App\Notifications\EventUpdatedNotification;
use App\Notifications\RsvpConfirmationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    use SyncsImageUploads;

    /**
     * Show a single event with registration details.
     */
    public function show(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);

        $event->load('club:id,name,category,college', 'media');

        /** @var User|null $user */
        $user = $request->user();

        $isRegistered = $user !== null
            && $event->attendances()
                ->where('user_id', $user->id)
                ->whereIn('status', EventAttendanceStatus::registeredValues())
                ->exists();

        $canManage = $user?->can(ClubCapability::ManageEvents->value, $event->club) ?? false;

        // Show a scan shortcut to attendance scanners while the activity is live.
        $canScan = ($user?->can(ClubCapability::RecordAttendance->value, $event->club) ?? false)
            && $event->isScannable();

        return Inertia::render('EventDetailPage', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'ends_at' => $event->ends_at?->toIso8601String(),
                'location' => $event->location,
                'capacity' => $event->capacity,
                'status' => $event->status->value,
                'registrations_count' => $event->registeredCount(),
                'is_full' => $event->isFull(),
                'is_open' => $event->isOpenForRegistration(),
                'images' => $event->imageUrls(),
                'club' => [
                    'id' => $event->club->id,
                    'name' => $event->club->name,
                    'category' => $event->club->category,
                    'college' => $event->club->college,
                ],
            ],
            'isRegistered' => $isRegistered,
            'canManage' => $canManage,
            'canScan' => $canScan,
        ]);
    }

    /**
     * Show the form to create a new event for a club, or for a committee within
     * it when a {committee} is bound on the route.
     */
    public function create(Request $request, Club $club, ?Committee $committee = null): Response
    {
        $this->authorizeManageEvents($club, $committee);

        return Inertia::render('EventForm', [
            'club' => $club->only(['id', 'name']),
            'committee' => $committee?->only(['id', 'name']),
            'mode' => 'create',
        ]);
    }

    /**
     * Store a newly created event for a club or committee.
     */
    public function store(StoreEventRequest $request, Club $club, ?Committee $committee = null): RedirectResponse
    {
        $event = Event::create([
            ...$request->safe()->except(['images', 'removed_media']),
            'club_id' => $club->id,
            'committee_id' => $committee?->id,
            'status' => $request->validated('status') ?? EventStatus::Active->value,
        ]);

        $this->syncImageGallery($event, Event::IMAGE_COLLECTION, $request->file('images', []));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('events.created'),
        ]);

        return $this->manageRedirect($club, $committee);
    }

    /**
     * Show the form to edit an existing event.
     */
    public function edit(Request $request, Club $club, ?Committee $committee = null, ?Event $event = null): Response
    {
        $this->authorizeManageEvents($club, $committee);

        $this->ensureEventScope($event, $club, $committee);

        $event->load('media');

        return Inertia::render('EventForm', [
            'club' => $club->only(['id', 'name']),
            'committee' => $committee?->only(['id', 'name']),
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'ends_at' => $event->ends_at?->toIso8601String(),
                'location' => $event->location,
                'capacity' => $event->capacity,
                'status' => $event->status->value,
                'images' => $event->imageGallery(),
            ],
            'mode' => 'edit',
        ]);
    }

    /**
     * Update an existing event.
     */
    public function update(UpdateEventRequest $request, Club $club, ?Committee $committee = null, ?Event $event = null): RedirectResponse
    {
        $this->authorizeManageEvents($club, $committee);

        $this->ensureEventScope($event, $club, $committee);

        $original = [
            'status' => $event->status,
            'starts_at' => $event->starts_at?->toIso8601String(),
            'ends_at' => $event->ends_at?->toIso8601String(),
            'location' => $event->location,
        ];

        $event->update($request->safe()->except(['images', 'removed_media']));

        $this->syncImageGallery(
            $event,
            Event::IMAGE_COLLECTION,
            $request->file('images', []),
            $request->input('removed_media', []),
        );

        $this->notifyAttendeesOfChanges($event, $original);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('events.updated'),
        ]);

        return $this->manageRedirect($club, $committee);
    }

    /**
     * Notify registered attendees when an event is cancelled or its schedule /
     * location changes, so they are never left with stale plans.
     *
     * @param  array{status: EventStatus, starts_at: ?string, ends_at: ?string, location: ?string}  $original
     */
    private function notifyAttendeesOfChanges(Event $event, array $original): void
    {
        $becameCancelled = $original['status'] !== EventStatus::Cancelled
            && $event->status === EventStatus::Cancelled;

        $scheduleChanged = $original['starts_at'] !== $event->starts_at?->toIso8601String()
            || $original['ends_at'] !== $event->ends_at?->toIso8601String()
            || $original['location'] !== $event->location;

        if (! $becameCancelled && ! $scheduleChanged) {
            return;
        }

        $recipients = User::whereIn(
            'id',
            $event->attendances()
                ->whereIn('status', EventAttendanceStatus::registeredValues())
                ->pluck('user_id'),
        )->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            $becameCancelled
                ? new EventCancelledNotification($event)
                : new EventUpdatedNotification($event),
        );
    }

    /**
     * Delete an event.
     */
    public function destroy(Request $request, Club $club, ?Committee $committee = null, ?Event $event = null): RedirectResponse
    {
        $this->authorizeManageEvents($club, $committee);

        $this->ensureEventScope($event, $club, $committee);

        $event->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('events.deleted'),
        ]);

        return $this->manageRedirect($club, $committee);
    }

    /**
     * Authorize event management against the committee when one is bound,
     * otherwise against the club.
     */
    private function authorizeManageEvents(Club $club, ?Committee $committee): void
    {
        $this->authorize(
            $committee !== null ? CommitteeCapability::ManageEvents->value : ClubCapability::ManageEvents->value,
            $committee ?? $club,
        );
    }

    /**
     * Ensure the event belongs to the bound club (and committee, when present),
     * preventing cross-club/committee tampering via mismatched route ids.
     */
    private function ensureEventScope(?Event $event, Club $club, ?Committee $committee): void
    {
        abort_if($event === null, 404);
        abort_unless($event->club_id === $club->id, 404);

        if ($committee !== null) {
            abort_unless($event->committee_id === $committee->id, 404);
        }
    }

    /**
     * Redirect back to the committee dashboard when managing a committee event,
     * otherwise the club dashboard.
     */
    private function manageRedirect(Club $club, ?Committee $committee): RedirectResponse
    {
        return $committee !== null
            ? redirect()->route('committees.manage', [$club, $committee])
            : redirect()->route('clubs.manage', $club);
    }

    /**
     * Register the authenticated user for an event (RSVP).
     */
    public function rsvp(Request $request, Event $event): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isStudent()) {
            abort(403);
        }

        if (! $event->isOpenForRegistration()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('events.rsvp_event_not_available'),
            ]);

            return back();
        }

        $existingAttendance = EventAttendance::query()
            ->where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        if ($existingAttendance === null && $event->isFull()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('events.rsvp_capacity_full'),
            ]);

            return back();
        }

        $attendance = EventAttendance::updateOrCreate(
            ['user_id' => $user->id, 'event_id' => $event->id],
            ['status' => EventAttendanceStatus::Registered->value]
        );

        // Confirm only a fresh registration, not a no-op re-RSVP (REQ-15).
        if ($attendance->wasRecentlyCreated) {
            $user->notify(new RsvpConfirmationNotification($event));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('events.rsvp_success'),
        ]);

        return back();
    }

    /**
     * Cancel the authenticated user's RSVP for an event.
     */
    public function cancelRsvp(Request $request, Event $event): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isStudent()) {
            abort(403);
        }

        if ($event->starts_at?->isPast()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('events.rsvp_cancel_past_event'),
            ]);

            return back();
        }

        EventAttendance::query()
            ->where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('events.rsvp_cancelled'),
        ]);

        return back();
    }
}
