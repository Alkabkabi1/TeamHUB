<?php

namespace App\Http\Controllers;

use App\Http\Requests\DownloadClubReportRequest;
use App\Models\Club;
use App\Services\ClubSupervisorReportService;
use App\Support\ArabicText;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ClubReportController extends Controller
{
    public function __construct(
        private readonly ClubSupervisorReportService $reports,
    ) {}

    public function members(DownloadClubReportRequest $request, Club $club): Response
    {
        return $this->download(
            $request,
            $club,
            'members',
            fn (string $locale) => $this->reports->membersReport($club, $locale, $request->user()),
        );
    }

    public function volunteerHours(DownloadClubReportRequest $request, Club $club): Response
    {
        return $this->download(
            $request,
            $club,
            'volunteer-hours',
            fn (string $locale) => $this->reports->volunteerHoursReport($club, $locale, $request->user()),
        );
    }

    public function attendance(DownloadClubReportRequest $request, Club $club): Response
    {
        return $this->download(
            $request,
            $club,
            'attendance',
            fn (string $locale) => $this->reports->attendanceReport($club, $locale, $request->user()),
        );
    }

    /**
     * @param  callable(string): array<string, mixed>  $dataResolver
     */
    private function download(
        DownloadClubReportRequest $request,
        Club $club,
        string $reportKey,
        callable $dataResolver,
    ): Response {
        $locale = $request->reportLocale();
        app()->setLocale($locale);

        $data = $dataResolver($locale);
        $filename = "{$reportKey}-club-{$club->id}-{$locale}.pdf";

        // DomPDF cannot shape Arabic; shape the rendered HTML's text nodes first.
        $html = ArabicText::shapeHtml(view("reports.{$reportKey}.{$locale}", $data)->render());

        return Pdf::loadHTML($html)->download($filename);
    }
}
