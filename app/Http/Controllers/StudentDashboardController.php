<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\Event;
use App\Models\User;
use App\Models\VolunteerHour;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    /**
     * Show the student dashboard with volunteer hours and club data from the database.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isStudent()) {
            abort(403);
        }

        $totalHours = (float) $user->volunteerHours()->sum('hours');

        $memberships = $user->clubMemberships()
            ->where('status', 'approved')
            ->with('club:id,name')
            ->orderBy('joined_at')
            ->get();

        $clubIds = $memberships->pluck('club_id')->filter()->all();

        $hoursByClubId = $clubIds === []
            ? collect()
            : VolunteerHour::query()
                ->selectRaw('club_id, SUM(hours) as hours_sum')
                ->where('user_id', $user->id)
                ->whereIn('club_id', $clubIds)
                ->groupBy('club_id')
                ->pluck('hours_sum', 'club_id');

        $clubs = $memberships
            ->filter(fn (ClubMembership $membership) => $membership->club !== null)
            ->map(fn (ClubMembership $membership) => [
                'name' => $membership->club->name,
                'memberSince' => $membership->joined_at?->format('Y') ?? '',
                'volunteerHours' => (float) ($hoursByClubId[$membership->club_id] ?? 0),
            ])
            ->values();

        $latestApplication = $user->joinApplications()
            ->where('status', 'approved')
            ->latest('reviewed_at')
            ->first();

        $certificates = $user->certificates()
            ->with(['event', 'club'])
            ->orderByDesc('issued_at')
            ->get()
            ->map(fn (Certificate $cert) => [
                'id' => $cert->id,
                'certificateNo' => $cert->certificate_no,
                'eventTitle' => $cert->event?->title ?? $cert->title ?? '',
                'clubName' => $cert->club?->name ?? '',
                'issuedAt' => $cert->issued_at?->format('d/m/Y') ?? '',
            ])
            ->values();

        $featuredEvents = Event::query()
            ->with(['club:id,name', 'media'])
            ->upcoming()
            ->active()
            ->orderBy('starts_at')
            ->limit(2)
            ->get(['id', 'club_id', 'title', 'description', 'starts_at'])
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'startsAt' => $event->starts_at?->toIso8601String(),
                'clubName' => $event->club?->name ?? '',
                'imageUrl' => $event->coverImageUrl(),
            ])
            ->values();

        return Inertia::render('StudentDashboard', [
            'totalHours' => $totalHours,
            'stats' => [
                'clubsCount' => $memberships->count(),
                'eventsCount' => $user->eventAttendances()
                    ->whereIn('status', ['approved', 'checked_in'])
                    ->count(),
                'certificatesCount' => $certificates->count(),
                'totalHours' => $totalHours,
            ],
            'clubs' => $clubs,
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'subtitle' => $this->profileSubtitle($latestApplication),
                'joinedAt' => $user->created_at?->toIso8601String(),
            ],
            'certificates' => $certificates,
            'featuredEvents' => $featuredEvents,
            // Personal attendance QR a club scanner reads to log the student's presence.
            'qrSvg' => $user->attendanceQrSvg(),
        ]);
    }

    private function profileSubtitle(?ClubJoinApplication $application): string
    {
        if ($application === null) {
            return '';
        }

        $parts = array_filter([$application->major, $application->level]);

        return implode(' - ', $parts);
    }
}
