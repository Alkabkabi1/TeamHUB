<?php

namespace App\Http\Controllers;

use App\Enums\ClubCapability;
use App\Http\Requests\StoreManualCertificateRequest;
use App\Models\Certificate;
use App\Models\Club;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use App\Notifications\CertificateIssuedNotification;
use App\Services\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use RuntimeException;

class CertificateController extends Controller
{
    public function __construct(
        private readonly CertificateService $service,
    ) {}

    /**
     * Generate (or regenerate) a certificate for a checked-in / approved attendance.
     */
    public function store(Request $request, EventAttendance $attendance): RedirectResponse
    {
        $attendance->loadMissing('event.club', 'user');
        $club = $attendance->event->club;

        $this->authorize(ClubCapability::IssueCertificates->value, $club);

        if (! in_array($attendance->status, ['checked_in', 'approved'], true)) {
            return redirect()->back()->with('error', __('certificates.not_eligible'));
        }

        if ($attendance->event->starts_at === null || $attendance->event->starts_at->isFuture()) {
            return redirect()->back()->with('error', __('certificates.event_not_ended'));
        }

        if ($club->defaultCertificateTemplate() === null) {
            return redirect()->back()->with('error', __('certificates.no_template'));
        }

        $certificate = $this->service->issue(
            user: $attendance->user,
            club: $club,
            event: $attendance->event,
            attendance: $attendance,
        );

        if ($certificate->wasRecentlyCreated) {
            $attendance->user->notify(new CertificateIssuedNotification($certificate));
        }

        return redirect()->back()->with(
            'success',
            $certificate->wasRecentlyCreated
                ? __('certificates.generated')
                : __('certificates.already_exists'),
        );
    }

    /**
     * Manually issue a certificate to a student from a chosen template,
     * optionally tied to an activity, then trigger its download.
     */
    public function storeManual(StoreManualCertificateRequest $request, Club $club): RedirectResponse
    {
        $validated = $request->validated();

        $template = $club->certificateTemplates()->findOrFail($validated['template_id']);
        $user = User::findOrFail($validated['user_id']);
        $event = isset($validated['event_id']) ? Event::find($validated['event_id']) : null;

        $attendance = $event !== null
            ? EventAttendance::query()
                ->where('user_id', $user->id)
                ->where('event_id', $event->id)
                ->first()
            : null;

        try {
            $certificate = $this->service->issue(
                user: $user,
                club: $club,
                event: $event,
                attendance: $attendance,
                template: $template,
            );
        } catch (RuntimeException) {
            return redirect()->back()->with('error', __('certificates.no_template'));
        }

        if ($certificate->wasRecentlyCreated) {
            $user->notify(new CertificateIssuedNotification($certificate));
        }

        // Surface a toast and hand the client the download URL so the freshly
        // generated PDF downloads without leaving the dashboard.
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $certificate->wasRecentlyCreated
                ? __('certificates.generated')
                : __('certificates.already_exists'),
        ]);
        Inertia::flash('download', route('certificates.download', $certificate));

        return redirect()->back();
    }

    /**
     * Download the certificate PDF.
     * Allowed to the certificate's own student or a supervisor of the club.
     */
    public function download(Request $request, Certificate $certificate): Response
    {
        $certificate->loadMissing('club');

        $isOwner = (int) $certificate->user_id === (int) $request->user()->id;
        $isSupervisor = $certificate->club !== null
            && $request->user()->can(ClubCapability::IssueCertificates->value, $certificate->club);

        if (! $isOwner && ! $isSupervisor) {
            abort(403);
        }

        $filename = basename($certificate->file_path ?? "{$certificate->certificate_no}.pdf");

        if ($certificate->file_path && Storage::disk('public')->exists($certificate->file_path)) {
            $contents = Storage::disk('public')->get($certificate->file_path);
        } else {
            // File missing — regenerate on-the-fly. Only possible while the
            // club still has an active template to render from.
            try {
                $contents = $this->service->regenerateBytes($certificate);
            } catch (RuntimeException) {
                abort(404, __('certificates.download_not_found'));
            }
        }

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
