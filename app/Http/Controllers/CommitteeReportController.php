<?php

namespace App\Http\Controllers;

use App\Http\Requests\DownloadCommitteeReportRequest;
use App\Models\Club;
use App\Models\Committee;
use App\Services\CommitteeReportService;
use App\Support\ArabicText;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class CommitteeReportController extends Controller
{
    public function __construct(
        private readonly CommitteeReportService $reports,
    ) {}

    public function members(DownloadCommitteeReportRequest $request, Club $club, Committee $committee): Response
    {
        return $this->download(
            $request,
            $committee,
            'members',
            fn (string $locale) => $this->reports->membersReport($committee, $locale, $request->user()?->name),
        );
    }

    public function volunteerHours(DownloadCommitteeReportRequest $request, Club $club, Committee $committee): Response
    {
        return $this->download(
            $request,
            $committee,
            'volunteer-hours',
            fn (string $locale) => $this->reports->volunteerHoursReport($committee, $locale, $request->user()?->name),
        );
    }

    public function attendance(DownloadCommitteeReportRequest $request, Club $club, Committee $committee): Response
    {
        return $this->download(
            $request,
            $committee,
            'attendance',
            fn (string $locale) => $this->reports->attendanceReport($committee, $locale, $request->user()?->name),
        );
    }

    /**
     * @param  callable(string): array<string, mixed>  $dataResolver
     */
    private function download(
        DownloadCommitteeReportRequest $request,
        Committee $committee,
        string $reportKey,
        callable $dataResolver,
    ): Response {
        $locale = $request->reportLocale();
        app()->setLocale($locale);

        $data = $dataResolver($locale);
        $filename = "{$reportKey}-committee-{$committee->id}-{$locale}.pdf";

        // Reuses the shared club report blade views (reports.{key}.{locale}).
        // DomPDF cannot shape Arabic; shape the rendered HTML's text nodes first.
        $html = ArabicText::shapeHtml(view("reports.{$reportKey}.{$locale}", $data)->render());

        return Pdf::loadHTML($html)->download($filename);
    }
}
