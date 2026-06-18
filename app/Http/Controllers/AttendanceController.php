<?php

namespace App\Http\Controllers;

use App\Enums\ClubCapability;
use App\Enums\EventAttendanceStatus;
use App\Models\Club;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use App\Notifications\CertificateIssuedNotification;
use App\Services\CertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly CertificateService $certificates,
    ) {}

    /**
     * Show the QR attendance scanner for an activity. Restricted to club
     * members holding the Attendance Scanner capability (and university staff).
     */
    public function scan(Club $club, Event $event): Response
    {
        $this->authorize(ClubCapability::RecordAttendance->value, $club);

        if ($event->club_id !== $club->id) {
            abort(404);
        }

        return Inertia::render('events/Scan', [
            'club' => $club->only(['id', 'name']),
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'ends_at' => $event->ends_at?->toIso8601String(),
                'location' => $event->location,
            ],
            'registered' => $this->registeredAttendees($event),
        ]);
    }

    /**
     * Record the scanned student's attendance for the current calendar day.
     * Idempotent per day; a multi-day activity accrues one check-in per day.
     */
    public function checkIn(Request $request, Club $club, Event $event): JsonResponse
    {
        $this->authorize(ClubCapability::RecordAttendance->value, $club);

        if ($event->club_id !== $club->id) {
            abort(404);
        }

        $validated = $request->validate([
            'qr_token' => ['required', 'string'],
        ]);

        $student = User::query()->where('qr_token', $validated['qr_token'])->first();

        if ($student === null) {
            return response()->json(['result' => 'invalid']);
        }

        // A student need not have registered: walk-ins are admitted by creating
        // their attendance record on the spot rather than turning them away.
        $attendance = EventAttendance::query()
            ->where('user_id', $student->id)
            ->where('event_id', $event->id)
            ->first();

        $isWalkIn = $attendance === null;

        if ($isWalkIn) {
            $attendance = $event->attendances()->create([
                'user_id' => $student->id,
                'status' => EventAttendanceStatus::CheckedIn->value,
                'checked_in_at' => now(),
            ]);
        }

        $today = now()->toDateString();

        $existing = $attendance->checkins()->whereDate('attended_on', $today)->exists();

        if (! $existing) {
            $attendance->checkins()->create([
                'attended_on' => $today,
                'checked_in_at' => now(),
                'recorded_by' => $request->user()->id,
            ]);
        }

        // Promote the registration to "checked in" so existing eligibility
        // (certificates, volunteer hours, reports) recognises the attendance.
        if ($attendance->status !== EventAttendanceStatus::CheckedIn->value) {
            $attendance->update([
                'status' => EventAttendanceStatus::CheckedIn->value,
                'checked_in_at' => $attendance->checked_in_at ?? now(),
            ]);
        }

        $this->autoIssueCertificate($attendance, $student, $event, $club);

        return response()->json([
            'result' => $existing ? 'already_today' : 'checked_in',
            'studentName' => $student->name,
            'userId' => $student->id,
            'wasWalkIn' => $isWalkIn,
            'daysAttended' => $attendance->checkins()->count(),
        ]);
    }

    /**
     * Best-effort: issue a certificate the moment a student is checked in to an
     * activity that has already ended, when the club has a default template.
     * Failures must never break the scanner response, so they are swallowed.
     */
    private function autoIssueCertificate(EventAttendance $attendance, User $student, Event $event, Club $club): void
    {
        $hasEnded = $event->starts_at !== null
            && ($event->ends_at ?? $event->starts_at)->isPast();

        if (! $hasEnded || $club->defaultCertificateTemplate() === null) {
            return;
        }

        try {
            $certificate = $this->certificates->issue(
                user: $student,
                club: $club,
                event: $event,
                attendance: $attendance,
            );

            if ($certificate->wasRecentlyCreated) {
                $student->notify(new CertificateIssuedNotification($certificate));
            }
        } catch (RuntimeException) {
            // No active default template (or render failure): leave the
            // certificate for the scheduled sweep / manual issuance instead.
        }
    }

    /**
     * Students registered for the event, each flagged with whether they have
     * already been checked in today (the scanner's live roster).
     *
     * @return array<int, array{userId: int, name: string, email: string, daysAttended: int, checkedInToday: bool}>
     */
    private function registeredAttendees(Event $event): array
    {
        $today = now()->toDateString();

        return $event->attendances()
            ->whereIn('status', EventAttendanceStatus::registeredValues())
            ->with('user:id,name,email')
            ->withCount('checkins')
            ->get()
            ->filter(fn (EventAttendance $attendance): bool => $attendance->user !== null)
            ->map(fn (EventAttendance $attendance): array => [
                'userId' => $attendance->user_id,
                'name' => $attendance->user->name,
                'email' => $attendance->user->email,
                'daysAttended' => $attendance->checkins_count,
                'checkedInToday' => $attendance->checkins()->whereDate('attended_on', $today)->exists(),
            ])
            ->values()
            ->all();
    }
}
