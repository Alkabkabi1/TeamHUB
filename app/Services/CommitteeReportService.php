<?php

namespace App\Services;

use App\Enums\CommitteeRole;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\VolunteerHour;
use Illuminate\Support\Collection;

/**
 * Committee-scoped reporting, mirroring {@see ClubSupervisorReportService} but
 * filtering events by `committee_id` and members via `committee_memberships`.
 */
class CommitteeReportService
{
    /**
     * @return array{committeeName: string, clubName: string, generatedAt: string, locale: string, supervisorName: string|null}
     */
    public function reportMeta(Committee $committee, string $locale, ?string $supervisorName = null): array
    {
        $clubName = $committee->club?->name;

        return [
            'committeeName' => $committee->name,
            // Shared club report blade views render `clubName` as the header;
            // qualify it with the committee so reused PDFs read correctly.
            'clubName' => $clubName !== null ? "{$clubName} – {$committee->name}" : $committee->name,
            'generatedAt' => now()->locale($locale)->translatedFormat('d F Y H:i'),
            'locale' => $locale,
            'supervisorName' => $supervisorName,
        ];
    }

    /**
     * @return Collection<int, array{id: int, title: string, startsAt: string|null}>
     */
    public function pastEventsForCommittee(Committee $committee): Collection
    {
        return Event::query()
            ->where('committee_id', $committee->id)
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
     * @return Collection<int, array<string, mixed>>
     */
    public function eligibleAttendeesForCommittee(Committee $committee, Collection $pastEvents): Collection
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
            ->with(['user:id,name,email', 'event:id,committee_id,title', 'certificate:id,event_attendance_id'])
            ->get()
            ->map(function (EventAttendance $attendance) use ($existingHoursByKey) {
                $existing = $existingHoursByKey->get("{$attendance->user_id}:{$attendance->event_id}");

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
     * Every approved membership of the committee enriched for the management
     * dashboard's member table (roles, join date, volunteer hours).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function committeeMembersForManagement(Committee $committee, ?string $locale = null): Collection
    {
        $locale = $locale ?? app()->getLocale();

        $memberships = CommitteeMembership::query()
            ->where('committee_id', $committee->id)
            ->where('status', 'approved')
            ->with(['user:id,name,email', 'roles'])
            ->orderBy('joined_at')
            ->get();

        $memberUserIds = $memberships->pluck('user_id')->filter()->all();

        $hoursByUserId = $memberUserIds === []
            ? collect()
            : VolunteerHour::query()
                ->selectRaw('volunteer_hours.user_id, SUM(volunteer_hours.hours) as hours_sum')
                ->join('events', 'events.id', '=', 'volunteer_hours.event_id')
                ->where('events.committee_id', $committee->id)
                ->whereIn('volunteer_hours.user_id', $memberUserIds)
                ->groupBy('volunteer_hours.user_id')
                ->pluck('hours_sum', 'user_id');

        return $memberships
            ->filter(fn (CommitteeMembership $membership) => $membership->user !== null)
            ->map(function (CommitteeMembership $membership) use ($hoursByUserId, $locale) {
                $roles = $membership->committeeRoles();

                return [
                    'membershipId' => $membership->id,
                    'userId' => $membership->user_id,
                    'name' => $membership->user->name,
                    'email' => $membership->user->email,
                    // Committees don't collect a major; kept for shared report view compatibility.
                    'major' => '',
                    'joinDate' => $membership->joined_at?->locale($locale)->translatedFormat('F Y') ?? '',
                    'volunteerHours' => (float) ($hoursByUserId[$membership->user_id] ?? 0),
                    'roles' => $roles->map(fn (CommitteeRole $role): string => $role->value)->values()->all(),
                    'isManager' => $roles->contains(fn (CommitteeRole $role): bool => $role->isManager()),
                    'status' => __('dashboard.status_active'),
                ];
            })
            ->values();
    }

    /**
     * @return array{totalHours: float, pendingApplicationsCount: int, upcomingEventsCount: int, membersCount: int}
     */
    public function committeeStats(Committee $committee, int $membersCount): array
    {
        return [
            'totalHours' => (float) VolunteerHour::query()
                ->whereHas('event', fn ($query) => $query->where('committee_id', $committee->id))
                ->sum('hours'),
            'pendingApplicationsCount' => CommitteeMembership::query()
                ->where('committee_id', $committee->id)
                ->where('status', 'pending')
                ->count(),
            'upcomingEventsCount' => Event::query()
                ->where('committee_id', $committee->id)
                ->where('starts_at', '>=', now())
                ->where('status', 'active')
                ->count(),
            'membersCount' => $membersCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function membersReport(Committee $committee, string $locale, ?string $supervisorName = null): array
    {
        $members = $this->committeeMembersForManagement($committee, $locale);

        return array_merge($this->reportMeta($committee, $locale, $supervisorName), [
            'members' => $members,
            'totalHours' => $members->sum('volunteerHours'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function volunteerHoursReport(Committee $committee, string $locale, ?string $supervisorName = null): array
    {
        $rows = VolunteerHour::query()
            ->whereHas('event', fn ($query) => $query->where('committee_id', $committee->id))
            ->with(['user:id,name,email', 'event:id,title,starts_at'])
            ->orderByDesc('approved_at')
            ->get()
            ->map(fn (VolunteerHour $record) => [
                'memberName' => $record->user?->name ?? '',
                'memberEmail' => $record->user?->email ?? '',
                'eventTitle' => $record->event?->title ?? '',
                'eventDate' => $record->event?->starts_at?->locale($locale)->translatedFormat('d F Y') ?? '',
                'hours' => (float) $record->hours,
                'approvedAt' => $record->approved_at?->locale($locale)->translatedFormat('d F Y') ?? '',
            ])
            ->values();

        return array_merge($this->reportMeta($committee, $locale, $supervisorName), [
            'rows' => $rows,
            'totalHours' => (float) $rows->sum('hours'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function attendanceReport(Committee $committee, string $locale, ?string $supervisorName = null): array
    {
        $events = Event::query()
            ->where('committee_id', $committee->id)
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

        return array_merge($this->reportMeta($committee, $locale, $supervisorName), [
            'events' => $eventBlocks,
            'totalAttendees' => $eventBlocks->sum('attendeeCount'),
        ]);
    }
}
