<?php

namespace App\Services;

use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use App\Models\VolunteerHour;
use Illuminate\Support\Collection;

class ClubSupervisorReportService
{
    /**
     * @return array{
     *     clubName: string,
     *     generatedAt: string,
     *     locale: string,
     *     supervisorName: string|null
     * }
     */
    public function reportMeta(Club $club, string $locale, ?User $supervisor = null): array
    {
        return [
            'clubName' => $club->name,
            'generatedAt' => now()->locale($locale)->translatedFormat('d F Y H:i'),
            'locale' => $locale,
            'supervisorName' => $supervisor?->name,
        ];
    }

    public function supervisedClub(User $user): ?Club
    {
        return $user->managedClub();
    }

    /**
     * @return Collection<int, array{id: int, title: string, startsAt: string|null}>
     */
    public function pastEventsForClub(Club $club): Collection
    {
        return Event::query()
            ->where('club_id', $club->id)
            ->where('starts_at', '<', now())
            ->orderByDesc('starts_at')
            ->get(['id', 'title', 'starts_at'])
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'startsAt' => $event->starts_at?->toIso8601String(),
            ])
            ->values();
    }

    /**
     * @param  Collection<int, array{id: int, title: string, startsAt: string|null}>  $pastEvents
     * @return Collection<int, array{eventId: int, userId: int, userName: string, userEmail: string, existingHours: float|null}>
     */
    public function eligibleAttendeesForClub(Club $club, Collection $pastEvents): Collection
    {
        $pastEventIds = $pastEvents->pluck('id')->all();

        if ($pastEventIds === []) {
            return collect();
        }

        $existingHoursByKey = VolunteerHour::query()
            ->whereIn('event_id', $pastEventIds)
            ->get(['user_id', 'event_id', 'hours'])
            ->keyBy(fn (VolunteerHour $record) => "{$record->user_id}:{$record->event_id}");

        return EventAttendance::query()
            ->whereIn('status', ['checked_in', 'approved'])
            ->whereIn('event_id', $pastEventIds)
            ->with(['user:id,name,email', 'event:id,club_id,title', 'certificate:id,event_attendance_id'])
            ->whereHas('event', fn ($query) => $query->where('club_id', $club->id))
            ->get()
            ->map(function (EventAttendance $attendance) use ($existingHoursByKey) {
                $key = "{$attendance->user_id}:{$attendance->event_id}";
                $existing = $existingHoursByKey->get($key);

                return [
                    'attendanceId' => $attendance->id,
                    'eventId' => $attendance->event_id,
                    'eventTitle' => $attendance->event?->title ?? '',
                    'userId' => $attendance->user_id,
                    'userName' => $attendance->user?->name ?? '',
                    'userEmail' => $attendance->user?->email ?? '',
                    'existingHours' => $existing !== null ? (float) $existing->hours : null,
                    'certificateId' => $attendance->certificate?->id,
                    'hasCertificate' => $attendance->certificate !== null,
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array{name: string, email: string, major: string, joinDate: string, volunteerHours: float, status: string}>
     */
    public function membersForClub(Club $club, ?string $locale = null): Collection
    {
        $locale = $locale ?? app()->getLocale();

        $memberships = ClubMembership::query()
            ->where('club_id', $club->id)
            ->where('status', 'approved')
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('role', ClubRole::managerRoleValues()))
            ->with('user:id,name,email')
            ->orderBy('joined_at')
            ->get();

        $memberUserIds = $memberships->pluck('user_id')->filter()->all();

        $hoursByUserId = $memberUserIds === []
            ? collect()
            : VolunteerHour::query()
                ->selectRaw('user_id, SUM(hours) as hours_sum')
                ->where('club_id', $club->id)
                ->whereIn('user_id', $memberUserIds)
                ->groupBy('user_id')
                ->pluck('hours_sum', 'user_id');

        $applicationsByUserId = $this->latestApprovedApplicationsByUserId($club->id, $memberUserIds);

        return $memberships
            ->filter(fn (ClubMembership $membership) => $membership->user !== null)
            ->map(function (ClubMembership $membership) use ($hoursByUserId, $applicationsByUserId, $locale) {
                $application = $applicationsByUserId->get($membership->user_id);

                return [
                    'name' => $membership->user->name,
                    'email' => $membership->user->email,
                    'major' => $this->applicationMajorLabel($application),
                    'joinDate' => $membership->joined_at?->locale($locale)->translatedFormat('F Y') ?? '',
                    'volunteerHours' => (float) ($hoursByUserId[$membership->user_id] ?? 0),
                    'status' => __('dashboard.status_active'),
                ];
            })
            ->values();
    }

    /**
     * Every approved membership of the club — including managers — enriched with
     * the data the management dashboard's member table needs (roles, major,
     * volunteer hours, join date). Unlike {@see membersForClub()} this does NOT
     * hide managers, so a club lead can review and adjust their roles.
     *
     * @return Collection<int, array{
     *     membershipId: int,
     *     userId: int,
     *     name: string,
     *     email: string,
     *     major: string,
     *     joinDate: string,
     *     volunteerHours: float,
     *     roles: array<int, string>,
     *     isManager: bool,
     *     status: string
     * }>
     */
    public function clubMembersForManagement(Club $club, ?string $locale = null): Collection
    {
        $locale = $locale ?? app()->getLocale();

        $memberships = ClubMembership::query()
            ->where('club_id', $club->id)
            ->where('status', 'approved')
            ->with(['user:id,name,email', 'roles'])
            ->orderBy('joined_at')
            ->get();

        $memberUserIds = $memberships->pluck('user_id')->filter()->all();

        $hoursByUserId = $memberUserIds === []
            ? collect()
            : VolunteerHour::query()
                ->selectRaw('user_id, SUM(hours) as hours_sum')
                ->where('club_id', $club->id)
                ->whereIn('user_id', $memberUserIds)
                ->groupBy('user_id')
                ->pluck('hours_sum', 'user_id');

        $applicationsByUserId = $this->latestApprovedApplicationsByUserId($club->id, $memberUserIds);

        return $memberships
            ->filter(fn (ClubMembership $membership) => $membership->user !== null)
            ->map(function (ClubMembership $membership) use ($hoursByUserId, $applicationsByUserId, $locale) {
                $roles = $membership->clubRoles();

                return [
                    'membershipId' => $membership->id,
                    'userId' => $membership->user_id,
                    'name' => $membership->user->name,
                    'email' => $membership->user->email,
                    'major' => $this->applicationMajorLabel($applicationsByUserId->get($membership->user_id)),
                    'joinDate' => $membership->joined_at?->locale($locale)->translatedFormat('F Y') ?? '',
                    'volunteerHours' => (float) ($hoursByUserId[$membership->user_id] ?? 0),
                    'roles' => $roles->map(fn (ClubRole $role): string => $role->value)->values()->all(),
                    'isManager' => $roles->contains(fn (ClubRole $role): bool => $role->isManager()),
                    'status' => __('dashboard.status_active'),
                ];
            })
            ->values();
    }

    /**
     * @return array{
     *     totalHours: float,
     *     pendingApplicationsCount: int,
     *     upcomingEventsCount: int,
     *     membersCount: int
     * }
     */
    public function clubStats(Club $club, int $membersCount): array
    {
        return [
            'totalHours' => (float) VolunteerHour::query()
                ->whereHas('event', fn ($query) => $query->where('club_id', $club->id))
                ->sum('hours'),
            'pendingApplicationsCount' => ClubJoinApplication::query()
                ->where('club_id', $club->id)
                ->where('status', 'pending')
                ->count(),
            'upcomingEventsCount' => Event::query()
                ->where('club_id', $club->id)
                ->where('starts_at', '>=', now())
                ->where('status', 'active')
                ->count(),
            'membersCount' => $membersCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function membersReport(Club $club, string $locale, ?User $supervisor = null): array
    {
        $members = $this->membersForClub($club, $locale);

        return array_merge($this->reportMeta($club, $locale, $supervisor), [
            'members' => $members,
            'totalHours' => $members->sum('volunteerHours'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function volunteerHoursReport(Club $club, string $locale, ?User $supervisor = null): array
    {
        $rows = VolunteerHour::query()
            ->where('club_id', $club->id)
            ->with(['user:id,name,email', 'event:id,title,starts_at'])
            ->orderByDesc('approved_at')
            ->get()
            ->map(function (VolunteerHour $record) use ($locale) {
                return [
                    'memberName' => $record->user?->name ?? '',
                    'memberEmail' => $record->user?->email ?? '',
                    'eventTitle' => $record->event?->title ?? __('dashboard_supervisor.no_linked_event'),
                    'eventDate' => $record->event?->starts_at?->locale($locale)->translatedFormat('d F Y') ?? '',
                    'hours' => (float) $record->hours,
                    'approvedAt' => $record->approved_at?->locale($locale)->translatedFormat('d F Y') ?? '',
                ];
            })
            ->values();

        return array_merge($this->reportMeta($club, $locale, $supervisor), [
            'rows' => $rows,
            'totalHours' => (float) $rows->sum('hours'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function attendanceReport(Club $club, string $locale, ?User $supervisor = null): array
    {
        $events = Event::query()
            ->where('club_id', $club->id)
            ->where('starts_at', '<', now())
            ->orderByDesc('starts_at')
            ->get(['id', 'title', 'starts_at']);

        $eventBlocks = $events->map(function (Event $event) use ($locale) {
            $attendees = EventAttendance::query()
                ->where('event_id', $event->id)
                ->whereIn('status', ['checked_in', 'approved'])
                ->with('user:id,name,email')
                ->orderBy('checked_in_at')
                ->get()
                ->map(fn (EventAttendance $attendance) => [
                    'name' => $attendance->user?->name ?? '',
                    'email' => $attendance->user?->email ?? '',
                    'status' => $attendance->status,
                ])
                ->values();

            return [
                'title' => $event->title,
                'date' => $event->starts_at?->locale($locale)->translatedFormat('d F Y H:i') ?? '',
                'attendees' => $attendees,
                'attendeeCount' => $attendees->count(),
            ];
        })->values();

        return array_merge($this->reportMeta($club, $locale, $supervisor), [
            'events' => $eventBlocks,
            'totalAttendees' => $eventBlocks->sum('attendeeCount'),
        ]);
    }

    /**
     * @param  array<int, int>  $userIds
     * @return Collection<int, ClubJoinApplication>
     */
    private function latestApprovedApplicationsByUserId(int $clubId, array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        return ClubJoinApplication::query()
            ->where('club_id', $clubId)
            ->whereIn('user_id', $userIds)
            ->where('status', 'approved')
            ->orderByDesc('reviewed_at')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');
    }

    private function applicationMajorLabel(?ClubJoinApplication $application): string
    {
        if ($application === null) {
            return '';
        }

        $parts = array_filter([$application->major, $application->level]);

        return implode(' - ', $parts);
    }
}
